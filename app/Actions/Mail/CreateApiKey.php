<?php

namespace App\Actions\Mail;

use App\Models\ApiKey;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Str;

class CreateApiKey
{
    /**
     * Create an API key and return the model plus the one-time plaintext token.
     *
     * @return array{key: ApiKey, plainText: string}
     */
    public function handle(Team $team, User $user, string $name, ?int $mailIdentityId = null): array
    {
        $plainText = 'qlv_'.Str::random(40);

        $apiKey = $team->apiKeys()->create([
            'mail_identity_id' => $mailIdentityId,
            'name' => $name,
            'key_prefix' => substr($plainText, 0, 12),
            'key_hash' => $plainText,
            'last_four' => substr($plainText, -4),
            'created_by' => $user->id,
        ]);

        return ['key' => $apiKey, 'plainText' => $plainText];
    }
}
