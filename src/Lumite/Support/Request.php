<?php

namespace Lumite\Support;

use Lumite\Support\Requests\RequestHeaders;
use Lumite\Support\Requests\RequestInfo;
use Lumite\Support\Requests\RequestInput;
use Lumite\Support\Requests\RequestFiles;
use Lumite\Support\Requests\RequestAuth;
use stdClass;

/**
 * @method string|array|null queryString(?string $key = null) Get query string or specific key
 * @method string scheme() Get request scheme (http/https)
 * @method string host() Get host
 * @method int port() Get port
 * @method string|null userAgent() Get user agent
 * @method string|null referer() Get referer
 * @method bool|string move(array $file, string $destination) Move uploaded file
 * @method string getExtension(array $file) Get file extension
 * @method string|false getMimeType(array $file) Get file MIME type
 * 
 * @method static string|array|null queryString(?string $key = null) Get query string or specific key
 * @method static string scheme() Get request scheme (http/https)
 * @method static string host() Get host
 * @method static int port() Get port
 * @method static string|null userAgent() Get user agent
 * @method static string|null referer() Get referer
 */
class Request
{
    private RequestInput $input;
    private RequestHeaders $headers;
    private RequestFiles $files;
    private RequestAuth $auth;
    private array $routeParams = [];

    public function __construct()
    {
        $this->input = new RequestInput();
        $this->headers = new RequestHeaders();
        $this->files = new RequestFiles();
        $this->auth = new RequestAuth($this->headers);
    }

    // -------------------- User & Auth -------------------- //

    public function user(): ?stdClass
    {
        return $this->auth->user();
    }

    public function bearer(): ?string
    {
        return $this->auth->bearer();
    }

    public static function bearerStatic(): ?string
    {
        return RequestAuth::bearerStatic();
    }

    // -------------------- Input Methods -------------------- //

    public function input(string $key): mixed
    {
        return $this->input->get($key);
    }

    public function get(string $key): mixed
    {
        return $this->input->getQuery($key);
    }

    public function post(string $key): mixed
    {
        return $this->input->getPost($key);
    }

    public function all(): array
    {
        return $this->input->all();
    }

    public function has(string $key): bool
    {
        return $this->input->has($key);
    }

    public function filled(string $key): bool
    {
        return $this->input->filled($key);
    }

    public function boolean(string $key, bool $default = false): bool
    {
        return $this->input->boolean($key, $default);
    }

    public function only(...$keys): array
    {
        return $this->input->only(...$keys);
    }

    public function except(...$keys): array
    {
        return $this->input->except(...$keys);
    }

    // -------------------- File Methods -------------------- //

    public function getFile(string $key): ?array
    {
        return $this->files->get($key);
    }

    public function getFiles(): array
    {
        return $this->files->all();
    }

    public function hasFile(string $key): bool
    {
        return $this->files->has($key);
    }

    public function validateFile(
        array $file,
        array $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'],
        int $maxSize = 2097152
    ): bool|string {
        return $this->files->validate($file, $allowedTypes, $maxSize);
    }

    // -------------------- Headers -------------------- //

    public function header(?string $key = null, $default = null): mixed
    {
        return $this->headers->get($key, $default);
    }

    // -------------------- Request Info -------------------- //

    public function ip(): ?string
    {
        return RequestInfo::ip();
    }

    public static function ipStatic(): ?string
    {
        return RequestInfo::ip();
    }

    public function method(): string
    {
        return RequestInfo::method();
    }

    public function isMethod(string $method): bool
    {
        return RequestInfo::isMethod($method);
    }

    public function url(): string
    {
        return RequestInfo::url();
    }

    public function path(): string
    {
        return RequestInfo::path();
    }

    public function secure(): bool
    {
        return RequestInfo::secure($this->headers);
    }

    public function expectsJson(): bool
    {
        return RequestInfo::expectsJson($this->headers);
    }

    public function ajax(): bool
    {
        return RequestInfo::ajax($this->headers);
    }

    // -------------------- Route Params -------------------- //

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(?string $key = null, $default = null): mixed
    {
        if ($key === null) {
            return $this->routeParams;
        }

        return $this->routeParams[$key] ?? $default;
    }

    // -------------------- Session -------------------- //

    public function session(): Session
    {
        return new Session();
    }

    // -------------------- Magic Methods -------------------- //

    public function __get($key)
    {
        if ($this->has($key)) {
            return $this->input($key);
        }

        throw new \Exception("Key '$key' does not exist in request.");
    }

    /**
     * Dynamically handle calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public function __call($method, $parameters)
    {
        // Check internal components
        $components = [
            $this->input,
            $this->headers,
            $this->files,
            $this->auth,
        ];

        foreach ($components as $component) {
            if (method_exists($component, $method)) {
                return $component->$method(...$parameters);
            }
        }

        // Check static helper
        if (method_exists(RequestInfo::class, $method)) {
            return RequestInfo::$method(...$parameters);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }

    /**
     * Dynamically handle static calls to the class.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     *
     * @throws \BadMethodCallException
     */
    public static function __callStatic($method, $parameters)
    {
        // Check static helper
        if (method_exists(RequestInfo::class, $method)) {
            return RequestInfo::$method(...$parameters);
        }

        throw new \BadMethodCallException(sprintf(
            'Method %s::%s does not exist.', static::class, $method
        ));
    }
}
