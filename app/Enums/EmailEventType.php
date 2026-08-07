<?php

namespace App\Enums;

enum EmailEventType: string
{
    case Send = 'send';
    case Delivery = 'delivery';
    case Bounce = 'bounce';
    case Complaint = 'complaint';
    case Open = 'open';
    case Click = 'click';
    case Reject = 'reject';
    case RenderingFailure = 'rendering_failure';
    case DeliveryDelay = 'delivery_delay';

    /**
     * Get the display label for the event type.
     */
    public function label(): string
    {
        return match ($this) {
            self::Send => 'Sent',
            self::Delivery => 'Delivered',
            self::Bounce => 'Bounced',
            self::Complaint => 'Complaint',
            self::Open => 'Opened',
            self::Click => 'Clicked',
            self::Reject => 'Rejected',
            self::RenderingFailure => 'Rendering failure',
            self::DeliveryDelay => 'Delivery delay',
        };
    }

    /**
     * The message status this event resolves to, if any.
     */
    public function toMessageStatus(): ?EmailMessageStatus
    {
        return match ($this) {
            self::Send => EmailMessageStatus::Sent,
            self::Delivery => EmailMessageStatus::Delivered,
            self::Bounce => EmailMessageStatus::Bounced,
            self::Complaint => EmailMessageStatus::Complained,
            self::Reject => EmailMessageStatus::Rejected,
            self::RenderingFailure => EmailMessageStatus::Failed,
            default => null,
        };
    }
}
