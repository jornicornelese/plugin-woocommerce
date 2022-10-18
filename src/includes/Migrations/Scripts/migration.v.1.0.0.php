<?php

namespace Biller\Migrations\Scripts;

use Biller\Migrations\Schema\Biller_Entity_Schema_Provider;
use Biller\Migrations\Abstract_Migration;

/**
 * Class Migration_1_0_0
 *
 * @package Biller\Migrations\Scripts
 */
class Migration_1_0_0 extends Abstract_Migration {

	/**
	 * Execute migration.
	 *
	 * @inheritDoc
	 */
	public function execute() {
		$this->create_biller_entity_table();
	}

	/**
	 * Creates biller entity table.
	 */
	private function create_biller_entity_table() {
		$table_name = $this->db->prefix . 'biller_entity';
		$query      = Biller_Entity_Schema_Provider::get_schema( $table_name );

		$this->db->query( $query );
	}
}
