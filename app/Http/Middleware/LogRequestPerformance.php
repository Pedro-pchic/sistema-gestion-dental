<?php

namespace App\Http\Middleware;

use App\Support\RequestPerformance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogRequestPerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.enabled') || ! app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $metrics = new RequestPerformance;
        $request->attributes->set('performance_metrics', $metrics);
        $response = null;
        $exceptionType = null;

        try {
            return $response = $next($request);
        } catch (Throwable $exception) {
            $exceptionType = class_basename($exception);

            throw $exception;
        } finally {
            Log::info('performance.http', [
                'route' => $request->route()?->getName() ?? 'unnamed',
                'method' => $request->method(),
                'status' => $response?->getStatusCode() ?? 500,
                'duration_ms' => round((hrtime(true) - $startedAt) / 1_000_000, 2),
                'query_count' => $metrics->queryCount,
                'query_ms' => round($metrics->queryTimeMs, 2),
                'slow_query_count' => $metrics->slowQueryCount,
                'slowest_query_ms' => round($metrics->slowestQueryMs, 2),
                'exception_type' => $exceptionType,
            ]);
        }
    }
}
