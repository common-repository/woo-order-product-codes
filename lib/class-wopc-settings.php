<?php

class WOPC_Settings extends WOPC {
	public $is_setting_loaded = false;
	public $settings_name = 'wopc_settings';
	public $capacity = 'manage_options';
	public $settings_list = array(
		'enabled',
		'code_send',
		'code_cancel',
		'cancel_order_can_resend',
		'disable_notification',
		'delete_meta_uninstall',
	);
	public $settings_default = array(
		'enabled'                 => '',
		'code_send'               => 'wc-processing',
		'code_cancel'             => 'wc-pending,wc-cancelled,wc-refunded,wc-failed',
		'cancel_order_can_resend' => '',
		'disable_notification'    => '',
		'delete_meta_uninstall'   => '',
	);

	public $settings = array();

	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	protected function __clone() {
		//Me not like clones! Me smash clones!
	}

	public function __construct() {
		$this->get_all_settings();
	}

	// SETTINGS
	public function get_all_settings() {
		if ( $this->is_setting_loaded ) {
			return $this->settings;
		}

		foreach ( $this->settings_list as $setting ) {
			$name                    = $this->module_prefix . $setting;
			$this->settings[ $name ] = get_option( $name );
		}
		$this->is_setting_loaded = true;
		$this->settings          = apply_filters( 'wopc_settings_filter', $this->settings );

		return $this->settings;
	}

	public function __set( string $key, string $value ) {
		if ( array_key_exists( $key ) ) {
			$this->settings[ $this->module_prefix . $key ] = $value;
		} else {
			die( sprint_f( __( 'Setting value %s does not exist.' ), $this->module_prefix . $key ) );
		}
	}

	public function __get( string $key ) {
		if ( array_key_exists( $this->module_prefix . $key, $this->settings ) ) {
			return $this->settings[ $this->module_prefix . $key ];
		}

		return null;
	}

	public function __isset( string $key ) {
		return array_key_exists( $this->module_prefix . $key, $this->settings );
	}

	public function add_settings() {
		if ( get_option( $this->module_prefix . 'enabled' ) === false ) {
			foreach ( $this->settings_list as $setting ) {
				add_option( $this->module_prefix . $setting, $this->settings_default[ $setting ] );
			}
		}
	}

