<?php

namespace App\Actions\Mail;

use App\Enums\IdentityType;
use App\Models\MailIdentity;
use App\Models\ProviderConnection;
use App\Services\Mail\MailProviderManager;

class CreateIdentity
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Create a sending identity at the provider and persist its DNS records.
     */
    public function handle(ProviderConnection $connection, string $identity, IdentityType $type): MailIdentity
    {
        $result = $this->manager->driver($connection)->createIdentity($identity, $type);

        return $connection->identities()->updateOrCreate(
            ['identity' => $identity],
            [
                'team_id' => $connection->team_id,
                'type' => $type,
                'status' => $result->status,
                'dns_records' => $result->dnsRecordsToArray(),
                'meta' => $result->meta,
                'last_checked_at' => now(),
            ],
        );
    }
}
