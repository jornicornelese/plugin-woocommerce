<?php

namespace Biller\Components\Services;

use Biller\Biller;
use Biller\Infrastructure\Configuration\Configuration;
use Biller\Utility\Shop_Helper;

/**
 * Class Configuration_Service
 *
 * @package Biller\Components\Services
 */
class Configuration_Service extends Configuration {

	/**
	 * Retrieves integration name.
	 *
	 * @return string
	 */
	public function getIntegrationName() {
		return Biller::INTEGRATION_NAME;
	}

	public function getCurrentSystemId() {
		return '';
	}

	/**
	 * @inheritDoc
	 *
	 * @return string
	 */
	public function getCurrentSystemName() {
		return Shop_Helper::get_shop_name();
	}

	/**
	 * @inheritDoc
	 *
	 * @param $name
	 *
	 * @return bool
	 */
	protected function isSystemSpecific($name)
	{
		return false;
	}
}
