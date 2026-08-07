<?php

namespace App\Models;

use App\Enums\MailProvider;
use App\Enums\ProviderConnectionStatus;
use Database\Factories\ProviderConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $team_id
 * @property MailProvider $provider
 * @property array<string, mixed>|null $credentials
 * @property array<string, mixed>|null $settings
 * @property ProviderConnectionStatus $status
 * @property bool $is_active
 * @property string $webhook_token
 * @property bool $production_access
 * @property float|null $send_quota_max
 * @property float|null $sent_last_24h
 * @property float|null $max_send_rate
 * @property string|null $enforcement_status
 * @property float|null $bounce_rate
 * @property float|null $complaint_rate
 * @property Carbon|null $connected_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
#[Fillable([
    'team_id', 'provider', 'credentials', 'settings', 'status', 'is_active',
    'production_access', 'send_quota_max', 'sent_last_24h', 'max_send_rate',
    'enforcement_status', 'bounce_rate', 'complaint_rate', 'connected_at', 'last_synced_at',
])]
class ProviderConnection extends Model
{
    /** @use HasFactory<ProviderConnectionFactory> */
    use HasFactory;

    /**
     * Bootstrap the model and its traits.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProviderConnection $connection) {
            if (empty($connection->webhook_token)) {
                $connection->webhook_token = Str::random(64);
            }
        });
    }

    /**
     * Get the team that owns the connection.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the sending identities created through this connection.
     *
     * @return HasMany<MailIdentity, $this>
     */
    public function identities(): HasMany
    {
        return $this->hasMany(MailIdentity::class);
    }

    /**
     * Get the messages sent through this connection.
     *
     * @return HasMany<EmailMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class);
    }

    /**
     * Get a single credential value.
     */
    public function credential(string $key): ?string
    {
        return $this->credentials[$key] ?? null;
    }

    /**
     * Get a single setting value.
     */
    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Determine whether the connection has been successfully connected.
     */
    public function isConnected(): bool
    {
        return $this->status === ProviderConnectionStatus::Connected;
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
            'credentials' => 'encrypted:array',
            'settings' => 'array',
            'status' => ProviderConnectionStatus::class,
            'is_active' => 'boolean',
            'production_access' => 'boolean',
            'send_quota_max' => 'float',
            'sent_last_24h' => 'float',
            'max_send_rate' => 'float',
            'bounce_rate' => 'float',
            'complaint_rate' => 'float',
            'connected_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'provider';
    }
}
