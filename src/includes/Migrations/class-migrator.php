<?php

namespace Biller\Migrations;

use Biller\Migrations\Exceptions\Migration_Exception;
use Biller\Migrations\Utility\Migration_Reader;
use wpdb;

/**
 * Class Migrator
 *
 * @package Biller\Migrations
 */
class Migrator {
	/**
	 * WP Database
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Migrator constructor.
	 *
	 * @param wpdb $db
	 * @param string $version
	 */
	public function __construct( $db, $version ) {
		$this->db      = $db;
		$this->version = $version;
	}

	/**
	 * Executes Migrations that have higher version then the provided version.
	 *
	 * @throws Migration_Exception
	 */
	public function execute() {
		$migration_reader = new Migration_Reader( $this->get_migration_directory(), $this->version, $this->db );
		while ( $migration_reader->has_next() ) {
			$migration = $migration_reader->read_next();
			if ( $migration ) {
				$migration->execute();
			}
		}
	}

	/**
	 * Provides migration directory.
	 *
	 * @return string
	 */
	protected function get_migration_directory() {
		return realpath( __DIR__ ) . '/Scripts/';
	}
}
