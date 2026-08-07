<?php

namespace App\Services\Mail\Contracts;

use App\Enums\IdentityType;
use App\Enums\SuppressionReason;
use App\Models\MailIdentity;
use App\Services\Mail\Data\AccountHealth;
use App\Services\Mail\Data\IdentityResult;
use App\Services\Mail\Data\NormalizedEvent;
use App\Services\Mail\Data\OutgoingMessage;
use App\Services\Mail\Data\SendResult;
use Illuminate\Http\Request;

/**
 * The contract every email provider driver implements. All methods speak in
 * normalized value objects so the rest of the application never touches a
 * provider SDK or provider-specific payload shape.
 */
interface MailProviderDriver
{
    /**
     * Validate the connection's stored credentials and return account health.
     */
    public function verifyCredentials(): AccountHealth;

    /**
     * Re-fetch the latest account health (quota, reputation, enforcement).
     */
    public function syncAccount(): AccountHealth;

    /**
     * Provision whatever the provider needs to deliver events to our webhook
     * (e.g. SES: configuration set + SNS topic + subscription).
     */
    public function provisionWebhooks(): void;

    /**
     * Create a sending identity and return its DNS records + status.
     */
    public function createIdentity(string $identity, IdentityType $type): IdentityResult;

    /**
     * Re-fetch the latest verification status for an identity.
     */
    public function refreshIdentity(MailIdentity $identity): IdentityResult;

    /**
     * Delete a sending identity at the provider.
     */
    public function deleteIdentity(MailIdentity $identity): void;

    /**
     * Send a message and return the provider's message id.
     */
    public function send(OutgoingMessage $message): SendResult;

    /**
     * List the provider-side suppressed destinations.
     *
     * @return array<int, array{email: string, reason: SuppressionReason}>
     */
    public function listSuppressions(): array;

    /**
     * Add an address to the provider suppression list.
     */
    public function addSuppression(string $email, SuppressionReason $reason): void;

    /**
     * Remove an address from the provider suppression list.
     */
    public function removeSuppression(string $email): void;

    /**
     * Verify the authenticity of an inbound webhook request.
     */
    public function verifyWebhookSignature(Request $request): bool;

    /**
     * Parse an inbound webhook request into normalized events. Implementations
     * also handle any provider subscription-confirmation handshake and return
     * an empty array when there are no deliverable events.
     *
     * @return array<int, NormalizedEvent>
     */
    public function parseWebhook(Request $request): array;
}
