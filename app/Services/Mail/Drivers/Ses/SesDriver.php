<?php

namespace App\Services\Mail\Drivers\Ses;

use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use App\Enums\SuppressionReason;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Services\Mail\Contracts\MailProviderDriver;
use App\Services\Mail\Data\AccountHealth;
use App\Services\Mail\Data\DnsRecord;
use App\Services\Mail\Data\IdentityResult;
use App\Services\Mail\Data\OutgoingMessage;
use App\Services\Mail\Data\SendResult;
use App\Support\DevWebhookTunnel;
use Aws\Exception\AwsException;
use Aws\Sns\Message;
use Aws\Sns\MessageValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Throwable;

class SesDriver implements MailProviderDriver
{
    public function __construct(
        protected ProviderConnection $connection,
        protected SesClientFactory $clients,
    ) {
        //
    }

    public function verifyCredentials(): AccountHealth
    {
        return $this->syncAccount();
    }

    public function syncAccount(): AccountHealth
    {
        $account = $this->clients->sesV2($this->connection)->getAccount();

        $quota = $account['SendQuota'] ?? [];

        return new AccountHealth(
            productionAccess: (bool) ($account['ProductionAccessEnabled'] ?? false),
            sendQuotaMax: isset($quota['Max24HourSend']) ? (float) $quota['Max24HourSend'] : null,
            sentLast24h: isset($quota['SentLast24Hours']) ? (float) $quota['SentLast24Hours'] : null,
            maxSendRate: isset($quota['MaxSendRate']) ? (float) $quota['MaxSendRate'] : null,
            enforcementStatus: $account['EnforcementStatus'] ?? null,
        );
    }

    public function provisionWebhooks(): void
    {
        $ses = $this->clients->sesV2($this->connection);
        $sns = $this->clients->sns($this->connection);

        $configSet = $this->configurationSetName();
        $topicName = $this->topicName();

        // Configuration set (idempotent).
        try {
            $ses->createConfigurationSet(['ConfigurationSetName' => $configSet]);
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() !== 'AlreadyExistsException') {
                throw $e;
            }
        }

        // SNS topic + HTTPS subscription to our webhook.
        $topicArn = $sns->createTopic(['Name' => $topicName])['TopicArn'];

        $subscription = $sns->subscribe([
            'TopicArn' => $topicArn,
            'Protocol' => 'https',
            'Endpoint' => $this->webhookUrl(),
            'ReturnSubscriptionArn' => true,
        ]);

