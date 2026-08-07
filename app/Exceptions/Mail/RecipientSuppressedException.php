<?php

namespace App\Exceptions\Mail;

use RuntimeException;

class RecipientSuppressedException extends RuntimeException
{
    /**
     * @param  array<int, string>  $emails
     */
    public function __construct(public readonly array $emails)
    {
        parent::__construct('One or more recipients are on the suppression list: '.implode(', ', $emails));
    }
}
