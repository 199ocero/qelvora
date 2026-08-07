<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Webhook route
    |--------------------------------------------------------------------------
    |
    | Inbound provider events are delivered to /webhooks/mail/{token}. The token
    | is per-connection and resolves the owning ProviderConnection.
    |
    | The provider (e.g. Amazon SNS) must be able to reach this URL from the
    | public internet. In local development, start a tunnel (Expose, ngrok,
    | `herd share`, Cloudflare Tunnel, …) and paste its URL into the "Local
    | webhook tunnel" field on the provider connection screen — it overrides
    | this value at runtime. Alternatively set MAIL_WEBHOOK_URL here. When both
    | are empty, the application URL (APP_URL) is used.
    |
    */

    'webhook_path' => 'webhooks/mail',

    'webhook_base_url' => env('MAIL_WEBHOOK_URL'),

    /*
    |--------------------------------------------------------------------------
    | Local webhook tunnel
    |--------------------------------------------------------------------------
    |
    | When enabled, the provider connection screen shows a field for pasting a
    | tunnel URL that overrides the webhook base URL at runtime (see
    | App\Support\DevWebhookTunnel). Defaults to on in local development only, so
    | production users can never redirect provider webhooks to an arbitrary host.
    |
    */

    'allow_dev_tunnel' => filter_var(
        env('MAIL_ALLOW_DEV_TUNNEL', env('APP_ENV') === 'local'),
        FILTER_VALIDATE_BOOL
    ),

    /*
    |--------------------------------------------------------------------------
    | Amazon SES
    |--------------------------------------------------------------------------
    */

    'ses' => [
        'default_region' => env('AWS_DEFAULT_REGION', 'us-east-1'),

        // Naming templates for the per-team configuration set and SNS topic.
        'configuration_set_template' => 'qelvora-{slug}',
        'topic_template' => 'qelvora-ses-{slug}',

        // The SES event types routed to our webhook via the SNS event destination.
        'event_types' => [
            'SEND',
            'DELIVERY',
            'BOUNCE',
            'COMPLAINT',
            'REJECT',
            'OPEN',
            'CLICK',
            'RENDERING_FAILURE',
            'DELIVERY_DELAY',
        ],
    ],

];
