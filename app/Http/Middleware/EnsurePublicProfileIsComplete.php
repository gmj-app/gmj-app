<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePublicProfileIsComplete
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->hasCompletedPublicProfile()) {
            return $next($request);
        }

        if ($request->routeIs('profile.setup', 'profile.setup.store', 'logout')) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            $request->session()->put('public_profile.intended', $request->getRequestUri());
        }

        return redirect()->route('profile.setup');
    }
}
