<?php

namespace Database\Factories;

use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use App\Models\ProviderConnection;
use App\Models\Suppression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Suppression>
 */
class SuppressionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider_connection_id' => ProviderConnection::factory(),
            'email' => fake()->unique()->safeEmail(),
            'reason' => SuppressionReason::Bounce,
            'source' => SuppressionSource::Event,
            'suppressed_at' => now(),
        ];
    }

    /**
     * Derive team_id from the parent connection when not explicitly set.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Suppression $suppression) {
            if (empty($suppression->team_id)) {
                $suppression->team_id = $suppression->connection?->team_id;
            }
        });
    }

    /**
     * Indicate a manual suppression.
     */
    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'reason' => SuppressionReason::Manual,
            'source' => SuppressionSource::Local,
        ]);
    }
}
