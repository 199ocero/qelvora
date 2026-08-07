<?php

namespace App\Actions\Mail;

use App\Models\ProviderConnection;
use App\Models\Team;
use Illuminate\Support\Facades\DB;

class SwitchProvider
{
    /**
     * Make the given connection the team's active one, preserving all others.
     */
    public function handle(Team $team, ProviderConnection $connection): ProviderConnection
    {
        return DB::transaction(function () use ($team, $connection): ProviderConnection {
            $team->connections()->update(['is_active' => false]);

            $connection->update(['is_active' => true]);

            return $connection;
        });
    }
}
