---
paths:
  - 'app/Services/Mail/**'
---

# Mail

## Local webhook base URL comes from a cache override, not .env
Provider webhook endpoints resolve their public base URL via App\Support\DevWebhookTunnel::resolveBaseUrl(): cache override (key mail.dev_webhook_base_url) > config('mail-providers.webhook_base_url') (MAIL_WEBHOOK_URL) > APP_URL. New provider drivers must build their endpoint from DevWebhookTunnel::resolveBaseUrl(), like SesDriver::webhookUrl().

For local dev, set the override from the "Local webhook tunnel" field on the provider connection screen (POST mail.connection.tunnel → ConnectionController::tunnel, validated by SetWebhookTunnelRequest). Paste the URL of a tunnel you started yourself (expose/ngrok/herd share/cloudflare). The field is gated by config('mail-providers.allow_dev_tunnel'), which defaults to true only when APP_ENV=local (override with MAIL_ALLOW_DEV_TUNNEL) — this keeps production users from redirecting provider webhooks to an arbitrary host. Gate feature-availability in tests by setting that config, NOT by flipping app()->detectEnvironment('local'): the env string also drives the framework's CSRF exemption, so flipping it makes web POSTs return 419.

Do NOT rely on setting MAIL_WEBHOOK_URL in .env at dev time: a running `php artisan dev` server reads env once at boot, so a value written after boot never takes effect. The cache override is read fresh per request (CACHE_STORE=database, shared across processes).
