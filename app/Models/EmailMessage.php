<?php

namespace App\Models;

use App\Enums\EmailMessageStatus;
use App\Enums\MailProvider;
use Database\Factories\EmailMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $provider_connection_id
 * @property int $team_id
 * @property MailProvider $provider
 * @property string|null $provider_message_id
 * @property string $from_address
 * @property array<int, string> $to
 * @property string|null $subject
 * @property string|null $html
 * @property string|null $text
 * @property EmailMessageStatus $status
 * @property string $sent_via
 * @property int|null $api_key_id
 * @property int|null $email_template_id
 * @property Carbon|null $scheduled_at
 * @property int $opens_count
 * @property int $clicks_count
 * @property string|null $error
 * @property array<string, string>|null $tags
 * @property Carbon|null $last_event_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProviderConnection|null $connection
 * @property-read Team $team
 * @property-read Collection<int, EmailEvent> $events
 */
#[Fillable([
    'provider_connection_id', 'team_id', 'provider', 'provider_message_id', 'from_address',
    'to', 'subject', 'html', 'text', 'status', 'sent_via', 'api_key_id', 'email_template_id',
    'opens_count', 'clicks_count', 'error', 'tags', 'last_event_at', 'scheduled_at',
])]
class EmailMessage extends Model
{
    /** @use HasFactory<EmailMessageFactory> */
    use HasFactory;

    /**
     * Get the connection the message was sent through.
     *
     * @return BelongsTo<ProviderConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class, 'provider_connection_id');
    }

    /**
     * Get the team that owns the message.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the events recorded for the message.
     *
     * @return HasMany<EmailEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(EmailEvent::class);
    }

    /**
     * Get the template the message was rendered from, if any.
     *
     * @return BelongsTo<EmailTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    /**
     * Apply the log's search and filter query parameters.
     *
     * @param  Builder<EmailMessage>  $query
     * @param  array{search?: string|null, status?: string|null, via?: string|null, from?: string|null, to?: string|null}  $filters
     * @return Builder<EmailMessage>
     */
    #[Scope]
    protected function filter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('subject', 'like', "%{$search}%")
                        ->orWhere('from_address', 'like', "%{$search}%")
                        ->orWhere('to', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['via'] ?? null, fn (Builder $query, string $via) => $query->where('sent_via', $via))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to));
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'provider' => MailProvider::class,
            'to' => 'array',
            'status' => EmailMessageStatus::class,
            'tags' => 'array',
            'last_event_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }
}
