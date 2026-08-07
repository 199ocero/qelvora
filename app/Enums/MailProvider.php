<?php

namespace App\Enums;

enum MailProvider: string
{
    case Ses = 'ses';
    case Postmark = 'postmark';
    case Resend = 'resend';
    case Mailgun = 'mailgun';

    /**
     * Get the display label for the provider.
     */
    public function label(): string
    {
        return match ($this) {
            self::Ses => 'Amazon SES',
            self::Postmark => 'Postmark',
            self::Resend => 'Resend',
            self::Mailgun => 'Mailgun',
        };
    }

    /**
     * Determine whether a concrete driver exists for this provider.
     *
     * Only implemented providers can be connected; the rest are surfaced in
     * the UI as "coming soon" and rejected by the MailProviderManager.
     */
    public function isImplemented(): bool
    {
        return match ($this) {
            self::Ses => true,
            default => false,
        };
    }

    /**
     * The capabilities this provider supports, so the UI can generically hide
     * pillars a provider does not offer.
     *
     * @return array<int, string>
     */
    public function capabilities(): array
    {
        return match ($this) {
            self::Ses => ['identities', 'events', 'suppression', 'tracking'],
            self::Postmark => ['identities', 'events', 'suppression'],
            self::Resend => ['identities', 'events', 'suppression'],
            self::Mailgun => ['identities', 'events', 'suppression', 'tracking'],
        };
    }

    /**
     * The credential inputs the connection form renders for this provider.
     *
     * @return array<int, array{name: string, label: string, type: string, required: bool, placeholder?: string, options?: array<int, array{value: string, label: string}>, help?: string}>
     */
    public function credentialFields(): array
    {
        return match ($this) {
            self::Ses => [
                ['name' => 'access_key_id', 'label' => 'Access key ID', 'type' => 'text', 'required' => true, 'placeholder' => 'AKIA…'],
                ['name' => 'secret_access_key', 'label' => 'Secret access key', 'type' => 'password', 'required' => true],
                ['name' => 'region', 'label' => 'Region', 'type' => 'select', 'required' => true, 'options' => self::sesRegionOptions()],
            ],
            self::Postmark => [
                ['name' => 'server_token', 'label' => 'Server API token', 'type' => 'password', 'required' => true],
            ],
            self::Resend => [
                ['name' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true, 'placeholder' => 're_…'],
            ],
            self::Mailgun => [
                ['name' => 'api_key', 'label' => 'API key', 'type' => 'password', 'required' => true],
                ['name' => 'domain', 'label' => 'Sending domain', 'type' => 'text', 'required' => true],
                ['name' => 'region', 'label' => 'Region', 'type' => 'select', 'required' => true, 'options' => [
                    ['value' => 'us', 'label' => 'US'],
                    ['value' => 'eu', 'label' => 'EU'],
                ]],
            ],
        };
    }

    /**
     * The AWS regions offered when connecting an SES account.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private static function sesRegionOptions(): array
    {
        return collect([
            'us-east-1' => 'US East (N. Virginia)',
            'us-east-2' => 'US East (Ohio)',
            'us-west-1' => 'US West (N. California)',
            'us-west-2' => 'US West (Oregon)',
            'eu-west-1' => 'Europe (Ireland)',
            'eu-west-2' => 'Europe (London)',
            'eu-central-1' => 'Europe (Frankfurt)',
            'ap-south-1' => 'Asia Pacific (Mumbai)',
            'ap-southeast-1' => 'Asia Pacific (Singapore)',
            'ap-southeast-2' => 'Asia Pacific (Sydney)',
            'ap-northeast-1' => 'Asia Pacific (Tokyo)',
            'ca-central-1' => 'Canada (Central)',
            'sa-east-1' => 'South America (São Paulo)',
        ])->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])
            ->values()
            ->all();
    }

    /**
     * Get all providers described for the frontend picker.
     *
     * @return array<int, array{value: string, label: string, implemented: bool, capabilities: array<int, string>, credentialFields: array<int, mixed>}>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->map(fn (self $provider) => [
                'value' => $provider->value,
                'label' => $provider->label(),
                'implemented' => $provider->isImplemented(),
                'capabilities' => $provider->capabilities(),
                'credentialFields' => $provider->credentialFields(),
            ])
            ->all();
    }
}
