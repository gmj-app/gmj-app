<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogSlowRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.slow_request_logging.enabled')) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $queryCount = 0;
        $queryDurationMs = 0.0;

        DB::listen(function ($query) use (&$queryCount, &$queryDurationMs): void {
            $queryCount++;
            $queryDurationMs += $query->time;
        });

        $response = $next($request);
        $durationMs = (hrtime(true) - $startedAt) / 1_000_000;

        if ($durationMs >= config('performance.slow_request_logging.threshold_ms')) {
            Log::warning('Slow application request', [
                'route' => $request->route()?->getName(),
                'method' => $request->method(),
                'path' => $request->path(),
                'status' => $response->getStatusCode(),
                'duration_ms' => round($durationMs, 2),
                'query_count' => $queryCount,
                'query_duration_ms' => round($queryDurationMs, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1048576, 2),
                'user_id' => $request->user()?->getAuthIdentifier(),
            ]);
        }

        return $response;
    }
}
