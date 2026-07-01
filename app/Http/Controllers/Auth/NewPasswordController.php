<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): RedirectResponse
    {
        return redirect()->route('login')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    public function store(Request $request): RedirectResponse
    {
        return redirect()->route('login')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
