<?php

namespace App\Services\Mail\Data;

/**
 * Normalized sending-account health, used to power the connection panel and
 * analytics dashboard regardless of provider.
 */
readonly class AccountHealth
{
    public function __construct(
        public bool $productionAccess = false,
        public ?float $sendQuotaMax = null,
        public ?float $sentLast24h = null,
        public ?float $maxSendRate = null,
        public ?string $enforcementStatus = null,
        public ?float $bounceRate = null,
        public ?float $complaintRate = null,
    ) {
        //
    }

    /**
     * @return array{production_access: bool, send_quota_max: float|null, sent_last_24h: float|null, max_send_rate: float|null, enforcement_status: string|null, bounce_rate: float|null, complaint_rate: float|null}
     */
    public function toAttributes(): array
    {
        return [
            'production_access' => $this->productionAccess,
            'send_quota_max' => $this->sendQuotaMax,
            'sent_last_24h' => $this->sentLast24h,
            'max_send_rate' => $this->maxSendRate,
            'enforcement_status' => $this->enforcementStatus,
            'bounce_rate' => $this->bounceRate,
            'complaint_rate' => $this->complaintRate,
        ];
    }
}
