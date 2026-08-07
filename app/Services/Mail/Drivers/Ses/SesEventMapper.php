<?php

namespace App\Services\Mail\Drivers\Ses;

use App\Enums\EmailEventType;
use App\Services\Mail\Data\NormalizedEvent;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Translates a raw SES configuration-set event (or SNS feedback notification)
 * into one or more normalized events.
 */
class SesEventMapper
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, NormalizedEvent>
     */
    public function map(array $payload): array
    {
        $rawType = $payload['eventType'] ?? $payload['notificationType'] ?? null;
        $type = $this->mapType($rawType);

        if ($type === null) {
            return [];
        }

        $messageId = $payload['mail']['messageId'] ?? null;
        $occurredAt = $this->timestamp($payload);

        if ($type === EmailEventType::Bounce) {
            return $this->bounceEvents($payload, $messageId, $occurredAt);
        }

        if ($type === EmailEventType::Complaint) {
            return $this->complaintEvents($payload, $messageId, $occurredAt);
        }

        $email = $payload['mail']['destination'][0] ?? null;

        return [new NormalizedEvent(
            type: $type,
            providerMessageId: $messageId,
            email: $email,
            occurredAt: $occurredAt,
            raw: $payload,
        )];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, NormalizedEvent>
     */
    protected function bounceEvents(array $payload, ?string $messageId, ?CarbonImmutable $occurredAt): array
    {
        $bounce = $payload['bounce'] ?? [];
        $events = [];

        foreach ($bounce['bouncedRecipients'] ?? [[]] as $recipient) {
            $events[] = new NormalizedEvent(
                type: EmailEventType::Bounce,
                providerMessageId: $messageId,
                email: $recipient['emailAddress'] ?? ($payload['mail']['destination'][0] ?? null),
                occurredAt: $occurredAt,
                bounceType: $bounce['bounceType'] ?? null,
                bounceSubtype: $bounce['bounceSubType'] ?? null,
                raw: $payload,
            );
        }

        return $events;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, NormalizedEvent>
     */
    protected function complaintEvents(array $payload, ?string $messageId, ?CarbonImmutable $occurredAt): array
    {
        $complaint = $payload['complaint'] ?? [];
        $events = [];

        foreach ($complaint['complainedRecipients'] ?? [[]] as $recipient) {
            $events[] = new NormalizedEvent(
                type: EmailEventType::Complaint,
                providerMessageId: $messageId,
                email: $recipient['emailAddress'] ?? ($payload['mail']['destination'][0] ?? null),
                occurredAt: $occurredAt,
                complaintType: $complaint['complaintFeedbackType'] ?? null,
                raw: $payload,
            );
        }

        return $events;
    }

    protected function mapType(?string $type): ?EmailEventType
    {
        return match (strtolower(str_replace(' ', '', (string) $type))) {
            'send' => EmailEventType::Send,
            'delivery' => EmailEventType::Delivery,
            'bounce' => EmailEventType::Bounce,
            'complaint' => EmailEventType::Complaint,
            'open' => EmailEventType::Open,
            'click' => EmailEventType::Click,
            'reject' => EmailEventType::Reject,
            'renderingfailure' => EmailEventType::RenderingFailure,
            'deliverydelay' => EmailEventType::DeliveryDelay,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function timestamp(array $payload): ?CarbonImmutable
    {
        $raw = $payload['mail']['timestamp']
            ?? $payload['bounce']['timestamp']
            ?? $payload['complaint']['timestamp']
            ?? $payload['delivery']['timestamp']
            ?? null;

        if ($raw === null) {
            return null;
        }

        try {
            return CarbonImmutable::parse($raw);
        } catch (Throwable) {
            return null;
        }
    }
}
