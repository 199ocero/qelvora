<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Models\Suppression;
use App\Models\User;

beforeEach(function () {
    fakeMailDriver();
});

test('an email can be sent from the UI', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Welcome',
            'html' => '<p>Hi there</p>',
        ])
        ->assertRedirect();

    $message = $team->emailMessages()->firstOrFail();

    expect($message->status)->toBe(EmailMessageStatus::Sent)
        ->and($message->sent_via)->toBe('ui')
        ->and($message->provider_message_id)->not->toBeNull()
        ->and($message->to)->toBe(['user@customer.test']);
});

test('sending is blocked for an unverified sender', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@not-verified.test',
            'to' => 'user@customer.test',
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertInvalid('from');

    expect($team->emailMessages()->count())->toBe(0);
});

test('sending is blocked to a suppressed recipient', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);
    Suppression::factory()->for($team)->create(['email' => 'blocked@customer.test']);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'blocked@customer.test',
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertInvalid('to');
});

test('sending requires an active provider', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertInvalid('from');
});

test('a body is required', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Hi',
        ])
        ->assertInvalid('html');
});

test('members can send email', function () {
    // Owner sets up the provider + domain, a member sends.
    [$owner, $team] = sendingTeam(TeamRole::Owner);
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Hi',
            'html' => '<p>Hi</p>',
        ])
        ->assertRedirect();

    expect($team->emailMessages()->count())->toBe(1);
});
