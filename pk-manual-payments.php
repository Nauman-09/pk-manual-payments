<?php
/**
 * Plugin Name:          PK Manual Payments for WooCommerce
 * Description:          Accept bank transfer and mobile wallet payments (JazzCash, Easypaisa) in WooCommerce, with payment instructions and a customer transaction ID field.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Nauman
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          pk-manual-payments
 * WC requires at least: 8.0
 *
 * @package PK_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

define( 'PKMP_VERSION', '1.0.0' );
define( 'PKMP_FILE', __FILE__ );
define( 'PKMP_PATH', plugin_dir_path( __FILE__ ) );
define( 'PKMP_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with High-Performance Order Storage and the Cart/Checkout blocks.
 */
function pkmp_declare_compatibility() {
	if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', PKMP_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', PKMP_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'pkmp_declare_compatibility' );

/**
 * Load the gateway once WooCommerce is available.
 */
function pkmp_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once PKMP_PATH . 'includes/class-pkmp-gateway.php';
	add_filter( 'woocommerce_payment_gateways', 'pkmp_register_gateway' );
}
add_action( 'plugins_loaded', 'pkmp_init' );

/**
 * Register the gateway class with WooCommerce.
 *
 * @param array $gateways Registered gateway class names.
 * @return array
 */
function pkmp_register_gateway( $gateways ) {
	$gateways[] = 'PKMP_Gateway';
	return $gateways;
}

/**
 * Load the Cart/Checkout blocks integration when the blocks package is present.
 */
function pkmp_register_blocks_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once PKMP_PATH . 'includes/class-pkmp-blocks-support.php';
	add_action( 'woocommerce_blocks_payment_method_type_registration', 'pkmp_add_blocks_payment_method' );
}
add_action( 'woocommerce_blocks_loaded', 'pkmp_register_blocks_support' );

/**
 * Register the payment method with the blocks payment registry.
 *
 * @param object $registry Payment method registry.
 */
function pkmp_add_blocks_payment_method( $registry ) {
	$registry->register( new PKMP_Blocks_Support() );
}

/**
 * Add a Settings shortcut on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function pkmp_settings_link( $links ) {
	$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=pkmp' );
	array_unshift(
		$links,
		'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'pk-manual-payments' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'pkmp_settings_link' );
