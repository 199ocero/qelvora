<?php

namespace App\Actions\Mail;

use App\Models\Suppression;
use App\Models\Team;
use App\Services\Mail\MailProviderManager;

class RemoveSuppression
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Remove an address from the team's and provider's suppression lists.
     */
    public function handle(Team $team, Suppression $suppression): void
    {
        $connection = $team->activeConnection();

        if ($connection !== null && $connection->isConnected()) {
            $this->manager->driver($connection)->removeSuppression($suppression->email);
        }

        $suppression->delete();
    }
}
