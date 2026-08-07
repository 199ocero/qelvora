<?php

use App\Enums\IdentityStatus;
use App\Enums\IdentityType;
use App\Enums\MailProvider;
use App\Enums\TeamRole;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Models\Team;

beforeEach(function () {
    fakeMailDriver();
});

function activeConnection(Team $team): ProviderConnection
{
    return ProviderConnection::factory()->for($team)->create([
        'provider' => MailProvider::Ses,
        'is_active' => true,
    ]);
}

test('creating a domain identity stores its DNS records', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    activeConnection($team);

    $this->actingAs($owner)
        ->post(route('mail.domains.store', $team), [
            'type' => IdentityType::Domain->value,
            'identity' => 'Example.com',
        ])
        ->assertRedirect();

    $identity = $team->mailIdentities()->firstOrFail();

    expect($identity->identity)->toBe('example.com')
        ->and($identity->type)->toBe(IdentityType::Domain)
        ->and($identity->status)->toBe(IdentityStatus::Pending)
        ->and($identity->dns_records)->toHaveCount(3);
});

test('adding an identity requires an active connection', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.domains.store', $team), [
            'type' => IdentityType::Domain->value,
            'identity' => 'example.com',
        ])
        ->assertStatus(409);
});

test('an invalid domain is rejected', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    activeConnection($team);

    $this->actingAs($owner)
        ->post(route('mail.domains.store', $team), [
            'type' => IdentityType::Domain->value,
            'identity' => 'not a domain',
        ])
        ->assertInvalid('identity');
});

test('refreshing an identity updates its verification status', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $connection = activeConnection($team);
    $identity = MailIdentity::factory()->for($connection, 'connection')->create([
        'status' => IdentityStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->post(route('mail.domains.refresh', [$team, $identity]))
        ->assertRedirect();

    $identity->refresh();

    expect($identity->status)->toBe(IdentityStatus::Verified)
        ->and($identity->verified_at)->not->toBeNull();
});

test('deleting an identity removes it', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $connection = activeConnection($team);
    $identity = MailIdentity::factory()->for($connection, 'connection')->create();

    $this->actingAs($owner)
        ->delete(route('mail.domains.destroy', [$team, $identity]))
        ->assertRedirect(route('mail.domains.index', $team));

    $this->assertDatabaseMissing('mail_identities', ['id' => $identity->id]);
});

test('identities are scoped to their team', function () {
    [, $teamA] = teamMember(TeamRole::Owner);
    [$ownerB, $teamB] = teamMember(TeamRole::Owner);

    $identityA = MailIdentity::factory()
        ->for(activeConnection($teamA), 'connection')
        ->create();

    $this->actingAs($ownerB)
        ->get(route('mail.domains.show', ['current_team' => $teamB->slug, 'identity' => $identityA->id]))
        ->assertNotFound();
});

test('members cannot manage domains', function () {
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->get(route('mail.domains.index', $team))
        ->assertForbidden();
});
