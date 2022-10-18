<?php

use Biller\Biller;
use Biller\Components\Bootstrap_Component;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

global $wpdb;

Bootstrap_Component::init();

$biller = Biller::init( __FILE__ );
$biller->uninstall();
