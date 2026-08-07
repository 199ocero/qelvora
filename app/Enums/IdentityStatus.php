<?php

namespace App\Enums;

enum IdentityStatus: string
{
    case NotStarted = 'not_started';
    case Pending = 'pending';
    case Verified = 'verified';
    case Failed = 'failed';
    case TemporaryFailure = 'temporary_failure';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => 'Not started',
            self::Pending => 'Pending',
            self::Verified => 'Verified',
            self::Failed => 'Failed',
            self::TemporaryFailure => 'Temporary failure',
        };
    }

    /**
     * Determine whether the identity is fully verified and usable for sending.
     */
    public function isVerified(): bool
    {
        return $this === self::Verified;
    }

    /**
     * Normalize a raw provider verification status string into a case.
     */
    public static function fromProviderStatus(?string $status): self
    {
        return match (strtoupper((string) $status)) {
            'SUCCESS', 'VERIFIED' => self::Verified,
            'PENDING' => self::Pending,
            'FAILED' => self::Failed,
            'TEMPORARY_FAILURE' => self::TemporaryFailure,
            default => self::NotStarted,
        };
    }
}
