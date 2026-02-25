<?php
class SecurityHeadersMiddleware {
    public static function handle(): void {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: no-referrer');
        $r2 = $_ENV['R2_ENDPOINT'] ?? '';
        $imgSrc = "'self' data:";
        if ($r2) {
            $imgSrc .= ' ' . $r2;
        }
        $csp = "default-src 'self'; img-src {$imgSrc}; frame-ancestors 'none'";
        header("Content-Security-Policy: {$csp}");
    }
}
