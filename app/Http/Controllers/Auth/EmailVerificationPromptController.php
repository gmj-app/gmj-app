<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationPromptController extends Controller
{
    /**
     * Display the email verification prompt.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
