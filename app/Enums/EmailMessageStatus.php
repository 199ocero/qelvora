<?php

namespace App\Enums;

enum EmailMessageStatus: string
{
    case Scheduled = 'scheduled';
    case Queued = 'queued';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Bounced = 'bounced';
    case Complained = 'complained';
    case Rejected = 'rejected';
    case Failed = 'failed';

    /**
     * Get the display label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Scheduled',
            self::Queued => 'Queued',
            self::Sent => 'Sent',
            self::Delivered => 'Delivered',
            self::Bounced => 'Bounced',
            self::Complained => 'Complained',
            self::Rejected => 'Rejected',
            self::Failed => 'Failed',
        };
    }

    /**
     * The relative severity of the status, used when rolling up events so a
     * later benign event can't overwrite a terminal negative state.
     */
    public function rank(): int
    {
        return match ($this) {
            self::Scheduled => 0,
            self::Queued => 0,
            self::Sent => 1,
            self::Delivered => 2,
            self::Rejected => 3,
            self::Failed => 4,
            self::Bounced => 5,
            self::Complained => 6,
        };
    }
}
