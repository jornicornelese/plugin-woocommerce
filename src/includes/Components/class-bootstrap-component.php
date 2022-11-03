<?php

namespace Biller\Components;

use Biller\BusinessLogic\Authorization\AuthorizationService;
use Biller\BusinessLogic\BootstrapComponent;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository;
use Biller\BusinessLogic\Integration\Cancellation\CancellationService;
use Biller\BusinessLogic\Integration\Order\OrderStatusTransitionService;
use Biller\BusinessLogic\Integration\Refund\OrderRefundService;
use Biller\BusinessLogic\Integration\Shipment\ShipmentService;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService;
use Biller\BusinessLogic\Notifications\DefaultNotificationChannel;
use Biller\BusinessLogic\Notifications\Interfaces\DefaultNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Interfaces\ShopNotificationChannelAdapter;
use Biller\BusinessLogic\Notifications\Model\Notification;
use Biller\BusinessLogic\Order\OrderReference\Entities\OrderReference;
use Biller\Components\Services\Cancellation_Service;
use Biller\Components\Services\Configuration_Service;
use Biller\Components\Services\Logger_Service;
use Biller\Components\Services\Null_Channel_Adapter_Service;
use Biller\Components\Services\Order_Refund_Service;
use Biller\Components\Services\Order_Status_Transition_Service;
use Biller\Components\Services\Shipment_Service;
use Biller\Components\Services\Refund_Amount_Service;
use Biller\Infrastructure\Configuration\ConfigEntity;
use Biller\Infrastructure\Configuration\Configuration;
use Biller\Infrastructure\Logger\Interfaces\ShopLoggerAdapter;
use Biller\Infrastructure\Logger\LogData;
use Biller\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Biller\Infrastructure\ORM\RepositoryRegistry;
use Biller\Infrastructure\ServiceRegister;
use Biller\Repositories\Base_Repository;
use Biller\Repositories\Plugin_Options_Repository;


/**
 * Class Bootstrap_Component
 *
 * @package Biller\Components
 */
class Bootstrap_Component extends BootstrapComponent {

	private static $is_init = false;

	public static function init() {
		if ( static::$is_init ) {
			return;
		}

		parent::init();

		static::$is_init = true;
	}

	protected static function initServices() {
		parent::initServices();

		ServiceRegister::registerService(
			ShopLoggerAdapter::CLASS_NAME,
			static function () {
				return Logger_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			Configuration::CLASS_NAME,
			static function () {
				return Configuration_Service::getInstance();
			}
		);

		ServiceRegister::registerService(
			UserInfoRepository::class,
			static function () {
				return new Plugin_Options_Repository();
			}
		);

		ServiceRegister::registerService(
			OrderStatusTransitionService::class,
			static function () {
				return new Order_Status_Transition_Service();
			}
		);
		ServiceRegister::registerService(
			OrderRefundService::class,
			static function () {
				return new Order_Refund_Service();
			}
		);
		ServiceRegister::registerService(
			\Biller\BusinessLogic\Authorization\Contracts\AuthorizationService::class,
			static function () {
				return AuthorizationService::getInstance();
			}
		);

		ServiceRegister::registerService(
			ShipmentService::class,
			static function () {
				return new Shipment_Service();
			}
		);

		ServiceRegister::registerService(
			DefaultNotificationChannelAdapter::class,
			static function () {
				return new DefaultNotificationChannel();
			}
		);
		ServiceRegister::registerService(
			ShopNotificationChannelAdapter::class,
			static function () {
				return new Null_Channel_Adapter_Service();
			}
		);

		ServiceRegister::registerService(
			CancellationService::class,
			static function () {
				return new Cancellation_Service();
			}
		);

		// Refund amount service is intentionally registered as a singleton service
		$refund_amount_service = new Refund_Amount_Service();
		ServiceRegister::registerService(
			RefundAmountRequestService::class,
			static function () use ( $refund_amount_service ) {
				return $refund_amount_service;
			}
		);
	}

	/**
	 * Repository class exception
	 *
	 * @throws RepositoryClassException
	 */
	protected static function initRepositories() {
		parent::initRepositories();

		RepositoryRegistry::registerRepository( LogData::class, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( ConfigEntity::class, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( OrderReference::class, Base_Repository::getClassName() );
		RepositoryRegistry::registerRepository( Notification::class, Base_Repository::getClassName() );
	}

	protected static function initEvents() {

	}
}
