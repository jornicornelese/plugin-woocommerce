<?php

namespace Biller\Utility;

use Biller\Infrastructure\Logger\Logger;
use Exception;

/**
 * Class Logging_Callable
 *
 * @package Biller\Utility
 */
class Logging_Callable {

	/**
	 * Callback
	 *
	 * @var callable
	 */
	private $callback;

	/**
	 * Logging_Callable constructor.
	 *
	 * @param callable $callback
	 */
	public function __construct( $callback) {
		$this->callback = $callback;
	}

	/**
	 * Invoke
	 *
	 * @throws Exception
	 */
	public function __invoke() {
		$args = func_get_args();
		try {
			return call_user_func_array($this->callback, $args);
		} catch (Exception $exception) {
			Logger::logError($exception->getMessage());
			throw $exception;
		}
	}
}
