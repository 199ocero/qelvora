<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\CreateTemplate;
use App\Actions\Mail\UpdateTemplate;
use App\Concerns\PresentsMailResources;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\StoreTemplateRequest;
use App\Http\Requests\Mail\UpdateTemplateRequest;
use App\Models\EmailTemplate;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TemplateController extends Controller
{
    use PresentsMailResources;

    /**
     * List the team's email templates.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        return Inertia::render('mail/templates/Index', [
            'templates' => $team->emailTemplates()
                ->latest()
                ->get()
                ->map(fn (EmailTemplate $template) => $this->presentTemplate($template))
                ->all(),
            'canManage' => $request->user()->can('manageTemplates', $team),
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageTemplates', $team);

        return Inertia::render('mail/templates/Create');
    }

    /**
     * Store a new template.
     */
    public function store(StoreTemplateRequest $request, CreateTemplate $createTemplate): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageTemplates', $team);

        $createTemplate->handle($team, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template created.')]);

        return to_route('mail.templates.index');
    }

    /**
     * Show the edit form.
     */
    public function edit(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageTemplates', $team);

        return Inertia::render('mail/templates/Edit', [
            'template' => $this->presentTemplate($this->findTemplate($request), withBody: true),
        ]);
    }

    /**
     * Update a template.
     */
    public function update(UpdateTemplateRequest $request, UpdateTemplate $updateTemplate): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageTemplates', $team);

        $updateTemplate->handle($this->findTemplate($request), $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template updated.')]);

        return to_route('mail.templates.index');
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('manageTemplates', $team);

        $this->findTemplate($request)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Template deleted.')]);

        return to_route('mail.templates.index');
    }

    /**
     * Resolve the route's template, scoped to the current team.
     */
    protected function findTemplate(Request $request): EmailTemplate
    {
        /** @var Team $team */
        $team = $request->user()->currentTeam;

        return $team->emailTemplates()->whereKey($request->route('template'))->firstOrFail();
    }
}
