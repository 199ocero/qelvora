<?php

namespace App\Enums;

enum IdentityType: string
{
    case Domain = 'domain';
    case Email = 'email';

    /**
     * Get the display label for the identity type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Domain => 'Domain',
            self::Email => 'Email address',
        };
    }
}
