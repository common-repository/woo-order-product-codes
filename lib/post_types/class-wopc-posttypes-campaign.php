<?php

class WOPC_PostTypes_Campaign extends WOPC {
	public $cpt_name = 'wopc_campaign';
	public $cpt_meta = 'wopc_campaign_meta';
	public $cpt_meta_products = 'products';
	private $fields = array();

	public function __construct() {
		add_action( 'init', array( $this, 'add_post_type_wopc' ), 0 );

		// META BOXES
		$this->add_fields();
		add_action( 'add_meta_boxes', array( $this, 'add_post_type_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'meta_boxes_save' ), 10, 2 );

		// REMOVE QUICK EDIT
		add_filter( 'post_row_actions', array( $this, 'wopc_camapign_edit_listing_actions' ) );
	}

	public function get_fields() {
		$fields = array(
			'products'      => array(
				'id'           => 'products',
				'name'         => 'products',
				'type'         => 'select_autocomplete',
				'title'        => 'Applied products',
				'default'      => '',
				'hidden_class' => '',
				'attr_hidden'  => array(),
				'class_input'  => 'posts_list with_icons',
				'attr_input'   => array(
					'data-attr'        => 'products',
					'data-action_ajax' => 'wopc_search_products',
				),
				'html_after'   => array( 'function' => 'wopc_products_tags', 'args' => 'products' ),
			),
			'code_how_many' => array(
				'id'      => 'code_how_many',
				'name'    => 'code_how_many',
				'type'    => 'select',
				'title'   => 'How many codes to send on order',
				'default' => 'fixed_codes',
				'values'  => array(
					'fixed_codes'    => __( 'Fixed number of codes per order', $this->domain ),
					'variable_codes' => __( 'Variable number of codes per order', $this->domain ),
				),
			),
			'code_fixed'    => array(
				'id'         => 'code_fixed',
				'name'       => 'code_fixed',
				'type'       => 'text',
				'title'      => 'Fixed number of codes per order',
				'default'    => '1',
				'attr_input' => array(
					'min' => '1',
					'max' => '10',
				),
			),
			'code_variable' => array(
				'id'         => 'code_variable',
				'name'       => 'code_variable',
				'type'       => 'text',
				'title'      => '1 Code every x value from order total',
				'default'    => '10',
				'attr_input' => array(
					'step' => '1',
					'min'  => '1',
					'max'  => '999999999',
				),
			),
			'code_pattern'  => array(
				'id'          => 'code_pattern',
				'name'        => 'code_pattern',
				'type'        => 'text',
				'title'       => 'Code Pattern',
				'default'     => '[a:1-3][C:YOUR FIXED CHARS][n:4][lL:4][n:1-3]',
				'html_after'  => '<div id="wopc_pattern_preview">' . __( 'Preview' ) . ': ------------------</div>',
				'description' => __( 'Example: [a:1-3][C:YOUR FIXED CHARS][n:4][lL:4][n:1-3]<br /><br />Usage: Inside you need structure like: <strong>[type:length]</strong><br /><br />Type can be:<br />l: lower case letters<br />L: upper case letters<br />c: special characters<br />n: numbers<br />a: all above<br />C: your choosen string<br /><br /><strong>Type identifiers</strong> can be combined. Example: <strong>nL</strong><br /><br /><strong>Length</strong> can be <strong>fixed number</strong> or choose <strong>random from a range</strong>. Example: <strong>2</strong> OR <strong>9</strong> OR <strong>1-10</strong>', $this->domain ),
			),
			'email_from'    => array(
				'id'      => 'email_from',
				'name'    => 'email_from',
				'type'    => 'text',
				'title'   => 'Email From',
				'default' => '{{site_email}}',
			),
			'email_name'    => array(
				'id'      => 'email_name',
				'name'    => 'email_name',
				'type'    => 'text',
				'title'   => 'Email Name',
				'default' => '{{site_name}}',
			),
			'email_subject' => array(
				'id'      => 'email_subject',
				'name'    => 'email_subject',
				'type'    => 'text',
				'title'   => 'Email Subject',
				'default' => 'Campaign {{campaign_name}}',
			),
			'email_message' => array(
				'id'          => 'email_message',
				'name'        => 'email_message',
				'type'        => 'textarea',
				'title'       => 'Email Message',
				'default'     => 'Your codes for order #{{order_id}} from {{order_date}} are:<br /><br />{{codes}}',
				'description' => __( 'Email message that will be sent foreach order.<br />HTML is allowed.<br /><br />Use:<br /><strong>{{site_name}}</strong> -  insert site name<br /><strong>{{site_email}}</strong> -  insert site email address<br /><strong>{{codes}}</strong> -  insert code(s) list<br /><strong>{{order_id}}</strong> - order id<br /><strong>{{order_date}}</strong> - date of order<br /><strong>{{campaign_name}}</strong> - campaign name', $this->domain ),
			),
		);

		return apply_filters( 'wopc_posttype_campaing_fields', $fields );
	}

	public function add_fields() {
		$this->fields = $this->get_fields();
	}

	public function add_post_type_wopc() {

		$labels = array(
			'name'                  => _x( 'Campaigns', 'Post Type Campaign General Name', $this->domain ),
			'singular_name'         => _x( 'Campaign', 'Post Type Campaign Singular Name', $this->domain ),
			'menu_name'             => _x( 'Campaigns', 'Post Type Campaign Menu Name', $this->domain ),
			'name_admin_bar'        => _x( 'Campaigns', 'Post Type Campaign Admin Bar', $this->domain ),
			'archives'              => __( 'Archive Campaigns', $this->domain ),
			'attributes'            => __( 'Campaign Attributes', $this->domain ),
			'parent_item_colon'     => __( 'Parent Campaign:', $this->domain ),
			'all_items'             => __( 'All Campaigns', $this->domain ),
			'add_new_item'          => __( 'Add New Campaign', $this->domain ),
			'add_new'               => __( 'Add New', $this->domain ),
			'new_item'              => __( 'New Campaign', $this->domain ),
			'edit_item'             => __( 'Edit Campaign', $this->domain ),
			'update_item'           => __( 'Update Campaigns', $this->domain ),
			'view_item'             => __( 'View all Campaigns', $this->domain ),
			'view_items'            => __( 'View Campaigns', $this->domain ),
			'search_items'          => __( 'Search Campaigns', $this->domain ),
			'not_found'             => __( 'Not found', $this->domain ),
			'not_found_in_trash'    => __( 'Not found in Trash', $this->domain ),
			'featured_image'        => __( 'Featured Image', $this->domain ),
			'set_featured_image'    => __( 'Set featured image', $this->domain ),
			'remove_featured_image' => __( 'Remove featured image', $this->domain ),
			'use_featured_image'    => __( 'Use as featured image', $this->domain ),
			'insert_into_item'      => __( 'Insert into Campaigns', $this->domain ),
			'uploaded_to_this_item' => __( 'Uploaded to this Code', $this->domain ),
			'items_list'            => __( 'Campaigns list', $this->domain ),
			'items_list_navigation' => __( 'Campaigns list navigation', $this->domain ),
			'filter_items_list'     => __( 'Filter items list', $this->domain ),
		);

		$args = array(
			'label'               => __( 'Campaign', $this->domain ),
			'description'         => __( 'Post type for WOPC Campaigns', $this->domain ),
			'labels'              => $labels,
			'supports'            => array( 'title' ),
			'taxonomies'          => array( 'post_tag' ),
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-welcome-view-site',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
		);

		$args = apply_filters( 'wopc_posttype_campaign_register', $args );

		register_post_type( $this->cpt_name, $args );

	}

	// META BOX
	public function add_post_type_meta_boxes() {
		$screens = array( $this->cpt_name );
		$screens = apply_filters( 'wopc_posttype_campaign_meta_boxes_screen', $screens );
		foreach ( $screens as $screen ) {
			add_meta_box(
				$this->cpt_meta,
				$this->module_title_short . '-' . __( 'Campaign Settings', $this->domain ),
				array( $this, 'meta_boxes_html' ),
				$screen
			);
		}
	}

	public function meta_boxes_html( $post ) {
		wp_nonce_field( basename( __FILE__ ), 'wopc_nonce' );
		wp_nonce_field( 'wopc_preview_ajax', 'wopc_nonce_preview' );
		//$html = '<div id="wopc_meta_campaign">';

		$html = '';
		//echo '<pre>'; print_r( $this->fields ); echo '</pre>';
		foreach ( $this->fields as $name => $field ) {
			$html_element = '';
			$html_element = apply_filters( 'wopc_campaign_meta_div_html_before', $html_element );
			$html_element = apply_filters( 'wopc_campaign_meta_div_' . $name . '_before_html', $html_element );

			$input_class = '';
			if ( isset( $field['input_class'] ) ) {
				$input_class = $field['input_class'];
			}

			$value = get_post_meta( $post->ID, $name, true );
			if ( '' == $value ) {
				$value = $field['default'];
			}
			$field['value'] = $value;

			if ( 'hidden' == $field['type'] ) {
				$html_element .= wopc_setting_html( $field, 'meta' );
			} else {
				//echo '<pre>'; print_r( $field ); echo '</pre>';

				$html_element .= '<div id="wopc_div_' . $name . '" class="wopc_meta_div wopc_div_' . $field['type'] . '">';

				$html_element .= '<label for="' . $name . '" class="wopc_main_label">' . __( $field['title'], $this->domain ) . '</label>';

				if ( isset( $field['html_before'] ) ) {
					$html_element .= $field['html_before'];
				}

				$html_element .= wopc_setting_html( $field, 'meta' );

				if ( isset( $field['html_after'] ) ) {
					if ( is_string( $field['html_after'] ) ) {
						$html_element .= $field['html_after'];
					} elseif ( is_array( $field['html_after'] ) ) {
						$value_arg = $value;
						if ( isset( $field['html_after']['args'] ) ) {
							$value_arg = get_post_meta( $post->ID, $field['html_after']['args'], true );
						}
						$html_element .= $field['html_after']['function']( $value_arg );
					}
				}

				if ( isset( $field['description'] ) ) {
					$description_html = '<br /><div class="wopc_element_description">%s</div>';
					$description_html = apply_filters( 'wopc_posttype_campaign_meta_boxes_html_input_description', $description_html );
					$html_element     .= str_replace( '%s', $field['description'], $description_html );
				}
				$html_element .= '</div>'; // class="wopc_meta_div"
			}

			$html_element = apply_filters( 'wopc_campaign_meta_div_' . $name . '_after_html', $html_element );
			$html_element = apply_filters( 'wopc_campaign_meta_div_html_after', $html_element );
			$html         .= $html_element;
		}
		//$html .= '</div>'; // id="wopc_meta_campaign"

		echo $html;
	}

	public function meta_boxes_save( $post_id, $post ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( 'revision' === $post->post_type ) {
			return;
		}

		if ( $this->cpt_name === $post->post_type ) {

			if ( ! wp_verify_nonce( $_POST['wopc_nonce'], basename( __FILE__ ) ) ) {
				return $post_id;
			}

			do_action( 'wopc_campaign_before_meta_save', $this->fields, $_POST, $post_id );

			foreach ( $this->fields as $name => $field ) {
				if ( isset( $field['do_not_save'] ) && true == $field['do_not_save'] ) {
					continue;
				} else {
					if ( get_post_meta( $post_id, $name, false ) ) {
						update_post_meta( $post_id, $name, $_POST[ $name ] );
					} else {
						add_post_meta( $post_id, $name, $_POST[ $name ] );
					}
				}
			}

			do_action( 'wopc_campaign_after_meta_save', $_POST, $post_id );
		}
	}

	// POST TYPE LISTING
	public function wopc_is_post_list_admin() {
		global $pagenow;
		$return_val = false;
		if ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && $this->cpt_name === $_GET['post_type'] ) {
			$return_val = true;
		}

		return $return_val;
	}

	public function wopc_camapign_edit_listing_actions( $actions ) {
		if ( $this->wopc_is_post_list_admin() ) {
			if ( isset( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}

			$actions = apply_filters( 'wopc_campaign_actions_filter', $actions );
		}

		do_action( 'wopc_campaign_actions_action', $actions );

		return $actions;
	}

	// REMOVE META
	public function remove_data() {
		global $wpdb;
		$sql = "DELETE post,relation,meta FROM wp_posts post LEFT JOIN wp_term_relationships relation ON (post.ID = relation.object_id) LEFT JOIN wp_postmeta meta ON (post.ID = meta.post_id) WHERE post.post_type = '" . $this->cpt_name . "'";
		$wpdb->query( $sql );
	}
}
