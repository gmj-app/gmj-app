<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RequiredLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $returnUrl = (string) $request->query('return', '');

        if ($this->isSafeInternalPath($returnUrl)) {
            $request->session()->put('url.intended', $returnUrl);
        }

        return redirect()->route('login');
    }

    private function isSafeInternalPath(string $url): bool
    {
        if (! str_starts_with($url, '/')
            || str_starts_with($url, '//')
            || str_contains($url, '\\')
            || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return false;
        }

        $parts = parse_url($url);

        return $parts !== false
            && ! isset($parts['scheme'])
            && ! isset($parts['host'])
            && ! isset($parts['user'])
            && ! isset($parts['pass']);
    }
}
