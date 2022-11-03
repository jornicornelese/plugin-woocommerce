<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\Integration\Cancellation\CancellationService;
use Biller\BusinessLogic\Integration\CancellationItem;
use Biller\Components\Exceptions\Biller_Cancellation_Rejected_Exception;
use Exception;
use WC_Order;

class Cancellation_Service implements CancellationService {

	/**
	 * Reject cancel action
	 *
	 * @throws Biller_Cancellation_Rejected_Exception
	 */
	public function reject( $request, Exception $reason ) {
		throw new Biller_Cancellation_Rejected_Exception( $reason->getMessage() );
	}

	/**
	 * Get all cancelled items
	 *
	 * @param $shopOrderId
	 *
	 * @return CancellationItem[]
	 */
	public function getAllItems( $shopOrderId ) {
		$order              = new WC_Order( $shopOrderId );
		$cancellation_items = [];
		foreach ( $order->get_items() as $item ) {
			$cancellation_items[] = new CancellationItem($item->get_product_id(), $order->get_status());
		}

		return $cancellation_items;
	}
}
