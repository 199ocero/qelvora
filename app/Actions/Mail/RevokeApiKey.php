<?php

namespace App\Actions\Mail;

use App\Models\ApiKey;

class RevokeApiKey
{
    /**
     * Revoke an API key so it can no longer authenticate.
     */
    public function handle(ApiKey $apiKey): void
    {
        $apiKey->update(['revoked_at' => now()]);
    }
}
