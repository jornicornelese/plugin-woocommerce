<?php


use Biller\Components\Bootstrap_Component;
use Biller\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Biller\Migrations\Schema\Biller_Entity_Schema_Provider;
use Biller\Tests\Infrastructure\ORM\AbstractGenericStudentRepositoryTest;

/**
 * Class BaseRepositoryTestAdapter
 */
class BaseRepositoryTestAdapter extends AbstractGenericStudentRepositoryTest {
	/**
	 * @var wpdb
	 */
	protected $db;

	/**
	 * @throws RepositoryClassException
	 */
	public function setUp() {
		global $wpdb;

		$this->db = $wpdb;
		$this->create_test_table();

		Bootstrap_Component::init();

		parent::setUp();
	}

	/**
	 * @inheritDoc
	 */
	public function getStudentEntityRepositoryClass() {
		return Base_Repository_Test::THIS_CLASS_NAME;
	}

	/**
	 * @inheritDoc
	 */
	public function cleanUpStorage() {
		/** @noinspection SqlDialectInspection */
		/** @noinspection SqlNoDataSourceInspection */
		$this->db->query( 'DROP TABLE IF EXISTS ' . $this->db->prefix . 'biller_test_entity' );
	}

	/**
	 * Creates test table.
	 */
	protected function create_test_table() {
		$table_name = $this->db->prefix . 'biller_test_entity';
		$query = Biller_Entity_Schema_Provider::get_schema( $table_name );

		$this->db->query( $query );
	}
}