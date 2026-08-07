<?php

namespace App\Services\Mail\Data;

/**
 * The normalized result of a provider send.
 */
readonly class SendResult
{
    public function __construct(
        public string $providerMessageId,
    ) {
        //
    }
}
