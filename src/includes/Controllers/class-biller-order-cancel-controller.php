<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\Cancellation\CancellationHandler;
use Biller\BusinessLogic\Integration\CancellationRequest;
use Biller\Components\Services\Notice_Service;
use Biller\DTO\Notice;
use Exception;

class Biller_Order_Cancel_Controller extends Biller_Base_Controller {

	/**
	 * Cancel order action
	 *
	 * @return void
	 */
	public function cancel() {
		$order_id             = $this->get_param( 'order_id' );
		$cancellation_handler = new CancellationHandler();
		try {
			$cancellation_handler->handle( new CancellationRequest( $order_id, false ) );

			/**
			 * Display successful notification
			 */
			$notice_service = new Notice_Service();
			$notice_service->push(new Notice(Notice_Service::SUCCESS_TYPE, 'Order cancelled successfully.'));

			$this->return_json( array( 'success' => true ) );
		} catch ( Exception $exception ) {
			$this->return_json( array( 'success' => false, 'message' => $exception->getMessage() ) );
		}
	}
}
