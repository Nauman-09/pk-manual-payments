<?php
/**
 * Cart/Checkout blocks integration.
 *
 * @package Raqam_Pay
 */

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes the gateway to the block-based checkout.
 */
final class Raqpay_Blocks_Support extends AbstractPaymentMethodType {

	/**
	 * Payment method name, matches the gateway ID.
	 *
	 * @var string
	 */
	protected $name = 'raqpay';

	/**
	 * Gateway instance.
	 *
	 * @var Raqpay_Gateway|null
	 */
	private $gateway = null;

	/**
	 * Load settings and the gateway.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_raqpay_settings', array() );
		$gateways       = WC()->payment_gateways()->payment_gateways();
		$this->gateway  = isset( $gateways['raqpay'] ) ? $gateways['raqpay'] : null;
	}

	/**
	 * Whether the method should appear in the checkout block.
	 *
	 * @return bool
	 */
	public function is_active() {
		return $this->gateway
			&& 'yes' === $this->gateway->enabled
			&& ! empty( $this->gateway->get_enabled_methods() );
	}

	/**
	 * Script handles for the checkout block.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		wp_register_script(
			'raqpay-blocks',
			RAQPAY_URL . 'assets/js/blocks.js',
			array( 'wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-html-entities' ),
			RAQPAY_VERSION,
			true
		);
		wp_set_script_translations( 'raqpay-blocks', 'raqam-pay' );

		return array( 'raqpay-blocks' );
	}

	/**
	 * Data passed to the block script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		if ( ! $this->gateway ) {
			return array();
		}

		$methods = array();

		foreach ( $this->gateway->get_enabled_methods() as $details ) {
			$methods[] = array(
				'key'          => $details['key'],
				'label'        => $details['label'],
				'rows'         => $this->gateway->get_detail_rows( $details ),
				'instructions' => wp_strip_all_tags( $details['instructions'] ),
			);
		}

		return array(
			'title'         => $this->gateway->get_title(),
			'description'   => $this->gateway->get_option( 'description' ),
			'supports'      => array_filter( (array) $this->gateway->supports, array( $this->gateway, 'supports' ) ),
			'methods'       => $methods,
			'defaultMethod' => ! empty( $methods ) ? $methods[0]['key'] : '',
			'requireTxn'    => 'yes' === $this->gateway->get_option( 'require_txn_id' ),
			'askSender'     => 'yes' === $this->gateway->get_option( 'ask_sender' ),
		);
	}
}
