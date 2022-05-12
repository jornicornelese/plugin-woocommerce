<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Order\OrderService;
use Biller\Components\Services\Order_Request_Service;
use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use Biller\Utility\Shop_Helper;
use Exception;
use WC_Order;

class Biller_Payment_Redirection_Controller extends Biller_Base_Controller {

	const SUCCESS_STATUS = 'success';
	const CANCEL_STATUS = 'cancel';
	const ERROR_STATUS = 'error';

	/**
	 * Is request call internal.
	 *
	 * @var bool
	 */
	protected $is_internal = false;

	/**
	 * @var OrderService
	 */
	private $order_service;


	public function __construct() {

		$this->order_service = ServiceRegister::getService( OrderService::class );
	}


	/**
	 * Redirects to order thank you page after successful order creation on Biller or to checkout otherwise
	 *
	 * @return void
	 */
	public function redirect() {
		$order_status = $this->get_param( 'order_status' );
		$order_id     = $this->get_param( 'order_id' );
		$order        = new WC_Order( $order_id );

		try{
			if ( $order_status === self::SUCCESS_STATUS  && $this->order_service->isPaymentAccepted( $order_id )) {
				$order->update_status( 'processing', __( 'Awaiting Biller invoice payment.', 'biller-business-invoice' ) );
				wp_redirect( $this->get_order_received_url( $order ) );

				return;
			}
		} catch ( Exception $e ) {
			Logger::logError('Fail to redirect after successful order creation. ' . $e->getMessage());
		}

		if($order_status === self::ERROR_STATUS) {
			wc_add_notice( 'Biller payment transaction failed. Please choose another billing option or change the company data.', 'error' );
		}

		wp_redirect( wc_get_checkout_url() );
	}

    /**
     * Redirects to Biller payment page
     *
     * @return void
     * @throws HttpCommunicationException
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     * @throws RequestNotSuccessfulException
     * @throws CurrencyMismatchException
     * @throws InvalidArgumentException
     * @throws InvalidTaxPercentage
     */
	public function payment_redirect() {
		$order_request_service = new Order_Request_Service();
		$order_id              = $this->get_param( 'order_id' );
		$token                 = $this->get_param( 'token' );
		$order                 = new WC_Order( $order_id );

		if ( ! $order || ! wp_check_password( Shop_Helper::get_raw_token( $order_id ), $token ) ) {
			wp_redirect( get_home_url() );
			return;
		}

		// Create order on Biller API using the order service and retrieve the payment link.
		$payment_link = $order_request_service->get_payment_link( $order );

		wp_redirect( $payment_link );
	}

	/**
	 * Get the return url (thank you page).
	 *
	 * @param WC_Order|null $order Order object.
	 *
	 * @return string
	 */
	private function get_order_received_url( WC_Order $order = null ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = wc_get_endpoint_url( 'order-received', '', wc_get_checkout_url() );
		}

		return apply_filters( 'woocommerce_get_return_url', $return_url, $order );
	}
}