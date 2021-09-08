<?php
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
	return;
}

class RFW_Payment_Gateway extends WC_Payment_Gateway {

	/**
	 * Class constructor with basic gateway's setup.
	 * @param bool $init  Should the class attributes be initialized.
	 */
	public function __construct() {
		$this->id                 = RFW_PLUGIN_ID;
		$this->method_title       = __( 'Resolve', 'resolve' );
		$this->method_description = __( 'A payment gateway for Resolve.', 'kekspay' );
		$this->has_fields         = true;

		$this->init_form_fields();
		$this->init_settings();

		$this->supports = ['products'];

		$this->title = esc_attr( $this->settings['title'] );
		$this->add_actions();

		if ( is_wc_endpoint_url( 'order-pay' ) || is_checkout() ) {
			add_action( 'wp_enqueue_scripts', [ $this, 'register_scripts' ] );
		}
	}

	/**
	 * Register Resolve and plugin's JS script.
	 */
	public function register_scripts() {
		wp_enqueue_script( 'rfw-vendor-js', '//app.paywithresolve.com/js/resolve.js', [], false, true );

		wp_enqueue_script( 'rfw-client-js', RFW_DIR_URL . '/assets/rfw-client.js', ['jquery'], false, true );

		wp_localize_script( 'rfw-client-js', 'RFWPaymentGateway', [
			'ajax_url' => admin_url( 'admin-ajax.php' )
		]);
	}

	/**
	 * Return true if payment gateway needs further setup.
	 *
	 * @override
	 * @return bool
	 */
	public function needs_setup() {
		if ( ! isset( $this->settings['merchant-id'] ) || ! isset( $this->settings['api-key'] ) ) {
			return true;
		}

		return empty( $this->settings['merchant-id'] ) || empty( $this->settings['api-key'] );
	}

