<?php
add_action( 'wp_ajax_wopc_preview', 'func_wopc_preview_pattern' );
add_action( 'wp_ajax_wopc_search_products', 'func_wopc_search_products' );
add_action( 'wp_ajax_wopc_send_feedback', 'func_wopc_send_feedback' );

function func_wopc_preview_pattern() {
	global $wopc;
	$nonce    = filter_var( $_POST['nonce'], FILTER_SANITIZE_STRING );
	$pattern  = filter_var( $_POST['pattern'], FILTER_SANITIZE_STRING );
	$campaign = filter_var( $_POST['campaign'], FILTER_SANITIZE_STRING );
	if ( empty( $campaign ) ) {
		$campaign = 0;
	}

	$return = array( 'status' => 'done', 'response' => '' );

	if ( wp_verify_nonce( $nonce, 'wopc_preview_ajax' ) ) {
		$code       = new WOPC_Code_Generator( $pattern, $campaign );
		$valid_code = $code->valid_pattern;
		if ( true === $valid_code ) {
			$return['response'] = __( 'Preview', $wopc->domain ) . ': ' . $code->wopc_create_code();
		} else {
			$return['status']   = 'error';
			$return['response'] = implode( ' ', $valid_code );
		}

	} else {
		$return['status']   = 'error';
		$return['response'] = __( 'You are not authorized to access this link.', $wopc->domain );
	}
	echo json_encode( $return );
	wp_die();
}

function func_wopc_search_products() {
	global $wopc;
	$nonce = filter_var( $_POST['nonce'], FILTER_SANITIZE_STRING );
	$name  = filter_var( $_POST['name'], FILTER_SANITIZE_STRING );

	$return = array(
		'status'   => 'done',
		'response' => '',
	);

	if ( wp_verify_nonce( $nonce, 'wopc_nonce_' . $name ) ) {
		$search = filter_var( $_POST['term'], FILTER_SANITIZE_STRING );

		$return        = array(
			'status' => 'done',
			'items'  => array(),
		);
		$args          = array(
			'post_type'      => array( 'product' ),
			'posts_per_page' => - 1,
			's'              => $search,
		);
		$loop_products = new WP_Query( $args );

		if ( $loop_products->have_posts() ) {
			while ( $loop_products->have_posts() ) {
				$loop_products->the_post();
				$return['items'][] = array(
					'id'    => get_the_ID(),
					'value' => get_the_title(),
				);
			}
		}
		wp_reset_postdata();

	} else {
		$return = array(
			'status'   => 'error',
			'response' => __( 'You are not authorized to access this link.', $wopc->domain ),
		);
	}

	echo json_encode( $return );
	wp_die();
}

function func_wopc_send_feedback() {
	global $wopc;
	$return = array(
		'status'   => 'done',
		'response' => '',
	);

	$nonce   = filter_var( $_POST['nonce'], FILTER_SANITIZE_STRING );
	if ( wp_verify_nonce( $nonce, 'wopc_nonce_send_feedback' ) ) {
		$email   = filter_var( $_POST['email'], FILTER_SANITIZE_STRING );
		$name    = filter_var( $_POST['name'], FILTER_SANITIZE_STRING );
		$note    = filter_var( $_POST['note'], FILTER_SANITIZE_STRING );
		$message = filter_var( $_POST['message'], FILTER_SANITIZE_STRING );

		$headers = wopc_email_headers( $email, $name );
		$email   = '<strong>FROM: </strong> ' . $name . ' - ' . $email . '<br /><strong>NOTE: </strong> ' . $note . '<br /><strong>MESSAGE: </strong><br />' . $message;

		if ( ! wp_mail( $wopc->plugin_email, 'WOPC - Feedback', $email, $headers ) ) {
			$return = array(
				'status'   => 'error',
				'response' => __( 'Email could not be sent from server.', $wopc->domain ),
			);
		}
	} else {
		$return = array(
			'status'   => 'error',
			'response' => __( 'You are not authorized to access this link.', $wopc->domain ),
		);
	}

	echo json_encode( $return );
	wp_die();
}