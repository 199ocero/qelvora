<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

/**
 * Local-development override for the public base URL that provider webhooks are
 * delivered to. The `mail:tunnel` command stores the live tunnel URL here so it
 * is read fresh on every request — unlike the MAIL_WEBHOOK_URL env value, which
 * a running server only reads once at boot.
 */
class DevWebhookTunnel
{
    public const CACHE_KEY = 'mail.dev_webhook_base_url';

    /**
     * The tunnel base URL captured by `mail:tunnel`, if one is active.
     */
    public static function baseUrl(): ?string
    {
        return Cache::get(self::CACHE_KEY);
    }

    /**
     * The base URL webhooks should use: an active tunnel wins, then the
     * configured MAIL_WEBHOOK_URL. Null means fall back to APP_URL.
     */
    public static function resolveBaseUrl(): ?string
    {
        return self::baseUrl() ?: config('mail-providers.webhook_base_url');
    }

    public static function store(string $url): void
    {
        Cache::forever(self::CACHE_KEY, rtrim($url, '/'));
    }

    public static function clear(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
