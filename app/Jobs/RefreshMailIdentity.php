<?php

namespace App\Jobs;

use App\Actions\Mail\RefreshIdentity;
use App\Models\MailIdentity;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RefreshMailIdentity implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public MailIdentity $identity,
    ) {
        //
    }

    /**
     * Re-check the identity's verification status with the provider.
     *
     * A single identity failing (a transient SES error, say) must not fail the
     * whole batch, so problems are reported and swallowed.
     */
    public function handle(RefreshIdentity $refreshIdentity): void
    {
        try {
            $refreshIdentity->handle($this->identity);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
