<?php

use App\Enums\MailProvider;
use App\Enums\TeamPermission;
use App\Enums\TeamRole;
use App\Exceptions\UnsupportedProviderException;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\Mail\Drivers\Ses\SesDriver;
use App\Services\Mail\MailProviderManager;

test('the manager resolves SES to the SES driver', function () {
    $connection = ProviderConnection::factory()->make(['provider' => MailProvider::Ses]);

    expect(app(MailProviderManager::class)->driver($connection))->toBeInstanceOf(SesDriver::class);
});

test('the manager throws for a provider without a driver', function () {
    $connection = ProviderConnection::factory()->make(['provider' => MailProvider::Postmark]);

    app(MailProviderManager::class)->driver($connection);
})->throws(UnsupportedProviderException::class);

test('a faked driver overrides resolution for every provider', function () {
    $fake = fakeMailDriver();

    $ses = ProviderConnection::factory()->make(['provider' => MailProvider::Ses]);
    $postmark = ProviderConnection::factory()->make(['provider' => MailProvider::Postmark]);

    expect(app(MailProviderManager::class)->driver($ses))->toBe($fake)
        ->and(app(MailProviderManager::class)->driver($postmark))->toBe($fake);
});

test('only SES is marked implemented', function () {
    expect(MailProvider::Ses->isImplemented())->toBeTrue()
        ->and(MailProvider::Postmark->isImplemented())->toBeFalse()
        ->and(MailProvider::Resend->isImplemented())->toBeFalse()
        ->and(MailProvider::Mailgun->isImplemented())->toBeFalse();
});

test('provider options expose credential fields for the picker', function () {
    $options = collect(MailProvider::options());

    $ses = $options->firstWhere('value', 'ses');

    expect($ses['implemented'])->toBeTrue()
        ->and(collect($ses['credentialFields'])->pluck('name')->all())
        ->toBe(['access_key_id', 'secret_access_key', 'region']);
});

test('role permissions gate the mail platform', function () {
    expect(TeamRole::Owner->hasPermission(TeamPermission::ManageMailProviders))->toBeTrue()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageMailProviders))->toBeFalse()
        ->and(TeamRole::Admin->hasPermission(TeamPermission::ManageMailDomains))->toBeTrue()
        ->and(TeamRole::Member->hasPermission(TeamPermission::ViewEmails))->toBeTrue()
        ->and(TeamRole::Member->hasPermission(TeamPermission::SendEmail))->toBeTrue()
        ->and(TeamRole::Member->hasPermission(TeamPermission::ManageMailDomains))->toBeFalse();
});

test('mail permissions dto reflects the users role', function () {
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => TeamRole::Member->value]);

    $permissions = $user->toMailPermissions($team);

    expect($permissions->canViewEmails)->toBeTrue()
        ->and($permissions->canSendEmail)->toBeTrue()
        ->and($permissions->canManageProviders)->toBeFalse()
        ->and($permissions->canManageDomains)->toBeFalse();
});
