<?php

namespace App\Enums;

enum SuppressionReason: string
{
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Manual = 'manual';

    /**
     * Get the display label for the reason.
     */
    public function label(): string
    {
        return match ($this) {
            self::Bounce => 'Bounce',
            self::Complaint => 'Complaint',
            self::Manual => 'Manual',
        };
    }
}
