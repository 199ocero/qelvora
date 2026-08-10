<?php

use App\Actions\Mail\SyncProvider;
use App\Actions\Mail\SyncSuppressions;
use App\Enums\ProviderConnectionStatus;
use App\Models\ProviderConnection;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Schedule;

Schedule::call(function () {
    TeamInvitation::query()
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily()->description('Delete expired team invitations');

Schedule::call(function () {
    ProviderConnection::query()
        ->where('is_active', true)
        ->where('status', ProviderConnectionStatus::Connected)
        ->each(function (ProviderConnection $connection) {
            try {
                app(SyncProvider::class)->handle($connection);
                app(SyncSuppressions::class)->handle($connection);
            } catch (Throwable $e) {
                report($e);
            }
        });
})->hourly()->description('Sync active mail provider health and suppressions');

Schedule::command('mail:send-scheduled')
    ->everyMinute()
    ->description('Release scheduled emails that are now due');

Schedule::command('mail:refresh-identities')
    ->everyFiveMinutes()
    ->description('Re-check pending SES identity verification status');

Schedule::command('horizon:snapshot')
    ->everyFiveMinutes()
    ->description('Capture Horizon metrics snapshot for the dashboard');
