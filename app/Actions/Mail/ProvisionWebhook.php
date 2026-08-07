<?php

namespace App\Actions\Mail;

use App\Models\ProviderConnection;
use App\Services\Mail\MailProviderManager;
use Throwable;

class ProvisionWebhook
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * (Re-)provision event delivery for a connection. Returns null on success
     * or the failure message so the caller can surface it.
     */
    public function handle(ProviderConnection $connection): ?string
    {
        try {
            $this->manager->driver($connection)->provisionWebhooks();

            return null;
        } catch (Throwable $e) {
            report($e);

            $connection->update([
                'settings' => array_merge($connection->settings ?? [], [
                    'webhook_provisioned' => false,
                    'provision_error' => $e->getMessage(),
                ]),
            ]);

            return $e->getMessage();
        }
    }
}
