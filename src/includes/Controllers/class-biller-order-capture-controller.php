<?php

namespace Biller\Controllers;

use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Integration\Shipment\ShipmentService;
use Biller\BusinessLogic\Shipment\ShipmentHandler;
use Biller\Components\Services\Notice_Service;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\DTO\Notice;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use Exception;

class Biller_Order_Capture_Controller extends Biller_Base_Controller {

	/**
	 * Capture an accepted order to let Biller know the goods are shipped
	 *
	 * @return void
	 * @throws InvalidCurrencyCode
	 * @throws InvalidTaxPercentage
	 * @throws RequestNotSuccessfulException
	 * @throws HttpCommunicationException
	 * @throws QueryFilterInvalidParamException
	 */
	public function capture() {
		$order_id         = $this->get_param( 'order_id' );
		$shipment_handler = new ShipmentHandler();
		try {
			$shipment_handler->handle( $this->get_shipment_service()->create_shipment_request( $order_id ) );

			/**
			 * Display successful notification
			 */
			$notice_service = new Notice_Service();
			$notice_service->push(new Notice(Notice_Service::SUCCESS_TYPE, 'Order captured successfully.'));

			$this->return_json( array( 'success' => true ) );
		} catch ( Exception $exception ) {
			$this->return_json( array( 'success' => false, 'message' => $exception->getMessage() ) );
		}
	}

	/**
	 * Get shipment service
	 *
	 * @return ShipmentService
	 */
	private function get_shipment_service() {
		return ServiceRegister::getService( ShipmentService::class );
	}
}
