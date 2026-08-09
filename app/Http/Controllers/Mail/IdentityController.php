<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\CreateIdentity;
use App\Actions\Mail\DeleteIdentity;
use App\Actions\Mail\RefreshIdentity;
use App\Concerns\PresentsMailResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\CreateIdentityRequest;
use App\Models\MailIdentity;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class IdentityController extends Controller
{
    use PresentsMailResources;

    /**
     * List the team's sending identities.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageDomains', $team);

        return Inertia::render('mail/domains/Index', [
            'identities' => $team->mailIdentities()
                ->latest()
                ->get()
                ->map(fn (MailIdentity $identity) => $this->presentIdentity($identity))
                ->all(),
            'hasActiveConnection' => $team->activeConnection() !== null,
        ]);
    }

    /**
     * Create a new sending identity.
     */
    public function store(CreateIdentityRequest $request, CreateIdentity $createIdentity): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageDomains', $team);

        $connection = $team->activeConnection();

        abort_if($connection === null, 409, 'Connect Amazon SES first.');

        $identity = $createIdentity->handle(
            $connection,
            $request->validated('identity'),
            $request->identityType(),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Identity added. Publish the DNS records to verify it.')]);

        return to_route('mail.domains.show', $identity->id);
    }

    /**
     * Show a single identity with its DNS records.
     */
    public function show(Request $request): Response
    {
        $identity = $this->resolveIdentity($request);

        return Inertia::render('mail/domains/Show', [
            'identity' => $this->presentIdentity($identity),
        ]);
    }

    /**
     * Re-check the identity's verification status.
     */
    public function refresh(Request $request, RefreshIdentity $refreshIdentity): RedirectResponse
    {
        $identity = $this->resolveIdentity($request);

        $refreshIdentity->handle($identity);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Verification status refreshed.')]);

        return to_route('mail.domains.show', $identity->id);
    }

    /**
     * Delete an identity.
     */
    public function destroy(Request $request, DeleteIdentity $deleteIdentity): RedirectResponse
    {
        $identity = $this->resolveIdentity($request);

        $deleteIdentity->handle($identity);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Identity removed.')]);

        return to_route('mail.domains.index');
    }

    /**
     * Resolve the route's identity scoped to the acting team (404 otherwise).
     */
    protected function resolveIdentity(Request $request): MailIdentity
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageDomains', $team);

        /** @var Team $team */
        return $team->mailIdentities()->whereKey($request->route('identity'))->firstOrFail();
    }
}
