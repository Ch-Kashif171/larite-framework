<?php

namespace Lumite\Support\Requests;

use Exception;

class RequestInput
{
    private array $fields = [];

    public function __construct()
    {
        $this->fields = $this->collectRequestFields();
    }

    public function get(string $key): mixed
    {
        return $this->fields[$key] ?? null;
    }

    public function getQuery(string $key): mixed
    {
        $this->ensureMethod('GET');
        return $_GET[$key] ?? null;
    }

    public function getPost(string $key): mixed
    {
        $this->ensureMethod('POST');
        return $_POST[$key] ?? null;
    }

    public function all(): array
    {
        return $this->fields;
    }

    public function has(string $key): bool
    {
        return isset($this->fields[$key]);
    }

    public function filled(string $key): bool
    {
        if (!$this->has($key)) {
            return false;
        }

        $value = $this->get($key);
        return !is_null($value) && $value !== '';
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        if (is_null($value)) {
            return $default;
        }

        $trueValues = ['1', 1, true, 'true', 'on', 'yes'];
        $falseValues = ['0', 0, false, 'false', 'off', 'no'];

        if (in_array($value, $trueValues, true)) {
            return true;
        }

        if (in_array($value, $falseValues, true)) {
            return false;
        }

        return $default;
    }

    public function only(...$keys): array
    {
        return array_intersect_key($this->fields, array_flip($keys));
    }

    public function except(...$keys): array
    {
        return array_diff_key($this->fields, array_flip($keys));
    }

    // -------------------- Internal Logic -------------------- //

    private function collectRequestFields(): array
    {
        $data = $this->parseInputByType();
        $files = $this->mapFileNames();
        return array_merge($data, $files);
    }

    private function parseInputByType(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if ($this->isJson($contentType)) {
            return $this->parseJsonPayload();
        }

        return match ($method) {
            'GET' => $this->sanitize($_GET),
            'POST' => $this->sanitize($_POST),
            'PUT', 'PATCH' => $this->parseRawUrlEncoded(),
            default => [],
        };
    }

    private function parseJsonPayload(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new Exception("Invalid JSON payload: " . json_last_error_msg());
        }

        return $this->sanitize($data);
    }

    private function parseRawUrlEncoded(): array
    {
        $raw = file_get_contents('php://input');
        parse_str($raw, $data);
        return $this->sanitize($data);
    }

    private function mapFileNames(): array
    {
        $names = [];
        foreach ($_FILES as $key => $file) {
            $names[$key] = $file['name'];
        }
        return $names;
    }

    private function sanitize($data): mixed
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    private function ensureMethod(string $expected): void
    {
        $actual = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($actual) !== strtoupper($expected)) {
            throw new \ErrorException("Expected $expected request, but received $actual");
        }
    }

    private function isJson(string $contentType): bool
    {
        return stripos($contentType, 'application/json') !== false;
    }
}
