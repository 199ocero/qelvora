<?php

namespace App\Jobs;

use App\Enums\EmailEventType;
use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;
use App\Models\Suppression;
use App\Services\Mail\Data\NormalizedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMailEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ProviderConnection $providerConnection,
        public NormalizedEvent $event,
    ) {
        //
    }

    /**
     * Persist the event, roll up the message status, and auto-suppress.
     */
    public function handle(): void
    {
        $message = $this->matchMessage();

        EmailEvent::create([
            'email_message_id' => $message?->id,
            'provider_connection_id' => $this->providerConnection->id,
            'team_id' => $this->providerConnection->team_id,
            'type' => $this->event->type,
            'payload' => $this->event->raw,
            'bounce_type' => $this->event->bounceType,
            'bounce_subtype' => $this->event->bounceSubtype,
            'complaint_type' => $this->event->complaintType,
            'occurred_at' => $this->event->occurredAt,
        ]);

        if ($message !== null) {
            $this->rollUpMessage($message);
        }

        if ($this->event->shouldSuppress() && $this->event->email !== null) {
            $this->suppress();
        }
    }

    /**
     * Find the message this event belongs to via its provider message id.
     */
    protected function matchMessage(): ?EmailMessage
    {
        if ($this->event->providerMessageId === null) {
            return null;
        }

        return $this->providerConnection->messages()
            ->where('provider_message_id', $this->event->providerMessageId)
            ->first();
    }

    /**
     * Advance the message status and engagement counters.
     */
    protected function rollUpMessage(EmailMessage $message): void
    {
        $updates = ['last_event_at' => $this->event->occurredAt ?? now()];

        $newStatus = $this->event->type->toMessageStatus();

        if ($newStatus !== null && $newStatus->rank() >= $message->status->rank()) {
            $updates['status'] = $newStatus;
        }

        if ($this->event->type === EmailEventType::Open) {
            $updates['opens_count'] = $message->opens_count + 1;
        }

        if ($this->event->type === EmailEventType::Click) {
            $updates['clicks_count'] = $message->clicks_count + 1;
        }

        $message->update($updates);
    }

    /**
     * Record the recipient on the team's suppression list.
     */
    protected function suppress(): void
    {
        $reason = $this->event->type === EmailEventType::Complaint
            ? SuppressionReason::Complaint
            : SuppressionReason::Bounce;

        Suppression::updateOrCreate(
            ['team_id' => $this->providerConnection->team_id, 'email' => $this->event->email],
            [
                'provider_connection_id' => $this->providerConnection->id,
                'reason' => $reason,
                'source' => SuppressionSource::Event,
                'suppressed_at' => now(),
            ],
        );
    }
}
