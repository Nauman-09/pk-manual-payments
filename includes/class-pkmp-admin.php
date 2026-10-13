<?php
/**
 * Admin tools: Orders list column, transaction ID search and payment verification.
 *
 * @package PK_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helps store owners reconcile manual payments from the Orders screen.
 */
class PKMP_Admin {

	const ACTION_VERIFY = 'pkmp_verify_payment';
	const COLUMN_ID     = 'pkmp_txn';

	/**
	 * Single instance.
	 *
	 * @var PKMP_Admin|null
	 */
	private static $instance = null;

	/**
	 * Register the hooks once.
	 *
	 * @return PKMP_Admin
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks.
	 */
	private function __construct() {
		// Search orders by transaction ID: HPOS first, then the legacy post-based screen.
		add_filter( 'woocommerce_order_table_search_query_meta_keys', array( $this, 'add_search_key' ) );
		add_filter( 'woocommerce_shop_order_search_fields', array( $this, 'add_search_key' ) );

		// Orders list column: HPOS screen.
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_column' ) );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_column_hpos' ), 10, 2 );

		// Orders list column: legacy post-based screen.
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column' ) );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_column_legacy' ), 10, 2 );

		// Verify and mark paid from the order actions dropdown.
		add_filter( 'woocommerce_order_actions', array( $this, 'add_order_action' ), 10, 2 );
		add_action( 'woocommerce_order_action_' . self::ACTION_VERIFY, array( $this, 'handle_verify' ) );
	}

	/**
	 * Make the transaction ID searchable on the Orders screen.
	 *
	 * @param array $keys Meta keys searched.
	 * @return array
	 */
	public function add_search_key( $keys ) {
		$keys   = is_array( $keys ) ? $keys : array();
		$keys[] = PKMP_Gateway::META_TXN;
		return array_values( array_unique( $keys ) );
	}

	/**
	 * Insert the column after the order status column.
	 *
	 * @param array $columns Existing columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$label    = __( 'Transaction ID', 'pk-manual-payments' );
		$result   = array();
		$inserted = false;

		foreach ( $columns as $key => $title ) {
			$result[ $key ] = $title;

			if ( 'order_status' === $key ) {
				$result[ self::COLUMN_ID ] = $label;
				$inserted                  = true;
			}
		}

		if ( ! $inserted ) {
			$result[ self::COLUMN_ID ] = $label;
		}

		return $result;
	}

	/**
	 * Render the column on the HPOS Orders screen.
	 *
	 * @param string   $column Column ID.
	 * @param WC_Order $order  Order.
	 */
	public function render_column_hpos( $column, $order ) {
		if ( self::COLUMN_ID === $column && $order instanceof WC_Order ) {
			$this->render_column( $order );
		}
	}

	/**
	 * Render the column on the legacy Orders screen.
	 *
	 * @param string $column  Column ID.
	 * @param int    $post_id Order ID.
	 */
	public function render_column_legacy( $column, $post_id ) {
		if ( self::COLUMN_ID !== $column ) {
			return;
		}

		$order = wc_get_order( $post_id );

		if ( $order instanceof WC_Order ) {
			$this->render_column( $order );
		}
	}

	/**
	 * Print the transaction summary for one order.
	 *
	 * @param WC_Order $order Order.
	 */
	private function render_column( $order ) {
		if ( PKMP_Gateway::GATEWAY_ID !== $order->get_payment_method() ) {
			echo '<span aria-hidden="true">&ndash;</span>';
			return;
		}

		$txn    = (string) $order->get_meta( PKMP_Gateway::META_TXN );
		$method = (string) $order->get_meta( PKMP_Gateway::META_METHOD );
		$labels = PKMP_Gateway::get_method_labels();

		echo '<strong>' . esc_html( '' !== $txn ? $txn : __( 'not provided', 'pk-manual-payments' ) ) . '</strong>';

		if ( isset( $labels[ $method ] ) ) {
			echo '<br /><small>' . esc_html( $labels[ $method ] ) . '</small>';
		}

		if ( $order->get_meta( PKMP_Gateway::META_VERIFIED_AT ) ) {
			echo '<br /><small>' . esc_html__( 'Verified', 'pk-manual-payments' ) . '</small>';
		}
	}

	/**
	 * Whether the order is a manual payment still waiting for verification.
	 *
	 * @param WC_Order $order Order.
	 * @return bool
	 */
	private function can_verify( $order ) {
		return PKMP_Gateway::GATEWAY_ID === $order->get_payment_method()
			&& $order->has_status( array( 'on-hold', 'pending' ) )
			&& current_user_can( 'edit_shop_orders' );
	}

	/**
	 * Offer the verify action on orders awaiting a manual payment.
	 *
	 * @param array         $actions Order actions.
	 * @param WC_Order|null $order   Order, when WooCommerce passes it.
	 * @return array
	 */
	public function add_order_action( $actions, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			global $theorder;
			$order = $theorder;
		}

		if ( $order instanceof WC_Order && $this->can_verify( $order ) ) {
			$actions[ self::ACTION_VERIFY ] = __( 'Verify manual payment and mark as paid', 'pk-manual-payments' );
		}

		return $actions;
	}

	/**
	 * Mark the order paid. WooCommerce moves it to Processing (or Completed for
	 * virtual downloadable orders) and sends the usual customer emails.
	 *
	 * @param WC_Order $order Order.
	 */
	public function handle_verify( $order ) {
		if ( ! $order instanceof WC_Order || ! $this->can_verify( $order ) ) {
			return;
		}

		$user = wp_get_current_user();

		$order->update_meta_data( PKMP_Gateway::META_VERIFIED_AT, time() );
		$order->update_meta_data( PKMP_Gateway::META_VERIFIED_BY, (int) $user->ID );
		$order->add_order_note(
			sprintf(
				/* translators: %s: name of the user who verified the payment. */
				__( 'Manual payment verified by %s.', 'pk-manual-payments' ),
				$user && $user->display_name ? $user->display_name : __( 'a store manager', 'pk-manual-payments' )
			)
		);
		$order->payment_complete( (string) $order->get_meta( PKMP_Gateway::META_TXN ) );
	}
}
