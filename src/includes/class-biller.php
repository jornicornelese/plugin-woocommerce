<?php

namespace Biller;

use Biller\BusinessLogic\Order\OrderReference\Repository\OrderReferenceRepository;
use Biller\BusinessLogic\Webhook\WebhookHandler;
use Biller\Components\Bootstrap_Component;
use Biller\Components\Services\Admin_Order_Action_Handlers;
use Biller\Components\Services\Notice_Service;
use Biller\Controllers\Biller_Base_Controller;
use Biller\Controllers\Biller_Order_Details_Controller;
use Biller\Gateways\Biller_Business_Invoice;
use Biller\Infrastructure\Logger\Logger;
use Biller\Infrastructure\ServiceRegister;
use Biller\Migrations\Exceptions\Migration_Exception;
use Biller\Repositories\Plugin_Options_Repository;
use Biller\Utility\Database;
use Biller\Utility\Logging_Callable;
use Biller\Utility\Shop_Helper;
use WC_Order;

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );


class Biller {

	const VERSION = '1.0.3';

	const INTEGRATION_NAME = 'biller-business-invoice';
	const BASE_API_URI = '/biller';
	const BILLER_BUSINESS_INVOICE_ID = 'biller_business_invoice';
	const BILLER_ICON_PATH = '/resources/images/biller_logo.svg';

	/**
	 * Biller instance
	 *
	 * @var Biller
	 */
	protected static $instance;

	/**
	 * Biller plugin file
	 *
	 * @var string
	 */
	private $biller_plugin_file;

	/**
	 * Database
	 *
	 * @var Database
	 */
	private $database;
	/**
	 * Flag that signifies that the plugin is initialized.
	 *
	 * @var bool
	 */
	private $is_initialized = false;

	/**
	 * Notice_Service
	 *
	 * @var Notice_Service
	 */
	private $notice_service;

	/**
	 * Biller_Plugin constructor.
	 *
	 * @param string $biller_plugin_file
	 */
	private function __construct( $biller_plugin_file ) {
		$this->biller_plugin_file = $biller_plugin_file;
		$this->database           = new Database( new Plugin_Options_Repository() );
		$this->notice_service     = new Notice_Service();
	}

	/**
	 * Initialize the plugin and returns instance of the plugin
	 *
	 * @param $biller_plugin_file
	 *
	 * @return Biller
	 */
	public static function init( $biller_plugin_file ) {
		if ( null === self::$instance ) {
			self::$instance = new self( $biller_plugin_file );
		}

		self::$instance->initialize();

		return self::$instance;
	}

	/**
	 * Returns base directory path
	 *
	 * @return string
	 */
	public static function get_plugin_dir_path() {
		return rtrim( plugin_dir_path( __DIR__ ), '/' );
	}

	/**
	 * Handle Webhooks from Biller
	 */
	public static function biller_webhook_handler() {
		/**
		 * Webhook handler
		 *
		 * @var WebhookHandler $webhook_handler
		 */
		$webhook_handler = ServiceRegister::getService( WebhookHandler::class );
		$webhook_handler->handle( file_get_contents( 'php://input' ) );
	}

	/**
	 * Returns biller icon url
	 *
	 * @param $path
	 *
	 * @return string
	 */
	public static function get_biller_icon_url() {
		return rtrim( plugins_url( self::BILLER_ICON_PATH, __DIR__ ), '/' );
	}

	/**
	 * Get name of the key value from options table
	 *
	 * @return string
	 */
	public static function get_option_name() {
		return 'woocommerce_' . self::BILLER_BUSINESS_INVOICE_ID . '_settings';
	}

	/**
	 * Get name of the key value from options table
	 *
	 * @return string
	 */
	public static function get_option_status_name() {
		return 'woocommerce_' . self::BILLER_BUSINESS_INVOICE_ID . '_status';
	}

	/**
	 * Allows query vars to be added, removed, or changed
	 *
	 * @param $vars
	 *
	 * @return mixed
	 */
	public function biller_query_vars_filter( $vars ) {
		$vars[] = 'biller_wc_controller';

		return $vars;
	}

	/**
	 * Allow Biller template to be used without interfering with the WordPress loading process.
	 *
	 * @return void
	 */
	public function biller_template_redirect() {
		$controller_name = get_query_var( 'biller_wc_controller' );
		if ( ! empty( $controller_name ) ) {
			$controller = new Biller_Base_Controller();
			$controller->index();
		}
	}

	public function add_biller_payment_box( $page, $post ) {
		if ( 'shop_order' === $page && $post ) {
			$order = new WC_Order( $post->ID );
			if ( $order->get_payment_method() !== self::BILLER_BUSINESS_INVOICE_ID ) {
				return;
			}

			$controller = new Biller_Order_Details_Controller(); // controller responsible for rendering the Biller payment box.
			add_meta_box(
				'biller-payment-modal',
				__( 'Biller business invoice', 'biller-business-invoice' ),
				array( $controller, 'render' ),
				'shop_order', // specifies on which page should the box be rendered
				'side',
				'core'
			);
		}
	}

	/**
	 * Adds biller payment gateways in the list of WooCommerce payment gateways
	 *
	 * @param $gateways
	 *
	 * @return mixed
	 */
	public function add_biller_payment_gateway( $gateways ) {
		$gateways[] = Biller_Business_Invoice::class;

		return $gateways;
	}

	/**
	 * Loads translations
	 */
	public function biller_init() {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
		load_plugin_textdomain( 'biller-business-invoice', false,
			basename( dirname( $this->biller_plugin_file ) ) . '/i18n/languages/' );
	}

	/**
	 * Action on plugin loaded.
	 */
	public function biller_bootstrap() {
		Bootstrap_Component::init();

		Admin_Order_Action_Handlers::initialize(
			ServiceRegister::getService( OrderReferenceRepository::class ),
			$this->notice_service
		);
	}

