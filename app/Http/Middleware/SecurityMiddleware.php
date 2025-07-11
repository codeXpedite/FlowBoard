<?php

namespace App\Http\Middleware;

use App\Services\SecurityService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityMiddleware
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
    public function handle(Request $request, Closure $next): Response
    {
        // Check for suspicious activity
        if ($this->securityService->detectSuspiciousActivity($request)) {
            return response()->json(['error' => 'Suspicious activity detected'], 403);
        }

        // Sanitize request data for form submissions
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('PATCH')) {
            $this->sanitizeRequestData($request);
        }

        $response = $next($request);

        // Add security headers to response
        $securityHeaders = $this->securityService->getSecurityHeaders();
        foreach ($securityHeaders as $header => $value) {
            $response->headers->set($header, $value);
        }

        return $response;
    }

    /**
     * Sanitize incoming request data
     */
    private function sanitizeRequestData(Request $request): void
    {
        $input = $request->all();
        $sanitized = $this->securityService->sanitizeForDatabase($input);
        $request->merge($sanitized);
    }
}
