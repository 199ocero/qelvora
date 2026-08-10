<?php

namespace App\Http\Requests\Mail;

use App\Enums\IdentityStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateApiKeyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $teamId = $this->user()->currentTeam?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            // Optional: restrict the key to one of the team's verified identities.
            'mail_identity_id' => [
                'nullable',
                'integer',
                Rule::exists('mail_identities', 'id')
                    ->where('team_id', $teamId)
                    ->where('status', IdentityStatus::Verified->value),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'mail_identity_id.exists' => 'Choose a verified domain or address for this team.',
        ];
    }
}
