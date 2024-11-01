<?php
/**
 * Plugin Name: WooCommerce - Order Product Codes
 * Description: Send customizable promo codes for orders that contains specific products. Requires plugin Woocommerce to be installed and active.
 * Version: 1.0.2
 * Author: Tymotey
 * Copyright: © 2018 Bondas.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: wopc
 * Domain Path: /languages
 * WC tested up to: 4.9.6
 * WC requires at least: 2.6
 */

class WOPC {
	protected static $instance = null;

	private $file = '';
	private $version = '';

	public $module_title = 'WooCommerce - Order Product Codes';
	public $module_title_short = 'WOPC';
	public $module_prefix = 'wopc_';
	public $plugin_path = '';
	public $plugin_url = '';
	public $settings_url = 'admin.php?page=wopc_settings';
	public $assets_url = '';
	public $assets_path = '';
	public $domain = 'wopc';
	public $settings = '';
	public $meta_order = array();
	public $meta_order_sent = 'code_sent';
	public $meta_order_sent_date = 'code_sent_date';
	public $plugin_email = 'wopc@bondas.ro';

	public static function get_instance() {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static;
		}

		return static::$instance;
	}

	protected function __clone() {
		//Me not like clones! Me smash clones!
	}

	function __construct() {
		$this->file    = __FILE__;
		$this->version = '1.0.2';
		$this->debug   = false;

		// PATHS
		$this->meta_order = array(
			$this->module_prefix . $this->meta_order_sent      => 'yes',
			$this->module_prefix . $this->meta_order_sent_date => date( 'Y-m-d H:i:s' ),
		);
		$this->meta_order = apply_filters( 'wopc_order_meta', $this->meta_order );

		$this->plugin_path = trailingslashit( plugin_dir_path( $this->file ) );
		$this->plugin_url  = trailingslashit( plugin_dir_url( $this->file ) );
		$this->assets_url  = $this->plugin_url . trailingslashit( 'assets' );
		$this->assets_path = $this->plugin_path . trailingslashit( 'assets' );

		// REQUIRE
		require_once 'lib/others.php';
		require_once 'lib/post_types/class-wopc-posttypes-ordercodes.php';
		require_once 'lib/post_types/class-wopc-posttypes-campaign.php';
		do_action( 'wopc_custom_post_type_add' );
		require_once 'lib/class-wopc-settings.php';
		require_once 'lib/class-wopc-code-generator.php';
		require_once 'lib/class-wopc-campaigns.php';
		require_once 'lib/class-wopc-campaign.php';
		require_once 'lib/class-wopc-code.php';

		// INITIALIZE CLASSES
		$this->settings = WOPC_Settings::get_instance();

		// PLUGIN RUN
		if ( $this->wopc_can_run() ) {
			$this->init_global_hooks();
			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'init_admin_modules_before_scripts' ) );

				$this->init_settings_admin();
			}

			if ( $this->wopc_enabled() ) {
				// INIT IF PLUGIN ENABLED
				new WOPC_PostTypes_Campaign();
				new WOPC_PostTypes_OrderCodes();
				$this->init_ajax_endpoints();

				add_action( 'init', array( $this, 'init_module_hooks' ) );


			} else {
				if ( '' == $this->settings->__get( 'disable_notification' ) ) {
					add_action( 'admin_notices', array( $this, 'wopc_notice_enable_settings' ) );
				}
			}

			if ( is_admin() ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'init_admin_modules_after_scripts' ) );
			}
		} else {
			add_action( 'admin_notices', array( $this, 'wopc_notice_no_woocommerce' ) );
		}

		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array(
			&$this,
			'wopc_plugin_manage_link',
		), 0, 1 );
	}

	private function assets_version( string $ver = null ) {
		if ( true == $this->debug ) {
			return date( 'Y-m-d-h-i-s' );
		} else {
			if ( null != $ver ) {
				return $ver;
			} else {
				return $this->version;
			}
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( $this->domain, false, plugin_basename( $this->plugin_path ) . '/languages' );
	}

	public function init_assets() {
		// ALL ZONES
		wp_enqueue_style( $this->module_prefix . 'main_css', $this->assets_url . 'css/bundle.css', array(), $this->assets_version(), 'all' );
		wp_enqueue_script( $this->module_prefix . 'main_js', $this->assets_url . 'js/bundle.js', array( 'jquery' ), $this->assets_version(), true );

		wp_localize_script( $this->module_prefix . 'main_js', 'wopc_ajax_object',
			array( 'ajax_url' => admin_url( 'admin-ajax.php' ) )
		);
	}

	public function init_admin_modules_before_scripts() {
	}


	public function init_admin_modules_after_scripts() {
		wp_localize_script( $this->module_prefix . 'main_js', 'wopc_admin_translation',
			array(
				'remove_product' => __( 'Remove product: %s', $this->domain ),
			)
		);
	}

	private function init_global_hooks() {
		// ALL ZONES
		register_activation_hook( $this->file, array( $this, 'module_on_activate' ) );
		//register_deactivation_hook( $this->file, array( $this, 'module_on_deactivate' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'init_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'init_assets' ) );
	}

	public function init_module_hooks() {
		if ( '' != $this->settings->__get( 'code_send' ) ) {
			$send = explode( ',', str_replace( 'wc-', '', str_replace( ', ', ',', $this->settings->__get( 'code_send' ) ) ) );
			foreach ( $send as $status ) {
				if ( $status != '' ) {
					add_action( 'woocommerce_order_status_' . $status, array( $this, 'wopc_send_email_code' ) );
				}
			}
		}
		if ( '' != $this->settings->__get( 'code_cancel' ) ) {
			$send = explode( ',', str_replace( 'wc-', '', str_replace( ', ', ',', $this->settings->__get( 'code_cancel' ) ) ) );
			foreach ( $send as $status ) {
				if ( $status != '' ) {
					add_action( 'woocommerce_order_status_' . $status, array( $this, 'wopc_cancel_code' ) );
				}
			}
		}
	}

	private function init_ajax_endpoints() {
		require_once 'lib/ajax_admin.php';
	}

	private function wopc_can_run() {
		return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
	}

	private function wopc_enabled() {
		return 1 == $this->settings->__get( 'enabled' );
	}

	// NOTICES
	public function wopc_create_notice( $message = '', $type = 'warning', $dismissible = false ) {
		// type: error, warning, success, info
		?>
        <div class="notice notice-<?php echo $type; ?> <?php if ( true == $dismissible ) {
			echo 'is-dismissible';
		} ?>">
            <p><?php echo __( $message, $this->domain ); ?></p>
        </div>
		<?php
	}

	public function wopc_notice_no_woocommerce() {
		$this->wopc_create_notice( $this->module_title . ' - Please install and activate Woocommerce plugin.', 'error' );
	}

	public function wopc_notice_enable_settings() {
		$this->wopc_create_notice( sprintf( $this->module_title . ' - To enable functionality go to <a href="%s">module settings</a> and enable it.', admin_url( $this->settings_url ) ) );
	}

	// ACTION LINKS
	public function wopc_plugin_manage_link( $actions ) {
		if ( $this->wopc_can_run() ) {
			$actions_module = array(
				'configure' => '<a href="' . admin_url( $this->settings_url ) . '">' . __( 'Configure', $this->domain ) . '</a>',
			);
		} else {
			$actions_module = array(
				'activate_woocommerce' => '<strong style="color: red;">' . __( 'Please activate Woocommerce', $this->domain ) . '</strong>',
			);
		}

		return array_merge( $actions_module, $actions );
	}

	// SETTINGS PANEL
	private function init_settings_admin() {
		$this->settings->add_menu();
	}

	// FUNCTIONALITY
	public function wopc_send_email_code( $order_id ) {
		$done       = false;
		$order      = new WC_Order( $order_id );
		$email_sent = $order->get_meta( $this->module_prefix . $this->meta_order_sent, true );
		if ( '' == $email_sent ) {
			$products    = array();
			$order_items = $order->get_items();
			foreach ( $order_items as $item ) {
				$products[] = $item->get_product_id();
			}

			$campaigns     = new WOPC_Campaigns();
			$hit_campaigns = $campaigns->get_campaigns( 'products', $products );

			foreach ( $hit_campaigns as $campaign ) {
				$campaign = new WOPC_Campaign( $campaign );
				if ( $campaign ) {
					$done = $campaign->send_email( $order );
				}
				if ( $done ) {
					$this->wopc_sent_code_info( $order );
					$done = false;
				}
			}
			do_action( 'wopc_after_code_sent', $done );
		}
		unset( $order, $order_items, $products, $campaigns, $hit_campaigns, $done, $email_sent );
	}

	public function wopc_cancel_code( $order_id ) {
		$code_obj = new WOPC_Code();
		$code_obj->delete_code_post( $order_id );
	}

	// ORDER FUNCTIONS
	public function wopc_sent_code_info( $order ) {
		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}
		$this->create_order_meta( $order );
	}

	public function create_order_meta( $order ) {
		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}
		$metas                                                       = $this->meta_order;
		$metas[ $this->module_prefix . $this->meta_order_sent_date ] = date( 'Y-m-d H:i:s' );
		foreach ( $metas as $key => $meta ) {
			update_post_meta( $order->get_id(), $key, $meta );
		}
	}

	public function delete_order_meta( $order ) {
		if ( is_int( $order ) ) {
			$order = new WC_Order( $order );
		}
		$metas = $this->meta_order;
		foreach ( array_keys( $metas ) as $key ) {
			delete_post_meta( $order->get_id(), $key );
		}
	}

	public function delete_orders_meta() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
				$this->module_prefix . $this->meta_order_sent
			)
		);
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM $wpdb->postmeta WHERE meta_key = %s",
				$this->module_prefix . $this->meta_order_sent_date
			)
		);
	}

	// MODULE SPECIFIC
	public function module_on_activate() {
		$this->settings->add_settings();
	}

	public function module_on_deactivate() {

	}
}

$wopc = WOPC::get_instance();