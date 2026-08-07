<?php

use App\Http\Controllers\Api\V1\EmailController;
use App\Http\Controllers\Mail\Webhooks\WebhookController;
use App\Http\Middleware\AuthenticateApiKey;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public sending API (authenticated by team API key)
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')
    ->middleware(AuthenticateApiKey::class)
    ->group(function () {
        Route::post('emails', [EmailController::class, 'store'])->name('api.v1.emails.store');
    });

/*
|--------------------------------------------------------------------------
| Provider webhooks (stateless, signature-verified)
|--------------------------------------------------------------------------
*/

Route::post('webhooks/mail/{token}', [WebhookController::class, 'handle'])
    ->name('webhooks.mail');
