<?php

namespace App\Exceptions;

use App\Enums\MailProvider;
use RuntimeException;

class UnsupportedProviderException extends RuntimeException
{
    public static function for(MailProvider $provider): self
    {
        return new self("The [{$provider->label()}] email provider is not yet supported.");
    }
}
