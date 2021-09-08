<?php

defined( 'ABSPATH' ) || exit;


class RFW_Ajax_Interface {

	/**
	 * Hook all the needed methods.
	 */
	public function register() {
		add_action( 'wp_ajax_rfw_get_checkout_data', [ $this, 'get_checkout_data_via_ajax' ] );
		add_action( 'wp_ajax_nopriv_rfw_get_checkout_data', [ $this, 'get_checkout_data_via_ajax' ] );
	}

	/**
	 * Echo checkout data for AJAX usage.
	 */
	public function get_checkout_data_via_ajax() {
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'rfw_checkout_action' ) ) {
			wp_send_json_error( ['message' => 'Invalid nonce provided.'] );
		}

		$order_id = filter_input( INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT );
		$order    = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( ['message' => 'Invalid order ID provided.'] );
		}

		$gateway_settings = get_option( 'woocommerce_' . RFW_PLUGIN_ID . '_settings', array() );
		if ( ! $gateway_settings ) {
			wp_send_json_error([
				'message' => 'Unable to obtain gateway settings.'
			]);
		}

		try {
			$data = RFW_Data::get_checkout_data( $order, $gateway_settings );
		} catch ( Exception $e ) {
			wp_send_json_error( ['message' => $e->getMessage()] );
		}

		if ( RFW_Data::test_mode() ) {
			$data['sandbox'] = true;
		}
		
		$data['customer'] = RFW_Data::get_customer_data( $order );
		$data['items']    = RFW_Data::get_items_data( $order );

		wp_send_json_success( $data );
	}

}