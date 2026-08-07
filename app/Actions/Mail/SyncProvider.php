<?php

namespace App\Actions\Mail;

use App\Models\ProviderConnection;
use App\Services\Mail\MailProviderManager;

class SyncProvider
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Refresh the cached account health for a connection.
     */
    public function handle(ProviderConnection $connection): ProviderConnection
    {
        $health = $this->manager->driver($connection)->syncAccount();

        $connection->update([
            ...$health->toAttributes(),
            'last_synced_at' => now(),
        ]);

        return $connection;
    }
}
