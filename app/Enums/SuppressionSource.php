<?php

namespace App\Enums;

enum SuppressionSource: string
{
    case Provider = 'provider';
    case Local = 'local';
    case Event = 'event';

    /**
     * Get the display label for the source.
     */
    public function label(): string
    {
        return match ($this) {
            self::Provider => 'Provider',
            self::Local => 'Local',
            self::Event => 'Event',
        };
    }
}
