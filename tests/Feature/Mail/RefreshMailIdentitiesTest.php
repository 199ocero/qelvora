<?php

use App\Actions\Mail\RefreshIdentity;
use App\Enums\IdentityStatus;
use App\Jobs\RefreshMailIdentity;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Services\Mail\Data\DnsRecord;
use App\Services\Mail\Dns\DnsRecordChecker;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    fakeMailDriver();
});

test('the command queues re-checks only for eligible pending identities', function () {
    Queue::fake();

    [, $team] = teamMember();
    [, $otherTeam] = teamMember();
    $active = ProviderConnection::factory()->for($team)->create();
    $inactive = ProviderConnection::factory()->for($otherTeam)->inactive()->create();

    $eligible = MailIdentity::factory()->for($active, 'connection')->create([
        'status' => IdentityStatus::Pending,
        'last_checked_at' => null,
    ]);

    // Already verified — nothing to poll.
    MailIdentity::factory()->for($active, 'connection')->create([
        'status' => IdentityStatus::Verified,
    ]);

    // Connection is not active.
    MailIdentity::factory()->for($inactive, 'connection')->create([
        'status' => IdentityStatus::Pending,
    ]);

    // Older than the polling window.
    MailIdentity::factory()->for($active, 'connection')->create([
        'status' => IdentityStatus::Pending,
        'created_at' => now()->subHours(80),
    ]);

    // Checked too recently.
    MailIdentity::factory()->for($active, 'connection')->create([
        'status' => IdentityStatus::Pending,
        'last_checked_at' => now()->subMinute(),
    ]);

    $this->artisan('mail:refresh-identities')->assertSuccessful();

    Queue::assertPushed(RefreshMailIdentity::class, 1);
    Queue::assertPushed(
        RefreshMailIdentity::class,
        fn (RefreshMailIdentity $job) => $job->identity->is($eligible),
    );
});

test('the job refreshes the identity status through the provider', function () {
    // Avoid a live DNS lookup during the refresh.
    $this->app->instance(DnsRecordChecker::class, new class extends DnsRecordChecker
    {
        public function annotate(array $records): array
        {
            return array_map(
                fn (DnsRecord $record): DnsRecord => $record->withStatus(self::STATUS_MISSING),
                $records,
            );
        }
    });

    [, $team] = teamMember();
    $connection = ProviderConnection::factory()->for($team)->create();
    $identity = MailIdentity::factory()->for($connection, 'connection')->create([
        'status' => IdentityStatus::Pending,
    ]);

    (new RefreshMailIdentity($identity))->handle(app(RefreshIdentity::class));

    $identity->refresh();

    expect($identity->status)->toBe(IdentityStatus::Verified)
        ->and($identity->last_checked_at)->not->toBeNull();
});
