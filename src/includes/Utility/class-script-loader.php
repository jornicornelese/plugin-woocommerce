<?php

namespace Biller\Utility;

class Script_Loader {

	/**
	 * Loads javascript files to template rendering.
	 *
	 * @param array $scripts
	 * @param false $in_footer
	 */
	public static function load_js( $scripts, $in_footer = false ) {
		self::load( $scripts, $in_footer, true );
	}

	/**
	 * Loads CSS files to template rendering.
	 *
	 * @param array $scripts
	 */
	public static function load_css( $scripts ) {
		self::load( $scripts );
	}

	/**
	 * Loads files to template rendering.
	 *
	 * @param $files
	 * @param false $in_footer
	 * @param false $is_js
	 */
	private static function load( $files, $in_footer = false, $is_js = false ) {
		$base_url = Shop_Helper::get_plugin_base_url() . 'resources/';
		$version  = Shop_Helper::get_plugin_version();
		foreach ( $files as $file_path ) {
			$file_path = ltrim($file_path, '/');
			$name = substr( $file_path, strrpos( '/', $file_path ) );
			if ( $is_js ) {
				wp_enqueue_script( $name, $base_url . $file_path, array(), $version, $in_footer );
			} else {
				wp_enqueue_style( $name, $base_url . $file_path, array(), $version );
			}
		}
	}
}