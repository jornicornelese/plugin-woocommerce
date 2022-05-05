<?php

namespace Biller\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BillerMakeMoCommand extends Command {
	protected static $defaultName = 'i18n:make:mo';

	protected function configure() {
		parent::configure();
		$this->setDescription( 'Creates mo files. Requires gettext linux library to be installed. 
		Alternatively mo files can be generated with <href=https://poedit.net/>poedit</> visual translation editor tool' );
		$this->setHelp( 'Creates mo files from po files.' );
	}

	protected function execute( InputInterface $input, OutputInterface $output ) {
		$envChecker = new Process( [ 'which', 'gettext' ] );
		$envChecker->run();
		if ( empty( $envChecker->getOutput() ) ) {
			$output->writeln( '<error>gettext not installed. Run sudo apt install gettext.</error>' );
		}

		$process = new Process( [ 'bash', './bin/console/process_po_files.sh' ] );
		$process->run();

		if ( ! $process->isSuccessful() ) {
			throw new ProcessFailedException( $process );
		}

		echo $process->getOutput();

		return Command::SUCCESS;
	}
}