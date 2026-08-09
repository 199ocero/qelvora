<?php

namespace App\Actions\Mail;

use App\Models\EmailTemplate;
use App\Models\Team;
use Illuminate\Support\Str;

class CreateTemplate
{
    /**
     * Create a team email template with a unique slug.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Team $team, array $data): EmailTemplate
    {
        return $team->emailTemplates()->create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($team, (string) $data['name']),
            'subject' => $data['subject'] ?? null,
            'html' => $data['html'] ?? null,
            'text' => $data['text'] ?? null,
        ]);
    }

    /**
     * Build a slug that is unique within the team.
     */
    protected function uniqueSlug(Team $team, string $name): string
    {
        $base = Str::slug($name) ?: 'template';
        $slug = $base;
        $suffix = 1;

        while ($team->emailTemplates()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
