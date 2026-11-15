<?php
/**
 * Plugin Name:          Nauman Manual Payments for WooCommerce
 * Description:          Accept bank transfer and mobile wallet payments (JazzCash, Easypaisa) in WooCommerce, with payment instructions and a customer transaction ID field.
 * Version:              1.1.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               Nauman
 * Author URI:           https://syednauman.com
 * License:              GPL v2 or later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          nauman-manual-payments
 * WC requires at least: 8.0
 *
 * @package Nauman_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

define( 'NMPAY_VERSION', '1.1.0' );
define( 'NMPAY_FILE', __FILE__ );
define( 'NMPAY_PATH', plugin_dir_path( __FILE__ ) );
define( 'NMPAY_URL', plugin_dir_url( __FILE__ ) );

/**
 * Declare compatibility with High-Performance Order Storage and the Cart/Checkout blocks.
 */
function nmpay_declare_compatibility() {
	if ( class_exists( 'Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', NMPAY_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', NMPAY_FILE, true );
	}
}
add_action( 'before_woocommerce_init', 'nmpay_declare_compatibility' );

/**
 * Load the gateway once WooCommerce is available.
 */
function nmpay_init() {
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		return;
	}

	require_once NMPAY_PATH . 'includes/class-nmpay-gateway.php';
	add_filter( 'woocommerce_payment_gateways', 'nmpay_register_gateway' );

	if ( is_admin() ) {
		require_once NMPAY_PATH . 'includes/class-nmpay-admin.php';
		Nmpay_Admin::init();
	}
}
add_action( 'plugins_loaded', 'nmpay_init' );

/**
 * Register the gateway class with WooCommerce.
 *
 * @param array $gateways Registered gateway class names.
 * @return array
 */
function nmpay_register_gateway( $gateways ) {
	$gateways[] = 'Nmpay_Gateway';
	return $gateways;
}

/**
 * Load the Cart/Checkout blocks integration when the blocks package is present.
 */
function nmpay_register_blocks_support() {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once NMPAY_PATH . 'includes/class-nmpay-blocks-support.php';
	add_action( 'woocommerce_blocks_payment_method_type_registration', 'nmpay_add_blocks_payment_method' );
}
add_action( 'woocommerce_blocks_loaded', 'nmpay_register_blocks_support' );

/**
 * Register the payment method with the blocks payment registry.
 *
 * @param object $registry Payment method registry.
 */
function nmpay_add_blocks_payment_method( $registry ) {
	$registry->register( new Nmpay_Blocks_Support() );
}

/**
 * Add a Settings shortcut on the Plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function nmpay_settings_link( $links ) {
	$url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=nmpay' );
	array_unshift(
		$links,
		'<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'nauman-manual-payments' ) . '</a>'
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'nmpay_settings_link' );
