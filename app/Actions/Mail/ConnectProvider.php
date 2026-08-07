<?php

namespace App\Actions\Mail;

use App\Enums\MailProvider;
use App\Enums\ProviderConnectionStatus;
use App\Exceptions\ProviderConnectionException;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Services\Mail\MailProviderManager;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConnectProvider
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Connect (or re-connect) a provider for the team: validate credentials,
     * provision event webhooks, and make it the team's active connection.
     *
     * @param  array<string, string>  $credentials
     */
    public function handle(Team $team, MailProvider $provider, array $credentials): ProviderConnection
    {
        $connection = $team->connections()->updateOrCreate(
            ['provider' => $provider->value],
            ['credentials' => $credentials, 'status' => ProviderConnectionStatus::Pending],
        );

        try {
            $health = $this->manager->driver($connection)->verifyCredentials();
        } catch (Throwable $e) {
            $connection->update(['status' => ProviderConnectionStatus::Failed]);

            throw ProviderConnectionException::failed($e);
        }

        // Provisioning event delivery is best-effort: a missing SNS permission
        // or an unreachable webhook must not block the connection itself.
        try {
            $this->manager->driver($connection)->provisionWebhooks();
            $connection->refresh();
        } catch (Throwable $e) {
            report($e);

            $connection->update([
                'settings' => array_merge($connection->settings ?? [], [
                    'webhook_provisioned' => false,
                    'provision_error' => $e->getMessage(),
                ]),
            ]);
        }

        return DB::transaction(function () use ($team, $connection, $health): ProviderConnection {
            $connection->update([
                ...$health->toAttributes(),
                'status' => ProviderConnectionStatus::Connected,
                'is_active' => true,
                'connected_at' => $connection->connected_at ?? now(),
                'last_synced_at' => now(),
            ]);

            $team->connections()
                ->whereKeyNot($connection->getKey())
                ->update(['is_active' => false]);

            return $connection->fresh();
        });
    }
}
