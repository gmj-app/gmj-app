<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ThemePreferenceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'theme' => ['required', 'in:light,dark'],
        ]);

        $request->user()->update(['theme_preference' => $validated['theme']]);

        return response()->json(['theme' => $validated['theme']]);
    }
}
