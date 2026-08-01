<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyPublicProfileController extends Controller
{
    public function __invoke(Request $request, string $handle): RedirectResponse
    {
        $normalizedHandle = strtolower($handle);

        $canonicalHandle = Creator::query()->where('slug', $normalizedHandle)->value('slug')
            ?? User::query()
                ->where('public_handle', $normalizedHandle)
                ->where('public_profile_enabled', true)
                ->whereNotNull('public_display_name')
                ->value('public_handle');

        abort_if($canonicalHandle === null, 404);

        $url = route('creator.queue', ['creator' => $canonicalHandle]);

        if ($request->getQueryString()) {
            $url .= '?'.$request->getQueryString();
        }

        return redirect()->to($url, 301);
    }
}
