<?php

class WOPC_Campaigns extends WOPC_PostTypes_Campaign {
	function __construct() {

	}

	public function get_all_campaigns() {
		return $this->get_campaigns( 'all' );
	}

	public function get_campaigns( $filter = 'all', $args = '' ) {
		$return = false;
		switch ( $filter ) {
			case 'products':
				$query_args = array(
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => - 1,
					'post_type'      => $this->cpt_name,
					'meta_query'     => array(
						'relation' => 'OR',
					),
				);
				foreach ( $args as $product ) {
					$query_args['meta_query'][] = array(
						'key'     => $this->cpt_meta_products,
						'value'   => $product,
						'compare' => 'find_in_set',
					);
				}
				$campaings = new WP_Query( $query_args );
				if ( $campaings->have_posts() ) {
					$return = $campaings->posts;
				}
				wp_reset_postdata();
				break;
			case 'all':

				break;
			default:
				do_action( 'wopc_campaigns_get_campaigns_new_filters', $filter, $args );
		}
		$return = apply_filters( 'wopc_campaigns_get_campaigns_return', $return );

		return $return;
	}
}
