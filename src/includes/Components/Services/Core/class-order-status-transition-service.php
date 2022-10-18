<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\API\DTO\Response\RejectResponse;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService;
use Biller\Domain\Order\Status;
use Biller\Utility\Status_Mapper;

class Order_Status_Transition_Service implements OrderStatusTransitionService {

	public function updateStatus( $orderUUID, Status $status ) {
		$order = wc_get_order( $orderUUID );

		if ( $status->isCaptured() ) {
			$order->payment_complete();
		}
		if ( $this->shouldChangeStatus( $status, $order ) ) {

			$woocommerceStatus = array_search( (string) $status, Status_Mapper::$orderStatusMapper, true );
			$order->update_status( $woocommerceStatus );

			$order->add_order_note( sprintf(
			/* translators: %s: order ID, WC status */
				__( 'Biller changed the order (%1$s) status to %2$s.', 'biller-business-invoice' ),
				$order->get_id(),
				$woocommerceStatus
			) );
		}
	}

	public function rejectRefund( $shopOrderId, RejectResponse $response ) {
//        throw new Biller_Request_Rejected_Exception( $response->getDetails() );
	}

	/**
	 * Should the status be changed
	 *
	 * @param Status $status
	 * @param $order
	 *
	 * @return bool
	 */
	private function shouldChangeStatus( Status $status, $order ) {
		return in_array( (string) $status, Status_Mapper::$orderStatusMapper, true ) &&
			   ! in_array( $order->get_status(),
				   array_keys( Status_Mapper::$orderStatusMapper, (string) $status ), true );
	}
}
