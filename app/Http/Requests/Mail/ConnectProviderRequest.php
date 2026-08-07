<?php

namespace App\Http\Requests\Mail;

use App\Enums\MailProvider;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class ConnectProviderRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'provider' => ['required', new Enum(MailProvider::class)],
        ];

        foreach ($this->providerCredentialFields() as $field) {
            $fieldRules = $field['required'] ? ['required', 'string'] : ['nullable', 'string'];

            if ($field['type'] === 'select' && ! empty($field['options'])) {
                $fieldRules[] = Rule::in(array_column($field['options'], 'value'));
            }

            $rules["credentials.{$field['name']}"] = $fieldRules;
        }

        return $rules;
    }

    /**
     * Reject providers that do not yet have a driver.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $provider = MailProvider::tryFrom((string) $this->input('provider'));

            if ($provider !== null && ! $provider->isImplemented()) {
                $validator->errors()->add('provider', "The {$provider->label()} provider is not yet available.");
            }
        });
    }

    /**
     * Get the resolved provider for the request.
     */
    public function provider(): MailProvider
    {
        return MailProvider::from($this->validated('provider'));
    }

    /**
     * The credential field schema for the submitted provider.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function providerCredentialFields(): array
    {
        return MailProvider::tryFrom((string) $this->input('provider'))?->credentialFields() ?? [];
    }
}
