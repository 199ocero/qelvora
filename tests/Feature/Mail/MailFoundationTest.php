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

test('the unsupported provider exception names the provider', function () {
    $exception = UnsupportedProviderException::for(MailProvider::Ses);

    expect($exception)->toBeInstanceOf(UnsupportedProviderException::class)
        ->and($exception->getMessage())->toContain('Amazon SES');
});

test('a faked driver overrides resolution', function () {
    $fake = fakeMailDriver();

    $one = ProviderConnection::factory()->make(['provider' => MailProvider::Ses]);
    $two = ProviderConnection::factory()->make(['provider' => MailProvider::Ses]);

    expect(app(MailProviderManager::class)->driver($one))->toBe($fake)
        ->and(app(MailProviderManager::class)->driver($two))->toBe($fake);
});

test('SES is the only provider and is implemented', function () {
    expect(MailProvider::cases())->toHaveCount(1)
        ->and(MailProvider::Ses->isImplemented())->toBeTrue();
});

test('provider options expose SES credential fields', function () {
    $options = collect(MailProvider::options());

    expect($options)->toHaveCount(1);

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
