<?php

namespace Biller\Components\Services;

use Biller\Biller;
use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Cancellation\CancellationHandler;
use Biller\BusinessLogic\Integration\CancellationRequest;
use Biller\BusinessLogic\Integration\Shipment\ShipmentService;
use Biller\BusinessLogic\Notifications\NotificationHub;
use Biller\BusinessLogic\Notifications\NotificationText;
use Biller\BusinessLogic\Order\Exceptions\InvalidOrderReferenceException;
use Biller\BusinessLogic\Order\OrderReference\Repository\OrderReferenceRepository;
use Biller\BusinessLogic\Order\OrderService;
use Biller\BusinessLogic\Shipment\ShipmentHandler;
use Biller\BusinessLogic\Webhook\WebHookContext;
use Biller\Components\Exceptions\Biller_Cancellation_Rejected_Exception;
use Biller\Components\Exceptions\Biller_Capture_Rejected_Exception;
use Biller\DTO\Notice;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use RuntimeException;
use WC_Order;
use WC_Order_Refund;

class Admin_Order_Action_Handlers {
	/**
	 * @var OrderReferenceRepository
	 */
	private $order_reference_repository;
	/**
	 * @var Notice_Service
	 */
	private $notice_service;

	private function __construct( OrderReferenceRepository $order_reference_repository, Notice_Service $notice_service ) {
		$this->order_reference_repository = $order_reference_repository;
		$this->notice_service             = $notice_service;
	}

	public static function initialize( OrderReferenceRepository $order_reference_repository, Notice_Service $notice_service ) {
		$handler = new self( $order_reference_repository, $notice_service );

		add_action( 'woocommerce_refund_deleted',
			WebHookContext::getProtectedCallable( [ $handler, 'refund_deleted' ] ), 10, 2
		);
		add_action( 'woocommerce_refund_created',
			WebHookContext::getProtectedCallable( [ $handler, 'refund_created' ] )
		);
		add_action( 'woocommerce_before_order_object_save',
			WebHookContext::getProtectedCallable( [ $handler, 'handle_before_order_update' ] )
		);
		/**
		 * WooCommerce event woocommerce_delete_order is not used here because it is not fired each time
		 * order gets deleted, and WordPress event before_delete_post covers all cases.
		 */
		add_action( 'before_delete_post',
			WebHookContext::getProtectedCallable( [ $handler, 'order_deleted' ] )
		);
	}

	public function refund_deleted( $refund_id, $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $this->is_biller_order( $order ) ) {
			return;
		}

