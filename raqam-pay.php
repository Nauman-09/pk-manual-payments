<?php
/**
 * Plugin Name:          Raqam Pay for WooCommerce
 * Description:          Accept bank transfer and mobile wallet payments (JazzCash, Easypaisa) in WooCommerce, with payment instructions and a customer transaction ID field.
 * Version:              1.1.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Nauman
 * Author URI:           https://syednauman.com
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          raqam-pay
 * WC requires at least: 8.0
 *
 * @package Raqam_Pay
 */

defined( 'ABSPATH' ) || exit;

define( 'RAQPAY_VERSION', '1.1.0' );
define( 'RAQPAY_FILE', __FILE__ );
define( 'RAQPAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'RAQPAY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with High-Performance Order Storage and the Cart/Checkout blocks.
 */
function raqpay_declare_compatibility() {
	if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RAQPAY_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', RAQPAY_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'raqpay_declare_compatibility' );

/**
 * Load the gateway once WooCommerce is available.
 */
function raqpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once RAQPAY_PATH . 'includes/class-raqpay-gateway.php';
	add_filter( 'woocommerce_payment_gateways', 'raqpay_register_gateway' );

	if ( is_admin() ) {
		require_once RAQPAY_PATH . 'includes/class-raqpay-admin.php';
		Raqpay_Admin::init();
	}
}
add_action( 'plugins_loaded', 'raqpay_init' );

/**
 * Register the gateway class with WooCommerce.
 *
 * @param array $gateways Registered gateway class names.
 * @return array
 */
function raqpay_register_gateway( $gateways ) {
	$gateways[] = 'Raqpay_Gateway';
	return $gateways;
}

/**
 * Load the Cart/Checkout blocks integration when the blocks package is present.
 */
function raqpay_register_blocks_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once RAQPAY_PATH . 'includes/class-raqpay-blocks-support.php';
	add_action( 'woocommerce_blocks_payment_method_type_registration', 'raqpay_add_blocks_payment_method' );
}
add_action( 'woocommerce_blocks_loaded', 'raqpay_register_blocks_support' );

/**
 * Register the payment method with the blocks payment registry.
 *
 * @param object $registry Payment method registry.
 */
function raqpay_add_blocks_payment_method( $registry ) {
	$registry->register( new Raqpay_Blocks_Support() );
}

/**
 * Add a Settings shortcut on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function raqpay_settings_link( $links ) {
	$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=raqpay' );
	array_unshift(
		$links,
		'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'raqam-pay' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'raqpay_settings_link' );
