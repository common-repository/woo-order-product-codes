<?php

class WOPC_Code extends WOPC_PostTypes_OrderCodes {
	public $cpt_name       = 'wopc_codes';
	public $cpt_meta_order = 'order_id';

	public function __construct() {

	}

	public function create_code_post( $codes, $order ) {
		foreach ( $codes as $code ) {
			$args = array(
				'post_title'  => $code,
				'post_type'   => $this->cpt_name,
				'post_status' => 'publish',
				'meta_input'  => array(
					$this->cpt_meta_order => $order->get_id(),
				),
			);
			$args = apply_filters( 'wopc_code_create_code_post_args', $args );
			wp_insert_post( $args );
		}
	}

	public function delete_code_post( $order_id ) {
		global $wopc;
		$query_args = array(
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => - 1,
			'post_type'      => $this->cpt_name,
			'meta_query'     => array(
				array(
					'key'     => $this->cpt_meta_order,
					'value'   => $order_id,
					'compare' => '=',
				),
			),
		);
		$query_args = apply_filters( 'wopc_code_delete_code_post_query_args', $query_args );

		$codes = new WP_Query( $query_args );
		if ( $codes->have_posts() ) {
			foreach ( $codes->posts as $code ) {
				wp_delete_post( $code );
			}
		}
		if ( 1 == $wopc->settings->__get( 'cancel_order_can_resend' ) ) {
			parent::delete_order_meta( $order_id );
		}
		wp_reset_postdata();
	}
}