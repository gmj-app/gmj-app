<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(): RedirectResponse
    {
        return redirect()->route('dashboard')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false))
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
