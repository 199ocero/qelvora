<?php

namespace App\Actions\Mail;

use App\Models\MailIdentity;
use App\Services\Mail\MailProviderManager;

class DeleteIdentity
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Remove an identity from the provider and delete it locally.
     */
    public function handle(MailIdentity $identity): void
    {
        $this->manager->driver($identity->connection)->deleteIdentity($identity);

        $identity->delete();
    }
}
