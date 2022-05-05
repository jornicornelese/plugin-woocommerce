<?php

namespace Biller\Components\Services;

use Biller\BusinessLogic\Integration\Refund\RefundAmountRejectResponse;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService;
use Biller\BusinessLogic\Integration\RefundAmountRequest;
use Biller\BusinessLogic\Refunds\Contracts\RefundAmountHandlerService;
use Biller\Domain\Amount\Amount;
use Biller\Domain\Amount\Currency;
use Biller\Infrastructure\ServiceRegister;
use Exception;
use WP_Error;

class Refund_Amount_Service implements RefundAmountRequestService {

	/**
	 * @var WP_Error|null
	 */
	private $refund_error;

	/**
	 * @inheritDoc
	 *
	 * Method will be automatically called by the core library during the amount refund handling when necessary.
	 * Just record the refund error mesage and use it in the process_refund method
	 * @see process_refund
	 */
	public function reject( RefundAmountRequest $request, Exception $reason ) {
		$this->refund_error = new \WP_Error(
			'biller_refund_rejected',
			sprintf( __( 'Order refund rejected with error: %s', 'biller' ), $reason->getMessage() )
		);

		return new RefundAmountRejectResponse( true );
	}

	/**
	 * Process a refund.
	 *
	 * @param int $order_id
	 * @param float $amount
	 * @param string $reason
	 *
	 * @return bool|WP_Error True or false based on success, or a WP_Error object
	 * @since WooCommerce 2.2
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return new WP_Error(
				'biller_refund_invalid',
				sprintf( __( 'Order refund could not be processed, unknown order id: %s', 'biller' ), $order_id )
			);
		}

		// Reset any potential previous errors
		$this->refund_error = null;

		try {
			$request = new RefundAmountRequest(
				(string) $order_id,
				! empty( $reason ) ? $reason : "Order $order_id refund",
				Amount::fromFloat( (float) $amount, Currency::fromIsoCode( $order->get_currency() ) )
			);
			$this->getRefundAmountHandlerService()->handle( $request );
		} catch ( Exception $e ) {
			$this->reject( $request, $e );
		}

		// In case there is an error during the refund request, return a corresponding error to WooCommerce
		// Otherwise, if the refund succeeded on the Biller API, return true
		return isset( $this->refund_error ) ? $this->refund_error : true;
	}

	/**
	 * @return RefundAmountHandlerService
	 */
	protected function getRefundAmountHandlerService() {
		return ServiceRegister::getService( RefundAmountHandlerService::class );
	}
}