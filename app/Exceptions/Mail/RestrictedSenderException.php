<?php

namespace App\Exceptions\Mail;

use RuntimeException;

class RestrictedSenderException extends RuntimeException
{
    public function __construct(string $from, string $identity)
    {
        parent::__construct("This API key may only send from [{$identity}], not [{$from}].");
    }
}
