<?php

namespace App\Services\Mail\Data;

use App\Enums\EmailEventType;
use Carbon\CarbonInterface;

/**
 * A single provider webhook notification, normalized so ProcessMailEvent can
 * persist it and roll up message status without knowing the provider.
 */
readonly class NormalizedEvent
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public EmailEventType $type,
        public ?string $providerMessageId = null,
        public ?string $email = null,
        public ?CarbonInterface $occurredAt = null,
        public ?string $bounceType = null,
        public ?string $bounceSubtype = null,
        public ?string $complaintType = null,
        public array $raw = [],
    ) {
        //
    }

    /**
     * Whether this event should cause the recipient to be suppressed.
     */
    public function shouldSuppress(): bool
    {
        if ($this->type === EmailEventType::Complaint) {
            return true;
        }

        return $this->type === EmailEventType::Bounce
            && strtolower((string) $this->bounceType) === 'permanent';
    }
}
