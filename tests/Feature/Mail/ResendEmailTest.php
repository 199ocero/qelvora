<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Jobs\SendQueuedEmail;
use App\Models\EmailMessage;
use App\Models\Suppression;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    fakeMailDriver();
});

test('a failed message can be resent', function () {
    Queue::fake();
    [$owner, $team, $connection] = sendingTeam(TeamRole::Owner);
    $message = EmailMessage::factory()->for($connection, 'connection')->status(EmailMessageStatus::Failed)->create([
        'from_address' => 'hello@example.com',
        'to' => ['user@customer.test'],
    ]);

    $this->actingAs($owner)
        ->post(route('mail.emails.resend', [$team, $message]))
        ->assertRedirect();

    expect($team->emailMessages()->count())->toBe(2)
        ->and($team->emailMessages()->where('status', EmailMessageStatus::Queued)->count())->toBe(1);

    Queue::assertPushed(SendQueuedEmail::class);
});

test('a sent message cannot be resent', function () {
    [$owner, $team, $connection] = sendingTeam(TeamRole::Owner);
    $message = EmailMessage::factory()->for($connection, 'connection')->status(EmailMessageStatus::Sent)->create([
        'from_address' => 'hello@example.com',
    ]);

    $this->actingAs($owner)
        ->post(route('mail.emails.resend', [$team, $message]))
        ->assertInvalid('status');

    expect($team->emailMessages()->count())->toBe(1);
});

test('resend is blocked when the recipient is now suppressed', function () {
    [$owner, $team, $connection] = sendingTeam(TeamRole::Owner);
    $message = EmailMessage::factory()->for($connection, 'connection')->status(EmailMessageStatus::Failed)->create([
        'from_address' => 'hello@example.com',
        'to' => ['blocked@customer.test'],
    ]);
    Suppression::factory()->for($team)->create(['email' => 'blocked@customer.test']);

    $this->actingAs($owner)
        ->post(route('mail.emails.resend', [$team, $message]))
        ->assertInvalid('to');
});
