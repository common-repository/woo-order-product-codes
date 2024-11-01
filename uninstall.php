<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once "woocommerce-order-product-codes.php";

if ( $wopc->settings->__get( 'delete_meta_uninstall' ) ){
	$temp = new WOPC_PostTypes_Campaign();
	$temp->remove_data();

	$temp = new WOPC_PostTypes_OrderCodes();
	$temp->remove_data();

	unset( $temp );

	$wopc->delete_orders_meta();
}

$wopc->settings->delete_settings();