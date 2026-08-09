<?php

namespace Database\Factories;

use App\Models\EmailTemplate;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EmailTemplate>
 */
class EmailTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word().' '.fake()->word();

        return [
            'team_id' => Team::factory(),
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'subject' => 'Welcome, {{ name }}',
            'html' => '<p>Hello {{ name }}, thanks for joining.</p>',
            'text' => 'Hello {{ name }}, thanks for joining.',
        ];
    }
}
