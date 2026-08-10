<?php

namespace App\Services\Mail\Data;

/**
 * A single DNS record a team must publish to verify a sending identity,
 * normalized across providers.
 */
readonly class DnsRecord
{
    public function __construct(
        public string $type,
        public string $host,
        public string $value,
        public ?int $priority = null,
        public ?string $purpose = null,
        public ?string $status = null,
    ) {
        //
    }

    /**
     * Return a copy of this record with its DNS-visibility status set.
     */
    public function withStatus(?string $status): self
    {
        return new self(
            type: $this->type,
            host: $this->host,
            value: $this->value,
            priority: $this->priority,
            purpose: $this->purpose,
            status: $status,
        );
    }

    /**
     * @return array{type: string, host: string, value: string, priority: int|null, purpose: string|null, status: string|null}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'host' => $this->host,
            'value' => $this->value,
            'priority' => $this->priority,
            'purpose' => $this->purpose,
            'status' => $this->status,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            type: (string) $data['type'],
            host: (string) $data['host'],
            value: (string) $data['value'],
            priority: isset($data['priority']) ? (int) $data['priority'] : null,
            purpose: isset($data['purpose']) ? (string) $data['purpose'] : null,
            status: isset($data['status']) ? (string) $data['status'] : null,
        );
    }
}
