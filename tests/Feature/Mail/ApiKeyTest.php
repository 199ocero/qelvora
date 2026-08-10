<?php

use App\Enums\TeamRole;
use App\Models\ApiKey;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    fakeMailDriver();
});

test('an owner can create an API key shown once', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $response = $this->actingAs($owner)
        ->post(route('mail.api-keys.store', $team), ['name' => 'Production'])
        ->assertRedirect(route('mail.api-keys.index', $team));

    $response->assertSessionHas('newApiKey');
    $plain = session('newApiKey');

    $apiKey = $team->apiKeys()->firstOrFail();

    expect($apiKey->name)->toBe('Production')
        ->and($plain)->toStartWith('qlv_')
        ->and(Hash::check($plain, $apiKey->key_hash))->toBeTrue();
});

test('an owner can restrict a key to a verified identity', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);
    $identity = $team->mailIdentities()->firstOrFail();

    $this->actingAs($owner)
        ->post(route('mail.api-keys.store', $team), [
            'name' => 'Domain-locked',
            'mail_identity_id' => $identity->id,
        ])
        ->assertRedirect(route('mail.api-keys.index', $team));

    expect($team->apiKeys()->firstOrFail()->mail_identity_id)->toBe($identity->id);
});

test('a key cannot be restricted to another team identity', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);
    [, $otherTeam] = sendingTeam(TeamRole::Owner);
    $foreign = $otherTeam->mailIdentities()->firstOrFail();

    $this->actingAs($owner)
        ->post(route('mail.api-keys.store', $team), [
            'name' => 'Sneaky',
            'mail_identity_id' => $foreign->id,
        ])
        ->assertSessionHasErrors('mail_identity_id');

    expect($team->apiKeys()->count())->toBe(0);
});

test('an API key can be revoked', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $apiKey = ApiKey::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('mail.api-keys.destroy', [$team, $apiKey]))
        ->assertRedirect(route('mail.api-keys.index', $team));

    expect($apiKey->fresh()->isRevoked())->toBeTrue();
});

test('members cannot manage API keys', function () {
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->get(route('mail.api-keys.index', $team))
        ->assertForbidden();
});

test('API keys are scoped to their team', function () {
    [, $teamA] = teamMember(TeamRole::Owner);
    [$ownerB, $teamB] = teamMember(TeamRole::Owner);
    $keyA = ApiKey::factory()->for($teamA)->create();

    $this->actingAs($ownerB)
        ->delete(route('mail.api-keys.destroy', ['current_team' => $teamB->slug, 'apiKey' => $keyA->id]))
        ->assertNotFound();
});