        // Route all tracked event types to the topic (idempotent on the name).
        try {
            $ses->createConfigurationSetEventDestination([
                'ConfigurationSetName' => $configSet,
                'EventDestinationName' => 'xelqun-sns',
                'EventDestination' => [
                    'Enabled' => true,
                    'MatchingEventTypes' => config('mail-providers.ses.event_types'),
                    'SnsDestination' => ['TopicArn' => $topicArn],
                ],
            ]);
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() !== 'AlreadyExistsException') {
                throw $e;
            }
        }

        $this->connection->update([
            'settings' => array_merge($this->connection->settings ?? [], [
                'configuration_set_name' => $configSet,
                'sns_topic_arn' => $topicArn,
                'sns_subscription_arn' => $subscription['SubscriptionArn'] ?? null,
                'webhook_provisioned' => true,
            ]),
        ]);
    }

    public function createIdentity(string $identity, IdentityType $type): IdentityResult
    {
        $ses = $this->clients->sesV2($this->connection);

        // Creating an identity that already exists in SES (a prior attempt, or
        // one registered outside Xelqun) is not an error — adopt it and read
        // back its current attributes below.
        try {
            $ses->createEmailIdentity(['EmailIdentity' => $identity]);
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() !== 'AlreadyExistsException') {
                throw $e;
            }
        }

        $result = $ses->getEmailIdentity(['EmailIdentity' => $identity]);

        $status = isset($result['VerificationStatus'])
            ? IdentityStatus::fromProviderStatus($result['VerificationStatus'])
            : (($result['VerifiedForSendingStatus'] ?? false) ? IdentityStatus::Verified : IdentityStatus::Pending);

        if ($type === IdentityType::Email) {
            return new IdentityResult(
                status: $status,
                dnsRecords: [],
                meta: [],
            );
        }

        $tokens = $result['DkimAttributes']['Tokens'] ?? [];
        $mailFromDomain = $result['MailFromAttributes']['MailFromDomain'] ?? "mail.{$identity}";

        // Route bounces/complaints through a custom MAIL FROM subdomain (idempotent).
        try {
            $ses->putEmailIdentityMailFromAttributes([
                'EmailIdentity' => $identity,
                'MailFromDomain' => $mailFromDomain,
                'BehaviorOnMxFailure' => 'USE_DEFAULT_VALUE',
            ]);
        } catch (AwsException) {
            $mailFromDomain = null;
        }

        return new IdentityResult(
            status: $status,
            dnsRecords: $this->domainDnsRecords($identity, $tokens, $mailFromDomain),
            meta: [
                'dkim_tokens' => $tokens,
                'mail_from_domain' => $mailFromDomain,
            ],
        );
    }

    public function refreshIdentity(MailIdentity $identity): IdentityResult
    {
        $ses = $this->clients->sesV2($this->connection);

        $result = $ses->getEmailIdentity(['EmailIdentity' => $identity->identity]);

        $status = isset($result['VerificationStatus'])
            ? IdentityStatus::fromProviderStatus($result['VerificationStatus'])
            : (($result['VerifiedForSendingStatus'] ?? false) ? IdentityStatus::Verified : IdentityStatus::Pending);

        $tokens = $result['DkimAttributes']['Tokens'] ?? Arr::get($identity->meta ?? [], 'dkim_tokens', []);
        $mailFromDomain = $result['MailFromAttributes']['MailFromDomain'] ?? Arr::get($identity->meta ?? [], 'mail_from_domain');

        $dnsRecords = $identity->type === IdentityType::Domain
            ? $this->domainDnsRecords($identity->identity, $tokens, $mailFromDomain)
            : [];

        return new IdentityResult(
            status: $status,
            dnsRecords: $dnsRecords,
            meta: [
                'dkim_tokens' => $tokens,
                'mail_from_domain' => $mailFromDomain,
            ],
        );
    }

    public function deleteIdentity(MailIdentity $identity): void
    {
        try {
            $this->clients->sesV2($this->connection)
                ->deleteEmailIdentity(['EmailIdentity' => $identity->identity]);
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() !== 'NotFoundException') {
                throw $e;
            }
        }
    }

    public function send(OutgoingMessage $message): SendResult
    {
        $body = [];

        if ($message->html !== null) {
            $body['Html'] = ['Data' => $message->html, 'Charset' => 'UTF-8'];
        }

        if ($message->text !== null) {
            $body['Text'] = ['Data' => $message->text, 'Charset' => 'UTF-8'];
        }

        $args = [
            'FromEmailAddress' => $message->from,
            'Destination' => ['ToAddresses' => $message->to],
            'Content' => [
                'Simple' => [
                    'Subject' => ['Data' => $message->subject, 'Charset' => 'UTF-8'],
                    'Body' => $body,
                ],
            ],
        ];

        if ($configSet = $this->connection->setting('configuration_set_name')) {
            $args['ConfigurationSetName'] = $configSet;
        }

        if ($message->replyTo !== null) {
            $args['ReplyToAddresses'] = [$message->replyTo];
        }

        if ($tags = $this->emailTags($message->tags)) {
            $args['EmailTags'] = $tags;
        }

        $result = $this->clients->sesV2($this->connection)->sendEmail($args);

        return new SendResult(providerMessageId: (string) $result['MessageId']);
    }

    public function listSuppressions(): array
    {
        $ses = $this->clients->sesV2($this->connection);
        $suppressions = [];
        $token = null;

        do {
            $args = ['PageSize' => 100];

            if ($token !== null) {
                $args['NextToken'] = $token;
            }

            $response = $ses->listSuppressedDestinations($args);

            foreach ($response['SuppressedDestinationSummaries'] ?? [] as $summary) {
                $suppressions[] = [
                    'email' => $summary['EmailAddress'],
                    'reason' => $this->suppressionReason($summary['Reason'] ?? null),
                ];
            }

            $token = $response['NextToken'] ?? null;
        } while ($token !== null);

        return $suppressions;
    }

    public function addSuppression(string $email, SuppressionReason $reason): void
    {
        $this->clients->sesV2($this->connection)->putSuppressedDestination([
            'EmailAddress' => $email,
            'Reason' => $reason === SuppressionReason::Complaint ? 'COMPLAINT' : 'BOUNCE',
        ]);
    }

    public function removeSuppression(string $email): void
    {
        try {
            $this->clients->sesV2($this->connection)
                ->deleteSuppressedDestination(['EmailAddress' => $email]);
        } catch (AwsException $e) {
            if ($e->getAwsErrorCode() !== 'NotFoundException') {
                throw $e;
            }
        }
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        try {
            $message = Message::fromJsonString($request->getContent());
            (new MessageValidator)->validate($message);

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function parseWebhook(Request $request): array
    {
        $message = Message::fromJsonString($request->getContent());
        $type = $message['Type'] ?? null;

        // Confirm the SNS subscription handshake and stop.
        if ($type === 'SubscriptionConfirmation') {
            if (! empty($message['SubscribeURL'])) {
                Http::get($message['SubscribeURL']);
            }

            return [];
        }

        if ($type !== 'Notification') {
            return [];
        }

        $payload = json_decode((string) ($message['Message'] ?? '{}'), true) ?: [];

        return (new SesEventMapper)->map($payload);
    }

    /**
     * Build the DKIM, MAIL FROM and DMARC DNS records for a domain identity.
     *
     * @param  array<int, string>  $dkimTokens
     * @return array<int, DnsRecord>
     */
    protected function domainDnsRecords(string $domain, array $dkimTokens, ?string $mailFromDomain): array
    {
        $records = [];

        foreach ($dkimTokens as $token) {
            $records[] = new DnsRecord(
                type: 'CNAME',
                host: "{$token}._domainkey.{$domain}",
                value: "{$token}.dkim.amazonses.com",
                purpose: 'DKIM',
            );
        }

        if ($mailFromDomain !== null) {
            $region = $this->connection->credential('region') ?? config('mail-providers.ses.default_region');

            $records[] = new DnsRecord(
                type: 'MX',
                host: $mailFromDomain,
                value: "feedback-smtp.{$region}.amazonses.com",
                priority: 10,
                purpose: 'MAIL FROM',
            );

            $records[] = new DnsRecord(
                type: 'TXT',
                host: $mailFromDomain,
                value: 'v=spf1 include:amazonses.com ~all',
                purpose: 'SPF',
            );
        }

        // DMARC is advisory but strongly recommended.
        $records[] = new DnsRecord(
            type: 'TXT',
            host: "_dmarc.{$domain}",
            value: 'v=DMARC1; p=none;',
            purpose: 'DMARC',
        );

        return $records;
    }

    /**
     * @param  array<string, string>  $tags
     * @return array<int, array{Name: string, Value: string}>
     */
    protected function emailTags(array $tags): array
    {
        $result = [];

        foreach ($tags as $name => $value) {
            $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $name);
            $safeValue = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $value);

            if ($safeName !== '' && $safeValue !== '') {
                $result[] = ['Name' => $safeName, 'Value' => $safeValue];
            }
        }

        return $result;
    }

    protected function suppressionReason(?string $reason): SuppressionReason
    {
        return match (strtoupper((string) $reason)) {
            'COMPLAINT' => SuppressionReason::Complaint,
            default => SuppressionReason::Bounce,
        };
    }

    protected function configurationSetName(): string
    {
        return $this->connection->setting('configuration_set_name')
            ?? str_replace('{slug}', $this->connection->team->slug, config('mail-providers.ses.configuration_set_template'));
    }

    protected function topicName(): string
    {
        return str_replace('{slug}', $this->connection->team->slug, config('mail-providers.ses.topic_template'));
    }

    protected function webhookUrl(): string
    {
        $path = config('mail-providers.webhook_path')."/{$this->connection->webhook_token}";

        $base = DevWebhookTunnel::resolveBaseUrl();

        return $base
            ? rtrim((string) $base, '/').'/'.$path
            : URL::to($path);
    }
}
