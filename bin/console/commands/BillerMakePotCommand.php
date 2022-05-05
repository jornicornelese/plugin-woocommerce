<?php

namespace Biller\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BillerMakePotCommand extends Command {
	protected static $defaultName = 'i18n:make:pot';

	protected function configure() {
		parent::configure();
		$this->setDescription( 'Creates pot files.' );
		$this->setHelp( 'The command acts as a wrapper around wp cli to create pot files.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$process = new Process( [
			'bash',
			'./vendor/bin/wp',
			'i18n',
			'make-pot',
			'./src/',
			'./src/i18n/languages/biller-business-invoice.pot',
			'--slug=biller'
		] );
		$process->run();

		if ( ! $process->isSuccessful() ) {
			throw new ProcessFailedException( $process );
		}

		echo $process->getOutput();

		return Command::SUCCESS;
	}
}