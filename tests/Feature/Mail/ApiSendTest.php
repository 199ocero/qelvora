<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Jobs\SendQueuedEmail;
use App\Models\ApiKey;
use App\Models\Suppression;
use App\Models\Team;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

beforeEach(function () {
    fakeMailDriver();
});

/**
 * @return array{0: Team, 1: string}
 */
function apiKeyFor(Team $team): array
{
    $plain = 'qlv_'.Str::random(40);

    ApiKey::factory()->for($team)->create([
        'key_prefix' => substr($plain, 0, 12),
        'key_hash' => $plain,
        'last_four' => substr($plain, -4),
    ]);

    return [$team, $plain];
}

test('the API sends an email with a valid key', function () {
    Queue::fake();
    [, $team] = sendingTeam(TeamRole::Owner);
    [, $token] = apiKeyFor($team);

    $this->withToken($token)
        ->postJson(route('api.v1.emails.store'), [
            'from' => 'hello@example.com',
            'to' => ['user@customer.test'],
            'subject' => 'API send',
            'html' => '<p>Hello</p>',
        ])
        ->assertStatus(202)
        ->assertJson(['status' => 'queued']);

    $message = $team->emailMessages()->firstOrFail();

    expect($message->status)->toBe(EmailMessageStatus::Queued)
        ->and($message->sent_via)->toBe('api')
        ->and($message->api_key_id)->not->toBeNull();

    Queue::assertPushed(SendQueuedEmail::class);
});

test('the API rejects a missing key', function () {
    $this->postJson(route('api.v1.emails.store'), [])
        ->assertStatus(401);
});

test('the API rejects an invalid key', function () {
    $this->withToken('qlv_invalidtoken')
        ->postJson(route('api.v1.emails.store'), [])
        ->assertStatus(401);
});

test('the API rejects a revoked key', function () {
    [, $team] = sendingTeam(TeamRole::Owner);
    $plain = 'qlv_'.Str::random(40);
    ApiKey::factory()->for($team)->revoked()->create([
        'key_prefix' => substr($plain, 0, 12),
        'key_hash' => $plain,
        'last_four' => substr($plain, -4),
    ]);

    $this->withToken($plain)
        ->postJson(route('api.v1.emails.store'), [
            'from' => 'hello@example.com',
            'to' => ['user@customer.test'],
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertStatus(401);
});

test('the API blocks a suppressed recipient', function () {
    [, $team] = sendingTeam(TeamRole::Owner);
    [, $token] = apiKeyFor($team);
    Suppression::factory()->for($team)->create(['email' => 'blocked@customer.test']);

    $this->withToken($token)
        ->postJson(route('api.v1.emails.store'), [
            'from' => 'hello@example.com',
            'to' => ['blocked@customer.test'],
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertStatus(422)
        ->assertJsonPath('suppressed.0', 'blocked@customer.test');
});

test('the API rejects an unverified sender', function () {
    [, $team] = sendingTeam(TeamRole::Owner);
    [, $token] = apiKeyFor($team);

    $this->withToken($token)
        ->postJson(route('api.v1.emails.store'), [
            'from' => 'spoof@evil.test',
            'to' => ['user@customer.test'],
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertStatus(422);
});
