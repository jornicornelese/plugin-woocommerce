<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\Notifications\Model\Notification;
use Biller\BusinessLogic\Notifications\NotificationController;
use Biller\Utility\Translator;


class Biller_Notifications_Controller extends Biller_Base_Controller {

	const LIMIT = 5;

	/**
	 * Notification controller
	 *
	 * @var NotificationController
	 */
	private $notification_controller;

	/**
	 * Translator
	 *
	 * @var Translator
	 */
	private $translator;

	public function __construct() {
		$this->notification_controller = new NotificationController();
		$this->translator              = new Translator();
	}

	/**
	 * Redirects to order thank you page after successful order creation on Biller or to checkout otherwise
	 */
	public function getNotifications() {
		$notifications = $this->notification_controller->get( self::LIMIT, (int) $this->get_param( 'page' ) * self::LIMIT );
		$result        = [];

		/**
		 * Notification
		 *
		 * @var Notification $notification
		 */
		foreach ( $notifications->getNotifications() as $notification ) {
			$orderNumber = wc_get_order( $notification->getOrderNumber() )->get_order_number();
			$date        = gmdate( 'M d, Y, h:i A', $notification->getTimestamp() );
			$desc        = $notification->getDescription();
			$message     = $notification->getMessage();

			$notificationArray['id']          = $notification->getId();
			$notificationArray['order']       = $orderNumber;
			$notificationArray['severity']    = $notification->getSeverity();
			$notificationArray['message']     = $this->translator->translate( $message->getMessageKey(), $message->getMessageParams() );
			$notificationArray['description'] = $this->translator->translate( $desc->getMessageKey(), $desc->getMessageParams() );
			$notificationArray['date']        = $date;

			$result [] = $notificationArray;
		}

		wp_send_json( $result, 200 );
	}
}
