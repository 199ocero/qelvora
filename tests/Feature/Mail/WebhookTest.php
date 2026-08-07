<?php

use App\Enums\EmailEventType;
use App\Enums\MailProvider;
use App\Jobs\ProcessMailEvent;
use App\Models\ProviderConnection;
use App\Services\Mail\Data\NormalizedEvent;
use Illuminate\Support\Facades\Queue;

test('an unknown webhook token returns 404', function () {
    fakeMailDriver();

    $this->postJson(route('webhooks.mail', ['token' => 'does-not-exist']))
        ->assertNotFound();
});

test('a webhook with an invalid signature is rejected', function () {
    $fake = fakeMailDriver();
    $fake->signatureValid = false;

    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);

    $this->postJson(route('webhooks.mail', ['token' => $connection->webhook_token]))
        ->assertForbidden();
});

test('a subscription confirmation queues no events', function () {
    fakeMailDriver();
    Queue::fake();

    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);

    $this->postJson(route('webhooks.mail', ['token' => $connection->webhook_token]))
        ->assertNoContent();

    Queue::assertNothingPushed();
});

test('a notification queues a job per parsed event', function () {
    $fake = fakeMailDriver();
    $fake->events = [
        new NormalizedEvent(type: EmailEventType::Delivery, providerMessageId: 'm-1', email: 'a@example.com'),
        new NormalizedEvent(type: EmailEventType::Open, providerMessageId: 'm-1', email: 'a@example.com'),
    ];
    Queue::fake();

    $connection = ProviderConnection::factory()->create(['provider' => MailProvider::Ses]);

    $this->postJson(route('webhooks.mail', ['token' => $connection->webhook_token]))
        ->assertNoContent();

    Queue::assertPushed(ProcessMailEvent::class, 2);
});
