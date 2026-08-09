<?php

namespace App\Actions\Mail;

use App\Exceptions\Mail\NoActiveProviderException;
use App\Exceptions\Mail\RecipientSuppressedException;
use App\Exceptions\Mail\UnverifiedSenderException;
use App\Models\EmailMessage;
use App\Services\Mail\Data\OutgoingMessage;

class ResendEmail
{
    public function __construct(
        protected SendEmail $sendEmail,
    ) {
        //
    }

    /**
     * Queue a fresh copy of a previously failed message.
     *
     * The original record is kept as an audit trail; a new queued message is
     * created so the send runs through the same validation as any other send.
     *
     * @throws NoActiveProviderException|UnverifiedSenderException|RecipientSuppressedException
     */
    public function handle(EmailMessage $message): EmailMessage
    {
        return $this->sendEmail->handle(
            $message->team,
            new OutgoingMessage(
                from: $message->from_address,
                to: $message->to,
                subject: (string) $message->subject,
                html: $message->html,
                text: $message->text,
                tags: $message->tags ?? [],
            ),
            sentVia: $message->sent_via,
            templateId: $message->email_template_id,
            queue: true,
        );
    }
}
