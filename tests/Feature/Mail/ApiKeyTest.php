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
