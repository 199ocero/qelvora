<?php

namespace App\Actions\Mail;

use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use App\Models\Suppression;
use App\Models\Team;
use App\Services\Mail\MailProviderManager;

class AddSuppression
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Add an address to the team's suppression list and, optionally, the
     * provider's account-level suppression list.
     */
    public function handle(
        Team $team,
        string $email,
        SuppressionReason $reason = SuppressionReason::Manual,
        ?string $notes = null,
    ): Suppression {
        $connection = $team->activeConnection();

        if ($connection !== null && $connection->isConnected()) {
            $this->manager->driver($connection)->addSuppression($email, $reason);
        }

        return $team->suppressions()->updateOrCreate(
            ['email' => $email],
            [
                'provider_connection_id' => $connection?->id,
                'reason' => $reason,
                'source' => SuppressionSource::Local,
                'notes' => $notes,
                'suppressed_at' => now(),
            ],
        );
    }
}
