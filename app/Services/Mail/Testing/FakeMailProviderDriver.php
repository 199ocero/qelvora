<?php

namespace App\Services\Mail\Testing;

use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use App\Enums\SuppressionReason;
use App\Models\MailIdentity;
use App\Services\Mail\Contracts\MailProviderDriver;
use App\Services\Mail\Data\AccountHealth;
use App\Services\Mail\Data\DnsRecord;
use App\Services\Mail\Data\IdentityResult;
use App\Services\Mail\Data\NormalizedEvent;
use App\Services\Mail\Data\OutgoingMessage;
use App\Services\Mail\Data\SendResult;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * In-memory driver used by the test suite so no real provider is contacted.
 * Records calls and returns configurable canned data.
 */
class FakeMailProviderDriver implements MailProviderDriver
{
    /** @var array<int, array{method: string, args: array<int, mixed>}> */
    public array $calls = [];

    public bool $failVerification = false;

    public bool $failProvision = false;

    public bool $signatureValid = true;

    public string $messageId = 'fake-message-id-0001';

    /** @var array<int, NormalizedEvent> */
    public array $events = [];

    /** @var array<int, array{email: string, reason: SuppressionReason}> */
    public array $suppressions = [];

    public function __construct(
        public AccountHealth $accountHealth = new AccountHealth(
            productionAccess: true,
            sendQuotaMax: 50000,
            sentLast24h: 25,
            maxSendRate: 14,
            enforcementStatus: 'HEALTHY',
        ),
    ) {
        //
    }

    public function verifyCredentials(): AccountHealth
    {
        $this->record(__FUNCTION__);

        if ($this->failVerification) {
            throw new RuntimeException('Invalid credentials.');
        }

        return $this->accountHealth;
    }

    public function syncAccount(): AccountHealth
    {
        $this->record(__FUNCTION__);

        return $this->accountHealth;
    }

    public function provisionWebhooks(): void
    {
        $this->record(__FUNCTION__);

        if ($this->failProvision) {
            throw new RuntimeException('Unreachable Endpoint');
        }
    }

    public function createIdentity(string $identity, IdentityType $type): IdentityResult
    {
        $this->record(__FUNCTION__, [$identity, $type]);

        if ($type === IdentityType::Email) {
            return new IdentityResult(status: IdentityStatus::Pending);
        }

        $tokens = ['tok1', 'tok2', 'tok3'];

        return new IdentityResult(
            status: IdentityStatus::Pending,
            dnsRecords: array_map(
                fn (string $token) => new DnsRecord(
                    type: 'CNAME',
                    host: "{$token}._domainkey.{$identity}",
                    value: "{$token}.dkim.amazonses.com",
                    purpose: 'DKIM',
                ),
                $tokens,
            ),
            meta: ['dkim_tokens' => $tokens, 'mail_from_domain' => "mail.{$identity}"],
        );
    }

    public function refreshIdentity(MailIdentity $identity): IdentityResult
    {
        $this->record(__FUNCTION__, [$identity->identity]);

        return new IdentityResult(
            status: IdentityStatus::Verified,
            dnsRecords: array_map(
                fn (array $record) => DnsRecord::fromArray($record),
                $identity->dns_records ?? [],
            ),
            meta: $identity->meta ?? [],
        );
    }

    public function deleteIdentity(MailIdentity $identity): void
    {
        $this->record(__FUNCTION__, [$identity->identity]);
    }

    public function send(OutgoingMessage $message): SendResult
    {
        $this->record(__FUNCTION__, [$message]);

        return new SendResult(providerMessageId: $this->messageId);
    }

    public function listSuppressions(): array
    {
        $this->record(__FUNCTION__);

        return $this->suppressions;
    }

    public function addSuppression(string $email, SuppressionReason $reason): void
    {
        $this->record(__FUNCTION__, [$email, $reason]);
    }

    public function removeSuppression(string $email): void
    {
        $this->record(__FUNCTION__, [$email]);
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $this->record(__FUNCTION__);

        return $this->signatureValid;
    }

    public function parseWebhook(Request $request): array
    {
        $this->record(__FUNCTION__);

        return $this->events;
    }

    /**
     * Determine whether a driver method was called.
     */
    public function called(string $method): bool
    {
        return collect($this->calls)->contains(fn (array $call) => $call['method'] === $method);
    }

    /**
     * @param  array<int, mixed>  $args
     */
    protected function record(string $method, array $args = []): void
    {
        $this->calls[] = ['method' => $method, 'args' => $args];
    }
}
