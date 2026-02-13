<?php

namespace Lumite\Support\Requests;

class RequestInfo
{
    /**
     * Get client IP address
     */
    public static function ip(): ?string
    {
        $keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if ($key === 'HTTP_X_FORWARDED_FOR') {
                    $ipList = explode(',', $ip);
                    return trim($ipList[0]);
                }
                return $ip;
            }
        }

        return null;
    }

    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Check if request method matches
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper(self::method()) === strtoupper($method);
    }

    /**
     * Get full URL
     */
    public static function url(): string
    {
        $protocol = self::isSecure() ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        return $protocol . '://' . $host . $uri;
    }

    /**
     * Get request path
     */
    public static function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }

    /**
     * Check if connection is secure (HTTPS)
     */
    public static function secure(?RequestHeaders $headers = null): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        if ($headers && ($headers->get('x-forwarded-proto') ?? '') === 'https') {
            return true;
        }

        return false;
    }

    /**
     * Alias for secure()
     */
    public static function isSecure(): bool
    {
        return self::secure();
    }

    /**
     * Check if request expects JSON response
     */
    public static function expectsJson(?RequestHeaders $headers = null): bool
    {
        if (!$headers) {
            $headers = new RequestHeaders();
        }

        $accept = $headers->get('accept');
        return str_contains($accept ?? '', 'application/json') || self::ajax($headers);
    }

    /**
     * Check if request is AJAX
     */
    public static function ajax(?RequestHeaders $headers = null): bool
    {
        if (!$headers) {
            $headers = new RequestHeaders();
        }

        return strtolower($headers->get('x-requested-with') ?? '') === 'xmlhttprequest';
    }

    /**
     * Get query string or specific key
     */
    public static function queryString(?string $key = null): string|array|null
    {
        if ($key) {
            return $_GET[$key] ?? null;
        }

        return $_SERVER['QUERY_STRING'] ?? '';
    }

    /**
     * Get request scheme (http/https)
     */
    public static function scheme(): string
    {
        return self::isSecure() ? 'https' : 'http';
    }

    /**
     * Get host
     */
    public static function host(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    /**
     * Get port
     */
    public static function port(): int
    {
        return (int) ($_SERVER['SERVER_PORT'] ?? 80);
    }

    /**
     * Get user agent
     */
    public static function userAgent(): ?string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Get referer
     */
    public static function referer(): ?string
    {
        return $_SERVER['HTTP_REFERER'] ?? null;
    }
}
