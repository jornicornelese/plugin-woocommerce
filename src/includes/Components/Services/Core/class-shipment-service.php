<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\Integration\Shipment\ShipmentService;
use Biller\BusinessLogic\Integration\ShipmentRequest;
use Biller\Components\Exceptions\Biller_Capture_Rejected_Exception;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Amount\TaxableAmount;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Order\OrderRequest\OrderLine;
use Exception;
use WC_Order;

class Shipment_Service implements ShipmentService {

	/**
	 * Reject shipment action
	 *
	 * @throws Biller_Capture_Rejected_Exception
	 */
	public function reject( $request, Exception $reason ) {
		throw new Biller_Capture_Rejected_Exception($reason->getMessage());
	}

	/**
	 * Create shipment request
	 *
	 * @param string $order_id
	 *
	 * @return ShipmentRequest
	 * @throws InvalidCurrencyCode
	 * @throws InvalidTaxPercentage
	 */
	public function create_shipment_request( $order_id ) {
		$order = new WC_Order( $order_id );
		$order_request_service = new Order_Request_Service();
		$order_request = $order_request_service->create_order_request($order);

		return new ShipmentRequest(
			$order_id,
			null,
			$order_request->getDiscount(),
			$order_request->getAmount(),
			iterator_to_array($order_request->getOrderLines()->getIterator())
		);
	}
}
