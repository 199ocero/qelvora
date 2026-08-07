<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;

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
