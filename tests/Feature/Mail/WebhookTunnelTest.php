<?php

use App\Enums\TeamRole;
use App\Support\DevWebhookTunnel;

beforeEach(function () {
    // The tunnel field defaults to local-only; enable it for these tests.
    config()->set('mail-providers.allow_dev_tunnel', true);
});

test('the connection screen exposes the tunnel state in local development', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    DevWebhookTunnel::store('https://tunnel.example.com');

    $this->actingAs($owner)
        ->get(route('mail.connection.index', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('webhookTunnel.editable', true)
            ->where('webhookTunnel.url', 'https://tunnel.example.com'));
});

test('an owner can save a webhook tunnel URL', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.connection.tunnel', $team), ['url' => 'https://abc123.sharedwithexpose.com/'])
        ->assertRedirect(route('mail.connection.index', $team));

    expect(DevWebhookTunnel::baseUrl())->toBe('https://abc123.sharedwithexpose.com');
});

test('an owner can clear the webhook tunnel URL', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    DevWebhookTunnel::store('https://abc123.sharedwithexpose.com');

    $this->actingAs($owner)
        ->post(route('mail.connection.tunnel', $team), ['url' => ''])
        ->assertRedirect(route('mail.connection.index', $team));

    expect(DevWebhookTunnel::baseUrl())->toBeNull();
});

test('a non-URL value is rejected', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->from(route('mail.connection.index', $team))
        ->post(route('mail.connection.tunnel', $team), ['url' => 'not-a-url'])
        ->assertInvalid(['url']);

    expect(DevWebhookTunnel::baseUrl())->toBeNull();
});

test('members cannot set the webhook tunnel URL', function () {
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->post(route('mail.connection.tunnel', $team), ['url' => 'https://abc123.sharedwithexpose.com'])
        ->assertForbidden();

    expect(DevWebhookTunnel::baseUrl())->toBeNull();
});

test('the tunnel URL cannot be set when the dev tunnel is disabled', function () {
    config()->set('mail-providers.allow_dev_tunnel', false);
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.connection.tunnel', $team), ['url' => 'https://abc123.sharedwithexpose.com'])
        ->assertForbidden();

    expect(DevWebhookTunnel::baseUrl())->toBeNull();
});
