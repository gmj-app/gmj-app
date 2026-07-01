<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
