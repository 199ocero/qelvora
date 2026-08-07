<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Authenticate a request using a team API key bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        abort_if($token === null || $token === '', 401, 'Missing API key.');

        $prefix = substr($token, 0, 12);

        $apiKey = ApiKey::active()
            ->where('key_prefix', $prefix)
            ->get()
            ->first(fn (ApiKey $key) => Hash::check($token, $key->key_hash));

        abort_if($apiKey === null, 401, 'Invalid API key.');

        $apiKey->forceFill(['last_used_at' => now()])->saveQuietly();

        $request->attributes->set('apiKey', $apiKey);
        $request->attributes->set('apiTeam', $apiKey->team);

        return $next($request);
    }
}
