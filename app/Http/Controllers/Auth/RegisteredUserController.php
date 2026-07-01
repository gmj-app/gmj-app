<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(): RedirectResponse
    {
        return redirect()->route('login')
            ->with('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
