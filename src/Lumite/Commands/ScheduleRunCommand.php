<?php

namespace Lumite\Commands;

use App\Console\Kernel;
use Lumite\Scheduling\Schedule;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ScheduleRunCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('schedule:run')
            ->setDescription('Run scheduled tasks that are due.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schedule = new Schedule();
        $kernel = new Kernel();
        $kernel->schedule($schedule);

        foreach ($schedule->dueEvents() as $event) {
            $output->writeln("<info>Running:</info> " . $event->commandClass);
            $event->run();
        }

        return Command::SUCCESS;
    }
}




