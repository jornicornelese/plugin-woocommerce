<?php

namespace Biller\Utility;

class Status_Mapper {

	const WOOCOMMERCE_PROCESSING = 'processing';
	const WOOCOMMERCE_ON_HOLD = 'on-hold';
	const WOOCOMMERCE_COMPLETED = 'completed';
	const WOOCOMMERCE_CANCELLED = 'cancelled';
	const WOOCOMMERCE_REFUNDED = 'refunded';
	const WOOCOMMERCE_FAILED = 'failed';
	const WOOCOMMERCE_PENDING_PAYMENT = 'pending';

	const BILLER_PENDING = 'pending';
	const BILLER_ACCEPTED = 'accepted';
	const BILLER_CAPTURED = 'captured';
	const BILLER_CANCELLED = 'cancelled';
	const BILLER_REFUNDED = 'refunded';
	const BILLER_FAILED = 'failed';

	/**
	 * Order status mapper
	 *
	 * @var array
	 */
	public static $orderStatusMapper = [
		self::WOOCOMMERCE_PENDING_PAYMENT => self::BILLER_PENDING,
		self::WOOCOMMERCE_PROCESSING => self::BILLER_ACCEPTED,
		self::WOOCOMMERCE_ON_HOLD => self::BILLER_PENDING,
		self::WOOCOMMERCE_COMPLETED => self::BILLER_CAPTURED,
		self::WOOCOMMERCE_CANCELLED => self::BILLER_CANCELLED,
		self::WOOCOMMERCE_REFUNDED => self::BILLER_REFUNDED,
		self::WOOCOMMERCE_FAILED => self::BILLER_FAILED,
	];
}
