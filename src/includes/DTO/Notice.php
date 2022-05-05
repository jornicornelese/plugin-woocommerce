<?php

namespace Biller\DTO;

use Biller\Infrastructure\Data\DataTransferObject;

class Notice extends DataTransferObject {
	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $message;

	/**
	 * @var bool
	 */
	private $is_dismissible;

	/**
	 * @param string $type
	 * @param string $message
	 * @param bool $is_dismissible
	 */
	public function __construct( $type, $message, $is_dismissible = false) {
		$this->type           = $type;
		$this->message        = $message;
		$this->is_dismissible = $is_dismissible;
	}


	/**
	 * @return string
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType( $type ) {
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 * @param string $message
	 */
	public function setMessage( $message ) {
		$this->message = $message;
	}

	/**
	 * @return bool
	 */
	public function isDismissible() {
		return $this->is_dismissible;
	}

	/**
	 * @param bool $is_dismissible
	 */
	public function setIsDismissible( $is_dismissible ) {
		$this->is_dismissible = $is_dismissible;
	}

	/**
	 * Transform object to array
	 *
	 * @return array
	 */
	public function toArray() {
		return [
			'type' => $this->type,
			'message' => $this->message,
			'is_dismissible' => $this->is_dismissible
		];
	}

	/**
	 * Create object from array
	 *
	 * @param array $data
	 *
	 * @return Notice
	 */
	public static function fromArray( array $data ) {
		return new self(
			self::getDataValue($data, 'type'),
			self::getDataValue($data, 'message'),
			self::getDataValue($data, 'is_dismissible')
		);
	}
}