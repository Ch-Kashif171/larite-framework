<?php

namespace Lumite\Commands\Executor;

use App\Console\Kernel;
use Lumite\Commands\CreateControllerCommand;
use Lumite\Commands\CreateMigrationCommand;
use Lumite\Commands\CreateModelCommand;
use Lumite\Commands\DatabaseSeedCommand;
use Lumite\Commands\MakeAuth;
use Lumite\Commands\MakeCommandCommand;
use Lumite\Commands\MakeMiddlewareCommand;
use Lumite\Commands\MakeSeederCommand;
use Lumite\Commands\MakeServiceProviderCommand;
use Lumite\Commands\ClearViewCacheCommand;
use Lumite\Commands\MigrationCommand;
use Lumite\Commands\RollbackMigrationCommand;
use Lumite\Commands\RouteListCommand;
use Lumite\Commands\ScheduleRunCommand;
use Lumite\Dotenv\Dotenv;
use Symfony\Component\Console\Application;

class Commander
{
    protected Application $app;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->loadEnv();

        $this->app = $application;
    }

    /**
     * @return void
     */
    private function registerCoreCommands()
    {
        $coreCommands = [
            CreateControllerCommand::class,
            CreateModelCommand::class,
            MakeAuth::class,
            MigrationCommand::class,
            CreateMigrationCommand::class,
            RollbackMigrationCommand::class,
            MakeSeederCommand::class,
            DatabaseSeedCommand::class,
            RouteListCommand::class,
            MakeMiddlewareCommand::class,
            MakeCommandCommand::class,
            MakeServiceProviderCommand::class,
            ClearViewCacheCommand::class,
            ScheduleRunCommand::class,
        ];

        foreach ($coreCommands as $command) {
            $this->app->add(new $command);
        }
    }

    /**
     * @return void
     */
    private function registerCustomCommands()
    {
        $kernel = new Kernel();

        foreach ($kernel->getCommands() as $command) {
            $this->app->add(new $command);
        }
    }


    /**
     * @return Application
     */
    public function register(): Application
    {
        $this->registerCoreCommands();

        $this->registerCustomCommands();

        return $this->app;
    }

    /**
     * @return void
     */
    private function loadEnv()
    {
        $dotenv = new Dotenv(ROOT_PATH);
        $dotenv->load();
    }
}