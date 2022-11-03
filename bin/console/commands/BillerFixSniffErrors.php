<?php

namespace Biller\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class BillerFixSniffErrors extends Command{
	protected static $defaultName = 'sniffer:fix';

	protected function configure()
	{
		$this->setDescription('Fix WooCommerce sniffer errors.')
		     ->setHelp('This command fixes WooCommerce sniffer errors.');
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
			'./vendor/bin/phpcbf',
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