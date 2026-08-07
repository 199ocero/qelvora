<?php

use App\Enums\EmailEventType;
use App\Enums\TeamRole;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;

beforeEach(function () {
    fakeMailDriver();
});

test('the message log lists the team messages', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $connection = ProviderConnection::factory()->for($team)->create();
    EmailMessage::factory()->for($connection, 'connection')->count(3)->create();

    $this->actingAs($owner)
        ->get(route('mail.emails.index', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/emails/Index')
            ->has('messages.data', 3));
});

test('a message shows its event timeline', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $connection = ProviderConnection::factory()->for($team)->create();
    $message = EmailMessage::factory()->for($connection, 'connection')->create();
    EmailEvent::factory()->for($message, 'message')->type(EmailEventType::Delivery)->create();

    $this->actingAs($owner)
        ->get(route('mail.emails.show', [$team, $message]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/emails/Show')
            ->where('message.id', $message->id)
            ->has('events', 1));
});

test('messages are scoped to their team', function () {
    [, $teamA] = teamMember(TeamRole::Owner);
    [$ownerB, $teamB] = teamMember(TeamRole::Owner);
    $connectionA = ProviderConnection::factory()->for($teamA)->create();
    $messageA = EmailMessage::factory()->for($connectionA, 'connection')->create();

    $this->actingAs($ownerB)
        ->get(route('mail.emails.show', ['current_team' => $teamB->slug, 'message' => $messageA->id]))
        ->assertNotFound();
});