	/**
	 * Show action links on the plugin screen.
	 *
	 * @param array $links Plugin Action links.
	 *
	 * @return array
	 */
	public function create_configuration_link( array $links ) {
		$action_links = array(
			'configuration' => '<a href="' . Shop_Helper::get_settings_url() . '" aria-label="' . esc_attr__( 'View Biller configuration',
					'biller-business-invoice' ) . '">' . esc_html__( 'Settings', 'biller-business-invoice' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

	/**
	 * Plugin uninstall method.
	 */
	public function uninstall() {
		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				$this->switch_to_site_and_uninstall_plugin( $site->blog_id );
			}
		} else {
			$this->uninstall_plugin_from_site();
			delete_option( 'BILLER_SCHEMA_VERSION' );
		}
	}

	/**
	 * Hook that triggers when network site is deleted
	 * and removes plugin data related to that site from the network.
	 *
	 * @param int $site_id Site identifier.
	 */
	public function uninstall_plugin_from_deleted_site( $site_id ) {
		$this->switch_to_site_and_uninstall_plugin( $site_id );
	}

	/**
	 * Plugin activation function.
	 *
	 */
	public function activate() {
		if ( ! Shop_Helper::is_woocommerce_active() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html( __( 'Please install and activate WooCommerce.', 'biller-business-invoice' ) ),
				'Plugin dependency check',
				array( 'back_link' => true )
			);
		}
		try {
			$this->database->update( is_multisite() );
		} catch ( Migration_Exception $e ) {
			Logger::logError( 'Failed to update database because ' . $e->getMessage() );
		}
	}

	/**
	 * Register filter for links on the plugin screen.
	 */
	private function add_settings_link() {
		add_filter(
			'plugin_action_links_' . plugin_basename( Shop_Helper::get_plugin_name() ),
			array(
				$this,
				'create_configuration_link',
			)
		);
	}

	/**
	 * Defines global constants and hooks actions to appropriate events
	 */
	private function initialize() {
		if ( $this->is_initialized ) {
			return;
		}
		$this->add_settings_link();
		register_activation_hook( $this->biller_plugin_file, array( $this, 'activate' ) );
		add_action( 'init', new Logging_Callable( array( $this, 'biller_init' ) ) );
		add_action( 'plugins_loaded', new Logging_Callable( array( $this, 'biller_bootstrap' ) ) );
		add_filter( 'woocommerce_save_settings_checkout_biller_business_invoice', array(
			$this,
			'allow_save_settings'
		) );
		add_action( 'woocommerce_api_biller_webhook', new Logging_Callable( array(
			$this,
			'biller_webhook_handler'
		) ) );
		add_filter( 'query_vars', array( $this, 'biller_query_vars_filter' ) );
		add_action( 'template_redirect', array( $this, 'biller_template_redirect' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_biller_payment_gateway' ) );
		add_filter( 'woocommerce_email_format_string', array( $this, 'biller_add_payment_link' ), 10, 4 );
		add_action( 'woocommerce_settings_api_sanitized_fields_' . self::BILLER_BUSINESS_INVOICE_ID, array(
			new Plugin_Options_Repository(),
			'before_settings_saved'
		) );
		add_action( 'add_meta_boxes', array( $this, 'add_biller_payment_box' ), 10, 2 );
		if ( is_multisite() ) {
			add_action( 'delete_blog', array( $this, 'uninstall_plugin_from_deleted_site' ) );
		}
		add_filter( 'post_updated_messages', array( $this, 'change_order_success_message' ), 20 );
		add_action( 'admin_notices', array( $this->notice_service, 'display' ) );

		$this->is_initialized = true;
	}

	/**
	 * Change order success message
	 *
	 * @param $messages
	 *
	 * @return array
	 */
	public function change_order_success_message( $messages ) {
		if ( $this->notice_service->has_errors() ) {
			/**
			 * When order is saved, success message is always shown.
			 * In order to stop WooCommerce from displaying success message
			 * if order save has failed, we have to unset that message.
			 *
			 * @see WC_Admin_Post_Types::post_updated_messages
			 */
			unset( $messages['shop_order'][1] );
		}

		return $messages;
	}

	public function allow_save_settings() {
		$gw = new Biller_Business_Invoice();

		return $gw->allow_save_settings();
	}

	/**
	 * Add payment link in email if placeholder is used
	 *
	 * @param $string
	 * @param $email
	 *
	 * @return array|string|string[]
	 */
	public function biller_add_payment_link( $string, $email ) {
		$placeholder = '{biller_payment_link}'; // The corresponding placeholder to be used
		/**
		 * WC Order
		 *
		 * @var WC_Order $order
		 */
		$order = $email->object; // Get the instance of the WC_Order Object

		// Generate a payment link for that order
		$payment_link = Shop_Helper::get_payment_link_url( $order->get_id() );

		// Insert the payment link in place of the "{biller_payment_link}" placeholder
		return str_replace( $placeholder, $payment_link, $string );
	}

	/**
	 * Switches to site with provided ID and removes plugin from that site.
	 *
	 * @param int $site_id Site identifier.
	 */
	private function switch_to_site_and_uninstall_plugin( $site_id ) {
		switch_to_blog( $site_id );
		$this->uninstall_plugin_from_site();
		restore_current_blog();
	}

	/**
	 * Removes plugin tables and configuration from the current site.
	 */
	private function uninstall_plugin_from_site() {
		$installer = new Database( new Plugin_Options_Repository() );
		$installer->uninstall();
		delete_option( 'BILLER_SCHEMA_VERSION' );
		delete_option( self::get_option_name() );
	}
}
