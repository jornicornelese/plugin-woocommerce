<?php

namespace Biller\Components\Services;

use Biller\DTO\Notice;

class Notice_Service {

	const OPTION_FIELD_NAME = '_biller_notices';

	const SUCCESS_TYPE = 'success';
	const ERROR_TYPE = 'error';
	const INFO_TYPE = 'info';

	/**
	 * Push new notice
	 *
	 * @param Notice $notice
	 *
	 * @return void
	 */
	public function push( Notice $notice ) {
		$notifications = $this->get_notifications();
		if ( $this->already_exists( $notifications, $notice ) ) {
			return;
		}
		$notifications[] = $notice;
		update_option( self::OPTION_FIELD_NAME, Notice::toBatch( $notifications ) );
	}

	/**
	 * Display all notices
	 *
	 * @return void
	 */
	public function display() {
		$notifications = $this->get_notifications();
		foreach ( $notifications as $notification ) {
			$is_dismissible = $notification->isDismissible() ? 'is-dismissible' : '';
			echo '<div class="notice notice-' . $notification->getType() . ' . ' . $is_dismissible . '"><p>' .
			     __( $notification->getMessage(), 'biller' ) . '</p></div>';
		}
		delete_option( self::OPTION_FIELD_NAME );
	}

	/**
	 * Get all notices
	 *
	 * @return Notice[]
	 */
	public function get_notifications() {
		return Notice::fromBatch( get_option( self::OPTION_FIELD_NAME ) ?: [] );
	}

	/**
	 * Check if there are some error notices
	 *
	 * @return bool|void
	 */
	public function has_errors() {
		$notifications = $this->get_notifications();
		foreach ( $notifications as $notification ) {
			if ( $notification->getType() === self::ERROR_TYPE ) {
				return true;
			}
		}
	}

	/**
	 * Check if notification already exists
	 *
	 * @param Notice[] $notifications
	 * @param Notice $new_notice
	 *
	 * @return bool
	 */
	private function already_exists( array $notifications, Notice $new_notice ) {
		$already_exists = false;
		foreach ( $notifications as $notification ) {
			if ( $notification->getMessage() === $new_notice->getMessage() ) {
				$already_exists = true;
			}
		}

		return $already_exists;
	}
}