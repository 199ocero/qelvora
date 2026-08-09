<?php

use App\Enums\EmailMessageStatus;
use App\Enums\TeamRole;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    fakeMailDriver();
});

test('a template can be created with a generated slug', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.templates.store', $team), [
            'name' => 'Welcome Email',
            'subject' => 'Welcome, {{ name }}',
            'html' => '<p>Hello {{ name }}</p>',
        ])
        ->assertRedirect(route('mail.templates.index', $team));

    $template = $team->emailTemplates()->firstOrFail();

    expect($template->name)->toBe('Welcome Email')
        ->and($template->slug)->toBe('welcome-email')
        ->and($template->variableNames())->toBe(['name']);
});

test('template slugs are unique within a team', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    EmailTemplate::factory()->for($team)->create(['name' => 'Welcome', 'slug' => 'welcome']);

    $this->actingAs($owner)
        ->post(route('mail.templates.store', $team), [
            'name' => 'Welcome',
            'html' => '<p>Hi</p>',
        ])
        ->assertRedirect();

    expect($team->emailTemplates()->pluck('slug')->all())->toBe(['welcome', 'welcome-2']);
});

test('a template requires a body', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);

    $this->actingAs($owner)
        ->post(route('mail.templates.store', $team), ['name' => 'Empty'])
        ->assertInvalid('html');
});

test('a template can be updated and deleted', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $template = EmailTemplate::factory()->for($team)->create();

    $this->actingAs($owner)
        ->put(route('mail.templates.update', [$team, $template]), [
            'name' => 'Renamed',
            'subject' => 'Hi',
            'html' => '<p>Updated</p>',
        ])
        ->assertRedirect();

    expect($template->fresh()->name)->toBe('Renamed');

    $this->actingAs($owner)
        ->delete(route('mail.templates.destroy', [$team, $template]))
        ->assertRedirect();

    expect($team->emailTemplates()->count())->toBe(0);
});

test('members cannot manage templates', function () {
    [$owner, $team] = teamMember(TeamRole::Owner);
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->post(route('mail.templates.store', $team), ['name' => 'Nope', 'html' => '<p>Hi</p>'])
        ->assertForbidden();

    // Members can still view the template list.
    $this->actingAs($member)
        ->get(route('mail.templates.index', $team))
        ->assertOk();
});

test('the model renders variables into subject and body', function () {
    $template = EmailTemplate::factory()->make([
        'subject' => 'Hi {{ name }}',
        'html' => '<p>Order {{ order }} shipped</p>',
        'text' => 'Order {{ order }} shipped',
    ]);

    $rendered = $template->render(['name' => 'Sam', 'order' => '1001']);

    expect($rendered['subject'])->toBe('Hi Sam')
        ->and($rendered['html'])->toBe('<p>Order 1001 shipped</p>')
        ->and($rendered['text'])->toBe('Order 1001 shipped');
});

test('sending with a template renders the message', function () {
    Queue::fake();
    [$owner, $team] = sendingTeam(TeamRole::Owner);
    $template = EmailTemplate::factory()->for($team)->create([
        'subject' => 'Welcome, {{ name }}',
        'html' => '<p>Hello {{ name }}</p>',
        'text' => null,
    ]);

    $this->actingAs($owner)
        ->post(route('mail.emails.store', $team), [
            'from' => 'hello@example.com',
            'to' => 'user@customer.test',
            'template_id' => $template->id,
            'variables' => ['name' => 'Dana'],
        ])
        ->assertRedirect();

    $message = $team->emailMessages()->firstOrFail();

    expect($message->subject)->toBe('Welcome, Dana')
        ->and($message->html)->toBe('<p>Hello Dana</p>')
        ->and($message->email_template_id)->toBe($template->id)
        ->and($message->status)->toBe(EmailMessageStatus::Sent);
});
