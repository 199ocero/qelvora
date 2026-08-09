<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Mail\SendEmail;
use App\Exceptions\Mail\NoActiveProviderException;
use App\Exceptions\Mail\RecipientSuppressedException;
use App\Exceptions\Mail\UnverifiedSenderException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Mail\SendMailRequest;
use App\Models\ApiKey;
use App\Models\Team;
use Illuminate\Http\JsonResponse;

class EmailController extends Controller
{
    /**
     * Accept an email for sending via the team's active provider.
     */
    public function store(SendMailRequest $request, SendEmail $sendEmail): JsonResponse
    {
        /** @var Team $team */
        $team = $request->attributes->get('apiTeam');
        /** @var ApiKey $apiKey */
        $apiKey = $request->attributes->get('apiKey');

        try {
            $message = $sendEmail->handle(
                $team,
                $request->outgoingMessage(),
                sentVia: 'api',
                apiKeyId: $apiKey->id,
                queue: true,
                templateId: $request->template()?->id,
                scheduledAt: $request->scheduledAt(),
            );
        } catch (NoActiveProviderException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        } catch (UnverifiedSenderException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (RecipientSuppressedException $e) {
            return response()->json(['message' => $e->getMessage(), 'suppressed' => $e->emails], 422);
        }

        return response()->json([
            'id' => $message->id,
            'status' => $message->status->value,
        ], 202);
    }
}
