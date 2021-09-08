jQuery( function( $ ) {
	'use strict';

	if ( typeof RFWPaymentGateway === 'undefined' ) {
		return;
	}
	
	if ( ! $('#rfw-order-id').length || ! $('#rfw_nonce').length ) {
		return;
	}
	
	let data = {
		action:           'rfw_get_checkout_data',
		order_id:         $('#rfw-order-id').val(),
		nonce:            $('#rfw_nonce').val(),
		_wp_http_referer: $('#rfw-payment-form input[name="_wp_http_referer"]').val()
	}
	
	$.post( RFWPaymentGateway.ajax_url, data )
	.done( function( response ) {
		let $payBtn = $( '#rfw-pay' );

		if ( $payBtn.length ) {
			$payBtn.on( 'click', (e) => resolve.checkout(response.data) );
		} else {
			resolve.checkout(response.data);
		}

	} )
	.fail( function() {
		console.log('Failed to obtain checkout data');
	} );

} );