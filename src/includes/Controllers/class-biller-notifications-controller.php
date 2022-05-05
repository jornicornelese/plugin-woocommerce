<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\Notifications\Model\Notification;
use Biller\BusinessLogic\Notifications\NotificationController;
use Biller\Utility\Translator;


class Biller_Notifications_Controller extends Biller_Base_Controller {

	const LIMIT = 5;

	/**
	 * @var NotificationController
	 */
	private $notification_controller;
	/**
	 * @var Translator
	 */
	private $translator;

	public function __construct() {
		$this->notification_controller = new NotificationController();
		$this->translator = new Translator();
	}

	/**
	 * Redirects to order thank you page after successful order creation on Biller or to checkout otherwise
	 */
	public function getNotifications()
	{
		$notifications = $this->notification_controller->get( self::LIMIT, (int)$this->get_param('page') * self::LIMIT );
		$result        = [];

		/**
		 * @var Notification $notification
		 */
		foreach ( $notifications->getNotifications() as $notification )
		{
			$date = date('M d, Y, h:i A', $notification->getTimestamp());
			$desc = $notification->getDescription();
			$message = $notification->getMessage();
			$notificationArray['id'] = $notification->getId();
			$notificationArray['order'] = $notification->getOrderNumber();
			$notificationArray['severity'] = $notification->getSeverity();
			$notificationArray['message'] = $this->translator->translate($message->getMessageKey(), $message->getMessageParams());
			$notificationArray['description'] = $this->translator->translate($desc->getMessageKey(), $desc->getMessageParams());
			$notificationArray['date'] = $date;

			$result [] = $notificationArray;
		}

		wp_send_json($result, 200);
	}
}