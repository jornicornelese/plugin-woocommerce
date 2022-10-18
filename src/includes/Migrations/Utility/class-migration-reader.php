<?php

namespace Biller\Migrations\Utility;

use Biller\Migrations\Abstract_Migration;
use wpdb;

/**
 * Class Migration_Reader
 *
 * @package Biller\Migrations\Utility
 */
class Migration_Reader {
	const MIGRATION_FILE_PREFIX = 'migration.v.';

	/**
	 * Migrations directory.
	 *
	 * @var string
	 */
	private $migrations_directory;
	/**
	 * Version number.
	 *
	 * @var string
	 */
	private $version;
	/**
	 * Files for execution.
	 *
	 * @var array
	 */
	private $sorted_files_for_execution = array();
	/**
	 * Pointer.
	 *
	 * @var int
	 */
	private $pointer = 0;
	/**
	 * WP Database
	 *
	 * @var wpdb
	 */
	private $db;

	/**
	 * Migration_Reader constructor.
	 *
	 * @param string $migrations_directory
	 * @param string $version
	 * @param wpdb $db
	 */
	public function __construct( $migrations_directory, $version, $db ) {
		$this->migrations_directory = $migrations_directory;
		$this->version              = $version;
		$this->db                   = $db;
	}


	/**
	 * Read next file from list of files for execution
	 *
	 * @return Abstract_Migration|null
	 */
	public function read_next() {
		if ( ! $this->has_next() ) {
			return null;
		}

		$version    = $this->get_file_version( $this->sorted_files_for_execution[ $this->pointer ] );
		$class_name = $this->get_class_name( $version );
		$this->pointer ++;

		return class_exists( $class_name ) ? new $class_name( $this->db ) : null;
	}

	/**
	 * Checks if there is a next file from list of files for execution
	 *
	 * @return bool
	 */
	public function has_next() {
		if ( empty( $this->sorted_files_for_execution ) ) {
			$this->sort_files();
		}

		return isset( $this->sorted_files_for_execution[ $this->pointer ] );
	}

	/**
	 * Sort and filter files for execution
	 */
	private function sort_files() {
		$files = array_diff( scandir( $this->migrations_directory, 0 ), array( '.', '..' ) );
		if ( $files ) {
			$self = $this;
			usort(
				$files,
				function ( $file1, $file2 ) use ( $self ) {
					$file_1_version = $self->get_file_version( $file1 );
					$file_2_version = $self->get_file_version( $file2 );

					return version_compare( $file_1_version, $file_2_version );
				}
			);

			foreach ( $files as $file ) {
				$file_version = $this->get_file_version( $file );
				if ( version_compare( $this->version, $file_version, '<' ) ) {
					$this->sorted_files_for_execution[] = $file;
				}
			}
		}
	}

	/**
	 * Get file version based on file name
	 *
	 * @param string $file File name.
	 *
	 * @return string
	 */
	private function get_file_version( $file ) {
		return str_ireplace( array( self::MIGRATION_FILE_PREFIX, '.php' ), '', $file );
	}

	/**
	 * Provides migration class name.
	 *
	 * @param string $version
	 *
	 * @return string
	 */
	private function get_class_name( $version ) {
		return 'Biller\\Migrations\\Scripts\\Migration_' . str_replace( '.', '_', $version );
	}
}
