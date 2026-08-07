<?php

namespace Database\Factories;

use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailIdentity>
 */
class MailIdentityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $domain = fake()->unique()->domainName();
        $tokens = [fake()->regexify('[a-z0-9]{32}'), fake()->regexify('[a-z0-9]{32}'), fake()->regexify('[a-z0-9]{32}')];

        return [
            'provider_connection_id' => ProviderConnection::factory(),
            'identity' => $domain,
            'type' => IdentityType::Domain,
            'status' => IdentityStatus::Pending,
            'dns_records' => array_map(fn (string $token) => [
                'type' => 'CNAME',
                'host' => "{$token}._domainkey.{$domain}",
                'value' => "{$token}.dkim.amazonses.com",
                'priority' => null,
                'purpose' => 'DKIM',
                'status' => null,
            ], $tokens),
            'meta' => ['dkim_tokens' => $tokens, 'mail_from_domain' => "mail.{$domain}"],
        ];
    }

    /**
     * Derive team_id from the parent connection when not explicitly set.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (MailIdentity $identity) {
            if (empty($identity->team_id)) {
                $identity->team_id = $identity->connection->team_id;
            }
        });
    }

    /**
     * Indicate that the identity is an email address.
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'identity' => fake()->unique()->safeEmail(),
            'type' => IdentityType::Email,
            'dns_records' => [],
            'meta' => [],
        ]);
    }

    /**
     * Indicate that the identity is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => IdentityStatus::Verified,
            'verified_at' => now(),
        ]);
    }
}
