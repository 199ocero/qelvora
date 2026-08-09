<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\AddSuppression;
use App\Actions\Mail\RemoveSuppression;
use App\Actions\Mail\SyncSuppressions;
use App\Concerns\PresentsMailResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\AddSuppressionRequest;
use App\Models\Suppression;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SuppressionController extends Controller
{
    use PresentsMailResources;

    /**
     * List the team's suppressed addresses.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageSuppressions', $team);

        $suppressions = $team->suppressions()
            ->latest('suppressed_at')
            ->paginate(50)
            ->through(fn (Suppression $suppression) => $this->presentSuppression($suppression));

        return Inertia::render('mail/Suppressions', [
            'suppressions' => $suppressions,
            'hasActiveConnection' => $team->activeConnection() !== null,
        ]);
    }

    /**
     * Manually add an address to the suppression list.
     */
    public function store(AddSuppressionRequest $request, AddSuppression $addSuppression): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageSuppressions', $team);

        $addSuppression->handle($team, $request->validated('email'), notes: $request->validated('notes'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address suppressed.')]);

        return to_route('mail.suppressions.index');
    }

    /**
     * Sync the suppression list from the provider.
     */
    public function sync(Request $request, SyncSuppressions $syncSuppressions): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageSuppressions', $team);

        $connection = $team->activeConnection();

        abort_if($connection === null, 409, 'Connect Amazon SES first.');

        $syncSuppressions->handle($connection);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Suppression list synced.')]);

        return to_route('mail.suppressions.index');
    }

    /**
     * Remove an address from the suppression list.
     */
    public function destroy(Request $request, RemoveSuppression $removeSuppression): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageSuppressions', $team);

        /** @var Team $team */
        $suppression = $team->suppressions()->whereKey($request->route('suppression'))->firstOrFail();

        $removeSuppression->handle($team, $suppression);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Address removed from suppression list.')]);

        return to_route('mail.suppressions.index');
    }
}
