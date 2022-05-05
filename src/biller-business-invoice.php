<?php
/*
Plugin Name: Biller Business Invoice
Description: The payment solution that advances both sides. We pay out every invoice on time. And buyers get to choose Buy Now, Pay Later.
Version: 1.0.0
Author: Biller
*/

use Biller\Biller;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once plugin_dir_path( __FILE__ ) . '/vendor/autoload.php';

Biller::init( __FILE__ );