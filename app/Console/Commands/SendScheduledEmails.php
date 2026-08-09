<?php

namespace App\Console\Commands;

use App\Actions\Mail\SendEmail;
use App\Enums\EmailMessageStatus;
use App\Models\EmailMessage;
use Illuminate\Console\Command;

class SendScheduledEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:send-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release scheduled emails that are now due into the sending queue';

    /**
     * Execute the console command.
     */
    public function handle(SendEmail $sendEmail): int
    {
        $due = EmailMessage::query()
            ->where('status', EmailMessageStatus::Scheduled)
            ->where('scheduled_at', '<=', now())
            ->get();

        $due->each(fn (EmailMessage $message) => $sendEmail->release($message));

        $this->info("Released {$due->count()} scheduled email(s).");

        return self::SUCCESS;
    }
}
