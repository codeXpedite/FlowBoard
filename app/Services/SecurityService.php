<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SecurityService
{
    /**
     * Sanitize input to prevent XSS attacks
     */
    public function sanitizeInput(string $input): string
    {
        // Remove script tags and event handlers
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        
        // Remove javascript: protocol
        $input = preg_replace('/javascript:/i', '', $input);
        
        // Remove on* event handlers
        $input = preg_replace('/\son\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        
        // Strip potentially dangerous HTML tags but allow basic formatting
        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';
        $input = strip_tags($input, $allowedTags);
        
        // Encode special characters
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return trim($input);
    }

    /**
     * Sanitize input for database storage
     */
    public function sanitizeForDatabase(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = $this->sanitizeInput($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeForDatabase($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate file upload security
     */
    public function validateFileUpload($file): array
    {
        $errors = [];
        
        if (!$file) {
            $errors[] = 'No file provided';
            return $errors;
        }

        // Check file size (10MB max)
        if ($file->getSize() > 10485760) {
            $errors[] = 'File size exceeds 10MB limit';
        }

        // Check file extension
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt', 'zip'];
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (!in_array($extension, $allowedExtensions)) {
            $errors[] = 'File type not allowed';
        }

        // Check MIME type
        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif',
            'application/pdf', 'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain', 'application/zip'
        ];
        
        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Invalid file type';
        }

        // Check for executable files
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'php', 'js', 'html', 'htm'];
        if (in_array($extension, $dangerousExtensions)) {
            $errors[] = 'Executable files are not allowed';
        }

        return $errors;
    }

    /**
     * Generate secure API token
     */
    public function generateApiToken(): string
    {
        return hash('sha256', Str::random(60) . time());
    }

    /**
     * Validate API token format
     */
    public function validateApiToken(string $token): bool
    {
        return strlen($token) === 64 && ctype_xdigit($token);
    }

    /**
     * Check rate limiting for API endpoints
     */
    public function checkRateLimit(Request $request, string $key, int $maxAttempts = 60, int $decayMinutes = 1): bool
    {
        $rateLimitKey = $this->getRateLimitKey($request, $key);
        
        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxAttempts)) {
            return false;
        }
        
        RateLimiter::hit($rateLimitKey, $decayMinutes * 60);
        return true;
    }

    /**
     * Get remaining rate limit attempts
     */
    public function getRemainingAttempts(Request $request, string $key, int $maxAttempts = 60): int
    {
        $rateLimitKey = $this->getRateLimitKey($request, $key);
        return RateLimiter::remaining($rateLimitKey, $maxAttempts);
    }

    /**
     * Get rate limit key for request
     */
    private function getRateLimitKey(Request $request, string $key): string
    {
        $identifier = $request->user()?->id ?? $request->ip();
        return "{$key}:{$identifier}";
    }

    /**
     * Log security event
     */
    public function logSecurityEvent(string $event, array $data = []): void
    {
        \Log::channel('security')->warning("Security Event: {$event}", array_merge($data, [
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'timestamp' => now(),
        ]));
    }

    /**
     * Validate password strength
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }

    /**
     * Detect suspicious user activity
     */
    public function detectSuspiciousActivity(Request $request): bool
    {
        // Check for SQL injection patterns
        $input = json_encode($request->all());
        $sqlPatterns = [
            '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION)\b)/i',
            '/(\b(OR|AND)\s+\d+\s*=\s*\d+)/i',
            '/(\'|\"|;|--|\*|\|)/i'
        ];
        
        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('Potential SQL Injection', [
                    'pattern' => $pattern,
                    'input' => $input
                ]);
                return true;
            }
        }
        
        // Check for XSS patterns
        $xssPatterns = [
            '/<script/i',
            '/javascript:/i',
            '/on\w+\s*=/i'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                $this->logSecurityEvent('Potential XSS Attack', [
                    'pattern' => $pattern,
                    'input' => $input
                ]);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get security headers for responses
     */
    public function getSecurityHeaders(): array
    {
        return [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self'; media-src 'self'; object-src 'none'; child-src 'self'; frame-ancestors 'none'; form-action 'self'; base-uri 'self';",
        ];
    }
}