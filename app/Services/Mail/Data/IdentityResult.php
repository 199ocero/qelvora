<?php

namespace App\Services\Mail\Data;

use App\Enums\IdentityStatus;

/**
 * The normalized result of creating or refreshing a sending identity.
 */
readonly class IdentityResult
{
    /**
     * @param  array<int, DnsRecord>  $dnsRecords
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public IdentityStatus $status,
        public array $dnsRecords = [],
        public array $meta = [],
    ) {
        //
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dnsRecordsToArray(): array
    {
        return array_map(fn (DnsRecord $record) => $record->toArray(), $this->dnsRecords);
    }
}
