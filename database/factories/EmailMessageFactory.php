<?php

namespace Database\Factories;

use App\Enums\EmailMessageStatus;
use App\Enums\MailProvider;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailMessage>
 */
class EmailMessageFactory extends Factory
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
            'provider' => MailProvider::Ses,
            'provider_message_id' => (string) Str::uuid(),
            'from_address' => fake()->safeEmail(),
            'to' => [fake()->safeEmail()],
            'subject' => fake()->sentence(),
            'html' => '<p>'.fake()->paragraph().'</p>',
            'text' => fake()->paragraph(),
            'status' => EmailMessageStatus::Sent,
            'sent_via' => 'ui',
        ];
    }

    /**
     * Derive team_id from the parent connection when not explicitly set.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (EmailMessage $message) {
            if (empty($message->team_id)) {
                $message->team_id = $message->connection?->team_id;
            }
        });
    }

    /**
     * Indicate the message reached a given status.
     */
    public function status(EmailMessageStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