	/**
	 * Register different actions.
	 */
	private function add_actions() {
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options'] );
		add_action( 'woocommerce_receipt_' . $this->id, [ $this, 'do_receipt_page' ] );

		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'show_confirmation_message' ] );
		add_action( 'woocommerce_thankyou_' . $this->id, [ $this, 'process_response' ] );

		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'maybe_disable' ], 90, 1 );

		// single order hooks
		add_action( 'woocommerce_order_item_add_action_buttons', [ $this, 'display_capture_button' ], 10, 1 );
		add_action( 'save_post', [ $this, 'process_capture' ] );
	}

	/**
	 * Define gateway's fields visible at WooCommerce's Settings page and
	 * Checkout tab.
	 */
	public function init_form_fields() {
		$this->form_fields = include( RFW_DIR_PATH . '/includes/settings/rfw-settings-fields.php' );
	}

	/**
	 * Echoes gateway's options (Checkout tab under WooCommerce's settings).
	 * @override
	 */
	public function admin_options() {
		?>
		<h2><?php esc_html_e( 'Resolve', 'resolve' ); ?></h2>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * Display description of the gateway on the checkout page.
	 * @override
	 */
	public function payment_fields() {
		if ( isset( $this->settings['description-msg'] ) && !empty( $this->settings['description-msg'] ) ) {
			echo '<p>' . wptexturize( $this->settings['description-msg'] ) . '</p>';
		}

		if ( $this->settings['in-test-mode'] === 'yes' ) {
			$test_mode_notice = '<p><b>' . __( 'Resolve Payment Gateway is currently in test mode, disable it for live web shop.', 'resolve' ); '</b></p>';
			$test_mode_notice = apply_filters( 'rfw_payment_description_test_mode_notice', $test_mode_notice );

			if ( !empty( $test_mode_notice ) ) {
				echo $test_mode_notice;
			}
		}
	}

	/**
	 * Echo confirmation message on the 'thank you' page.
	 */
	public function show_confirmation_message() {
		if ( isset( $this->settings['confirmation-msg'] ) && !empty( $this->settings['confirmation-msg'] ) ) {
			echo '<p>' . wptexturize( $this->settings['confirmation-msg'] ) . '</p>';
		}
	}

	/**
	 * Echo redirect message on the 'receipt' page.
	 */
	private function show_receipt_message() {
		if ( isset( $this->settings['receipt-redirect-msg'] ) && !empty( $this->settings['receipt-redirect-msg'] ) ) {
			echo '<p>' . wptexturize( $this->settings['receipt-redirect-msg'] ) . '</p>';
		}
	}

	/**
	 * Trigger actions for 'receipt' ('order-pay') page.
	 * @param int $order_id
	 */
	public function do_receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return false;
		}
		?>
		<form id="rfw-payment-form" action="" method="post">
			<input id="rfw-order-id" type="hidden" value="<?php echo esc_attr( $order_id ); ?>">
			<?php wp_nonce_field( 'rfw_checkout_action', 'rfw_nonce' ); ?>
			<?php if ( $this->settings['auto-redirect'] !== 'yes' ): ?>

				<?php $this->show_receipt_message(); ?>
	
				<button type="button" id="rfw-pay" class="button btn-primary">
					<?php esc_html_e( 'Pay', 'resolve' ); ?>
				</button>

			<?php endif; ?>
		</form>
		<?php
	}


	/**
	 * Remove payment gateway from list of available ones depending on plugin settings.
	 *
	 * @param   array  $available_gateways  Array of available gateways.
	 *
	 * @return  array                       Edited array of available gateways.
	 */
	public function maybe_disable( $available_gateways ) {
		if ( is_admin() ) {
			return $available_gateways;
		}

		// Remove payment gateway if any of items in cart are on backorder.
		if ( RFW_Data::is_backorder_pay_disabled() ) {
			foreach( WC()->cart->get_cart() as $cart_item ){
				if( $cart_item['data']->is_on_backorder( $cart_item['quantity'] ) ) {
					unset( $available_gateways['resolve'] );
					break;
				}
			}
		}

		// Remove payment gateway if order totals do not match limits from settings.
		$order_min = (float) RFW_Data::get_settings( 'order-limit-min' ) ?: null;
		$order_max = (float) RFW_Data::get_settings( 'order-limit-max' ) ?: null;

		if ( $order_min || $order_max ) {
			$total = (float) WC()->cart->get_total( 'raw' );

			if ( ( $order_min && $total < $order_min ) || ( $order_max && $total > $order_max ) ) {
				unset( $available_gateways['resolve'] );
			}
		}

		return $available_gateways;
	}

	/**
	 * Process the payment and return the result.
	 * @override
	 * @param string $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = new WC_Order( $order_id );

		return [
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true )
		];
	}

	/**
	 * Try to extract charge and loan ID from URL, and save them to the order.
	 * @param int $order_id
	 */
	public function process_response( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$loan_id   = $order->get_meta( 'rfw_loan_id', true );
		$charge_id = $order->get_meta( 'rfw_charge_id', true );
		if ( $loan_id || $charge_id ) {
			return; // Data aleady saved, bail early.
		}

		$note = __( 'Order successfully processed by the Resolve payment system', 'resolve' );

		$charge_id = filter_input( INPUT_GET, 'charge_id', FILTER_SANITIZE_STRING );
		if ( $charge_id ) {
			$note .= sprintf( __( ', charge ID: %s', 'resolve' ), '<b>' . $charge_id . '</b>' );
			$order->add_meta_data( 'rfw_charge_id', $charge_id, true );
		}

		$loan_id = filter_input( INPUT_GET, 'loan_id', FILTER_SANITIZE_STRING );
		if ( $loan_id ) {
			$note .= sprintf( __( ', loan ID: %s', 'resolve' ), '<b>' . $loan_id . '</b>' );
			$order->add_meta_data( 'rfw_loan_id', $loan_id, true );
		}

		$order->add_order_note( $note );
		$order->save();

		if ( RFW_Data::is_mode_capture() ) {
			$this->send_capture_request( $charge_id, $order );
		}
	}

	/**
	 * Register capture button for single order admin view.
	 * @param WC_Order $order
	 */
	public function display_capture_button( $order ) {
		if ( $this->id !== $order->get_payment_method() ) {
			return;
		}

		if ( ! $order->get_meta( 'rfw_charge_id', true ) ) {
			return;
		}

		if ( $order->get_meta( 'rfw_payment_captured', true ) === 'yes' ) {
			return;
		}

		wp_nonce_field( 'rfw_capture_payment', 'rfw_capture_nonce' );
		?>
		<button type="submit" name="rfw_capture_payment" id="rfw-capture-payment" class="button" value="yes">
			<?php esc_html_e( 'Capture', 'resolve' ); ?>
		</button>
		<?php
	}

	/**
	 * Process the capture action if possible.
	 */
	public function process_capture( $post_id ) {
		if ( get_post_type( $post_id ) !== 'shop_order' ) {
			return;
		}

		// is the capture button clicked?
		if ( ! isset( $_POST['rfw_capture_payment'] ) ) {
			return;
		}

		if ( $_POST['rfw_capture_payment'] !== 'yes' ) {
			return;
		}

		// check the nonce!
		if ( ! isset( $_POST['rfw_capture_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['rfw_capture_nonce'], 'rfw_capture_payment' ) ) {
			return;
		}

		$order = wc_get_order( $post_id );
		if ( ! $order ) {
			return;
		}

		if ( $order->get_meta( 'rfw_payment_captured', true ) === 'yes' ) {
			return;
		}

		$charge_id = $order->get_meta( 'rfw_charge_id', true );
		if ( ! $charge_id ) {
			$charge_id = $order->get_meta( 'rfw_loan_id', true );

			if ( ! $charge_id ) {
				return;
			}
		}

		$this->send_capture_request( $charge_id, $order );
	}

	/**
	 * Send a capture request to the API.
	 * @param  string $charge_id
	 * @return WP_Error|array
	 */
	private function send_capture_request( $charge_id, $order ) {
		$url_format = 'https://%s:%s@%s.resolvepay.com/api/charges/%s/capture';

		$merchant_id = RFW_Data::get_settings( 'webshop-merchant-id', true );
		$secret_key = RFW_Data::get_settings( 'webshop-api-key', true );

		$mode = $this->settings['in-test-mode'] === 'yes' ? 'app-sandbox' : 'app';
		$url  = sprintf( $url_format, $merchant_id, $secret_key, $mode, $charge_id );
		$args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( $merchant_id . ':' . $secret_key )
			]
		];
		
		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$order->add_order_note( sprintf( __( 'Failed to capture the payment! Error message: %s', 'resolve' ), '<b>' . $response->get_error_message() . '</b>' ) );
		} else {
			try {
				$code = $response['response']['code'];
				$body = json_decode( $response['body'], true );

				if ( isset( $body['error'] ) ) {
					$order->add_order_note( sprintf( __( 'Failed to capture the payment! Resolve returned an error message: %s', 'resolve' ), '<b>' . $body['error']['message'] . '</b>' ) );
				} else {
					$order->set_status( 'processing', sprintf( __( 'The payment was successfully captured! Resolve ID: %s.', 'resolve' ), '<b>' . $body['number'] . '</b>' ) );

					$order->payment_complete( $body['number'] );
					$order->add_meta_data( 'rfw_payment_captured', 'yes', true );
					$order->save();
				}
			} catch ( Exception $e ) {
				$note  = __( 'Failed to capture the payment because of the unknown error.', 'resolve' );
				$order->add_order_note( $note );
			}

		}
	}
}
