<?php

namespace Biller\Components\Services;


use Biller\BusinessLogic\Integration\Refund\OrderRefundService;
use Biller\BusinessLogic\Notifications\NotificationHub;
use Biller\BusinessLogic\Notifications\NotificationText;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Refunds\RefundCollection;
use Exception;

class Order_Refund_Service implements OrderRefundService {

	/**
	 * @throws InvalidTaxPercentage
	 * @throws Exception
	 */
	public function refund( $externalOrderUUID, RefundCollection $billerRefunds = null ) {
		$order = wc_get_order( $externalOrderUUID );

		if ( $billerRefunds === null ) {
			$this->refundAmount( $order->get_remaining_refund_amount(), $order->get_id() );
		} else {
			$this->refundAmount( $billerRefunds->getTotalRefunded()->getAmountInclTax()->getPriceInCurrencyUnits() - $order->get_total_refunded(),
				$order->get_id() );
		}
	}

	/**
	 * @param float $amount
	 * @param int $orderId
	 *
	 * @return void
	 * @throws Exception
	 */
	private function refundAmount( $amount, $orderId ) {
		if ( $amount <= 0 ) {
			return;
		}

		$refund = wc_create_refund(
			array(
				'amount'         => $amount,
				'order_id'       => $orderId,
				'refund_payment' => false,
			)
		);

		if ( is_wp_error( $refund ) ) {
			NotificationHub::pushWarning(
				new NotificationText( 'biller.payment.webhook.refund.error.title' ),
				new NotificationText(
					'biller.payment.webhook.refund.error.description',
					array( 'message' => $refund->get_error_message() )
				),
				$orderId
			);
		}
	}
}