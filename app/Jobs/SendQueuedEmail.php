<?php

namespace App\Jobs;

use App\Actions\Mail\SendEmail;
use App\Models\EmailMessage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendQueuedEmail implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public EmailMessage $emailMessage,
    ) {
        //
    }

    /**
     * Deliver the queued message through its provider.
     */
    public function handle(SendEmail $sendEmail): void
    {
        $sendEmail->deliver($this->emailMessage);
    }
}
