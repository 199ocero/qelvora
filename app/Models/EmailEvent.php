<?php

namespace App\Models;

use App\Enums\EmailEventType;
use Database\Factories\EmailEventFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $email_message_id
 * @property int|null $provider_connection_id
 * @property int $team_id
 * @property EmailEventType $type
 * @property array<string, mixed>|null $payload
 * @property string|null $bounce_type
 * @property string|null $bounce_subtype
 * @property string|null $complaint_type
 * @property Carbon|null $occurred_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read EmailMessage|null $message
 * @property-read Team $team
 */
#[Fillable([
    'email_message_id', 'provider_connection_id', 'team_id', 'type', 'payload',
    'bounce_type', 'bounce_subtype', 'complaint_type', 'occurred_at',
])]
class EmailEvent extends Model
{
    /** @use HasFactory<EmailEventFactory> */
    use HasFactory;

    /**
     * Get the message the event belongs to.
     *
     * @return BelongsTo<EmailMessage, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(EmailMessage::class, 'email_message_id');
    }

    /**
     * Get the team that owns the event.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => EmailEventType::class,
            'payload' => 'array',
            'occurred_at' => 'datetime',
        ];
    }
}
