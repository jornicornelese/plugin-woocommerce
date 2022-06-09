<?php

namespace Biller\Gateways;

use Biller\Biller;
use Biller\BusinessLogic\API\Http\Exceptions\RequestNotSuccessfulException;
use Biller\BusinessLogic\Authorization\AuthorizationService;
use Biller\BusinessLogic\Authorization\Exceptions\UnauthorizedException;
use Biller\BusinessLogic\Integration\Refund\RefundAmountRequestService;
use Biller\Components\Services\Notice_Service;
use Biller\Components\Services\Order_Request_Service;
use Biller\Components\Services\Refund_Amount_Service;
use Biller\Domain\Amount\Currency;
use Biller\Domain\Exceptions\CurrencyMismatchException;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Domain\Exceptions\InvalidCountryCode;
use Biller\Domain\Exceptions\InvalidCurrencyCode;
use Biller\Domain\Exceptions\InvalidTaxPercentage;
use Biller\Domain\Order\OrderRequest\Country;
use Biller\DTO\Notice;
use Biller\Infrastructure\Http\Exceptions\HttpCommunicationException;
use Biller\Infrastructure\Http\Exceptions\HttpRequestException;
use Biller\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Biller\Infrastructure\ServiceRegister;
use Biller\Repositories\Plugin_Options_Repository;
use Biller\Utility\Script_Loader;
use Biller\Utility\Shop_Helper;
use Biller\Utility\View;
use Exception;
use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

class Biller_Business_Invoice extends WC_Payment_Gateway {
	const BILLER_ICON_PATH = '/resources/images/biller_logo.svg';
	const BILLER_DEFAULT_TITLE = 'Biller business invoice';
	const BILLER_DEFAULT_DESCRIPTION = 'Biller is designed to optimally serve both the business seller and buyer. With Biller businesses buy now and pay later.';

	/**
	 * @var AuthorizationService
	 */
	private $auth_service;

	/**
	 * @var Plugin_Options_Repository $options_repository
	 */
	private $options_repository;

	/**
	 * @var Order_Request_Service
	 */
	private $order_request_service;

	/**
	 * @var Refund_Amount_Service
	 */
	private $refund_amount_service;

	/**
	 * @var Notice_Service
	 */
	private $notice_service;

