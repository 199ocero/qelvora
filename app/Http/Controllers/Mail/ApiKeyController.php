<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\CreateApiKey;
use App\Actions\Mail\RevokeApiKey;
use App\Concerns\PresentsMailResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\CreateApiKeyRequest;
use App\Models\ApiKey;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    use PresentsMailResources;

    /**
     * List the team's API keys.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageApiKeys', $team);

        return Inertia::render('mail/ApiKeys', [
            'apiKeys' => $team->apiKeys()
                ->with('creator')
                ->latest()
                ->get()
                ->map(fn (ApiKey $apiKey) => $this->presentApiKey($apiKey))
                ->all(),
            // Shown exactly once, immediately after creation.
            'newApiKey' => $request->session()->get('newApiKey'),
        ]);
    }

    /**
     * Create a new API key.
     */
    public function store(CreateApiKeyRequest $request, CreateApiKey $createApiKey): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageApiKeys', $team);

        ['plainText' => $plainText] = $createApiKey->handle($team, $request->user(), $request->validated('name'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API key created.')]);

        return to_route('mail.api-keys.index')->with('newApiKey', $plainText);
    }

    /**
     * Revoke an API key.
     */
    public function destroy(Request $request, RevokeApiKey $revokeApiKey): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageApiKeys', $team);

        /** @var Team $team */
        $apiKey = $team->apiKeys()->whereKey($request->route('apiKey'))->firstOrFail();

        $revokeApiKey->handle($apiKey);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('API key revoked.')]);

        return to_route('mail.api-keys.index');
    }
}