	public function delete_settings() {
		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ( $sites as $site_data ) {
				switch_to_blog( $site_data->blog_id );
				foreach ( $this->settings_list as $setting ) {
					delete_option( $this->module_prefix . $setting );
				}
				restore_current_blog();
			}
		} else {
			foreach ( $this->settings_list as $setting ) {
				delete_option( $this->module_prefix . $setting );
			}
		}
	}

	// ADMIN MENU
	public function add_menu() {
		add_action( 'admin_init', array( $this, 'add_form_fields' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_func' ) );
	}

	public function add_menu_func() {
		add_menu_page(
			__( 'Product Codes', $this->domain ),
			__( 'Product Codes settings', $this->domain ),
			$this->capacity,
			$this->settings_name,
			array( $this, 'wopc_menu_html' ),
			'dashicons-admin-links'
		);

		add_submenu_page(
			$this->settings_name,
			__( 'Feedback', $this->domain ),
			__( 'Feedback', $this->domain ),
			$this->capacity,
			$this->settings_name . '_feedback',
			array( $this, 'wopc_menu_feedback_html' )
		);
	}

	// FEEDBACK
	public function wopc_menu_feedback_html() {
		if ( ! current_user_can( $this->capacity ) ) {
			return;
		}

		$this->add_header();
		$this->add_form_feedback();
		$this->add_footer();
	}

	public function add_form_feedback() {
		echo '<p>' . __( 'Your feedback is important! Please leave us your feeling about the module.', $this->domain ) . '</p>';
		?>
        <div id="feedback_sent"
             class="feedback_hidden_div"><?php echo __( 'Feedback sent. Thank you!', $this->domain ); ?></div>
        <div id="feedback_not_sent"
             class="feedback_hidden_div"><?php echo __( 'Feedback could not be sent. Email is not working.', $this->domain ); ?></div>
        <form id="feedback_form" action="#">
			<?php echo wp_nonce_field( 'wopc_nonce_send_feedback', 'wopc_nonce_send_feedback', '', false ); ?>
            <label for="feedback_email"><?php echo __( 'Your email', $this->domain ); ?></label>
            <input type="email" id="feedback_email" required/>
            <br/>
            <label for="feedback_name"><?php echo __( 'Your name', $this->domain ); ?></label>
            <input type="text" id="feedback_name" required/>
            <br/>
            <label for="feedback_note"><?php echo __( 'Note the plugin experience(1-10)', $this->domain ); ?></label>
            <input type="number" min="1" max="10" value="5" id="feedback_note"/>
            <br/>
            <label for="feedback_message"><?php echo __( 'Your message', $this->domain ); ?></label>
            <textarea id="feedback_message"
                      placeholder="<?php echo __( 'Leave us a message about what can be improved or added', $this->domain ); ?>"
                      required></textarea>
            <br/>
            <br/>
            <input type="submit" id="feedback_send" value="<?php echo __( 'Send feedback', $this->domain ); ?>"/>
        </form>
		<?php
		echo '<br /><br /><span id="feedback_note_span">' . __( 'Your data will not be processed in any form.', $this->domain ) . '</span>';
	}


	// SETTINGS
	public function wopc_menu_html() {
		if ( ! current_user_can( $this->capacity ) ) {
			return;
		}

		if ( isset( $_GET[ $this->module_prefix . 'settings-updated' ] ) ) {
			add_settings_error( $this->module_prefix . 'redirect_messages', $this->module_prefix . 'redirect_messages', __( 'Settings Saved', $this->domain ), 'updated' );
		}

		// show error/update messages
		settings_errors( $this->module_prefix . 'redirect_messages' );

		$this->add_header();
		$this->add_form();
		$this->add_footer();
	}

	private function add_header() {
		?>
        <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php
	}

	public function add_form_fields() {

		add_settings_section(
			$this->settings_name,
			__( 'General Settings', $this->domain ),
			null,
			$this->settings_name
		);

		// ENABLED
		$this->add_field(
			$this->module_prefix . 'enabled',
			'Module functionality enabled',
			$this->settings_name,
			'integer',
			array(
				'type'          => 'checkbox_slider',
				'id'            => $this->module_prefix . 'enabled',
				'name'          => $this->module_prefix . 'enabled',
				'default'       => $this->settings_default['enabled'],
				'value'         => $this->__get( 'enabled' ),
				'checked_value' => 1,
			)
		);

		// SEND CODE ON STATUS ORDER
		$this->add_field(
			$this->module_prefix . 'code_send',
			'Send code on order status',
			$this->settings_name,
			'array',
			array(
				'type'        => 'select_multiple',
				'id'          => $this->module_prefix . 'code_send',
				'name'        => $this->module_prefix . 'code_send',
				'required'    => true,
				'default'     => $this->settings_default['code_send'],
				'value'       => $this->__get( 'code_send' ),
				'values'      => wc_get_order_statuses(),
				'description' => __( 'Select multiple status by holding CTRL. <strong>This will be applied for future orders.</strong>', $this->domain ),
			)
		);

		// CANCEL CODE ON STATUS ORDER
		$this->add_field(
			$this->module_prefix . 'code_cancel',
			'Cancel code on order status',
			$this->settings_name,
			'array',
			array(
				'type'        => 'select_multiple',
				'id'          => $this->module_prefix . 'code_cancel',
				'name'        => $this->module_prefix . 'code_cancel',
				'required'    => true,
				'default'     => $this->settings_default['code_cancel'],
				'value'       => $this->__get( 'code_cancel' ),
				'values'      => wc_get_order_statuses(),
				'description' => __( 'Select multiple status by holding CTRL. <strong>This will be applied for future orders.</strong>', $this->domain ),
			)
		);

		// CANCEL CODE ON STATUS ORDER
		$this->add_field(
			$this->module_prefix . 'cancel_order_can_resend',
			'Enable code resent',
			$this->settings_name,
			'integer',
			array(
				'type'          => 'checkbox_slider',
				'id'            => $this->module_prefix . 'cancel_order_can_resend',
				'name'          => $this->module_prefix . 'cancel_order_can_resend',
				'default'       => $this->settings_default['cancel_order_can_resend'],
				'value'         => $this->__get( 'cancel_order_can_resend' ),
				'checked_value' => 1,
				'description'   => __( 'After order status is set to a Cancel state, enable codes resend.', $this->domain ),
			)
		);

		// ADMIN NOTIFICATION
		$this->add_field(
			$this->module_prefix . 'disable_notification',
			'Disable admin notification',
			$this->settings_name,
			'integer',
			array(
				'type'          => 'checkbox_slider',
				'id'            => $this->module_prefix . 'disable_notification',
				'name'          => $this->module_prefix . 'disable_notification',
				'default'       => $this->settings_default['disable_notification'],
				'value'         => $this->__get( 'disable_notification' ),
				'checked_value' => 1,
			)
		);

		// DELETE META ON UNINSTALL
		$this->add_field(
			$this->module_prefix . 'delete_meta_uninstall',
			'On uninstall remove all data',
			$this->settings_name,
			'integer',
			array(
				'type'          => 'checkbox_slider',
				'id'            => $this->module_prefix . 'delete_meta_uninstall',
				'name'          => $this->module_prefix . 'delete_meta_uninstall',
				'default'       => $this->settings_default['delete_meta_uninstall'],
				'value'         => $this->__get( 'delete_meta_uninstall' ),
				'checked_value' => 1,
				'description'   => __( 'On uninstall remove all Codes, Orders, Campaigns data created by module. <strong>THIS IS IRREVERSIBLE. USE IT WITH CARE.</strong>', $this->domain ),
			)
		);
	}

	public function add_form() {
		?>
        <form action="options.php" method="post" id="wopc_form">
			<?php
			settings_fields( 'wopc_settings' );
			do_settings_sections( 'wopc_settings' );
			submit_button( __( 'Save Settings', $this->domain ) );
			?>
        </form>
		<?php
	}

	private function add_footer() {
		?>
        </div>
		<?php
	}

	/* FIELDS */
	private function add_field( string $name, string $title, string $section, string $type = '', array $args ) {
		$sanitize = '';
		switch ( $type ) {
			case 'array':
				$sanitize = 'wopc_sanitize_array';
				break;
			default:
				$sanitize = 'sanitize_text_field';
		}
		register_setting(
			$this->settings_name,
			$name,
			array(
				'default'           => $args['default'],
				'type'              => $type,
				'sanitize_callback' => $sanitize,
			)
		);
		add_settings_field(
			$name,
			__( $title, $this->domain ),
			array( $this, 'return_field_html' ),
			$this->settings_name,
			$section,
			$args
		);
	}

	public function return_field_html( array $args = array() ) {
		$args['value'] = ( isset( $args['value'] ) && $args['value'] != '' ) ? $args['value'] : $args['default'];
		echo wopc_setting_html( $args );
	}
}
