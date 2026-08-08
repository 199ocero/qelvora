<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * The dashboard defaults to the email overview.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        return redirect()->route('mail.dashboard', [
            'current_team' => $request->user()->currentTeam,
        ]);
    }
}
