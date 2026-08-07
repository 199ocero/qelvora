<?php

namespace App\Http\Requests\Mail;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class SetWebhookTunnelRequest extends FormRequest
{
    /**
     * The dev webhook tunnel is a local-development affordance only, and still
     * requires provider-management rights on the current team.
     */
    public function authorize(): bool
    {
        return (bool) config('mail-providers.allow_dev_tunnel')
            && Gate::allows('manageProviders', $this->user()->currentTeam);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'url' => ['nullable', 'string', 'url:http,https', 'max:255'],
        ];
    }
}
