<?php

namespace App\Concerns;

use App\Models\ApiKey;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Models\Suppression;

/**
 * Serializes mail-platform models into the camelCase prop shapes the Inertia
 * frontend expects (see resources/js/types/mail.ts).
 */
trait PresentsMailResources
{
    /**
     * @return array<string, mixed>
     */
    protected function presentConnection(ProviderConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'provider' => $connection->provider->value,
            'providerLabel' => $connection->provider->label(),
            'status' => $connection->status->value,
            'isActive' => $connection->is_active,
            'productionAccess' => $connection->production_access,
            'sendQuotaMax' => $connection->send_quota_max,
            'sentLast24h' => $connection->sent_last_24h,
            'maxSendRate' => $connection->max_send_rate,
            'enforcementStatus' => $connection->enforcement_status,
            'bounceRate' => $connection->bounce_rate,
            'complaintRate' => $connection->complaint_rate,
            'webhookProvisioned' => (bool) $connection->setting('webhook_provisioned', false),
            'provisionError' => $connection->setting('provision_error'),
            'connectedAt' => $connection->connected_at?->toIso8601String(),
            'lastSyncedAt' => $connection->last_synced_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentIdentity(MailIdentity $identity): array
    {
        return [
            'id' => $identity->id,
            'identity' => $identity->identity,
            'type' => $identity->type->value,
            'status' => $identity->status->value,
            'statusLabel' => $identity->status->label(),
            'dnsRecords' => $identity->dns_records ?? [],
            'verifiedAt' => $identity->verified_at?->toIso8601String(),
            'lastCheckedAt' => $identity->last_checked_at?->toIso8601String(),
            'createdAt' => $identity->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentMessage(EmailMessage $message, bool $withBody = false): array
    {
        return array_merge([
            'id' => $message->id,
            'provider' => $message->provider->value,
            'providerMessageId' => $message->provider_message_id,
            'fromAddress' => $message->from_address,
            'to' => $message->to,
            'subject' => $message->subject,
            'status' => $message->status->value,
            'statusLabel' => $message->status->label(),
            'sentVia' => $message->sent_via,
            'opensCount' => $message->opens_count,
            'clicksCount' => $message->clicks_count,
            'error' => $message->error,
            'lastEventAt' => $message->last_event_at?->toIso8601String(),
            'createdAt' => $message->created_at?->toIso8601String(),
        ], $withBody ? [
            'html' => $message->html,
            'text' => $message->text,
        ] : []);
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentEvent(EmailEvent $event): array
    {
        return [
            'id' => $event->id,
            'type' => $event->type->value,
            'typeLabel' => $event->type->label(),
            'bounceType' => $event->bounce_type,
            'complaintType' => $event->complaint_type,
            'occurredAt' => $event->occurred_at?->toIso8601String(),
            'createdAt' => $event->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentSuppression(Suppression $suppression): array
    {
        return [
            'id' => $suppression->id,
            'email' => $suppression->email,
            'reason' => $suppression->reason->value,
            'reasonLabel' => $suppression->reason->label(),
            'source' => $suppression->source->value,
            'notes' => $suppression->notes,
            'suppressedAt' => $suppression->suppressed_at?->toIso8601String(),
            'createdAt' => $suppression->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function presentApiKey(ApiKey $apiKey): array
    {
        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'keyPrefix' => $apiKey->key_prefix,
            'lastFour' => $apiKey->last_four,
            'createdBy' => $apiKey->creator?->name,
            'lastUsedAt' => $apiKey->last_used_at?->toIso8601String(),
            'revokedAt' => $apiKey->revoked_at?->toIso8601String(),
            'createdAt' => $apiKey->created_at?->toIso8601String(),
        ];
    }
}
