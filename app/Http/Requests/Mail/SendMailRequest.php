<?php

namespace App\Http\Requests\Mail;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class SendMailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['required', 'string', 'email', 'max:255'],
            'to' => ['required', 'array', 'min:1', 'max:50'],
            'to.*' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'html' => ['nullable', 'string'],
            'text' => ['nullable', 'string'],
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
     * Require at least one body part.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
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
}
