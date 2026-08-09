<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\ConnectProvider;
use App\Actions\Mail\DisconnectProvider;
use App\Actions\Mail\ProvisionWebhook;
use App\Actions\Mail\SwitchProvider;
use App\Actions\Mail\SyncProvider;
use App\Concerns\PresentsMailResources;
use App\Enums\MailProvider;
use App\Exceptions\ProviderConnectionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\ConnectProviderRequest;
use App\Http\Requests\Mail\SetWebhookTunnelRequest;
use App\Models\ProviderConnection;
use App\Support\DevWebhookTunnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ConnectionController extends Controller
{
    use PresentsMailResources;

    /**
     * Show the provider connection screen.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        return Inertia::render('mail/Connection', [
            'provider' => MailProvider::Ses->toArray(),
            'connections' => $team->connections()
                ->latest('is_active')
                ->get()
                ->map(fn (ProviderConnection $connection) => $this->presentConnection($connection))
                ->all(),
            'webhookTunnel' => [
                'editable' => (bool) config('mail-providers.allow_dev_tunnel'),
                'url' => DevWebhookTunnel::baseUrl(),
            ],
        ]);
    }

    /**
     * Connect (or re-connect) a provider and make it active.
     */
    public function store(ConnectProviderRequest $request, ConnectProvider $connectProvider): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        try {
            $connectProvider->handle(
                $team,
                $request->provider(),
                $request->validated('credentials', []),
            );
        } catch (ProviderConnectionException $e) {
            throw ValidationException::withMessages([
                'credentials.access_key_id' => $e->getMessage(),
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Provider connected.')]);

        return to_route('mail.connection.index');
    }

    /**
     * Switch the active provider connection.
     */
    public function switch(Request $request, SwitchProvider $switchProvider): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        $switchProvider->handle($team, $this->resolveConnection($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Active connection switched.')]);

        return to_route('mail.connection.index');
    }

    /**
     * (Re-)provision the event webhook for a connection.
     */
    public function webhook(Request $request, ProvisionWebhook $provisionWebhook): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        $error = $provisionWebhook->handle($this->resolveConnection($request));

        Inertia::flash('toast', $error === null
            ? ['type' => 'success', 'message' => __('Event webhook set up.')]
            : ['type' => 'error', 'message' => __('Webhook setup failed: :error', ['error' => $error])]);

        return to_route('mail.connection.index');
    }

    /**
     * Set or clear the local-development webhook tunnel URL. Providers register
     * their event endpoint against this base URL, so it must be updated after
     * (re)starting a tunnel whose public URL has changed.
     */
    public function tunnel(SetWebhookTunnelRequest $request): RedirectResponse
    {
        $url = $request->validated('url');

        if (blank($url)) {
            DevWebhookTunnel::clear();

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Webhook tunnel URL cleared.')]);
        } else {
            DevWebhookTunnel::store($url);

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Webhook tunnel URL saved.')]);
        }

        return to_route('mail.connection.index');
    }

    /**
     * Refresh cached account health for a connection.
     */
    public function sync(Request $request, SyncProvider $syncProvider): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        $syncProvider->handle($this->resolveConnection($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Account health refreshed.')]);

        return to_route('mail.connection.index');
    }

    /**
     * Disconnect a provider.
     */
    public function destroy(Request $request, DisconnectProvider $disconnectProvider): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageProviders', $team);

        $disconnectProvider->handle($this->resolveConnection($request));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Provider disconnected.')]);

        return to_route('mail.connection.index');
    }

    /**
     * Resolve the target connection from the submitted provider.
     */
    protected function resolveConnection(Request $request): ProviderConnection
    {
        $provider = MailProvider::tryFrom((string) $request->input('provider'));

        abort_if($provider === null, 404);

        return $request->user()->currentTeam
            ->connections()
            ->where('provider', $provider->value)
            ->firstOrFail();
    }
}
