<?php

namespace App\Actions\Mail;

use App\Models\ProviderConnection;

class DisconnectProvider
{
    /**
     * Disconnect a provider: remove the stored credentials and its identities.
     * Sent messages are retained (their connection is nulled) for history.
     */
    public function handle(ProviderConnection $connection): void
    {
        $connection->delete();
    }
}
