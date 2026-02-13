<?php

namespace Lumite\Support\Requests;

class RequestHeaders
{
    private array $headers = [];

    public function __construct()
    {
        $this->headers = $this->parseHeaders();
    }

    public function get(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->headers;
        }

        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->headers[strtolower($key)]);
    }

    public function all(): array
    {
        return $this->headers;
    }

    // -------------------- Internal Logic -------------------- //

    private function parseHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = strtolower(str_replace('_', '-', substr($name, 5)));
                $headers[$key] = $value;
            }
        }

        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        if (isset($_SERVER['CONTENT_LENGTH'])) {
            $headers['content-length'] = $_SERVER['CONTENT_LENGTH'];
        }

        return $headers;
    }
}
