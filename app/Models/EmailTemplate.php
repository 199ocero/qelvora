<?php

namespace App\Models;

use Database\Factories\EmailTemplateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $team_id
 * @property string $name
 * @property string $slug
 * @property string|null $subject
 * @property string|null $html
 * @property string|null $text
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Team $team
 */
#[Fillable(['team_id', 'name', 'slug', 'subject', 'html', 'text'])]
class EmailTemplate extends Model
{
    /** @use HasFactory<EmailTemplateFactory> */
    use HasFactory;

    /**
     * Get the team that owns the template.
     *
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Render the template's subject and bodies with the given variables.
     *
     * Substitutes `{{ key }}` (and `{{key}}`) tokens; unknown tokens are left
     * untouched so a missing variable is visible rather than silently blanked.
     *
     * @param  array<string, scalar|null>  $variables
     * @return array{subject: string|null, html: string|null, text: string|null}
     */
    public function render(array $variables = []): array
    {
        return [
            'subject' => $this->substitute($this->subject, $variables),
            'html' => $this->substitute($this->html, $variables),
            'text' => $this->substitute($this->text, $variables),
        ];
    }

    /**
     * Get the distinct variable names referenced across the template.
     *
     * @return array<int, string>
     */
    public function variableNames(): array
    {
        $names = [];

        foreach ([$this->subject, $this->html, $this->text] as $part) {
            if (blank($part)) {
                continue;
            }

            if (preg_match_all('/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/', (string) $part, $matches) === false) {
                continue;
            }

            $names = array_merge($names, $matches[1]);
        }

        return array_values(array_unique($names));
    }

    /**
     * Replace `{{ key }}` tokens in a single string.
     *
     * @param  array<string, scalar|null>  $variables
     */
    protected function substitute(?string $subject, array $variables): ?string
    {
        if (blank($subject)) {
            return $subject;
        }

        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $matches) use ($variables): string {
                $key = $matches[1];

                return array_key_exists($key, $variables)
                    ? (string) $variables[$key]
                    : $matches[0];
            },
            $subject,
        );
    }
}
