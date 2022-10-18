<?php
/*
Plugin Name: Biller Business Invoice
Description: Biller is designed to optimally serve both the business seller and buyer. With Biller businesses buy now and pay later.
Version: 1.0.3
Author: Biller
*/

use Biller\Biller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

Biller::init( __FILE__ );
