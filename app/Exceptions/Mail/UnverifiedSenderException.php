<?php

namespace App\Exceptions\Mail;

use RuntimeException;

class UnverifiedSenderException extends RuntimeException
{
    public function __construct(string $from)
    {
        parent::__construct("The sender [{$from}] is not a verified identity for this team.");
    }
}
