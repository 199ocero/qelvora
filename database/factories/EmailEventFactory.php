<?php

namespace Database\Factories;

use App\Enums\EmailEventType;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailEvent>
 */
class EmailEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_message_id' => EmailMessage::factory(),
            'type' => EmailEventType::Delivery,
            'payload' => [],
            'occurred_at' => now(),
        ];
    }

    /**
     * Derive team_id and connection from the parent message when not set.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (EmailEvent $event) {
            $message = $event->message;

            if (empty($event->team_id)) {
                $event->team_id = $message?->team_id;
            }

            if (empty($event->provider_connection_id)) {
                $event->provider_connection_id = $message?->provider_connection_id;
            }
        });
    }

    /**
     * Indicate the event type.
     */
    public function type(EmailEventType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
