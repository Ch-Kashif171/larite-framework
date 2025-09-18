<?php

namespace Lumite\Support;

use Dotenv\Dotenv;

class LoadEnv
{
    public function __construct($path)
    {
        $envFile = $path . DIRECTORY_SEPARATOR . '.env';

        // Use unsafe repository so values are available via getenv() (env() helper relies on getenv)
        $dotenv = Dotenv::createUnsafeImmutable($path);

        if (file_exists($envFile)) {
            $dotenv->load();
        } else {
            if (!defined('ENV_FILE_MISSING')) {
                define('ENV_FILE_MISSING', true);
            }
            if (method_exists($dotenv, 'safeLoad')) {
                $dotenv->safeLoad();
            } else {
                try { $dotenv->load(); } catch (\Throwable $e) { /* ignore when missing */ }
            }
        }
    }
}
