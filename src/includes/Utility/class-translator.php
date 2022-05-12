<?php

namespace Biller\Utility;

class Translator {

	/**
	 * @var array
	 */
	private $translations;

	public function __construct() {
		$this->translations['biller.payment.webhook.error.title']                                         = __( 'Automatic order synchronization failed!',
			'biller-business-invoice' );
		$this->translations['biller.payment.webhook.error.description']                                   = __( 'There was an error during the synchronization of the order %s. The plugin will not be able to synchronize any further changes automatically. Failure message: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.webhook.notification.order_status_changed_error.title']       = __( 'Order status update failed!',
			'biller-business-invoice' );
		$this->translations['biller.payment.webhook.notification.order_status_changed_error.description'] = __( 'Error message: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.order.capture.title']                                         = __( 'Order capture is rejected by Biller.',
			'biller-business-invoice' );
		$this->translations['biller.payment.order.capture.description']                                   = __( 'Biller error message: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.order.cancellation.title']                                    = __( 'Cancellation is rejected by Biller.',
			'biller-business-invoice' );
		$this->translations['biller.payment.order.cancellation.description']                              = __( 'Biller error message: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.amount.refund.error.title']                                   = __( 'Order amount refund failed',
			'biller-business-invoice' );
		$this->translations['biller.payment.amount.refund.error.description']                             = __( 'Order refund finished with errors: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.refund.line.error.title']                                     = __( 'Order line refund failed',
			'biller-business-invoice' );
		$this->translations['biller.payment.refund.line.error.description']                               = __( 'Order refund finished with errors: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.webhook.refund.error.title']                                  = __( 'Woocommerce order refund failed.',
			'biller-business-invoice' );
		$this->translations['biller.payment.webhook.refund.error.description']                            = __( 'Order refund failed for refund from Biller with error message: %s',
			'biller-business-invoice' );
		$this->translations['biller.payment.order.action.not_synced.title']                               = __( 'Shop change is not synchronized',
			'biller-business-invoice' );
		$this->translations['biller.payment.wc.refund.deleted.description']                               = __( 'Order %s changes will not be automatically synchronized to the Biller anymore. Order refund %s was deleted from the order %s, but the refund deletion is not permitted by the Biller.',
			'biller-business-invoice' );
		$this->translations['biller.payment.wc.refund.created.description']                               = __( 'Order %s changes will not be automatically synchronized to the Biller anymore. Manual order refund %s created for the order %s.',
			'biller-business-invoice' );
		$this->translations['biller.payment.wc.order.deleted.description']                                = __( 'Order %s changes will not be automatically synchronized to the Biller anymore. Order was deleted, but the order deletion is not permitted by the Biller.',
			'biller-business-invoice' );
		$this->translations['biller.payment.wc.order.total_updated.description']                          = __( 'Order %s changes will not be automatically synchronized to the Biller anymore. Order total is updated, but the order update is not permitted by the Biller.',
			'biller-business-invoice' );
		$this->translations['biller.payment.wc.order.updated.description']                                = __( 'Order %s changes are detected but not synchronized to the Biller. Order update is not permitted by the Biller.',
			'biller-business-invoice' );
	}

	/**
	 * @param $message
	 * @param $params
	 *
	 * @return string
	 */
	public function translate( $message, $params ) {
		if ( ! array_key_exists( $message, $this->translations ) ) {
			return '';
		}

		return vsprintf( $this->translations[ $message ], $params );
	}
}