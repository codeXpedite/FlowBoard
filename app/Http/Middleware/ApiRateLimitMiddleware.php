<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    protected SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $maxAttempts = '60', string $decayMinutes = '1'): Response
    {
        $key = 'api';
        $maxAttempts = (int) $maxAttempts;
        $decayMinutes = (int) $decayMinutes;

        if (!$this->securityService->checkRateLimit($request, $key, $maxAttempts, $decayMinutes)) {
            return response()->json([
                'error' => 'Too many requests',
                'retry_after' => $decayMinutes * 60
            ], 429);
        }

        $response = $next($request);

        // Add rate limit headers
        $remaining = $this->securityService->getRemainingAttempts($request, $key, $maxAttempts);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', $remaining);

        return $response;
    }
}
