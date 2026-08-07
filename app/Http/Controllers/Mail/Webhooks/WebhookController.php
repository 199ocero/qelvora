<?php

namespace App\Http\Controllers\Mail\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMailEvent;
use App\Models\ProviderConnection;
use App\Services\Mail\MailProviderManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WebhookController extends Controller
{
    public function __construct(
        protected MailProviderManager $manager,
    ) {
        //
    }

    /**
     * Receive a provider webhook, verify it, and queue its events.
     */
    public function handle(Request $request, string $token): Response
    {
        $connection = ProviderConnection::where('webhook_token', $token)->first();

        abort_if($connection === null, 404);

        $driver = $this->manager->driver($connection);

        abort_unless($driver->verifyWebhookSignature($request), 403);

        foreach ($driver->parseWebhook($request) as $event) {
            ProcessMailEvent::dispatch($connection, $event);
        }

        return response()->noContent();
    }
}
