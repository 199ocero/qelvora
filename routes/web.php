<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\Mail\ApiKeyController;
use App\Http\Controllers\Mail\ConnectionController;
use App\Http\Controllers\Mail\DashboardController as MailDashboardController;
use App\Http\Controllers\Mail\EmailController;
use App\Http\Controllers\Mail\IdentityController;
use App\Http\Controllers\Mail\SuppressionController;
use App\Http\Controllers\Mail\TemplateController;
use App\Http\Controllers\Teams\TeamInvitationController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// Public product documentation. Registered before the team-prefixed group so
// `/docs` is not captured by the `{current_team}` wildcard.
Route::prefix('docs')->name('docs.')->group(function () {
    Route::get('/', [DocsController::class, 'index'])->name('index');
    Route::get('{page}', [DocsController::class, 'show'])->name('show');
});

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        Route::prefix('mail')->name('mail.')->group(function () {
            Route::get('dashboard', MailDashboardController::class)->name('dashboard');

            Route::get('connection', [ConnectionController::class, 'index'])->name('connection.index');
            Route::post('connection', [ConnectionController::class, 'store'])->name('connection.store');
            Route::post('connection/switch', [ConnectionController::class, 'switch'])->name('connection.switch');
            Route::post('connection/sync', [ConnectionController::class, 'sync'])->name('connection.sync');
            Route::post('connection/webhook', [ConnectionController::class, 'webhook'])->name('connection.webhook');
            Route::post('connection/tunnel', [ConnectionController::class, 'tunnel'])->name('connection.tunnel');
            Route::delete('connection', [ConnectionController::class, 'destroy'])->name('connection.destroy');

            Route::get('domains', [IdentityController::class, 'index'])->name('domains.index');
            Route::post('domains', [IdentityController::class, 'store'])->name('domains.store');
            Route::get('domains/{identity}', [IdentityController::class, 'show'])->name('domains.show');
            Route::post('domains/{identity}/refresh', [IdentityController::class, 'refresh'])->name('domains.refresh');
            Route::delete('domains/{identity}', [IdentityController::class, 'destroy'])->name('domains.destroy');

            Route::get('templates', [TemplateController::class, 'index'])->name('templates.index');
            Route::get('templates/create', [TemplateController::class, 'create'])->name('templates.create');
            Route::post('templates', [TemplateController::class, 'store'])->name('templates.store');
            Route::get('templates/{template}/edit', [TemplateController::class, 'edit'])->name('templates.edit');
            Route::put('templates/{template}', [TemplateController::class, 'update'])->name('templates.update');
            Route::delete('templates/{template}', [TemplateController::class, 'destroy'])->name('templates.destroy');

            Route::get('emails', [EmailController::class, 'index'])->name('emails.index');
            Route::get('emails/create', [EmailController::class, 'create'])->name('emails.create');
            Route::post('emails', [EmailController::class, 'store'])->name('emails.store');
            Route::get('emails/{message}', [EmailController::class, 'show'])->name('emails.show');
            Route::post('emails/{message}/resend', [EmailController::class, 'resend'])->name('emails.resend');
            Route::delete('emails/{message}/cancel', [EmailController::class, 'cancel'])->name('emails.cancel');

            Route::get('api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
            Route::post('api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
            Route::delete('api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

            Route::get('suppressions', [SuppressionController::class, 'index'])->name('suppressions.index');
            Route::post('suppressions', [SuppressionController::class, 'store'])->name('suppressions.store');
            Route::post('suppressions/sync', [SuppressionController::class, 'sync'])->name('suppressions.sync');
            Route::delete('suppressions/{suppression}', [SuppressionController::class, 'destroy'])->name('suppressions.destroy');
        });
    });

Route::middleware(['auth'])->group(function () {
    Route::post('invitations/{invitation}/accept', [TeamInvitationController::class, 'accept'])->name('invitations.accept');
    Route::delete('invitations/{invitation}', [TeamInvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__.'/settings.php';
