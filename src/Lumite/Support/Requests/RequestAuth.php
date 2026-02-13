<?php

namespace Lumite\Support\Requests;

use Lumite\Support\Auth;
use Lumite\Support\Requests\RequestHeaders;
use stdClass;

class RequestAuth
{
    private RequestHeaders $headers;

    public function __construct(RequestHeaders $headers)
    {
        $this->headers = $headers;
    }

    public function user(): ?stdClass
    {
        $user = Auth::user();
        return $user === false ? null : $user;
    }

    public function bearer(): ?string
    {
        return self::parseBearer($this->headers->get('authorization'));
    }

    public static function bearerStatic(): ?string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if (!$auth && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $auth = $headers['Authorization'] ?? null;
        }

        return self::parseBearer($auth);
    }

    // -------------------- Internal Logic -------------------- //

    private static function parseBearer(?string $header): ?string
    {
        if (!$header) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
