<?php

namespace Biller\Utility;

use Biller\Biller;
use RuntimeException;

class View {
	const VIEW_FOLDER_PATH = '/resources/views';

	/**
	 * File path
	 *
	 * @var string
	 */
	private $file;

	private function __construct( $file ) {
		$this->file = $file;
	}

	/**
	 * Returns view instance if the provided file exists.
	 *
	 * @param $view_name
	 *
	 * @return View
	 */
	public static function file( $view_name ) {
		$file = Biller::get_plugin_dir_path() . self::VIEW_FOLDER_PATH . $view_name;
		if ( file_exists( $file ) ) {
			return new self( $file );
		}

		throw new RuntimeException( "Could not find specified view file: $view_name" );
	}

	/**
	 * Render page.
	 *
	 * @param array $data
	 *
	 * @return false|string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function render( $data = array() ) {
		ob_start();

		require $this->file;

		return ob_get_clean();
	}

	/**
	 * Get allowed HTML tags
	 *
	 * @return array
	 */
	public static function get_allowed_tags() {
		return array(
			'a'          => array(
				'class'  => array(),
				'href'   => array(),
				'rel'    => array(),
				'title'  => array(),
				'target' => array()
			),
			'abbr'       => array(
				'title' => array(),
			),
			'b'          => array(),
			'blockquote' => array(
				'cite' => array(),
			),
			'br'         => array(),
			'button'     => array(
				'class'    => array(),
				'id'       => array(),
				'disabled' => array(),
				'type' => array(),
			),
			'cite'       => array(
				'title' => array(),
			),
			'code'       => array(),
			'del'        => array(
				'datetime' => array(),
				'title'    => array(),
			),
			'dd'         => array(),
			'div'        => array(
				'class' => array(),
				'id'    => array(),
				'title' => array(),
				'style' => array(),
			),
			'dl'         => array(),
			'dt'         => array(),
			'em'         => array(),
			'h1'         => array(),
			'h2'         => array(),
			'h3'         => array(),
			'h4'         => array(),
			'h5'         => array(),
			'h6'         => array(),
			'hr'         => array(
				'class' => array()
			),
			'i'          => array(
				'class' => array()
			),
			'img'        => array(
				'alt'    => array(),
				'class'  => array(),
				'height' => array(),
				'src'    => array(),
				'width'  => array(),
			),
			'input'      => array(
				'id'    => array(),
				'class'  => array(),
				'name' => array(),
				'value'    => array(),
				'type'  => array(),
				'readonly' => array(),
			),
			'li'         => array(
				'class' => array(),
			),
			'label' => array(
				'class' => array(),
				'for'=> array(),
				'id' => array(),

			),
			'ol'         => array(
				'class' => array(),
			),
			'p'          => array(
				'class' => array(),
			),
			'path'       => array(
				'fill'            => array(),
				'd'               => array(),
				'class'           => array(),
				'data-v-19c3f3ae' => array()
			),
			'q'          => array(
				'cite'  => array(),
				'title' => array(),
			),
			'script'     => array(
				'type' => array(),
				'id'   => array(),
			),
			'span'       => array(
				'class'       => array(),
				'title'       => array(),
				'style'       => array(),
				'data-tip'    => array(),
				'data-target' => array(),
			),
			'strike'     => array(),
			'strong'     => array(),
			'svg'        => array(
				'aria-hidden'     => array(),
				'focusable'       => array(),
				'data-prefix'     => array(),
				'data-icon'       => array(),
				'role'            => array(),
				'xmlns'           => array(),
				'viewbox'         => array(),
				'class'           => array(),
				'data-v-19c3f3ae' => array(),
			),
			'table'      => array(
				'class' => array()
			),
			'tbody'      => array(
				'class' => array()
			),
			'thead'      => array(
				'class' => array()
			),
			'tr'         => array(
				'class'     => array(),
				'data-name' => array(),
			),
			'td'         => array(
				'class'   => array(),
				'colspan' => array(),
			),
			'ul'         => array(
				'id'    => array(),
				'class' => array(),
			),
		);
	}
}
