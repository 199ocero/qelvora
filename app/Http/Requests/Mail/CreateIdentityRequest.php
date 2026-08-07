<?php

namespace App\Http\Requests\Mail;

use App\Enums\IdentityType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CreateIdentityRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $identityRule = $this->input('type') === IdentityType::Email->value
            ? ['required', 'string', 'email', 'max:255']
            : ['required', 'string', 'max:255', 'regex:/^([a-z0-9]([a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/i'];

        return [
            'type' => ['required', new Enum(IdentityType::class)],
            'identity' => $identityRule,
        ];
    }

    /**
     * Normalize the identity before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('identity')) {
            $this->merge(['identity' => strtolower(trim((string) $this->input('identity')))]);
        }
    }

    /**
     * Get the resolved identity type.
     */
    public function identityType(): IdentityType
    {
        return IdentityType::from($this->validated('type'));
    }
}
