<?php

namespace App\Actions\Mail;

use App\Enums\SuppressionSource;
use App\Models\ProviderConnection;
use App\Services\Mail\MailProviderManager;

class SyncSuppressions
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Pull the provider's suppression list into the team's local list.
     */
    public function handle(ProviderConnection $connection): int
    {
        $suppressions = $this->manager->driver($connection)->listSuppressions();

        foreach ($suppressions as $suppression) {
            $connection->team->suppressions()->updateOrCreate(
                ['email' => $suppression['email']],
                [
                    'provider_connection_id' => $connection->id,
                    'reason' => $suppression['reason'],
                    'source' => SuppressionSource::Provider,
                    'suppressed_at' => now(),
                ],
            );
        }

        return count($suppressions);
    }
}
