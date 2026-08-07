<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\SendEmail;
use App\Concerns\PresentsMailResources;
use App\Enums\IdentityStatus;
use App\Exceptions\Mail\NoActiveProviderException;
use App\Exceptions\Mail\RecipientSuppressedException;
use App\Exceptions\Mail\UnverifiedSenderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\SendMailRequest;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\MailIdentity;
use App\Models\Team;
use App\Services\Mail\Data\OutgoingMessage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EmailController extends Controller
{
    use PresentsMailResources;

    /**
     * Show the paginated message log.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        $messages = $team->emailMessages()
            ->latest()
            ->paginate(20)
            ->through(fn (EmailMessage $message) => $this->presentMessage($message));

        return Inertia::render('mail/emails/Index', [
            'messages' => $messages,
        ]);
    }

    /**
     * Show the compose screen.
     */
    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('sendEmail', $team);

        return Inertia::render('mail/emails/Create', [
            'senders' => $team->mailIdentities()
                ->where('status', IdentityStatus::Verified)
                ->orderBy('identity')
                ->get()
                ->map(fn (MailIdentity $identity) => $identity->identity)
                ->all(),
        ]);
    }

    /**
     * Send a message from the UI.
     */
    public function store(SendMailRequest $request, SendEmail $sendEmail): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('sendEmail', $team);

        try {
            $message = $sendEmail->handle(
                $team,
                new OutgoingMessage(
                    from: $request->validated('from'),
                    to: $request->recipients(),
                    subject: $request->validated('subject'),
                    html: $request->validated('html'),
                    text: $request->validated('text'),
                ),
                sentVia: 'ui',
            );
        } catch (NoActiveProviderException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        } catch (UnverifiedSenderException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        } catch (RecipientSuppressedException $e) {
            throw ValidationException::withMessages(['to' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email sent.')]);

        return to_route('mail.emails.show', $message->id);
    }

    /**
     * Show a single message with its event timeline.
     */
    public function show(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        /** @var Team $team */
        $message = $team->emailMessages()->whereKey($request->route('message'))->firstOrFail();

        return Inertia::render('mail/emails/Show', [
            'message' => $this->presentMessage($message, withBody: true),
            'events' => $message->events()
                ->latest('occurred_at')
                ->get()
                ->map(fn (EmailEvent $event) => $this->presentEvent($event))
                ->all(),
        ]);
    }
}
