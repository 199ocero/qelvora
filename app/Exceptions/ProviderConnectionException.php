<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ProviderConnectionException extends RuntimeException
{
    public static function failed(Throwable $previous): self
    {
        return new self(
            'Could not connect to the provider. Check your credentials and try again.',
            previous: $previous,
        );
    }
}
