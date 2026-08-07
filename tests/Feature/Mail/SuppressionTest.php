<?php

use App\Enums\MailProvider;
use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use App\Enums\TeamRole;
use App\Models\ProviderConnection;
use App\Models\Suppression;

test('an address can be suppressed manually and pushed to the provider', function () {
    $fake = fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);
    ProviderConnection::factory()->for($team)->create(['provider' => MailProvider::Ses, 'is_active' => true]);

    $this->actingAs($owner)
        ->post(route('mail.suppressions.store', $team), ['email' => 'Block@Example.com'])
        ->assertRedirect(route('mail.suppressions.index', $team));

    $this->assertDatabaseHas('suppressions', [
        'team_id' => $team->id,
        'email' => 'block@example.com',
        'source' => SuppressionSource::Local->value,
    ]);

    expect($fake->called('addSuppression'))->toBeTrue();
});

test('a suppression can be removed', function () {
    $fake = fakeMailDriver();
    [$owner, $team] = teamMember(TeamRole::Owner);
    ProviderConnection::factory()->for($team)->create(['provider' => MailProvider::Ses, 'is_active' => true]);
    $suppression = Suppression::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('mail.suppressions.destroy', [$team, $suppression]))
        ->assertRedirect(route('mail.suppressions.index', $team));

    $this->assertDatabaseMissing('suppressions', ['id' => $suppression->id]);
    expect($fake->called('removeSuppression'))->toBeTrue();
});

test('the suppression list can be synced from the provider', function () {
    $fake = fakeMailDriver();
    $fake->suppressions = [
        ['email' => 'imported@example.com', 'reason' => SuppressionReason::Complaint],
    ];
    [$owner, $team] = teamMember(TeamRole::Owner);
    ProviderConnection::factory()->for($team)->create(['provider' => MailProvider::Ses, 'is_active' => true]);

    $this->actingAs($owner)
        ->post(route('mail.suppressions.sync', $team))
        ->assertRedirect(route('mail.suppressions.index', $team));

    $this->assertDatabaseHas('suppressions', [
        'team_id' => $team->id,
        'email' => 'imported@example.com',
        'reason' => SuppressionReason::Complaint->value,
        'source' => SuppressionSource::Provider->value,
    ]);
});

test('members cannot manage suppressions', function () {
    fakeMailDriver();
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->get(route('mail.suppressions.index', $team))
        ->assertForbidden();
});

test('suppressions are scoped to their team', function () {
    fakeMailDriver();
    [, $teamA] = teamMember(TeamRole::Owner);
    [$ownerB, $teamB] = teamMember(TeamRole::Owner);
    $suppressionA = Suppression::factory()->for($teamA)->create();

    $this->actingAs($ownerB)
        ->delete(route('mail.suppressions.destroy', ['current_team' => $teamB->slug, 'suppression' => $suppressionA->id]))
        ->assertNotFound();
});
