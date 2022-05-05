<?php

namespace Biller\Utility;

use Biller\Biller;

/**
 * Class Shop_Helper
 *
 * @package Biller\Utility
 */
class Shop_Helper {
	/**
	 * The version of the plugin.
	 *
	 * @var string
	 */
	private static $plugin_version;

	/**
	 * Returns whether Biller plugin is enabled.
	 */
	public static function is_plugin_enabled() {
		if ( self::is_plugin_active_for_network() ) {
			return true;
		}

		return self::is_plugin_active_for_current_site();
	}

	/**
	 * Checks if WooCommerce is active in the shop.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return self::is_plugin_active( 'woocommerce.php' );
	}

	/**
	 * Returns if Biller plugin is active through network.
	 *
	 * @return bool
	 */
	public static function is_plugin_active_for_network() {
		if ( ! is_multisite() ) {
			return false;
		}

		$plugins = get_site_option( 'active_sitewide_plugins' );

		return isset( $plugins[ self::get_plugin_name() ] );
	}

	/**
	 * Returns if Biller plugin is active for current site.
	 *
	 * @return bool
	 */
	public static function is_plugin_active_for_current_site() {
		return in_array(
			self::get_plugin_name(),
			apply_filters( 'active_plugins', get_option( 'active_plugins' ) ),
			true
		);
	}

	/**
	 * Returns tha name of the plugin.
	 *
	 * @return string
	 */
	public static function get_plugin_name() {
		return plugin_basename(dirname(dirname(__DIR__)) . '/biller-business-invoice.php');
	}

	/**
	 * Gets URL to Biller plugin root folder.
	 *
	 * @return string
	 */
	public static function get_plugin_base_url() {
		return plugins_url( '/', dirname( __DIR__ ) );
	}

	/**
	 * Gets URL to Biller plugin root folder.
	 *
	 * @return string
	 */
	public static function get_settings_url() {
		return get_site_url() . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=biller_business_invoice';
	}

	/**
	 * Gets plugin resources path.
	 *
	 * @param string $dir
	 *
	 * @return string
	 */
	public static function get_plugin_resources_path( $dir ) {
		return plugin_dir_url( $dir );
	}

	/**
	 * Returns plugin current version.
	 *
	 * @return string
	 */
	public static function get_plugin_version() {
		if ( ! self::$plugin_version ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . self::get_plugin_name() );

			self::$plugin_version = $plugin_data['Version'];
		}

		return self::$plugin_version;
	}

	/**
	 * Checks if plugin is active.
	 *
	 * @param string $plugin_name The name of the plugin main entry point file.
	 *
	 * @return bool
	 */
	public static function is_plugin_active( $plugin_name ) {
		$all_plugins = get_option( 'active_plugins' );

		if ( is_multisite() ) {
			$all_plugins = array_merge( $all_plugins, array_keys( get_site_option( 'active_sitewide_plugins' ) ) );
		}

		foreach ( $all_plugins as $plugin ) {
			if ( false !== strpos( $plugin, '/' . $plugin_name ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Gets the name of the shop
	 *
	 * @return string
	 */
	public static function get_shop_name() {
		$name = get_bloginfo( 'name' );

		return $name ?: '';
	}

	/**
	 * Gets url of Biller controller
	 *
	 * @param string $name
	 * @param string $action
	 * @param array $params
	 *
	 * @return string
	 */
	public static function get_controller_url( $name, $action = '', array $params = array() ) {
		$query = array( 'biller_wc_controller' => $name );
		if ( ! empty( $action ) ) {
			$query['action'] = $action;
		}

		$query = array_merge( $query, $params );

		return get_site_url() . '/?' . http_build_query( $query );
	}

	/**
	 * Generates the url for the pay,ent link for a given order id
	 *
	 * @param int $order_id
	 *
	 * @return string
	 */
	public static function get_payment_link_url( $order_id ) {
		return Shop_Helper::get_controller_url(
			'Payment_Redirection',
			'payment_redirect',
			[
				'order_id' => $order_id,
				'token'    => wp_hash_password( Shop_Helper::get_raw_token($order_id) )
			]
		);
	}

	/**
	 * @param int $order_id
	 *
	 * @return string
	 */
	public static function get_raw_token( $order_id ) {
		return Biller::INTEGRATION_NAME . $order_id . AUTH_KEY;
	}
}