<?php

namespace App\Exceptions\Mail;

use RuntimeException;

class NoActiveProviderException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No connected email provider is active for this team.');
    }
}
