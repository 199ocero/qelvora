<?php

namespace App\Enums;

enum ProviderConnectionStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Failed = 'failed';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Connected => 'Connected',
            self::Failed => 'Failed',
        };
    }
}
