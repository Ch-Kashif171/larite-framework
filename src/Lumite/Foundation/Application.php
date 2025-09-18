<?php

namespace Lumite\Foundation;

use App\Exceptions\Handler;
use Lumite\Mailer\Exception;
use Lumite\Support\AssetsNotFound;
use Lumite\Support\LoadEnv;
use Lumite\Support\Facades\Route;
use Lumite\Support\Routing\RegisterAllRoutes;
use Lumite\Support\Container\App as Container;
use Lumite\Exception\Handlers\MiddlewareException;
use Lumite\Exception\Handlers\RouteNotFoundException;
use Lumite\Exception\Log;
use Lumite\Exception\Whoops;
use Lumite\Utils\RouteExist;

class Application
{
    const VERSION = '6.1.0';

    const FRAMEWORK = 'Larite';

    protected Container $container;

    protected array $configFiles = [
        'common' => [
            'app',
        ],
        'http' => [
            'mail',
        ],
        'cli' => [],
    ];

    protected array $notFound = [
        'routeExist' => RouteExist::class,
    ];

    /**
     * @param Container|null $container
     */
    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
    }

    /**
     * @return string
     */
    public static function version(): string
    {
        return static::VERSION;
    }

    /**
     * @return string
     */
    public static function framework(): string
    {
        return static::FRAMEWORK;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->container->make($key);
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->registerSingletons();

        if (config('app.app_env') !== 'production') {
            $this->registerExceptionHandler();
        }
    }

    /**
     * @return array
     */
    protected function getConfigFiles(): array
    {
        $files = $this->configFiles['common'];
        if ($this->isCli()) {
            $files = array_merge($files, $this->configFiles['cli']);
        } else {
            $files = array_merge($files, $this->configFiles['http']);
        }
        return array_map(fn($file) => __DIR__ . '/../config/' . $file . '.php', $files);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    protected function registerSingletons(): void
    {
        $envExists = file_exists(ROOT_PATH . '/.env');

        if (!$this->isCli()) {
            // In web requests, register assets handler
            $this->registerBootstrapSingleton('assetsNotFound', [AssetsNotFound::class, 'run']);
        } else {
            // In CLI mode, fail hard if .env is missing
            if (!$envExists) {
                throw new \RuntimeException(".env file doesn't exist or is not readable at: {$envPath}");
            }
        }

        if (!$envExists) {
            // If no .env → Whoops first, then dotenv
            $this->registerBootstrapSingleton('whoops', [Whoops::class, 'handler']);
            $this->registerBootstrapSingleton('dotenv', LoadEnv::class, [ROOT_PATH]);
        } else {
            // If .env exists → dotenv first, then Whoops
            $this->registerBootstrapSingleton('dotenv', LoadEnv::class, [ROOT_PATH]);
            $this->registerBootstrapSingleton('whoops', [Whoops::class, 'handler']);
        }
    }


    /**
     * @return bool
     */
    protected function isCli(): bool
    {
        return PHP_SAPI === 'cli';
    }

    /**
     * @param string $key
     * @param mixed $concrete
     * @param array $args
     * @return void
     * @throws \ReflectionException
     */
    protected function registerBootstrapSingleton(string $key, mixed $concrete, array $args = [])
    {
        $instance = null;
        if (is_array($concrete) && isset($concrete[0], $concrete[1])) {
            $class = $concrete[0];
            $method = $concrete[1];
            $instance = $class::$method(...$args);
        } elseif (is_string($concrete) && class_exists($concrete)) {
            $reflection = new \ReflectionClass($concrete);
            $instance = $reflection->newInstanceArgs($args);
        } else {
            $instance = $concrete;
        }
        $this->container->singleton($key, $instance);
    }

    /**
     * @param $commander
     * @return bool
     * @throws MiddlewareException
     */
    public function init($commander = null): bool
    {
        // Bind all facades
        Binding::facades();

        // Register all service providers
        $this->registerServiceProviders();

        //Load all route files
        RegisterAllRoutes::loadAll();

        $commander?->run();

        $routeMatched = false;

        try {
            $routeMatched = Route::executeRoutes();
        } catch (MiddlewareException | RouteNotFoundException $e) {
            Log::error($e, "Not Found Exception");
            throw new MiddlewareException($e->getMessage());
        }

        if (!$routeMatched) {
            $routeExistClass = $this->notFound['routeExist'];
            (new $routeExistClass())->handle();
        }

        return true;
    }

    /**
     * @return void
     */
    protected function registerExceptionHandler(): void
    {
        $handler = new Handler(!(config('app.app_env') === 'production'));
        set_exception_handler([$handler, 'handle']);
    }

    /**
     * @return void
     */
    protected function registerServiceProviders(): void
    {
        $providers = config('providers');
        foreach ($providers as $providerClass) {
            $provider = new $providerClass($this->container);
            if (method_exists($provider, 'register')) {
                $provider->register();
            }
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

}
