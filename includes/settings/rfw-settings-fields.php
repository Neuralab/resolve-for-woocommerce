<?php
defined( 'ABSPATH' ) || exit;

return array(
	'enabled' => array(
		'title'     => __( 'Enable', 'resolve' ),
		'type'      => 'checkbox',
		'label'     => __( 'Enable Resolve Payment Gateway', 'resolve' ),
		'default'   => 'no',
		'desc_tip'  => false
	),
	'title' => array(
		'title'       => __( 'Title', 'resolve' ),
		'type'        => 'text',
		'description' => __( 'This controls the title which the user sees during the checkout.', 'resolve' ),
		'default'     => __( 'Resolve', 'resolve' ),
		'desc_tip'    => true
	),
	'description-msg' => array(
		'title'       => __( 'Description', 'resolve' ),
		'type'        => 'textarea',
		'description' => __( 'Payment method description that the customer will see on the checkout page.', 'resolve' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'confirmation-msg' => array(
		'title'       => __( 'Confirmation', 'resolve' ),
		'type'        => 'textarea',
		'description' => __( 'Confirmation message that will be added to the "thank you" page.', 'resolve' ),
		'default'     => __( 'Your account has been charged and your transaction is successful.', 'resolve' ),
		'desc_tip'    => true
	),
	'receipt-redirect-msg' => array(
		'title'       => __( 'Receipt', 'resolve' ),
		'type'        => 'textarea',
		'description' => __( 'Message that will be added to the "receipt" page. Shown if automatic redirect is enabled.', 'resolve' ),
		'default'     => '',
		'desc_tip'    => true
	),
	'merchant-settings'      => array(
		'title'       => __( 'Merchant settings', 'resolve' ),
		'type'        => 'title',
		'description' => '',
	),
	'webshop-merchant-id' => array(
		'title'       => __( 'Merchant ID', 'resolve' ),
		'type'        => 'text',
	),
	'webshop-api-key' => array(
		'title'       => __( 'API Key', 'resolve' ),
		'type'        => 'password',
	),
	'test-webshop-merchant-id' => array(
		'title'       => __( 'TEST Merchant ID', 'resolve' ),
		'type'        => 'text',
	),
	'test-webshop-api-key' => array(
		'title'       => __( 'TEST API Key', 'resolve' ),
		'type'        => 'password',
	),
	'webshop-settings'      => array(
		'title'       => __( 'Webshop settings', 'resolve' ),
		'type'        => 'title',
		'description' => '',
	),
	'payment-mode' => array( // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
		'title'       => __( 'Payment mode', 'resolve' ),
		'type'        => 'select',
		'class'       => 'wc-enhanced-select',
		'description' => __( 'Capture mode will automatically capture all authorized Resolve orders. All automatically captured orders will be updated with an order status of; "Processing".', 'resolve' ),
		'default'     => 'authorize',
		'options'     => array(
			'authorize' => __( 'Authorize', 'resolve' ),
			'capture'   => __( 'Capture', 'resolve' ),
		),
	),
	'backorder-disable' => array(
		'title'       => __( 'Backorder disable', 'resolve' ),
		'type'        => 'checkbox',
		'label'       => __( 'Disable for backordered items', 'resolve' ),
		'description' => __( 'Resolve will not display as a payment method for orders that contain backordered items.', 'resolve' ),
		'default'     => 'no',
	),
	'order-limit-min' => array(
		'title'       => __( 'Set minimum order price', 'resolve' ),
		'type'        => 'number',
		'label'       => __( 'Set minimum order price', 'resolve' ),
		'description' => __( 'Resolve will not display as a payment method for orders below the defined minimum price.', 'resolve' ),
	),
	'order-limit-max' => array(
		'title'       => __( 'Set maximum order price', 'resolve' ),
		'type'        => 'number',
		'label'       => __( 'Set maximum order price', 'resolve' ),
		'description' => __( 'Resolve will not display as a payment method for orders that exceed the defined maximum price.', 'resolve' ),
	),
	'auto-redirect' => array(
		'title'       => __( 'Automatic redirect', 'resolve' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable automatic redirect to the Resolve checkout form.', 'resolve' ),
		'description' => __( 'This option is using JavaScript (Ajax).', 'resolve' ),
		'default'     => 'yes',
		'desc_tip'    => true
	),
	'advanced-settings'        => array(
		'title'       => __( 'Advanced settings', 'kekspay' ),
		'type'        => 'title',
		'description' => '',
	),
	'in-test-mode' => array(
		'title'       => __( 'Resolve Sandbox mode', 'resolve' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable Sandbox test mode.', 'resolve' ),
		'description' => __( 'Mode used for testing purposes, disable this for live web shop.', 'resolve' ),
		'default'     => 'no',
		'desc_tip'    => true
	),
	'use-logger' => array(
		'title'       => __( 'Debug log', 'resolve' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'resolve' ),
		'description' => sprintf( __( 'Log gateway events, stored in %s. Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'resolve' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'resolve' ) . '</code>' ),
		'default'     => 'no',
	),
);