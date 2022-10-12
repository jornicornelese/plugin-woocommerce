<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Order\OrderService;
use Biller\Domain\Order\Status;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use Biller\Utility\Script_Loader;
use Biller\Utility\Shop_Helper;
use Biller\Utility\View;
use WC_Order;
use WP_Post;

class Biller_Order_Details_Controller extends Biller_Base_Controller {

	const BILLER_STATUS_UNKNOWN = 'unknown';

	/**
	 * @var OrderService
	 */
	private $order_service;

	/**
	 * Biller_Order_Details_Controller constructor
	 */
	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_resources' ) );
		$this->order_service = ServiceRegister::getService( OrderService::class );
	}

	/**
	 * Render meta post box in order details page
	 *
	 * @param WP_Post $wp_post
	 *
	 * @return void
	 * @throws RequestNotSuccessfulException
	 * @throws HttpCommunicationException
	 * @throws HttpRequestException
	 * @throws QueryFilterInvalidParamException
	 */
	public function render( WP_Post $wp_post ) {
		// Use unknown (custom) as a default Biller order status (orders created from admin panel will not have Biller order)
		$status = Status::fromString( self::BILLER_STATUS_UNKNOWN );
		try {
			$status = $this->order_service->getStatus( $wp_post->ID );
		} catch ( \Exception $exception ) {
			Logger::logDebug(
				"Order with id {$wp_post->ID} not found.",
				'Integration',
				[ 'ExceptionMessage' => $exception->getMessage(), 'ExceptionTrace' => $exception->getTraceAsString() ]
			);
		}

		echo wp_kses(View::file( '/admin/order/meta-post-box.php' )->render(
			[
				'payment_status'               => $this->get_status_label( $status ),
				'payment_link'                 => Shop_Helper::get_payment_link_url( $wp_post->ID ),
				'cancel_link'                  => Shop_Helper::get_controller_url(
					'Order_Cancel',
					'cancel',
					[ 'order_id' => $wp_post->ID ]
				),
				'capture_link'                 => Shop_Helper::get_controller_url(
					'Order_Capture',
					'capture',
					[ 'order_id' => $wp_post->ID ]
				),
				'display_link'                 => $this->should_link_be_displayed( $status, wc_get_order( $wp_post->ID ) ),
				'display_company_info_message' => $this->should_company_info_message_be_displayed( $status, wc_get_order( $wp_post->ID ) ),
				'display_capture_button'       => $status->isAccepted(),
				'display_cancel_button'        => $status->isAccepted() || $status->isPending()
			]
		), View::get_allowed_tags());
	}

	/**
	 * Load css and js files
	 *
	 * @return void
	 */
	public function load_resources() {
		wp_register_script( 'biller.order-details.js',
			Shop_Helper::get_plugin_base_url() . 'resources/js/admin/biller.order-details.js',
			array( 'jquery' ), Shop_Helper::get_plugin_version(), true );
		wp_enqueue_script( 'biller.order-details.js' );

		Script_Loader::load_css( [ '/css/meta-post-box.css' ] );
		Script_Loader::load_js( [ '/js/admin/biller.ajax.js' ] );
	}

	/**
	 * Get status label
	 *
	 * @param Status $status
	 *
	 * @return string|void
	 */
	public function get_status_label( Status $status ) {
		switch ( $status ) {
			case $status->isPending():
				return __( 'Pending' );
			case $status->isAccepted():
				return __( 'Accepted' );
			case $status->isCaptured():
				return __( 'Captured' );
			case $status->isPartiallyCaptured():
				return __( 'Partially captured' );
			case $status->isRefunded():
				return __( 'Refunded' );
			case $status->isRefundedPartially():
				return __( 'Partially refunded' );
			case $status->isCancelled():
				return __( 'Cancelled' );
			case $status->isRejected():
				return __( 'Rejected' );
			case $status->isFailed():
				return __( 'Failed' );
			default:
				return __( 'Unknown' );
		}
	}

	/**
	 * Return true if payment link should be displayed
	 *
	 * @param Status $status
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function should_link_be_displayed( Status $status, WC_Order $order ) {
		return ! empty( $order->get_meta( 'biller_company_name' ) ) &&
		       $this->is_valid_payment_link_status( $status ) &&
		       $order->get_total() > 0 &&
		       $order->has_billing_address() &&
		       ! empty( $order->get_billing_first_name() ) &&
		       ! empty( $order->get_billing_last_name() ) &&
		       ! empty( $order->get_billing_email() ) &&
		       ! empty( $order->get_billing_city() ) &&
		       ! empty( $order->get_billing_postcode() ) &&
		       ! empty( $order->get_billing_country() ) &&
		       ! empty( $order->get_billing_address_1() );
	}

	/**
	 * Return true if company info message should be displayed
	 *
	 * @param Status $status
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function should_company_info_message_be_displayed( Status $status, WC_Order $order ) {
		return empty( $order->get_meta( 'biller_company_name' ) ) && $this->is_valid_payment_link_status( $status );
	}

	/**
	 * Return true if status is valid payment link status
	 *
	 * @param Status $status
	 *
	 * @return bool
	 */
	private function is_valid_payment_link_status( Status $status ) {
		return false !== strpos( (string) $status, 'unknown' ) ||
		       in_array( (string) $status, [
			       Status::BILLER_STATUS_PENDING,
			       Status::BILLER_STATUS_CANCELLED,
			       Status::BILLER_STATUS_FAILED,
			       Status::BILLER_STATUS_REJECTED
		       ], true );
	}
}