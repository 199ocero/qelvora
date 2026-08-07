<?php

namespace Database\Factories;

use App\Enums\MailProvider;
use App\Enums\ProviderConnectionStatus;
use App\Models\ProviderConnection;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderConnection>
 */
class ProviderConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'provider' => MailProvider::Ses,
            'credentials' => [
                'access_key_id' => 'AKIA'.fake()->regexify('[A-Z0-9]{16}'),
                'secret_access_key' => fake()->regexify('[A-Za-z0-9]{40}'),
                'region' => 'us-east-1',
            ],
            'settings' => [
                'configuration_set_name' => 'qelvora-'.fake()->slug(2),
                'sns_topic_arn' => 'arn:aws:sns:us-east-1:123456789012:'.fake()->slug(2),
                'webhook_provisioned' => true,
            ],
            'status' => ProviderConnectionStatus::Connected,
            'is_active' => true,
            'production_access' => true,
            'send_quota_max' => 50000,
            'sent_last_24h' => 120,
            'max_send_rate' => 14,
            'enforcement_status' => 'HEALTHY',
            'connected_at' => now(),
            'last_synced_at' => now(),
        ];
    }

    /**
     * Indicate that the connection is pending (not yet verified).
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProviderConnectionStatus::Pending,
            'is_active' => false,
            'connected_at' => null,
        ]);
    }

    /**
     * Indicate that the connection is inactive (saved but not the active one).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set the provider for the connection.
     */
    public function provider(MailProvider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => $provider,
        ]);
    }
}
