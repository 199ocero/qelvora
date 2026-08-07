<?php

namespace App\Services\Mail\Data;

/**
 * A provider-agnostic outbound email, built once and handed to whichever driver
 * is active for the sending team.
 */
readonly class OutgoingMessage
{
    /**
     * @param  array<int, string>  $to
     * @param  array<string, string>  $tags
     */
    public function __construct(
        public string $from,
        public array $to,
        public string $subject,
        public ?string $html = null,
        public ?string $text = null,
        public ?string $replyTo = null,
        public array $tags = [],
    ) {
        //
    }
}
