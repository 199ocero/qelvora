<?php

use App\Models\ProviderConnection;
use App\Services\Mail\Drivers\Ses\SesClientFactory;
use App\Services\Mail\Drivers\Ses\SesDriver;
use App\Support\DevWebhookTunnel;
use Illuminate\Support\Facades\URL;

test('resolveBaseUrl prefers an active tunnel over the configured env value', function () {
    config()->set('mail-providers.webhook_base_url', 'https://config.example.com');
    DevWebhookTunnel::store('https://tunnel.example.com/');

    expect(DevWebhookTunnel::resolveBaseUrl())->toBe('https://tunnel.example.com');
});

test('resolveBaseUrl falls back to the configured env value when no tunnel is active', function () {
    config()->set('mail-providers.webhook_base_url', 'https://config.example.com');
    DevWebhookTunnel::clear();

    expect(DevWebhookTunnel::resolveBaseUrl())->toBe('https://config.example.com');
});

test('resolveBaseUrl is null when neither a tunnel nor env value is set', function () {
    config()->set('mail-providers.webhook_base_url', null);
    DevWebhookTunnel::clear();

    expect(DevWebhookTunnel::resolveBaseUrl())->toBeNull();
});

test('the SES webhook endpoint uses the active tunnel URL, then env, then APP_URL', function () {
    $connection = ProviderConnection::factory()->create(['webhook_token' => 'tok123']);
    $driver = new SesDriver($connection, app(SesClientFactory::class));

    $webhookUrl = fn (): string => (function () {
        return $this->webhookUrl();
    })->call($driver);

    URL::forceRootUrl('http://localhost:8000');
    config()->set('mail-providers.webhook_base_url', null);
    DevWebhookTunnel::clear();
    expect($webhookUrl())->toBe(url('webhooks/mail/tok123'));

    config()->set('mail-providers.webhook_base_url', 'https://config.example.com');
    expect($webhookUrl())->toBe('https://config.example.com/webhooks/mail/tok123');

    DevWebhookTunnel::store('https://tunnel.example.com');
    expect($webhookUrl())->toBe('https://tunnel.example.com/webhooks/mail/tok123');
});
