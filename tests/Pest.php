<?php

use App\Enums\IdentityType;
use App\Enums\MailProvider;
use App\Enums\TeamRole;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Models\User;
use App\Services\Mail\MailProviderManager;
use App\Services\Mail\Testing\FakeMailProviderDriver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Create a team with a member of the given role, switch the user onto it, and
 * return both. Used throughout the mail feature suite.
 *
 * @return array{0: User, 1: Team}
 */
function teamMember(TeamRole $role = TeamRole::Owner): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);
    $user->switchTeam($team);

    return [$user, $team];
}

/**
 * Install a fake mail provider driver and return it for assertions.
 */
function fakeMailDriver(?FakeMailProviderDriver $driver = null): FakeMailProviderDriver
{
    $driver ??= new FakeMailProviderDriver;

    app(MailProviderManager::class)->fake($driver);

    return $driver;
}

/**
 * Build a team ready to send: an active connection and a verified example.com
 *
 * domain, so `*@example.com` is an allowed sender.
 *
 * @return array{0: User, 1: Team, 2: ProviderConnection}
 */
function sendingTeam(TeamRole $role = TeamRole::Owner): array
{
    [$user, $team] = teamMember($role);

    $connection = ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
    ]);

    MailIdentity::factory()->for($connection, 'connection')->verified()->create([
        'identity' => 'example.com',
        'type' => IdentityType::Domain,
    ]);

    return [$user, $team, $connection];
}