	/**
	 * Biller_Business_Invoice constructor.
	 */
	public function __construct() {
		$this->id                 = Biller::BILLER_BUSINESS_INVOICE_ID;
		$this->has_fields         = true;
		$this->method_title       = __( 'Biller business invoice', 'biller-business-invoice' );
		$this->method_description = __( 'Biller is designed to optimally serve both the business seller and buyer. With Biller businesses buy now and pay later.',
			'biller-business-invoice' );
		$this->icon               = Biller::get_plugin_url( self::BILLER_ICON_PATH );

		$this->auth_service          = ServiceRegister::getService( \Biller\BusinessLogic\Authorization\Contracts\AuthorizationService::class );
		$this->options_repository    = new Plugin_Options_Repository();
		$this->order_request_service = new Order_Request_Service();
		$this->refund_amount_service = ServiceRegister::getService( RefundAmountRequestService::class );
		$this->notice_service        = new Notice_Service();

		$this->supports = [
			'products',
			'refunds',
		];

		$this->init_form_fields();
		$this->init_settings();
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->title       = $this->get_option( 'title', self::BILLER_DEFAULT_TITLE );
		$this->description = $this->get_option( 'description', self::BILLER_DEFAULT_DESCRIPTION );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array(
			$this,
			'process_admin_options'
		) );
		add_action( 'wp_enqueue_scripts', function () {
			Script_Loader::load_js( [ '/js/checkout/biller.checkout.js' ] );
			Script_Loader::load_css( [ '/css/icon.css' ] );
		} );
		add_action( 'admin_enqueue_scripts', function () {
			Script_Loader::load_js( [ '/js/admin/biller.mode-switch.js' ] );
			Script_Loader::load_css( [ '/css/mode-switch.css' ] );

			Script_Loader::load_js( [ '/js/admin/biller.ajax.js' ] );
			Script_Loader::load_js( [ '/js/admin/biller.notifications.js' ] );
			Script_Loader::load_css( [ '/css/notifications.css' ] );
		} );
	}

	/**
	 * Get name of the key value from options table
	 *
	 * @return string
	 */
	public static function get_option_name() {
		return 'woocommerce_' . Biller::BILLER_BUSINESS_INVOICE_ID . '_settings';
	}

	/**
	 * Get name of the key value from options table
	 *
	 * @return string
	 */
	public static function get_option_status_name() {
		return 'woocommerce_' . Biller::BILLER_BUSINESS_INVOICE_ID . '_status';
	}

	public function allow_save_settings() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		if ( empty( $post_data ) ) {
			return false;
		}
		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}
		$prefix = 'sandbox-';
		if ( ! empty( $this->settings['mode'] ) ) {
			$prefix = $this->settings['mode'] . '-';
		}

		if ( empty( $this->settings[ $prefix . 'webShopUID' ] ) || empty( $this->settings[ $prefix . 'username' ] ) || empty( $this->settings[ $prefix . 'password' ] ) ) {
			$this->notice_service->push(
				new Notice( Notice_Service::ERROR_TYPE, 'Webshop UUID, Username and Password fields are mandatory.', true )
			);

			return false;
		}

		try {
			$this->auth_service->validate( $this->settings[ $prefix . 'username' ],
				$this->settings[ $prefix . 'password' ],
				$this->settings['mode'] );
		} catch ( UnauthorizedException $exception ) {
			if ( ! empty( $post_data ) ) {
				$this->notice_service->push(
					new Notice( Notice_Service::ERROR_TYPE, 'Invalid credentials: Unable to establish connection with Biller API.', true )
				);
			}

			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @return void
	 */
	public function init_settings() {
		parent::init_settings();

		// Reinitialize password since WC reads data directly from the DB. We need decoded values.
		if ( ! empty( $this->settings['sandbox'] ) || ! empty( $this->settings['live'] ) ) {
			$settings = $this->options_repository->get_biller_gateway_settings();

			$this->settings['mode'] = $this->options_repository->get_mode();
			if ( array_key_exists( 'live', $this->settings ) ) {
				$this->settings['live-webShopUID'] = $this->settings['live']['webShopUID'];
				$this->settings['live-username']   = $this->settings['live']['username'];
				$this->settings['live-password']   = $settings['live']['password'];
			}
			if ( array_key_exists( 'sandbox', $this->settings ) ) {
				$this->settings['sandbox-webShopUID'] = $this->settings['sandbox']['webShopUID'];
				$this->settings['sandbox-username']   = $this->settings['sandbox']['username'];
				$this->settings['sandbox-password']   = $settings['sandbox']['password'];
			}
		}

		unset( $this->settings['sandbox'], $this->settings['live'] );
	}

	/**
	 * Redirects merchant to payment configuration when tries to enable payment method
	 * on payments listing page
	 *
	 * @return bool
	 */
	public function needs_setup() {
		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @return array|array[]
	 */
	public function get_form_fields() {
		if ( ! $this->options_repository->get_biller_gateway_settings() ) {
			return $this->get_base_form_fields();
		}

		return parent::get_form_fields();
	}

	/**
	 * Saves configuration
	 */
	public function process_admin_options() {
		$this->init_settings();

		$post_data = $this->get_post_data();

		foreach ( $this->get_form_fields() as $key => $field ) {
			if ( 'title' !== $this->get_field_type( $field ) ) {
				try {
					$this->settings[ $key ] = $this->get_field_value( $key, $field, $post_data );
				} catch ( Exception $e ) {
					$this->add_error( $e->getMessage() );
				}
			}
		}

		unset( $this->settings['accessToken'], $this->settings['refreshToken'] );
		$prefix = '';
		if ( ! empty( $this->settings['mode'] ) ) {
			$prefix = $this->settings['mode'] . '-';
		}

		try {
			$this->auth_service->authorize( $this->settings[ $prefix . 'username' ],
				$this->settings[ $prefix . 'password' ],
				$this->settings[ $prefix . 'webShopUID' ], $this->settings['mode'] );
		} catch ( UnauthorizedException $exception ) {
			return false;
		}

		unset( $this->settings['sandbox-password'], $this->settings['live-password'] );
		$settings_saved = $this->options_repository->save_biller_gateway_settings( [
			'enabled'     => $this->settings['enabled'],
			'title'       => $this->settings['title'],
			'description' => $this->settings['description']
		] );
		if ( $settings_saved ) {
			return true;
		}

		return false;
	}

	/**
	 * Process payment
	 *
	 * @param $order_id
	 *
	 * @return array
	 * @throws HttpCommunicationException
	 * @throws HttpRequestException
	 * @throws QueryFilterInvalidParamException
	 * @throws RequestNotSuccessfulException
	 * @throws CurrencyMismatchException
	 * @throws InvalidArgumentException
	 * @throws InvalidTaxPercentage
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );
		if ( isset( $_POST['biller_company_name'] ) ) {
			$order->update_meta_data( 'biller_company_name', $_POST['biller_company_name'] );
			$order->update_meta_data( 'biller_registration_number', $_POST['biller_registration_number'] );
			$order->update_meta_data( 'biller_vat_number', $_POST['biller_vat_number'] );
			$order->save();
		}

		// Create order on Biller API using the order service and retrieve the payment link.
		$payment_link = $this->order_request_service->get_payment_link( $order );

		// Return external payment link on Biller.
		return array(
			'result'   => 'success',
			'redirect' => $payment_link
		);
	}

	/**
	 * Process a refund.
	 *
	 * @param int $order_id
	 * @param float $amount
	 * @param string $reason
	 *
	 * @return bool|WP_Error True or false based on success, or a WP_Error object
	 * @since WooCommerce 2.2
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		return $this->refund_amount_service->process_refund( $order_id, $amount, $reason );
	}

	/**
	 * Check if Biller payment method should be available on checkout based on country and currency
	 *
	 * @return bool
	 */
	public function is_available() {
		$is_available = parent::is_available();
		if ( $is_available && WC()->cart ) {
			$country  = WC()->cart->get_customer()->get_shipping_country();
			$currency = get_option( 'woocommerce_currency' );
			try {
				Country::fromIsoCode( $country );
				Currency::fromIsoCode( $currency );
			} catch ( InvalidCountryCode  $e ) {
				$is_available = false;
			} catch ( InvalidCurrencyCode  $e ) {
				$is_available = false;
			}
		}

		return $is_available;
	}

	/**
	 * Adds custom payment fields to the checkout
	 *
	 * @return void
	 */
	public function payment_fields() {
		$description = $this->get_description();
		if ( $description ) {
			echo wpautop( wptexturize( $description ) );
		}

		echo View::file( '/checkout/custom-payment-fields.php' )->render();
	}

	/**
	 * Validates custom payment fields on the checkout
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$errorMessage = __( 'Invalid field: ', 'biller-business-invoice' );
		if ( empty( $_POST['biller_company_name'] ) ) {
			wc_add_notice( $errorMessage . __( 'Company name cannot be empty.', 'biller-business-invoice' ), 'error' );

			return false;
		}

		if ( ! empty( $_POST['biller_registration_number'] ) && ! is_numeric( $_POST['biller_registration_number'] ) ) {
			wc_add_notice( $errorMessage . __( 'Registration number should be a numeric value.', 'biller-business-invoice' ), 'error' );

			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 * @return void
	 */
	public function init_form_fields() {
		$this->form_fields = array_merge( $this->get_base_form_fields(),
			array(
				'title'         => array(
					'title'       => __( 'Name', 'biller-business-invoice' ),
					'type'        => 'text',
					'description' => __( 'This controls the title which the user sees during checkout.', 'biller-business-invoice' ),
					'default'     => __( self::BILLER_DEFAULT_TITLE, 'biller-business-invoice' ),
					'desc_tip'    => true,
				),
				'description'   => array(
					'title'       => __( 'Description', 'biller-business-invoice' ),
					'type'        => 'textarea',
					'description' => __( 'Payment method description that the customer will see on your checkout.',
						'biller-business-invoice' ),
					'default'     => __( self::BILLER_DEFAULT_DESCRIPTION, 'biller-business-invoice' ),
					'desc_tip'    => true
				),
				'notifications' => array(
					'type' => 'notifications',
				),
			) );
	}

	/**
	 * Generates notification hub in init_form_fields method
	 *
	 * @return false|string
	 */
	public function generate_notifications_html() {
		return View::file( '/admin/payments/notification-hub.php' )->render(
			[
				'url' => Shop_Helper::get_controller_url(
					'Notifications',
					'getNotifications'
				)
			]
		);
	}

	/**
	 * Renders invalid credentials notice
	 *
	 * @return void
	 */
	public function invalid_credentials_notice() {
		echo View::file( '/admin/payments/invalid-credentials-notice.php' )->render();
	}

	private function get_base_form_fields() {
		return array(
			'enabled'            => array(
				'title'       => __( 'Enable/Disable', 'biller-business-invoice' ),
				'label'       => __( 'Enable Biller business invoice', 'biller-business-invoice' ),
				'type'        => 'checkbox',
				'default'     => 'no',
				'description' => __( 'Enable/Disable Biller payment method.', 'biller-business-invoice' ),
				'desc_tip'    => true,
			),
			'mode'               => array(
				'title'       => __( 'Mode', 'biller-business-invoice' ),
				'type'        => 'select',
				'options'     => array(
					'sandbox' => __( 'Sandbox', 'biller-business-invoice' ),
					'live'    => __( 'Live', 'biller-business-invoice' ),
				),
				'description' => __( 'Options field that allows merchants to choose either live or sandbox mode.',
					'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => 'live',
			),
			'live-webShopUID'    => array(
				'title'       => __( 'Webshop UUID', 'biller-business-invoice' ),
				'type'        => 'text',
				'description' => __( 'Unique identifier of the Webshop.', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'live-username'      => array(
				'title'       => __( 'Username', 'biller-business-invoice' ),
				'type'        => 'text',
				'description' => __( ' Biller username', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'live-password'      => array(
				'title'       => __( 'Password', 'biller-business-invoice' ),
				'type'        => 'password',
				'description' => __( 'Biller password', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'sandbox-webShopUID' => array(
				'title'       => __( 'Webshop UUID', 'biller-business-invoice' ),
				'type'        => 'text',
				'description' => __( 'Unique identifier of the Webshop.', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'sandbox-username'   => array(
				'title'       => __( 'Username', 'biller-business-invoice' ),
				'type'        => 'text',
				'description' => __( ' Biller username', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			),
			'sandbox-password'   => array(
				'title'       => __( 'Password', 'biller-business-invoice' ),
				'type'        => 'password',
				'description' => __( 'Biller password', 'biller-business-invoice' ),
				'desc_tip'    => true,
				'default'     => '',
			)
		);
	}
}