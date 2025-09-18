<?php

namespace Lumite\Support;
use Dotenv\Dotenv;

class LoadEnv
{
    public function __construct($path)
    {
        $dotenv = Dotenv::createImmutable($path);
        $dotenv->load();
    }
}
