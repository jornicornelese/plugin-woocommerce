<?php

namespace Biller\Repositories;

use Biller\Biller;
use Biller\BusinessLogic\Authorization\DTO\UserInfo;
use Biller\BusinessLogic\Integration\Authorization\UserInfoRepository;
use Biller\Domain\Exceptions\InvalidArgumentException;
use Biller\Gateways\Biller_Business_Invoice;
use RuntimeException;

/**
 * Class Plugin_Options_Repository
 *
 * @package Biller\Repositories
 */
class Plugin_Options_Repository implements UserInfoRepository {

	/**
	 * Provides current schema version.
	 *
	 * @NOTICE default version is 0.0.1 if version has not been previously set.
	 *
	 * @return string
	 */
	public function get_schema_version() {
		return get_option( 'BILLER_SCHEMA_VERSION', '0.0.1' );
	}

	/**
	 * Sets schema version.
	 *
	 * @param string $version
	 */
	public function set_schema_version( $version ) {
		update_option( 'BILLER_SCHEMA_VERSION', $version );
	}

	/**
	 * @inheritDoc
	 */
	public function saveUserInfo( UserInfo $userInfo ) {
		$gateway_settings = $this->get_biller_gateway_settings();
		$settings         = array_merge( isset( $gateway_settings ) ? $gateway_settings : [], [$userInfo->getMode() => $userInfo->toArray()] );
		$this->save_biller_gateway_settings( $settings );
	}

	/**
	 * @inheritDoc
	 * @throws InvalidArgumentException
	 */
	public function getActiveUserInfo() {
		$mode = $this->get_mode();

		$gateway_settings = $this->get_biller_gateway_settings_by_mode($mode);

		return UserInfo::fromArray( isset( $gateway_settings ) ? $gateway_settings : [] );
	}

	/**
	 * @param string $mode
	 *
	 * @return void
	 */
	public function saveMode( $mode ) {
		update_option( Biller_Business_Invoice::get_option_status_name(), $mode );
	}

	/**
	 * Save settings in options table
	 *
	 * @param array $settings
	 *
	 * @return bool
	 */
	public function save_biller_gateway_settings( array $settings ) {
		return update_option(
			Biller_Business_Invoice::get_option_name(),
			apply_filters( 'woocommerce_settings_api_sanitized_fields_' . Biller::BILLER_BUSINESS_INVOICE_ID, $settings ),
			'yes' );

	}

	/**
	 * Apply password encryption and merge saved settings with the provided ones
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	public function before_settings_saved( array $settings ) {
		$gateway_settings = $this->get_biller_gateway_settings();
		$settings = array_merge( isset( $gateway_settings ) ? $gateway_settings : [], $settings );

		foreach ( $settings as $key => $setting ) {
			$settings[$key] = $this->encrypt( $setting );
		}

		return $settings;
	}

	/**
	 * Get biller gateway settings
	 *
	 * @return false|mixed|void
	 */
	public function get_biller_gateway_settings() {
		$settings = get_option( Biller_Business_Invoice::get_option_name(), null );
		if ( $settings ) {
			$settings = $this->decrypt( $settings );
		}

		return $settings;
	}

	public function get_biller_gateway_settings_by_mode( $mode ) {
		$settings = $this->get_biller_gateway_settings();

		return array_key_exists($mode, $settings) ? $settings[$mode] : [];
	}

	/**
	 * @return string|null
	 */
	public function get_mode() {
		return get_option( Biller_Business_Invoice::get_option_status_name(), null );
	}

	/**
	 * Encrypt password
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	private function encrypt( $settings ) {
		if(!is_array($settings) || !array_key_exists('password', $settings)) {
			return $settings;
		}

		foreach ( openssl_get_cipher_methods() as $cypher_method ) {
			$iv_length     = openssl_cipher_iv_length( $cypher_method );
			$encryption_iv = openssl_random_pseudo_bytes( $iv_length, $string_result );
			if ( false !== $encryption_iv && $string_result !== false ) {
				$ciphertext = openssl_encrypt( $settings['password'], $cypher_method, AUTH_KEY, 0, $encryption_iv );

				return array_merge( $settings, [
						'password'       => $ciphertext,
						'encryption_iv'  => bin2hex( $encryption_iv ),
						'cypher_method'  => $cypher_method,
					]
				);
			}
		}

		throw new RuntimeException( 'Encrypting password failed.' );
	}

	/**
	 * Decrypt password
	 *
	 * @param array $settings
	 *
	 * @return array
	 */
	private function decrypt( array $settings ) {
		foreach ( $settings as &$setting ) {
			if(is_array($setting) && array_key_exists('password', $setting)) {
				$setting['password'] = openssl_decrypt(
					$setting['password'],
					$setting['cypher_method'],
					AUTH_KEY,
					0,
					hex2bin( $setting['encryption_iv'] )
				);
			}
		}

		return $settings;
	}

}