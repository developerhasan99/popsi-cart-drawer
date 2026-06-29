<?php
/**
 * Plugin Name: Popsi Cart Drawer for WooCommerce
 * Description: A premium side cart plugin for WooCommerce featuring Cart Drawer, Upsell, and Cart Progress.
 * Version: 1.1.3
 * Author: Mehedi Hasan
 * Author URI: https://developerhasan99.github.io/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Text Domain: popsi-cart-drawer
 *
 * @package Popsi_Cart_Drawer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'POPSI_CART_VERSION', '1.1.3' );
define( 'POPSI_CART_FILE', __FILE__ );
define( 'POPSI_CART_PATH', plugin_dir_path( __FILE__ ) );
define( 'POPSI_CART_URL', plugin_dir_url( __FILE__ ) );

/**
 * Display admin notice when WooCommerce is not installed
 */
function popsi_cart_woocommerce_missing_notice() {
	?>
	<div class="notice notice-error is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Popsi Cart Drawer', 'popsi-cart-drawer' ); ?></strong><br>
			<?php esc_html_e( 'WooCommerce is required to use the Popsi Cart Drawer plugin. Please install and activate WooCommerce to continue.', 'popsi-cart-drawer' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Main instance initialization
 */
function popsi_cart_run() {
	if ( class_exists( 'WooCommerce' ) ) {
		require_once POPSI_CART_PATH . 'includes/class-popsi-cart.php';
		require_once POPSI_CART_PATH . 'includes/class-popsi-cart-admin.php';

		$plugin = new Popsi_Cart_Drawer();
		$plugin->init();

		$admin = new Popsi_Cart_Admin();
		$admin->init();
	} else {
		// Show admin notice if WooCommerce is not installed.
		add_action( 'admin_notices', 'popsi_cart_woocommerce_missing_notice' );
	}
}
add_action( 'plugins_loaded', 'popsi_cart_run' );
