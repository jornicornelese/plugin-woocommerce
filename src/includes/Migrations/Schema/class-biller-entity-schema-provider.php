<?php

namespace Biller\Migrations\Schema;

class Biller_Entity_Schema_Provider {
	/**
	 * Provides Biller entity schema.
	 *
	 * @param $table_name
	 *
	 * @return string
	 */
	public static function get_schema( $table_name ) {
		return 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (
                       `id` INT NOT NULL AUTO_INCREMENT,
                       `type` VARCHAR(191) NOT NULL,
                       `data` LONGTEXT NOT NULL,
                       `index_1` VARCHAR(191),
                       `index_2` VARCHAR(191),
                       `index_3` VARCHAR(191),
                       `index_4` VARCHAR(191),
                       `index_5` VARCHAR(191),
                       `index_6` VARCHAR(191),
                       `index_7` VARCHAR(191),
                       PRIMARY KEY (`id`),
                       INDEX `type_index_1` (`type`, `index_1`),
                       INDEX `type_index_2` (`type`, `index_2`),
                       INDEX `type_index_3` (`type`, `index_3`),
                       INDEX `type_index_4` (`type`, `index_4`),
                       INDEX `type_index_5` (`type`, `index_5`),
                       INDEX `type_index_6` (`type`, `index_6`),
                       INDEX `type_index_7` (`type`, `index_7`)
                       )';
	}
}