		$this->detach_order(
			new NotificationText( 'biller.payment.order.action.not_synced.title' ),
			new NotificationText( 'biller.payment.wc.refund.deleted.description', [
				$order_id,
				$refund_id,
				$order_id
			] ),
			$order
		);
	}

	public function refund_created( $refund_id ) {
		/**
		 * @var WC_Order_Refund $refund
		 */
		$refund = wc_get_order( $refund_id );
		if ( ! $refund || $refund->get_refunded_payment() ) {
			return;
		}

		$order = wc_get_order( $refund->get_parent_id() );
		if ( ! $order || ! $this->is_biller_order( $order ) ) {
			return;
		}

		$this->detach_order(
			new NotificationText( 'biller.payment.order.action.not_synced.title' ),
			new NotificationText( 'biller.payment.wc.refund.created.description', [
				$order->get_id(),
				$refund_id,
				$order->get_id()
			] ),
			$order
		);
	}

	/**
	 * Handle order deleted event
	 *
	 * @param $post_id
	 *
	 * @return void
	 * @throws QueryFilterInvalidParamException
	 */
	public function order_deleted( $post_id ) {
		$order = wc_get_order( $post_id );
		if ( ! $order || $order->get_type() !== 'shop_order' || ! $this->is_biller_order( $order ) ) {
			return;
		}

		$this->detach_order(
			new NotificationText( 'biller.payment.order.action.not_synced.title' ),
			new NotificationText( 'biller.payment.wc.order.deleted.description', [
				$post_id,
			] ),
			$order
		);
	}

	/**
	 * Handle order before update
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 * @throws HttpCommunicationException
	 * @throws QueryFilterInvalidParamException
	 * @throws RequestNotSuccessfulException
	 * @throws HttpRequestException
	 */
	public function handle_before_order_update( WC_Order $order ) {
		$this->handle_admin_order_create( $order );

		if ( ! $this->is_biller_order( $order ) ) {
			return;
		}

		if ( array_key_exists( 'status', $order->get_changes() ) ) {
			$this->update_order_status( $order );
		}

		if ( $this->is_total_changed( $order ) ) {
			$this->detach_order(
				new NotificationText( 'biller.payment.order.action.not_synced.title' ),
				new NotificationText( 'biller.payment.wc.order.total_updated.description', [
					$order->get_id(),
				] ),
				$order
			);
		}

		if ($this->is_address_or_customer_changed($order)) {
			NotificationHub::pushInfo(
				new NotificationText( 'biller.payment.order.action.not_synced.title' ),
				new NotificationText( 'biller.payment.wc.order.updated.description', [
					$order->get_id(),
				] ),
				(string) $order->get_id()
			);

			$message = sprintf( __( 'Order %s changes are not synchronized to the Biller.', 'biller-business-invoice' ), $order->get_id() );
			$this->notice_service->push( new Notice( Notice_Service::INFO_TYPE, $message ) );
			$order->add_order_note( $message );
		}
	}

	private function handle_admin_order_create( WC_Order $order ) {
		// Act only when admin order is about to be created (only if date_created is in the list of changes)
		if (
			'admin' !== $order->get_created_via() || ! $order->is_editable() ||
			$order->get_payment_method() !== Biller::BILLER_BUSINESS_INVOICE_ID ||
			! empty( $order->get_meta( 'biller_company_name' ) ) ||
			! array_key_exists( 'date_created', $order->get_changes() )
		) {
			return;
		}

		// If order is already attached to a Biller skip company name coping
		$order_reference = $this->order_reference_repository->findByExternalUUID( (string) $order->get_id() );
		if ( $order_reference !== null ) {
			return;
		}

		if (!empty($order->get_billing_company())) {
			$order->update_meta_data( 'biller_company_name', $order->get_billing_company() );
		}
	}

	/**
	 * Update order status
	 *
	 * @param WC_Order $order
	 *
	 * @return void
	 * @throws HttpCommunicationException
	 * @throws HttpRequestException
	 * @throws QueryFilterInvalidParamException
	 * @throws RequestNotSuccessfulException
	 */
	private function update_order_status( WC_Order $order ) {
		/**
		 * @var OrderService $order_service
		 */
		$order_service = ServiceRegister::getService( OrderService::class );
		$status        = $order->get_changes()['status'];

		try {
			$biller_status = $order_service->getStatus( $order->get_id() );
		} catch ( InvalidOrderReferenceException $exception ) {
			Logger::logDebug( 'Order with id ' . $order->get_id() . ' not found on Biller API. ' . $exception->getMessage() );

			return;
		}

		if ( strtolower( $status ) === 'completed' && $biller_status->isAccepted() ) {
			$this->handle_order_completed( $order->get_id() );
		}
		if ( strtolower( $status ) === 'cancelled' && ( $biller_status->isAccepted() || $biller_status->isPending() ) ) {
			$this->handle_order_cancelled( $order->get_id() );
		}

	}

	/**
	 * Check if address has changed
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function is_address_or_customer_changed( WC_Order $order ) {
		return array_key_exists( 'billing', $order->get_changes() ) ||
		       array_key_exists( 'shipping', $order->get_changes() ) ||
		       array_key_exists( 'customer_id', $order->get_changes() );
	}

	/**
	 * Check if order total has changed
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	private function is_total_changed( WC_Order $order ) {
		return array_key_exists( 'total', $order->get_changes() );
	}

	/**
	 * Handle order cancelled event
	 *
	 * @param $order_id
	 *
	 * @return void
	 * @throws HttpCommunicationException
	 * @throws QueryFilterInvalidParamException
	 * @throws RequestNotSuccessfulException
	 */
	private function handle_order_cancelled( $order_id ) {
		$cancellation_handler = new CancellationHandler();
		try {
			$cancellation_handler->handle( new CancellationRequest( $order_id, false ) );
		} catch ( Biller_Cancellation_Rejected_Exception $exception ) {
			$this->notice_service->push( new Notice( Notice_Service::ERROR_TYPE, $exception->getMessage() ) );
			throw new RuntimeException( 'Biller status change not allowed.' );
		}
	}

	/**
	 * Handle order completed event
	 *
	 * @param $order_id
	 *
	 * @return void
	 * @throws RequestNotSuccessfulException
	 * @throws HttpCommunicationException
	 * @throws QueryFilterInvalidParamException
	 */
	private function handle_order_completed( $order_id ) {
		$shipment_service = ServiceRegister::getService( ShipmentService::class );
		$shipment_handler = new ShipmentHandler();
		try {
			$shipment_handler->handle( $shipment_service->create_shipment_request( $order_id ) );
		} catch ( Biller_Capture_Rejected_Exception $exception ) {
			$this->notice_service->push( new Notice( Notice_Service::ERROR_TYPE, $exception->getMessage() ) );
			throw new RuntimeException( 'Biller status change not allowed.' );
		}
	}

	/**
	 * Detach order, remove order reference from repository and add notification
	 *
	 * @param NotificationText $title
	 * @param NotificationText $description
	 * @param WC_Order $order
	 *
	 * @return void
	 * @throws QueryFilterInvalidParamException
	 */
	private function detach_order( NotificationText $title, NotificationText $description, WC_Order $order ) {
		NotificationHub::pushError( $title, $description, (string) $order->get_id() );
		$this->order_reference_repository->deleteBuExternalUUID( (string) $order->get_id() );

		$message = sprintf( __( 'Order %s changes will not be automatically synchronized to the Biller anymore due to unsupported action.', 'biller-business-invoice' ), $order->get_id() );
		$this->notice_service->push( new Notice( Notice_Service::ERROR_TYPE, $message ) );
		$order->add_order_note( $message );
	}

	/**
	 * Check if payment method is Biller
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 * @throws QueryFilterInvalidParamException
	 */
	private function is_biller_order( \WC_Order $order ) {
		if ( $order->get_payment_method() !== Biller::BILLER_BUSINESS_INVOICE_ID ) {
			return false;
		}

		return null !== $this->order_reference_repository->findByExternalUUID( (string) $order->get_id() );
	}
}