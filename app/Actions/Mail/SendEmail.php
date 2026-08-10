<?php

namespace App\Actions\Mail;

use App\Enums\EmailMessageStatus;
use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use App\Exceptions\Mail\NoActiveProviderException;
use App\Exceptions\Mail\RecipientSuppressedException;
use App\Exceptions\Mail\RestrictedSenderException;
use App\Exceptions\Mail\UnverifiedSenderException;
use App\Jobs\SendQueuedEmail;
use App\Models\EmailMessage;
use App\Models\MailIdentity;
use App\Models\Team;
use App\Services\Mail\Data\OutgoingMessage;
use App\Services\Mail\MailProviderManager;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Throwable;

class SendEmail
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Validate and send (or queue) an email for the team's active provider.
     *
     * @throws NoActiveProviderException|UnverifiedSenderException|RestrictedSenderException|RecipientSuppressedException
     */
    public function handle(
        Team $team,
        OutgoingMessage $message,
        string $sentVia = 'api',
        ?int $apiKeyId = null,
        bool $queue = false,
        ?int $templateId = null,
        ?CarbonInterface $scheduledAt = null,
        ?MailIdentity $restrictedTo = null,
    ): EmailMessage {
        $connection = $team->activeConnection();

        if ($connection === null || ! $connection->isConnected()) {
            throw new NoActiveProviderException;
        }

        if (! $this->isVerifiedSender($team, $message->from)) {
            throw new UnverifiedSenderException($message->from);
        }

        if ($restrictedTo !== null && ! $this->senderMatchesIdentity($restrictedTo, $message->from)) {
            throw new RestrictedSenderException($message->from, $restrictedTo->identity);
        }

        $suppressed = $team->suppressions()
            ->whereIn('email', $message->to)
            ->pluck('email');

        if ($suppressed->isNotEmpty()) {
            throw new RecipientSuppressedException($suppressed->all());
        }

        $scheduled = $scheduledAt !== null && $scheduledAt->isFuture();

        $record = $team->emailMessages()->create([
            'provider_connection_id' => $connection->id,
            'provider' => $connection->provider,
            'from_address' => $message->from,
            'to' => $message->to,
            'subject' => $message->subject,
            'html' => $message->html,
            'text' => $message->text,
            'status' => $scheduled ? EmailMessageStatus::Scheduled : EmailMessageStatus::Queued,
            'sent_via' => $sentVia,
            'api_key_id' => $apiKeyId,
            'email_template_id' => $templateId,
            'tags' => $message->tags,
            'scheduled_at' => $scheduled ? $scheduledAt : null,
        ]);

        // Held until the scheduler dispatches it; see SendScheduledEmails.
        if ($scheduled) {
            return $record;
        }

        if ($queue) {
            SendQueuedEmail::dispatch($record);

            return $record;
        }

        $this->deliver($record);

        return $record->fresh();
    }

    /**
     * Move a scheduled message into the sending queue.
     */
    public function release(EmailMessage $record): void
    {
        $record->update([
            'status' => EmailMessageStatus::Queued,
            'scheduled_at' => null,
        ]);

        SendQueuedEmail::dispatch($record);
    }

    /**
     * Deliver an already-persisted message through its provider.
     */
    public function deliver(EmailMessage $record): void
    {
        $connection = $record->connection;

        if ($connection === null) {
            $record->update(['status' => EmailMessageStatus::Failed, 'error' => 'Provider connection no longer exists.']);

            return;
        }

        try {
            $result = $this->manager->driver($connection)->send(new OutgoingMessage(
                from: $record->from_address,
                to: $record->to,
                subject: (string) $record->subject,
                html: $record->html,
                text: $record->text,
                tags: $record->tags ?? [],
            ));

            $record->update([
                'provider_message_id' => $result->providerMessageId,
                'status' => EmailMessageStatus::Sent,
            ]);
        } catch (Throwable $e) {
            $record->update(['status' => EmailMessageStatus::Failed, 'error' => $e->getMessage()]);

            throw $e;
        }
    }

    /**
     * Determine whether the from address maps to a verified team identity.
     */
    protected function isVerifiedSender(Team $team, string $from): bool
    {
        $address = Str::lower($this->extractAddress($from));
        $domain = Str::afterLast($address, '@');

        return $team->mailIdentities()
            ->where('status', IdentityStatus::Verified)
            ->where(fn ($query) => $query
                ->where('identity', $address)
                ->orWhere('identity', $domain))
            ->exists();
    }

    /**
     * Determine whether the from address is allowed by a key's restricted identity.
     *
     * A domain identity authorizes any address at that domain; an email
     * identity authorizes only its exact address.
     */
    protected function senderMatchesIdentity(MailIdentity $identity, string $from): bool
    {
        $address = Str::lower($this->extractAddress($from));

        if ($identity->type === IdentityType::Domain) {
            return Str::afterLast($address, '@') === Str::lower($identity->identity);
        }

        return $address === Str::lower($identity->identity);
    }

    /**
     * Extract the bare email address from a "Name <addr>" style header.
     */
    protected function extractAddress(string $from): string
    {
        if (preg_match('/<([^>]+)>/', $from, $matches) === 1) {
            return trim($matches[1]);
        }

        return trim($from);
    }
}
