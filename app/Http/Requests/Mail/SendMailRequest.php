<?php

namespace App\Http\Requests\Mail;

use App\Models\EmailTemplate;
use App\Models\Team;
use App\Services\Mail\Data\OutgoingMessage;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $team = $this->team();

        return [
            'from' => ['required', 'string', 'email', 'max:255'],
            'to' => ['required', 'array', 'min:1', 'max:50'],
            'to.*' => ['required', 'email'],
            // Subject/body may come from a template instead of the request.
            'subject' => ['required_without:template_id', 'nullable', 'string', 'max:255'],
            'html' => ['nullable', 'string'],
            'text' => ['nullable', 'string'],
            'template_id' => [
                'nullable',
                Rule::exists('email_templates', 'id')->where('team_id', $team?->id),
            ],
            'variables' => ['nullable', 'array'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /**
     * Accept a comma-separated or single "to" value and normalize to an array.
     */
    protected function prepareForValidation(): void
    {
        $to = $this->input('to');

        if (is_string($to)) {
            $this->merge([
                'to' => collect(explode(',', $to))
                    ->map(fn (string $email) => trim($email))
                    ->filter()
                    ->values()
                    ->all(),
            ]);
        }
    }

    /**
     * Require a body unless a template supplies one.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->filled('template_id')) {
                return;
            }

            if (blank($this->input('html')) && blank($this->input('text'))) {
                $validator->errors()->add('html', 'Provide an HTML or plain-text body.');
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function recipients(): array
    {
        return $this->validated('to');
    }

    /**
     * @return array<string, scalar|null>
     */
    public function variables(): array
    {
        return (array) ($this->validated('variables') ?? []);
    }

    /**
     * The template selected for this send, if any.
     */
    public function template(): ?EmailTemplate
    {
        $id = $this->validated('template_id');

        return $id ? EmailTemplate::query()->find((int) $id) : null;
    }

    /**
     * Build the outgoing message, rendering the template when one is selected.
     */
    public function outgoingMessage(): OutgoingMessage
    {
        $subject = (string) $this->validated('subject');
        $html = $this->validated('html');
        $text = $this->validated('text');

        if ($template = $this->template()) {
            $rendered = $template->render($this->variables());
            $subject = $rendered['subject'] ?? $subject;
            $html = $rendered['html'] ?? $html;
            $text = $rendered['text'] ?? $text;
        }

        return new OutgoingMessage(
            from: $this->validated('from'),
            to: $this->recipients(),
            subject: $subject,
            html: $html,
            text: $text,
        );
    }

    /**
     * The moment the send should be released, if scheduled for later.
     */
    public function scheduledAt(): ?CarbonImmutable
    {
        $value = $this->validated('scheduled_at');

        return $value ? CarbonImmutable::parse($value) : null;
    }

    /**
     * Resolve the team the send is scoped to (API key team or current team).
     */
    public function team(): ?Team
    {
        $apiTeam = $this->attributes->get('apiTeam');

        if ($apiTeam instanceof Team) {
            return $apiTeam;
        }

        return $this->user()?->currentTeam;
    }
}
