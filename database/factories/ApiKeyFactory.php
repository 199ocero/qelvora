<?php

namespace Database\Factories;

use App\Models\ApiKey;
use App\Models\MailIdentity;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApiKey>
 */
class ApiKeyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $plain = 'qlv_'.Str::random(40);

        return [
            'team_id' => Team::factory(),
            'name' => ucfirst(fake()->word()).' API key',
            'key_prefix' => substr($plain, 0, 12),
            'key_hash' => $plain,
            'last_four' => substr($plain, -4),
            'created_by' => null,
        ];
    }

    /**
     * Indicate that the API key has been revoked.
     */
    public function revoked(): static
    {
        return $this->state(fn (array $attributes) => [
            'revoked_at' => now(),
        ]);
    }

    /**
     * Restrict the key to a single verified sending identity.
     */
    public function restrictedTo(MailIdentity $identity): static
    {
        return $this->state(fn (array $attributes) => [
            'mail_identity_id' => $identity->id,
        ]);
    }
}
