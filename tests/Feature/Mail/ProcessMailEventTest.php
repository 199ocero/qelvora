<?php

use App\Enums\EmailEventType;
use App\Enums\EmailMessageStatus;
use App\Enums\MailProvider;
use App\Enums\SuppressionReason;
use App\Enums\SuppressionSource;
use App\Jobs\ProcessMailEvent;
use App\Models\EmailMessage;
use App\Models\ProviderConnection;
use App\Services\Mail\Data\NormalizedEvent;

function processEvent(ProviderConnection $connection, NormalizedEvent $event): void
{
    (new ProcessMailEvent($connection, $event))->handle();
}

function messageFor(ProviderConnection $connection, string $providerMessageId, EmailMessageStatus $status = EmailMessageStatus::Sent): EmailMessage
{
    return EmailMessage::factory()->for($connection, 'connection')->create([
        'provider_message_id' => $providerMessageId,
        'status' => $status,
    ]);
}

test('a delivery event marks the message delivered', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);
    $message = messageFor($connection, 'm-1');

    processEvent($connection, new NormalizedEvent(
        type: EmailEventType::Delivery,
        providerMessageId: 'm-1',
        email: 'a@example.com',
        occurredAt: now(),
    ));

    expect($message->fresh()->status)->toBe(EmailMessageStatus::Delivered);
    $this->assertDatabaseHas('email_events', [
        'email_message_id' => $message->id,
        'type' => EmailEventType::Delivery->value,
    ]);
});

test('a permanent bounce marks bounced and suppresses the recipient', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);
    $message = messageFor($connection, 'm-2', EmailMessageStatus::Delivered);

    processEvent($connection, new NormalizedEvent(
        type: EmailEventType::Bounce,
        providerMessageId: 'm-2',
        email: 'bounce@example.com',
        bounceType: 'Permanent',
    ));

    expect($message->fresh()->status)->toBe(EmailMessageStatus::Bounced);
    $this->assertDatabaseHas('suppressions', [
        'team_id' => $connection->team_id,
        'email' => 'bounce@example.com',
        'reason' => SuppressionReason::Bounce->value,
        'source' => SuppressionSource::Event->value,
    ]);
});

test('a transient bounce does not suppress', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);
    messageFor($connection, 'm-3');

    processEvent($connection, new NormalizedEvent(
        type: EmailEventType::Bounce,
        providerMessageId: 'm-3',
        email: 'soft@example.com',
        bounceType: 'Transient',
    ));

    $this->assertDatabaseMissing('suppressions', ['email' => 'soft@example.com']);
});

test('a complaint suppresses the recipient', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);
    messageFor($connection, 'm-4');

    processEvent($connection, new NormalizedEvent(
        type: EmailEventType::Complaint,
        providerMessageId: 'm-4',
        email: 'spam@example.com',
    ));

    $this->assertDatabaseHas('suppressions', [
        'email' => 'spam@example.com',
        'reason' => SuppressionReason::Complaint->value,
    ]);
});

test('open and click events increment engagement counters', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);
    $message = messageFor($connection, 'm-5', EmailMessageStatus::Delivered);

    processEvent($connection, new NormalizedEvent(type: EmailEventType::Open, providerMessageId: 'm-5'));
    processEvent($connection, new NormalizedEvent(type: EmailEventType::Click, providerMessageId: 'm-5'));

    $message->refresh();

    expect($message->opens_count)->toBe(1)
        ->and($message->clicks_count)->toBe(1)
        // A benign engagement event must not downgrade a delivered status.
        ->and($message->status)->toBe(EmailMessageStatus::Delivered);
});

test('an event for an unknown message is still recorded', function () {
    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);

    processEvent($connection, new NormalizedEvent(
        type: EmailEventType::Delivery,
        providerMessageId: 'unknown-id',
        email: 'a@example.com',
    ));

    $this->assertDatabaseHas('email_events', [
        'email_message_id' => null,
        'team_id' => $connection->team_id,
        'type' => EmailEventType::Delivery->value,
    ]);
});
