<?php

class WOPC_PostTypes_OrderCodes extends WOPC {
	public $cpt_name       = 'wopc_codes';
	public $cpt_meta_order = 'order_id';

	public function __construct() {
		add_action( 'init', array( $this, 'add_post_type_wopc' ), 0 );

		add_filter( 'manage_wopc_codes_posts_columns', array( $this, 'wopc_code_columns' ) );
		add_action( 'manage_wopc_codes_posts_custom_column', array( $this, 'wopc_code_custom_column' ), 10, 2 );
		add_filter( 'manage_edit-wopc_codes_sortable_columns', array( $this, 'wopc_code_sortable_columns' ) );
		add_action( 'pre_get_posts', array( $this, 'wopc_code_orderby_columns' ) );

		add_filter( 'post_row_actions', array( $this, 'wopc_code_edit_listing_actions' ) );
		add_filter( 'page_row_actions', array( $this, 'wopc_code_edit_listing_actions' ) );

	}

	public function add_post_type_wopc() {

		$labels = array(
			'name'                  => _x( 'Order Codes', 'Post Type Order Code General Name', $this->domain ),
			'singular_name'         => _x( 'Order Code', 'Post Type Order Code Singular Name', $this->domain ),
			'menu_name'             => _x( 'Order Codes', 'Post Type Order Code Menu Name', $this->domain ),
			'name_admin_bar'        => _x( 'Order Codes', 'Post Type Order Code Admin Bar', $this->domain ),
			'archives'              => __( 'Archive Order Codes', $this->domain ),
			'attributes'            => __( 'Item Attributes', $this->domain ),
			'parent_item_colon'     => __( 'Parent Item:', $this->domain ),
			'all_items'             => __( 'All Codes', $this->domain ),
			'add_new_item'          => __( 'Add New Code', $this->domain ),
			'add_new'               => __( 'Add New', $this->domain ),
			'new_item'              => __( 'New Code', $this->domain ),
			'edit_item'             => __( 'Edit Code', $this->domain ),
			'update_item'           => __( 'Update Codes', $this->domain ),
			'view_item'             => __( 'View all Codes', $this->domain ),
			'view_items'            => __( 'View Codes', $this->domain ),
			'search_items'          => __( 'Search Codes', $this->domain ),
			'not_found'             => __( 'Not found', $this->domain ),
			'not_found_in_trash'    => __( 'Not found in Trash', $this->domain ),
			'featured_image'        => __( 'Featured Image', $this->domain ),
			'set_featured_image'    => __( 'Set featured image', $this->domain ),
			'remove_featured_image' => __( 'Remove featured image', $this->domain ),
			'use_featured_image'    => __( 'Use as featured image', $this->domain ),
			'insert_into_item'      => __( 'Insert into Codes', $this->domain ),
			'uploaded_to_this_item' => __( 'Uploaded to this Code', $this->domain ),
			'items_list'            => __( 'Codes list', $this->domain ),
			'items_list_navigation' => __( 'Codes list navigation', $this->domain ),
			'filter_items_list'     => __( 'Filter items list', $this->domain ),
		);

		$args = array(
			'label'               => __( 'Code', $this->domain ),
			'description'         => __( 'Post type for WOPC Order codes', $this->domain ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-admin-network',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capabilities'        => array(
				'edit_post'          => 'wopc_cap_code_edit',
				'read_post'          => 'wopc_cap_code_read',
				'delete_post'        => 'wopc_cap_code_delete',
				//'edit_posts'         => 'wopc_cap_code_edit_more',
				'edit_others_posts'  => 'wopc_cap_code_edit_other',
				'publish_posts'      => 'wopc_cap_code_publish',
				'read_private_posts' => 'wopc_cap_code_read_private',
				'create_posts'       => 'wopc_cap_code_create_more',
			),
			'map_meta_cap'        => true,
		);

		$args = apply_filters( 'wopc_posttype_ordercodes_register', $args );

		register_post_type( $this->cpt_name, $args );

	}

	public function wopc_is_post_list_admin() {
		global $pagenow;
		$return_val = false;
		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && $this->cpt_name === $_GET['post_type'] ) {
			$return_val = true;
		}

		return $return_val;
	}

	public function wopc_code_columns( $columns ) {
		if ( $this->wopc_is_post_list_admin() ) {
			$add_columns['wopc_code_orderid'] = __( 'Order', $this->domain );

			array_splice( $columns, count( $columns ) - 1, 0, $add_columns );

			$columns = apply_filters( 'wopc_ordercodes_columns_filter', $columns );
		}

		do_action( 'wopc_ordercodes_columns_action', $columns );

		return $columns;
	}

	public function wopc_code_custom_column( $column, $post_id ) {
		if ( $this->wopc_is_post_list_admin() ) {
			//$post_data = get_post( $post_id );
			switch ( $column ) {
				case 'wopc_code_orderid':
					echo '<div style="text-align: center;">';
					$order_id = get_post_meta( $post_id, $this->cpt_meta_order, true );
					$order    = new WC_Order( $order_id );
					if ( false != $order ) {
						echo '<a href="' . get_edit_post_link( $order_id ) . '">' . __( 'Order #', $this->domain ) . $order_id . ' - ' . $order->get_formatted_billing_full_name() . '<a>';
					} else {
						echo __( 'Order deleted', $this->domain );
					}
					echo '</div>';
					break;
			}
			$column = apply_filters( 'wopc_ordercodes_customcolumn_filter', $column, $post_id );
		}

		do_action( 'wopc_ordercodes_customcolumn_action', $column, $post_id );
	}

	public function wopc_code_sortable_columns( $columns ) {
		if ( $this->wopc_is_post_list_admin() ) {
			$columns['wopc_code_orderid'] = 'wopc_code_orderid';

			$columns = apply_filters( 'wopc_ordercodes_sortable_filter', $columns );
		}

		do_action( 'wopc_ordercodes_sortable_action', $columns );

		return $columns;
	}

	public function wopc_code_orderby_columns( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		if ( $this->wopc_is_post_list_admin() ) {
			if ( 'wopc_code_orderid' === $query->get( 'orderby' ) ) {
				$query->set( 'orderby', 'meta_value' );
				$query->set( 'meta_key', $this->cpt_meta_order );
				$query->set( 'meta_type', 'numeric' );
			}

			do_action( 'wopc_ordercodes_orderby_action', $query );
		} else {
			return false;
		}
	}

	public function wopc_code_edit_listing_actions( $actions ) {
		if ( $this->wopc_is_post_list_admin() ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			if ( isset( $actions['edit'] ) ) {
				unset( $actions['edit'] );
			}

			$actions = apply_filters( 'wopc_ordercodes_actions_filter', $actions );
		}

		do_action( 'wopc_ordercodes_actions_action', $actions );
		return $actions;
	}

	// REMOVE META
	public function remove_data() {
		global $wpdb;
		$sql = "DELETE post,relation,meta FROM wp_posts post LEFT JOIN wp_term_relationships relation ON (post.ID = relation.object_id) LEFT JOIN wp_postmeta meta ON (post.ID = meta.post_id) WHERE post.post_type = '" . $this->cpt_name . "'";
		$wpdb->query( $sql );
	}
}
