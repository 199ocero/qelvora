<?php

namespace App\Exceptions\Mail;

use RuntimeException;

class NoActiveProviderException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No active Amazon SES connection for this team.');
    }
}
