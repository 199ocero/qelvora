<?php

namespace App\Models;

use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use Database\Factories\SuppressionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $provider_connection_id
 * @property int $team_id
 * @property string $email
 * @property SuppressionReason $reason
 * @property SuppressionSource $source
 * @property string|null $notes
 * @property Carbon|null $suppressed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
#[Fillable(['provider_connection_id', 'team_id', 'email', 'reason', 'source', 'notes', 'suppressed_at'])]
class Suppression extends Model
{
    /** @use HasFactory<SuppressionFactory> */
    use HasFactory;

    /**
     * Get the connection the suppression belongs to.
     *
     * @return BelongsTo<ProviderConnection, $this>
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(ProviderConnection::class, 'provider_connection_id');
    }

    /**
     * Get the team that owns the suppression.
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
            'reason' => SuppressionReason::class,
            'source' => SuppressionSource::class,
            'suppressed_at' => 'datetime',
        ];
    }
}
