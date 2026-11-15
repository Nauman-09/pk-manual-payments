<?php
/**
 * Manual payment gateway (bank transfer, JazzCash, Easypaisa).
 *
 * @package Nauman_Manual_Payments
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gateway that lets customers pay by transfer and submit a transaction ID.
 * Payments are verified manually by the store owner.
 */
class Nmpay_Gateway extends WC_Payment_Gateway {

	const GATEWAY_ID  = 'nmpay';
	const META_METHOD = '_nmpay_method';
	const META_TXN    = '_nmpay_txn_id';
	const META_SENDER = '_nmpay_sender';

	const META_VERIFIED_AT = '_nmpay_verified_at';
	const META_VERIFIED_BY = '_nmpay_verified_by';

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = self::GATEWAY_ID;
		$this->icon               = '';
		$this->has_fields         = true;
		$this->method_title       = __( 'Nauman Manual Payments', 'nauman-manual-payments' );
		$this->method_description = __( 'Let customers pay by bank transfer, JazzCash or Easypaisa and submit their transaction ID. You verify each payment manually.', 'nauman-manual-payments' );
		$this->supports           = array( 'products' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
		add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'admin_order_details' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Supported payment methods, keyed by slug.
	 *
	 * @return array
	 */
	public static function get_method_labels() {
		return array(
			'jazzcash'  => 'JazzCash',
			'easypaisa' => 'Easypaisa',
			'bank'      => __( 'Bank Transfer', 'nauman-manual-payments' ),
		);
	}

	/**
	 * Admin settings fields.
	 */
	public function init_form_fields() {
		$fields = array(
			'enabled'        => array(
				'title'   => __( 'Enable/Disable', 'nauman-manual-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable manual payments', 'nauman-manual-payments' ),
				'default' => 'no',
			),
			'title'          => array(
				'title'       => __( 'Title', 'nauman-manual-payments' ),
				'type'        => 'text',
				'description' => __( 'The payment option name customers see at checkout.', 'nauman-manual-payments' ),
				'default'     => __( 'Bank Transfer / JazzCash / Easypaisa', 'nauman-manual-payments' ),
				'desc_tip'    => true,
			),
			'description'    => array(
				'title'       => __( 'Description', 'nauman-manual-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Shown above the payment methods at checkout.', 'nauman-manual-payments' ),
				'default'     => __( 'Send the order total to one of our accounts below, then enter your transaction ID.', 'nauman-manual-payments' ),
				'desc_tip'    => true,
			),
			'order_status'   => array(
				'title'       => __( 'Order status after checkout', 'nauman-manual-payments' ),
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => __( 'On hold keeps the order waiting until you confirm the payment.', 'nauman-manual-payments' ),
				'default'     => 'on-hold',
				'desc_tip'    => true,
				'options'     => array(
					'on-hold' => __( 'On hold', 'nauman-manual-payments' ),
					'pending' => __( 'Pending payment', 'nauman-manual-payments' ),
				),
			),
			'require_txn_id' => array(
				'title'   => __( 'Transaction ID', 'nauman-manual-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Require customers to enter a transaction ID', 'nauman-manual-payments' ),
				'default' => 'yes',
			),
			'unique_txn_id'  => array(
				'title'       => __( 'Duplicate check', 'nauman-manual-payments' ),
				'type'        => 'checkbox',
				'label'       => __( 'Reject a transaction ID that was already used on another order', 'nauman-manual-payments' ),
				'description' => __( 'Cancelled and failed orders are ignored.', 'nauman-manual-payments' ),
				'default'     => 'yes',
			),
			'ask_sender'     => array(
				'title'   => __( 'Sender details', 'nauman-manual-payments' ),
				'type'    => 'checkbox',
				'label'   => __( 'Ask for the number or account the payment was sent from (optional for customers)', 'nauman-manual-payments' ),
				'default' => 'yes',
			),
		);

		foreach ( self::get_method_labels() as $key => $label ) {
			$fields[ $key . '_section' ] = array(
				'title' => $label,
				'type'  => 'title',
			);

			$fields[ $key . '_enabled' ] = array(
				'title'   => __( 'Enable', 'nauman-manual-payments' ),
				'type'    => 'checkbox',
				/* translators: %s: payment method name. */
				'label'   => sprintf( __( 'Offer %s at checkout', 'nauman-manual-payments' ), $label ),
				'default' => 'no',
			);

			if ( 'bank' === $key ) {
				$fields['bank_name'] = array(
					'title'   => __( 'Bank name', 'nauman-manual-payments' ),
					'type'    => 'text',
					'default' => '',
				);
			}

			$fields[ $key . '_account_title' ] = array(
				'title'   => __( 'Account title', 'nauman-manual-payments' ),
				'type'    => 'text',
				'default' => '',
			);

			$fields[ $key . '_account_number' ] = array(
				'title'   => 'bank' === $key ? __( 'Account number / IBAN', 'nauman-manual-payments' ) : __( 'Mobile account number', 'nauman-manual-payments' ),
				'type'    => 'text',
				'default' => '',
			);

			$fields[ $key . '_instructions' ] = array(
				'title'       => __( 'Extra instructions', 'nauman-manual-payments' ),
				'type'        => 'textarea',
				'description' => __( 'Plain text. Shown with the account details at checkout, on the thank-you page and in the order email.', 'nauman-manual-payments' ),
				'default'     => '',
				'desc_tip'    => true,
			);
		}

		$this->form_fields = $fields;
	}

	/**
	 * Saved details for one payment method, or null if the key is unknown.
	 *
	 * @param string $key Method slug.
	 * @return array|null
	 */
	public function get_method_details( $key ) {
		$labels = self::get_method_labels();

		if ( ! isset( $labels[ $key ] ) ) {
			return null;
		}

		return array(
			'key'            => $key,
			'label'          => $labels[ $key ],
			'bank_name'      => 'bank' === $key ? (string) $this->get_option( 'bank_name' ) : '',
			'account_title'  => (string) $this->get_option( $key . '_account_title' ),
			'account_number' => (string) $this->get_option( $key . '_account_number' ),
			'instructions'   => (string) $this->get_option( $key . '_instructions' ),
		);
	}

	/**
	 * Payment methods the store owner switched on.
	 *
	 * @return array
	 */
	public function get_enabled_methods() {
		$enabled = array();

		foreach ( array_keys( self::get_method_labels() ) as $key ) {
			if ( 'yes' === $this->get_option( $key . '_enabled' ) ) {
				$enabled[ $key ] = $this->get_method_details( $key );
			}
		}

		return $enabled;
	}

	/**
	 * Label/value rows for the account details of a method.
	 *
	 * @param array $details Method details from get_method_details().
	 * @return array
	 */
	public function get_detail_rows( $details ) {
		$rows = array();

		if ( '' !== $details['bank_name'] ) {
			$rows[] = array(
				'label' => __( 'Bank', 'nauman-manual-payments' ),
				'value' => $details['bank_name'],
			);
		}

		if ( '' !== $details['account_title'] ) {
			$rows[] = array(
				'label' => __( 'Account title', 'nauman-manual-payments' ),
				'value' => $details['account_title'],
			);
		}

		if ( '' !== $details['account_number'] ) {
			$rows[] = array(
				'label' => 'bank' === $details['key'] ? __( 'Account no. / IBAN', 'nauman-manual-payments' ) : __( 'Account number', 'nauman-manual-payments' ),
				'value' => $details['account_number'],
			);
		}

		return $rows;
	}

	/**
	 * Only offer the gateway when at least one method is configured.
	 *
	 * @return bool
	 */
	public function is_available() {
		return parent::is_available() && ! empty( $this->get_enabled_methods() );
	}

	/**
	 * Load checkout styles and the classic checkout script.
	 */
	public function enqueue_assets() {
		if ( 'yes' !== $this->enabled ) {
			return;
		}

		$has_blocks = function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' );

		if ( ! is_checkout() && ! $has_blocks ) {
			return;
		}

		wp_enqueue_style( 'nmpay-checkout', NMPAY_URL . 'assets/css/checkout.css', array(), NMPAY_VERSION );

		if ( ! $has_blocks ) {
			wp_enqueue_script( 'nmpay-classic', NMPAY_URL . 'assets/js/classic.js', array( 'jquery' ), NMPAY_VERSION, true );
		}
	}

	/**
	 * Read and normalise the customer's submission from the checkout request.
	 *
	 * @return array
	 */
	private function read_submission() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the checkout nonce before gateways run.
		$method = isset( $_POST['nmpay_method'] ) ? sanitize_key( wp_unslash( $_POST['nmpay_method'] ) ) : '';
		$txn    = isset( $_POST['nmpay_txn_id'] ) ? sanitize_text_field( wp_unslash( $_POST['nmpay_txn_id'] ) ) : '';
		$sender = isset( $_POST['nmpay_sender'] ) ? sanitize_text_field( wp_unslash( $_POST['nmpay_sender'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$txn    = strtoupper( preg_replace( '/\s+/', '', $txn ) );
		$sender = substr( trim( preg_replace( '/[^0-9A-Za-z+\-\s]/', '', $sender ) ), 0, 40 );

		return array(
			'method' => $method,
			'txn'    => $txn,
			'sender' => $sender,
		);
	}

	/**
	 * Check a submission against the store settings.
	 *
	 * @param array $data             Result of read_submission().
	 * @param int   $exclude_order_id Order to ignore in the duplicate check.
	 * @return true|WP_Error
	 */
	private function validate_submission( $data, $exclude_order_id = 0 ) {
		$methods = $this->get_enabled_methods();

		if ( ! isset( $methods[ $data['method'] ] ) ) {
			return new WP_Error( 'nmpay_method', __( 'Please choose the payment method you used.', 'nauman-manual-payments' ) );
		}

		if ( '' === $data['txn'] ) {
			if ( 'yes' === $this->get_option( 'require_txn_id' ) ) {
				return new WP_Error( 'nmpay_txn_required', __( 'Please enter your transaction ID.', 'nauman-manual-payments' ) );
			}
			return true;
		}

		if ( ! preg_match( '/^[A-Za-z0-9\-_\/]{4,40}$/', $data['txn'] ) ) {
			return new WP_Error( 'nmpay_txn_invalid', __( 'The transaction ID looks invalid. Please check it and try again.', 'nauman-manual-payments' ) );
		}

		if ( 'yes' === $this->get_option( 'unique_txn_id' ) && $this->txn_id_exists( $data['txn'], $exclude_order_id ) ) {
			return new WP_Error( 'nmpay_txn_used', __( 'This transaction ID has already been used on another order. Please check the ID or contact us.', 'nauman-manual-payments' ) );
		}

		return true;
	}

	/**
	 * Whether a transaction ID is already attached to a live order.
	 *
	 * @param string $txn              Normalised transaction ID.
	 * @param int    $exclude_order_id Order to ignore.
	 * @return bool
	 */
	private function txn_id_exists( $txn, $exclude_order_id = 0 ) {
		$args = array(
			'limit'      => 1,
			'return'     => 'ids',
			'status'     => array( 'pending', 'on-hold', 'processing', 'completed' ),
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Single indexed lookup, limited to one result.
				array(
					'key'   => self::META_TXN,
					'value' => $txn,
				),
			),
		);

		if ( $exclude_order_id ) {
			$args['exclude'] = array( (int) $exclude_order_id );
		}

		return ! empty( wc_get_orders( $args ) );
	}

	/**
	 * Checkout form fields (classic checkout).
	 */
	public function payment_fields() {
		$methods = $this->get_enabled_methods();

		if ( empty( $methods ) ) {
			return;
		}

		$submitted = $this->read_submission();
		$selected  = isset( $methods[ $submitted['method'] ] ) ? $submitted['method'] : array_key_first( $methods );
		$require   = 'yes' === $this->get_option( 'require_txn_id' );
		?>
		<div class="nmpay-fields">
			<?php if ( '' !== trim( (string) $this->description ) ) : ?>
				<p class="nmpay-description"><?php echo esc_html( $this->description ); ?></p>
			<?php endif; ?>

			<fieldset class="nmpay-methods">
				<legend><?php esc_html_e( 'I will pay via', 'nauman-manual-payments' ); ?></legend>
				<?php foreach ( $methods as $key => $details ) : ?>
					<label class="nmpay-method">
						<input type="radio" name="nmpay_method" value="<?php echo esc_attr( $key ); ?>" <?php checked( $selected, $key ); ?> />
						<span><?php echo esc_html( $details['label'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<?php
			foreach ( $methods as $key => $details ) {
				$this->output_instructions( $details, null, $key !== $selected );
			}
			?>

			<p class="form-row form-row-wide nmpay-field">
				<label for="nmpay_txn_id">
					<?php esc_html_e( 'Transaction ID', 'nauman-manual-payments' ); ?>
					<?php if ( $require ) : ?>
						<abbr class="required" title="<?php esc_attr_e( 'required', 'nauman-manual-payments' ); ?>">*</abbr>
					<?php endif; ?>
				</label>
				<input type="text" class="input-text" name="nmpay_txn_id" id="nmpay_txn_id" value="<?php echo esc_attr( $submitted['txn'] ); ?>" autocomplete="off" maxlength="40" />
				<span class="nmpay-hint"><?php esc_html_e( 'Find it in the confirmation message or receipt from your bank or wallet app.', 'nauman-manual-payments' ); ?></span>
			</p>

			<?php if ( 'yes' === $this->get_option( 'ask_sender' ) ) : ?>
				<p class="form-row form-row-wide nmpay-field">
					<label for="nmpay_sender"><?php esc_html_e( 'Sent from (number or account, optional)', 'nauman-manual-payments' ); ?></label>
					<input type="text" class="input-text" name="nmpay_sender" id="nmpay_sender" value="<?php echo esc_attr( $submitted['sender'] ); ?>" autocomplete="off" maxlength="40" />
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Print the account details panel for a method.
	 *
	 * @param array         $details Method details.
	 * @param WC_Order|null $order   Order, when shown after checkout.
	 * @param bool          $hidden  Whether to render the panel hidden.
	 */
	private function output_instructions( $details, $order = null, $hidden = false ) {
		$rows = $this->get_detail_rows( $details );
		?>
		<div class="nmpay-instructions" data-method="<?php echo esc_attr( $details['key'] ); ?>"<?php if ( $hidden ) : ?> hidden<?php endif; ?>>
			<?php if ( $rows ) : ?>
				<ul class="nmpay-details">
					<?php foreach ( $rows as $row ) : ?>
						<li><strong><?php echo esc_html( $row['label'] ); ?>:</strong> <?php echo esc_html( $row['value'] ); ?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php if ( '' !== trim( $details['instructions'] ) ) : ?>
				<p class="nmpay-instruction-text"><?php echo esc_html( $details['instructions'] ); ?></p>
			<?php endif; ?>
			<?php if ( $order ) : ?>
				<p class="nmpay-reference"><?php echo esc_html( $this->get_reference_text( $order ) ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Plain-text payment reference reminder for an order.
	 *
	 * @param WC_Order $order Order.
	 * @return string
	 */
	private function get_reference_text( $order ) {
		$total = html_entity_decode(
			wp_strip_all_tags( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ),
			ENT_QUOTES,
			'UTF-8'
		);

		return sprintf(
			/* translators: 1: order number, 2: order total. */
			__( 'Please use order #%1$s as your payment reference and send exactly %2$s.', 'nauman-manual-payments' ),
			$order->get_order_number(),
			$total
		);
	}

	/**
	 * Validate on classic checkout before the order is created.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		$result = $this->validate_submission( $this->read_submission() );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			return false;
		}

		return true;
	}

	/**
	 * Save the submission and put the order on hold.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			wc_add_notice( __( 'Could not find your order. Please try again.', 'nauman-manual-payments' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$data   = $this->read_submission();
		$result = $this->validate_submission( $data, $order_id );

		if ( is_wp_error( $result ) ) {
			wc_add_notice( $result->get_error_message(), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_meta_data( self::META_METHOD, $data['method'] );
		$order->update_meta_data( self::META_TXN, $data['txn'] );
		$order->update_meta_data( self::META_SENDER, 'yes' === $this->get_option( 'ask_sender' ) ? $data['sender'] : '' );
		$order->save();

		$labels = self::get_method_labels();
		$status = 'pending' === $this->get_option( 'order_status' ) ? 'pending' : 'on-hold';

		$order->update_status(
			$status,
			sprintf(
				/* translators: 1: payment method name, 2: transaction ID. */
				__( 'Awaiting manual payment verification. Paid via %1$s, transaction ID: %2$s.', 'nauman-manual-payments' ),
				$labels[ $data['method'] ],
				'' !== $data['txn'] ? $data['txn'] : __( 'not provided', 'nauman-manual-payments' )
			)
		);

		wc_reduce_stock_levels( $order_id );

		$woocommerce = function_exists( 'WC' ) ? WC() : null;

		if ( $woocommerce && ! empty( $woocommerce->cart ) ) {
			$woocommerce->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Submitted payment details saved on an order.
	 *
	 * @param WC_Order $order Order.
	 * @return array|null
	 */
	private function get_submitted_details( $order ) {
		$method = (string) $order->get_meta( self::META_METHOD );
		$txn    = (string) $order->get_meta( self::META_TXN );
		$sender = (string) $order->get_meta( self::META_SENDER );

		if ( '' === $method && '' === $txn ) {
			return null;
		}

		$labels = self::get_method_labels();

		return array(
			'method_label' => isset( $labels[ $method ] ) ? $labels[ $method ] : $method,
			'txn'          => $txn,
			'sender'       => $sender,
		);
	}

	/**
	 * Thank-you page: show how to pay and echo back the submitted ID.
	 *
	 * @param int $order_id Order ID.
	 */
	public function thankyou_page( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return;
		}

		$details = $this->get_method_details( (string) $order->get_meta( self::META_METHOD ) );

		if ( ! $details ) {
			return;
		}

		$submitted = $this->get_submitted_details( $order );
		?>
		<section class="nmpay-thankyou">
			<h2 class="woocommerce-order-details__title"><?php esc_html_e( 'How to complete your payment', 'nauman-manual-payments' ); ?></h2>
			<?php $this->output_instructions( $details, $order ); ?>
			<?php if ( $submitted && '' !== $submitted['txn'] ) : ?>
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: transaction ID. */
							__( 'You submitted transaction ID %s. We will confirm your payment and process your order shortly.', 'nauman-manual-payments' ),
							$submitted['txn']
						)
					);
					?>
				</p>
			<?php endif; ?>
		</section>
		<?php
	}

	/**
	 * Order emails: instructions for the customer, submitted details for the admin.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Whether the email goes to the store owner.
	 * @param bool     $plain_text    Whether the email is plain text.
	 */
	public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		if ( ! $order instanceof WC_Order || $this->id !== $order->get_payment_method() ) {
			return;
		}

		if ( $sent_to_admin ) {
			$submitted = $this->get_submitted_details( $order );

			if ( ! $submitted ) {
				return;
			}

			$rows = array(
				array(
					'label' => __( 'Paid via', 'nauman-manual-payments' ),
					'value' => $submitted['method_label'],
				),
				array(
					'label' => __( 'Transaction ID', 'nauman-manual-payments' ),
					'value' => '' !== $submitted['txn'] ? $submitted['txn'] : __( 'not provided', 'nauman-manual-payments' ),
				),
			);

			if ( '' !== $submitted['sender'] ) {
				$rows[] = array(
					'label' => __( 'Sent from', 'nauman-manual-payments' ),
					'value' => $submitted['sender'],
				);
			}

			$this->output_email_block( __( 'Manual payment details', 'nauman-manual-payments' ), $rows, $plain_text );
			return;
		}

		if ( ! $order->has_status( 'on-hold' ) ) {
			return;
		}

		$details = $this->get_method_details( (string) $order->get_meta( self::META_METHOD ) );

		if ( ! $details ) {
			return;
		}

		$rows = $this->get_detail_rows( $details );

		if ( '' !== trim( $details['instructions'] ) ) {
			$rows[] = array(
				'label' => '',
				'value' => $details['instructions'],
			);
		}

		$rows[] = array(
			'label' => '',
			'value' => $this->get_reference_text( $order ),
		);

		$this->output_email_block( __( 'How to complete your payment', 'nauman-manual-payments' ), $rows, $plain_text );
	}

	/**
	 * Print a heading and label/value rows in an email.
	 *
	 * @param string $heading    Block heading.
	 * @param array  $rows       Rows with label and value keys.
	 * @param bool   $plain_text Whether to print plain text.
	 */
	private function output_email_block( $heading, $rows, $plain_text ) {
		if ( $plain_text ) {
			echo "\n" . esc_html( $heading ) . "\n";
			foreach ( $rows as $row ) {
				echo esc_html( '' === $row['label'] ? $row['value'] : $row['label'] . ': ' . $row['value'] ) . "\n";
			}
			echo "\n";
			return;
		}

		echo '<h2>' . esc_html( $heading ) . '</h2><ul>';
		foreach ( $rows as $row ) {
			echo '<li>';
			if ( '' !== $row['label'] ) {
				echo '<strong>' . esc_html( $row['label'] ) . ':</strong> ';
			}
			echo esc_html( $row['value'] ) . '</li>';
		}
		echo '</ul>';
	}

	/**
	 * Show the submitted payment details on the admin order screen.
	 *
	 * @param WC_Order $order Order.
	 */
	public function admin_order_details( $order ) {
		if ( ! $order instanceof WC_Order || $this->id !== $order->get_payment_method() ) {
			return;
		}

		$submitted = $this->get_submitted_details( $order );

		if ( ! $submitted ) {
			return;
		}
		?>
		<div class="nmpay-admin-details">
			<h3><?php esc_html_e( 'Manual payment details', 'nauman-manual-payments' ); ?></h3>
			<p>
				<strong><?php esc_html_e( 'Paid via:', 'nauman-manual-payments' ); ?></strong> <?php echo esc_html( $submitted['method_label'] ); ?><br />
				<strong><?php esc_html_e( 'Transaction ID:', 'nauman-manual-payments' ); ?></strong> <?php echo esc_html( '' !== $submitted['txn'] ? $submitted['txn'] : __( 'not provided', 'nauman-manual-payments' ) ); ?>
				<?php if ( '' !== $submitted['sender'] ) : ?>
					<br /><strong><?php esc_html_e( 'Sent from:', 'nauman-manual-payments' ); ?></strong> <?php echo esc_html( $submitted['sender'] ); ?>
				<?php endif; ?>
			</p>
			<?php
			$verified_at = (int) $order->get_meta( self::META_VERIFIED_AT );

			if ( $verified_at ) :
				$verified_by = get_userdata( (int) $order->get_meta( self::META_VERIFIED_BY ) );
				?>
				<p>
					<strong><?php esc_html_e( 'Verified:', 'nauman-manual-payments' ); ?></strong>
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: date and time, 2: user display name. */
							__( '%1$s by %2$s', 'nauman-manual-payments' ),
							wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $verified_at ),
							$verified_by ? $verified_by->display_name : __( 'a store manager', 'nauman-manual-payments' )
						)
					);
					?>
				</p>
			<?php elseif ( $order->has_status( array( 'on-hold', 'pending' ) ) ) : ?>
				<p class="description"><?php esc_html_e( 'Check this transaction ID against your bank or wallet statement, then choose "Verify manual payment and mark as paid" in the Order actions box.', 'nauman-manual-payments' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}
}
