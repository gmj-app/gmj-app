<?php

namespace App\Http\Middleware;

use App\Services\DailyJourney\AccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanAccessDailyJourney
{
    public function __construct(private readonly AccessService $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($this->access->allows($request->user()), 404);

        return $next($request);
    }
}
