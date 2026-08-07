<?php

namespace App\Services\Mail\Drivers\Ses;

use App\Models\ProviderConnection;
use Aws\SesV2\SesV2Client;
use Aws\Sns\SnsClient;

/**
 * Builds SES v2 and SNS clients from a connection's decrypted credentials.
 * SES SDK construction lives here so it never leaks past the SES driver.
 */
class SesClientFactory
{
    public function sesV2(ProviderConnection $connection): SesV2Client
    {
        return new SesV2Client($this->config($connection));
    }

    public function sns(ProviderConnection $connection): SnsClient
    {
        return new SnsClient($this->config($connection));
    }

    /**
     * @return array<string, mixed>
     */
    protected function config(ProviderConnection $connection): array
    {
        return [
            'version' => 'latest',
            'region' => $connection->credential('region') ?? config('mail-providers.ses.default_region'),
            'credentials' => [
                'key' => (string) $connection->credential('access_key_id'),
                'secret' => (string) $connection->credential('secret_access_key'),
            ],
        ];
    }
}
