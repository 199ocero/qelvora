<?php

namespace App\Actions\Mail;

use App\Enums\IdentityStatus;
use App\Models\MailIdentity;
use App\Services\Mail\MailProviderManager;

class RefreshIdentity
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Re-fetch the verification status and DNS records for an identity.
     */
    public function handle(MailIdentity $identity): MailIdentity
    {
        $result = $this->manager->driver($identity->connection)->refreshIdentity($identity);

        $verifiedAt = $result->status === IdentityStatus::Verified
            ? ($identity->verified_at ?? now())
            : $identity->verified_at;

        $identity->update([
            'status' => $result->status,
            'dns_records' => $result->dnsRecords === [] ? $identity->dns_records : $result->dnsRecordsToArray(),
            'meta' => array_merge($identity->meta ?? [], $result->meta),
            'verified_at' => $verifiedAt,
            'last_checked_at' => now(),
        ]);

        return $identity;
    }
}
