<?php

namespace App\Actions\Mail;

use App\Enums\IdentityStatus;
use App\Models\MailIdentity;
use App\Services\Mail\Data\DnsRecord;
use App\Services\Mail\Dns\DnsRecordChecker;
use App\Services\Mail\MailProviderManager;

class RefreshIdentity
{
    public function __construct(
        protected MailProviderManager $manager,
        protected DnsRecordChecker $dnsChecker,
    ) {
        //
    }

    /**
     * Re-fetch the verification status and DNS records for an identity, and
     * annotate each record with whether it is already visible in public DNS.
     */
    public function handle(MailIdentity $identity): MailIdentity
    {
        $result = $this->manager->driver($identity->connection)->refreshIdentity($identity);

        $verifiedAt = $result->status === IdentityStatus::Verified
            ? ($identity->verified_at ?? now())
            : $identity->verified_at;

        $records = $result->dnsRecords !== []
            ? $result->dnsRecords
            : array_map(
                fn (array $record): DnsRecord => DnsRecord::fromArray($record),
                $identity->dns_records ?? [],
            );

        $annotated = $this->dnsChecker->annotate($records);

        $identity->update([
            'status' => $result->status,
            'dns_records' => array_map(fn (DnsRecord $record): array => $record->toArray(), $annotated),
            'meta' => array_merge($identity->meta ?? [], $result->meta),
            'verified_at' => $verifiedAt,
            'last_checked_at' => now(),
        ]);

        return $identity;
    }
}
