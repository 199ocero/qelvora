<?php

namespace App\Http\Requests\Mail;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'html' => ['nullable', 'string'],
            'text' => ['nullable', 'string'],
        ];
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
}
