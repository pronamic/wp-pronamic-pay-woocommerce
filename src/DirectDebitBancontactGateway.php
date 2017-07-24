<?php

/**
 * Title: WooCommerce Direct Debit mandate via Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.2.5
 * @since unreleased
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitBancontactGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_direct_debit_bancontact';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Bancontact gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Direct Debit (mandate via Bancontact)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_BANCONTACT;

		// @since unreleased
		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
		);

		// Handle subscription payments
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );

		// Filters
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'get_available_payment_gateways' ) );

		parent::__construct();
	}

	//////////////////////////////////////////////////

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable Direct Debit (mandate via Bancontact)', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = __( 'By using this payment method you authorize us via Bancontact to debit payments from your bank account.', 'pronamic_ideal' );
		$this->form_fields['icon']['default']        = plugins_url( 'images/sepa-bancontact/wc-sepa-bancontact.png', Pronamic_WP_Pay_Plugin::$file );
	}

	/**
	 * Only show gateway if cart or order contains a subscription product.
	 *
	 * @since unreleased
	 */
	public function get_available_payment_gateways( $available_gateways ) {
		if ( ! class_exists( 'WC_Subscriptions_Cart' ) || ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return $available_gateways;
		}

		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_STRING );

		if ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_order_contains_subscription( $order_id ) ) {
			return $available_gateways;
		}

		if ( isset( $available_gateways[ self::ID ] ) ) {
			unset( $available_gateways[ self::ID ] );
		}

		return $available_gateways;
	}
}
