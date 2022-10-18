<?php


namespace Biller\Migrations;

use Biller\Migrations\Exceptions\Migration_Exception;
use wpdb;

/**
 * Class Abstract_Migration
 *
 * @package Biller\Migrations
 */
abstract class Abstract_Migration {

	/**
	 * WP Database
	 *
	 * @var wpdb
	 */
	protected $db;

	/**
	 * Abstract_Migration constructor.
	 *
	 * @param wpdb $db
	 */
	public function __construct( $db) {
		$this->db = $db;
	}

	/**
	 * Executes migration.
	 *
	 * @throws Migration_Exception
	 */
	abstract public function execute();
}
