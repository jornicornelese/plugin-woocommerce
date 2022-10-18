<?php

namespace Biller\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BillerRunSniffer extends Command {
	protected static $defaultName = 'sniffer:run';

	protected function configure()
	{
		$this->setDescription('Runs WooCommerce sniffer.')
		     ->setHelp('This command runs WooCommerce sniffer.');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 *
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$process = new Process( [
			'php',
			'./vendor/bin/phpcs',
			'--standard=pbs-rules-set.xml',
			'--warning-severity=0',
			'--report-source',
			'--report-full=phpcs-report.txt',
			'--ignore-annotations',
			'--extensions=php,html',
			'src'
		] );
		$process->run();

		echo $process->getOutput();

		return Command::SUCCESS;
	}
}