<?php
/**
 * Plugin Name:       Resolve for WooCommerce
 * Plugin URI:        https://resolvepay.com/
 * Description:       A payment gateway for Resolve.
 * Author:            Resolve
 * Author URI:        https://resolvepay.com/
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       resolve
 * Domain Path:       /languages
 *
 * Version: 0.9
 *
 * Requires at least:    5.0
 * Requires PHP:         7.2
 * WC requires at least: 3.3
 * WC tested up to:      5.6.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return true if the Woocommerce plugin is active or false otherwise.
 *
 * @return boolean
 */
function rfw_is_woocommerce_active() {
	return in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) );
}

/**
 * Echo admin notice HTML for missing WooCommerce plugin.
 *
 * @return void
 */
function rfw_admin_notice_missing_woocommerce() {
	global $current_screen;

	if( $current_screen->parent_base === 'plugins' ) {
		?>
		<div class="notice notice-error">
			<p><?php _e( 'Please install and activate <a href="http://www.woothemes.com/woocommerce/" target="_blank">WooCommerce</a> before activating Resolve payment gateway!', 'resolve' ); ?></p>
		</div>
		<?php
	}
}
if ( ! rfw_is_woocommerce_active() ) {
	add_action( 'admin_notices', 'rfw_admin_notice_missing_woocommerce' );
	return;
}

if ( ! class_exists( 'RFW_Main' ) ) {
	class RFW_Main {

		/**
		 * Current plugin's version.
		 * @var string
		 */
		const VERSION = '0.9';

		/**
		 * Instance of the current class, null before first usage.
		 * @var RFW_Main
		 */
		protected static $_instance = null;

		/**
		 * Class constructor, initialize constants and settings.
		 * @since 0.1
		 */
		protected function __construct() {
			RFW_Main::register_constants();

			// Utilites.
			require_once 'includes/utilities/class-rfw-data.php';
			require_once 'includes/utilities/class-rfw-logger.php';

			// Core.
			require_once 'includes/core/class-rfw-payment-gateway.php';
			require_once 'includes/core/class-rfw-ajax-interface.php';

			$this->ajax = new RFW_Ajax_Interface();
			$this->ajax->register();

			add_filter( 'woocommerce_payment_gateways', [ $this, 'register_gateway' ] );
			add_filter( 'plugin_action_links_' . RFW_PLUGIN_BASENAME, [ $this, 'add_settings_link' ] );

			add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_script' ] );
		}

		/**
		 * Register plugin's constants.
		 */
		public static function register_constants() {
			if ( ! defined( 'RFW_PLUGIN_ID' ) ) {
				define( 'RFW_PLUGIN_ID', 'resolve' );
			}
			if ( ! defined( 'RFW_PLUGIN_VERSION' ) ) {
				define( 'RFW_PLUGIN_VERSION', '0.9' );
			}
			if ( ! defined( 'RFW_PLUGIN_BASENAME' ) ) {
				define( 'RFW_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
			}
			if ( ! defined( 'RFW_DIR_PATH' ) ) {
				define( 'RFW_DIR_PATH', plugin_dir_path( __FILE__ ) );
			}
			if ( ! defined( 'RFW_DIR_URL' ) ) {
				define( 'RFW_DIR_URL', plugin_dir_url( __FILE__ ) );
			}
			if ( ! defined( 'RFW_ADMIN_SETTINGS_URL' ) ) {
				define( 'RFW_ADMIN_SETTINGS_URL', get_admin_url( null, 'admin.php?page=wc-settings&tab=checkout&section=' . RFW_PLUGIN_ID ) );
			}
		}

		/**
		 * Register payment gateway's class as a new method of payment.
		 * @param array $methods
		 * @return array
		 */
		public function register_gateway( $methods ) {
			$methods[] = 'RFW_Payment_Gateway';
			return $methods;
		}

		/**
		 * Register admin JS script.
		 */
		public function register_admin_script() {
			wp_enqueue_script( 'rfw-admin-js', RFW_DIR_URL . '/assets/rfw-admin.js', [ 'jquery' ], false, true );

			wp_localize_script( 'rfw-admin-js', 'RFWPaymentGateway', [
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'capture_notice' => __( 'Are you sure you want to capture this payment?', 'resolve' ),
			]);
		}

		/**
		 * Adds the link to the settings page on the plugins WP page.
		 * @param array   $links
		 * @return array
		 */
		public function add_settings_link( $links ) {
			$settings_link = '<a href="' . RFW_ADMIN_SETTINGS_URL . '">' . __( 'Settings', 'woocommerce' ) . '</a>';
			array_unshift( $links, $settings_link );

			return $links;
		}

		/**
		 * Installation procedure.
		 * @static
		 */
		public static function install() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			RFW_Main::register_constants();
		}

		/**
		 * Uninstallation procedure.
		 * @static
		 */
		public static function uninstall() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			wp_cache_flush();
		}

		/**
		 * Deactivation function.
		 * @static
		 */
		public static function deactivate() {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return false;
			}

			wp_cache_flush();
		}

		/**
		 * Return class instance.
		 * @static
		 * @return RFW_Main
		 */
		public static function get_instance() {
			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self;
			}
			return self::$_instance;
		}

		/**
		 * Cloning is forbidden.
		 * @since 0.1
		 */
		public function __clone() {
			return wp_die( 'Cloning is forbidden!' );
		}

		/**
		 * Unserializing instances of this class is forbidden.
		 * @since 0.1
		 */
		public function __wakeup() {
			return wp_die( 'Unserializing instances is forbidden!' );
		}

	}
}

register_activation_hook( __FILE__, array( 'RFW_Main', 'install' ) );
register_uninstall_hook( __FILE__, array( 'RFW_Main', 'uninstall' ) );
register_deactivation_hook( __FILE__, array( 'RFW_Main', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'RFW_Main', 'get_instance' ), 0 );
