<?php

use App\Enums\MailProvider;
use App\Enums\ProviderConnectionStatus;
use App\Enums\TeamRole;
use App\Models\ProviderConnection;
use App\Services\Mail\Data\AccountHealth;
use App\Services\Mail\Testing\FakeMailProviderDriver;
use Illuminate\Support\Facades\DB;

test('an owner can view the connection screen', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->get(route('mail.connection.index', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/Connection')
            ->has('providers', 4)
            ->has('connections'));
});

test('members cannot manage providers', function () {
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->get(route('mail.connection.index', $team))
        ->assertForbidden();
});

test('connecting a provider stores encrypted credentials and provisions webhooks', function () {
    $fake = fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.connection.store', $team), [
            'provider' => MailProvider::Ses->value,
            'credentials' => [
                'access_key_id' => 'AKIAEXAMPLE0001',
                'secret_access_key' => 'secret-value-123',
                'region' => 'us-east-1',
            ],
        ])
        ->assertRedirect(route('mail.connection.index', $team));

    $connection = $team->activeConnection();

    expect($connection)->not->toBeNull()
        ->and($connection->status)->toBe(ProviderConnectionStatus::Connected)
        ->and($connection->is_active)->toBeTrue()
        ->and($connection->credential('access_key_id'))->toBe('AKIAEXAMPLE0001')
        ->and($fake->called('verifyCredentials'))->toBeTrue()
        ->and($fake->called('provisionWebhooks'))->toBeTrue();

    // Credentials are encrypted at rest, not stored in plaintext.
    $raw = DB::table('provider_connections')->value('credentials');
    expect($raw)->not->toContain('AKIAEXAMPLE0001');
});

test('invalid credentials fail the connection without activating it', function () {
    $fake = fakeMailDriver();
    $fake->failVerification = true;
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.connection.store', $team), [
            'provider' => MailProvider::Ses->value,
            'credentials' => [
                'access_key_id' => 'AKIABAD',
                'secret_access_key' => 'nope',
                'region' => 'us-east-1',
            ],
        ])
        ->assertInvalid('credentials.access_key_id');

    $connection = $team->connections()->firstOrFail();

    expect($connection->status)->toBe(ProviderConnectionStatus::Failed)
        ->and($connection->is_active)->toBeFalse();
});

test('an unimplemented provider is rejected', function () {
    fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.connection.store', $team), [
            'provider' => MailProvider::Postmark->value,
            'credentials' => ['server_token' => 'abc'],
        ])
        ->assertInvalid('provider');

    expect($team->connections()->count())->toBe(0);
});

test('switching activates another saved connection and preserves credentials', function () {
    fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);

    $ses = ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
    ]);
    $postmark = ProviderConnection::factory()->for($team)->inactive()->create([
        'provider' => MailProvider::Postmark,
        'credentials' => ['server_token' => 'keep-me'],
    ]);

    $this->actingAs($owner)
        ->post(route('mail.connection.switch', $team), ['provider' => MailProvider::Postmark->value])
        ->assertRedirect(route('mail.connection.index', $team));

    expect($ses->fresh()->is_active)->toBeFalse()
        ->and($postmark->fresh()->is_active)->toBeTrue()
        ->and($postmark->fresh()->credential('server_token'))->toBe('keep-me');
});

test('syncing refreshes cached account health', function () {
    fakeMailDriver(new FakeMailProviderDriver(new AccountHealth(
        productionAccess: true,
        sendQuotaMax: 200000,
        sentLast24h: 999,
        maxSendRate: 50,
    )));
    [$owner, $team] = teamMember(TeamRole::Owner);

    ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
        'sent_last_24h' => 10,
    ]);

    $this->actingAs($owner)
        ->post(route('mail.connection.sync', $team), ['provider' => MailProvider::Ses->value])
        ->assertRedirect(route('mail.connection.index', $team));

    expect($team->activeConnection()->sent_last_24h)->toBe(999.0);
});

test('an owner can retry event webhook provisioning', function () {
    $fake = fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);
    ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
        'settings' => ['webhook_provisioned' => false, 'provision_error' => 'Unreachable Endpoint'],
    ]);

    $this->actingAs($owner)
        ->post(route('mail.connection.webhook', $team), ['provider' => MailProvider::Ses->value])
        ->assertRedirect(route('mail.connection.index', $team));

    expect($fake->called('provisionWebhooks'))->toBeTrue();
});

test('a failed webhook provisioning stores the error', function () {
    $fake = fakeMailDriver();
    $fake->failProvision = true;
    [$owner, $team] = teamMember(TeamRole::Owner);
    ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
    ]);

    $this->actingAs($owner)
        ->post(route('mail.connection.webhook', $team), ['provider' => MailProvider::Ses->value])
        ->assertRedirect();

    expect($team->activeConnection()->setting('provision_error'))->toBe('Unreachable Endpoint');
});

test('disconnecting removes the connection', function () {
    fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);

    ProviderConnection::factory()->for($team)->create(['provider' => MailProvider::Ses]);

    $this->actingAs($owner)
        ->delete(route('mail.connection.destroy', $team), ['provider' => MailProvider::Ses->value])
        ->assertRedirect(route('mail.connection.index', $team));

    expect($team->connections()->count())->toBe(0);
});
