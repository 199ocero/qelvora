<?php

namespace App\Models;

use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use Database\Factories\MailIdentityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $provider_connection_id
 * @property int $team_id
 * @property string $identity
 * @property IdentityType $type
 * @property IdentityStatus $status
 * @property array<int, array<string, mixed>>|null $dns_records
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $verified_at
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read ProviderConnection $connection
 * @property-read Team $team
 */
#[Fillable(['provider_connection_id', 'team_id', 'identity', 'type', 'status', 'dns_records', 'meta', 'verified_at', 'last_checked_at'])]
class MailIdentity extends Model
{
    /** @use HasFactory<MailIdentityFactory> */
    use HasFactory;

    /**
     * Get the connection the identity belongs to.
     *
     * @return BelongsTo<ProviderConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class, 'provider_connection_id');
    }

    /**
     * Get the team that owns the identity.
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
            'type' => IdentityType::class,
            'status' => IdentityStatus::class,
            'dns_records' => 'array',
            'meta' => 'array',
            'verified_at' => 'datetime',
            'last_checked_at' => 'datetime',
        ];
    }
}
