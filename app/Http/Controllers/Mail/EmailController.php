<?php

namespace App\Http\Controllers\Mail;

use App\Actions\Mail\ResendEmail;
use App\Actions\Mail\SendEmail;
use App\Concerns\PresentsMailResources;
use App\Enums\EmailMessageStatus;
use App\Enums\IdentityStatus;
use App\Exceptions\Mail\NoActiveProviderException;
use App\Exceptions\Mail\RecipientSuppressedException;
use App\Exceptions\Mail\UnverifiedSenderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\SendMailRequest;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailTemplate;
use App\Models\MailIdentity;
use App\Models\Team;
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
     * Show the paginated, filterable message log.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        $filters = [
            'search' => $request->string('search')->trim()->value() ?: null,
            'status' => $request->string('status')->value() ?: null,
            'via' => $request->string('via')->value() ?: null,
            'from' => $request->date('from')?->toDateString(),
            'to' => $request->date('to')?->toDateString(),
        ];

        $messages = $team->emailMessages()
            ->filter($filters)
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (EmailMessage $message) => $this->presentMessage($message));

        return Inertia::render('mail/emails/Index', [
            'messages' => $messages,
            'filters' => $filters,
            'statuses' => collect(EmailMessageStatus::cases())
                ->map(fn (EmailMessageStatus $status) => ['value' => $status->value, 'label' => $status->label()])
                ->all(),
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
            'templates' => $team->emailTemplates()
                ->orderBy('name')
                ->get()
                ->map(fn (EmailTemplate $template) => $this->presentTemplate($template, withBody: true))
                ->all(),
        ]);
    }

    /**
     * Send (or schedule) a message from the UI.
     */
    public function store(SendMailRequest $request, SendEmail $sendEmail): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('sendEmail', $team);

        try {
            $message = $sendEmail->handle(
                $team,
                $request->outgoingMessage(),
                sentVia: 'ui',
                templateId: $request->template()?->id,
                scheduledAt: $request->scheduledAt(),
            );
        } catch (NoActiveProviderException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        } catch (UnverifiedSenderException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        } catch (RecipientSuppressedException $e) {
            throw ValidationException::withMessages(['to' => $e->getMessage()]);
        }

        $scheduled = $message->status === EmailMessageStatus::Scheduled;

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $scheduled ? __('Email scheduled.') : __('Email sent.'),
        ]);

        return to_route('mail.emails.show', $message->id);
    }

    /**
     * Show a single message with its event timeline.
     */
    public function show(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        $message = $this->findMessage($request);

        return Inertia::render('mail/emails/Show', [
            'message' => $this->presentMessage($message, withBody: true),
            'events' => $message->events()
                ->latest('occurred_at')
                ->get()
                ->map(fn (EmailEvent $event) => $this->presentEvent($event))
                ->all(),
        ]);
    }

    /**
     * Queue a fresh copy of a failed message.
     */
    public function resend(Request $request, ResendEmail $resendEmail): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('sendEmail', $team);

        $message = $this->findMessage($request);

        if (! in_array($message->status, [EmailMessageStatus::Failed, EmailMessageStatus::Rejected], true)) {
            throw ValidationException::withMessages(['status' => __('Only failed messages can be resent.')]);
        }

        try {
            $resend = $resendEmail->handle($message);
        } catch (NoActiveProviderException|UnverifiedSenderException $e) {
            throw ValidationException::withMessages(['from' => $e->getMessage()]);
        } catch (RecipientSuppressedException $e) {
            throw ValidationException::withMessages(['to' => $e->getMessage()]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Email queued for resend.')]);

        return to_route('mail.emails.show', $resend->id);
    }

    /**
     * Cancel a scheduled message before it is sent.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('sendEmail', $team);

        $message = $this->findMessage($request);

        if ($message->status !== EmailMessageStatus::Scheduled) {
            throw ValidationException::withMessages(['status' => __('Only scheduled messages can be canceled.')]);
        }

        $message->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Scheduled email canceled.')]);

        return to_route('mail.emails.index');
    }

    /**
     * Resolve the route's message, scoped to the current team.
     */
    protected function findMessage(Request $request): EmailMessage
    {
        /** @var Team $team */
        $team = $request->user()->currentTeam;

        return $team->emailMessages()->whereKey($request->route('message'))->firstOrFail();
    }
}
