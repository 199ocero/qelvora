<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Jobs\SendQueuedEmail;
use App\Models\EmailMessage;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    fakeMailDriver();
});

test('an email can be scheduled for later', function () {
    Queue::fake();
    [$owner, $team] = sendingTeam(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Later',
            'html' => '<p>Hi</p>',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
        ])
        ->assertRedirect();

    $message = $team->emailMessages()->firstOrFail();

    expect($message->status)->toBe(EmailMessageStatus::Scheduled)
        ->and($message->scheduled_at)->not->toBeNull();

    Queue::assertNotPushed(SendQueuedEmail::class);
});

test('a past schedule time is rejected', function () {
    [$owner, $team] = sendingTeam(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'subject' => 'Nope',
            'html' => '<p>Hi</p>',
            'scheduled_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])
        ->assertInvalid('scheduled_at');
});

test('the command releases only due scheduled emails', function () {
    Queue::fake();
    [, $team, $connection] = sendingTeam(TeamRole::Owner);

    $due = EmailMessage::factory()->for($connection, 'connection')
        ->status(EmailMessageStatus::Scheduled)->create(['scheduled_at' => now()->subMinute()]);
    $future = EmailMessage::factory()->for($connection, 'connection')
        ->status(EmailMessageStatus::Scheduled)->create(['scheduled_at' => now()->addDay()]);

    $this->artisan('mail:send-scheduled')->assertSuccessful();

    expect($due->fresh()->status)->toBe(EmailMessageStatus::Queued)
        ->and($future->fresh()->status)->toBe(EmailMessageStatus::Scheduled);

    Queue::assertPushed(SendQueuedEmail::class, 1);
});

test('a scheduled email can be canceled', function () {
    [$owner, $team, $connection] = sendingTeam(TeamRole::Owner);
    $message = EmailMessage::factory()->for($connection, 'connection')
        ->status(EmailMessageStatus::Scheduled)->create(['scheduled_at' => now()->addDay()]);

    $this->actingAs($owner)
        ->delete(route('mail.emails.cancel', [$team, $message]))
        ->assertRedirect(route('mail.emails.index', $team));

    expect($team->emailMessages()->count())->toBe(0);
});

test('a sent email cannot be canceled', function () {
    [$owner, $team, $connection] = sendingTeam(TeamRole::Owner);
    $message = EmailMessage::factory()->for($connection, 'connection')
        ->status(EmailMessageStatus::Sent)->create();

    $this->actingAs($owner)
        ->delete(route('mail.emails.cancel', [$team, $message]))
        ->assertInvalid('status');

    expect($team->emailMessages()->count())->toBe(1);
});
