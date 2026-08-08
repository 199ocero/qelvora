<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\User;

beforeEach(function () {
    fakeMailDriver();
});

test('the overview renders sending stats', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $connection = ProviderConnection::factory()->for($team)->create(['is_active' => true]);

    EmailMessage::factory()->for($connection, 'connection')->count(4)->create(['status' => EmailMessageStatus::Delivered]);
    EmailMessage::factory()->for($connection, 'connection')->create(['status' => EmailMessageStatus::Bounced]);

    $this->actingAs($owner)
        ->get(route('mail.dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/Dashboard')
            ->where('stats.sent', 5)
            ->where('stats.delivered', 4)
            ->where('stats.bounced', 1)
            ->where('stats.deliveryRate', 80)
            ->has('trend', 14)
            ->has('counts'));
});

test('the overview is visible to members', function () {
    [$member, $team] = teamMember(TeamRole::Member);

    $this->actingAs($member)
        ->get(route('mail.dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('mail/Dashboard')->where('connection', null));
});

test('the overview includes pending invitations for the authenticated user', function () {
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create(['name' => 'Laravel Team']);
    $owner = User::factory()->create(['name' => 'Taylor Otwell']);

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $invitation = TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('mail.dashboard', $invitedUser->currentTeam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/Dashboard')
            ->has('pendingInvitations', 1)
            ->where('pendingInvitations.0.code', $invitation->code)
            ->where('pendingInvitations.0.inviterName', 'Taylor Otwell')
            ->where('pendingInvitations.0.team.name', 'Laravel Team')
            ->where('pendingInvitations.0.team.slug', $team->slug));
});

test('the overview excludes accepted, expired and other users invitations', function () {
    $invitedUser = User::factory()->create(['email' => 'invited@example.com']);
    $team = Team::factory()->create();
    $owner = User::factory()->create();

    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    TeamInvitation::factory()->accepted()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    $expired = TeamInvitation::factory()->expired()->create([
        'team_id' => $team->id,
        'email' => 'invited@example.com',
        'invited_by' => $owner->id,
    ]);

    TeamInvitation::factory()->create([
        'team_id' => $team->id,
        'email' => 'someone@example.com',
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($invitedUser)
        ->get(route('mail.dashboard', $invitedUser->currentTeam))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('mail/Dashboard')
            ->has('pendingInvitations', 0));

    $this->assertDatabaseHas('team_invitations', ['id' => $expired->id]);
});
