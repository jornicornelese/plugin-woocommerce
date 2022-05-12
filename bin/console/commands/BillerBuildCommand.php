<?php

namespace Biller\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class BillerBuildCommand
 *
 * @package Biller\Console\Commands
 */
class BillerBuildCommand extends Command {
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'build';

    protected function configure()
    {
        $this->setDescription('Creates the plugin archive.')
            ->setHelp('This command generates plugin archive.')
            ->addArgument('version', InputArgument::OPTIONAL,
                'Creates folder based on the provided version with empty release notes file and generated plugin archive.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $process = new Process(['sh', './bin/console/deploy.sh', $input->getArgument('version')]);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        echo $process->getOutput();

        return Command::SUCCESS;
    }
}