<?php

/**
 * Title: WooCommerce Direct Debit mandate via iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since unreleased
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitIDealGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_direct_debit_ideal';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Direct Debit (mandate via iDEAL)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_IDEAL;

		// The iDEAL payment gateway has an issuer select field in case of the iDEAL advanced variant
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = true;

		// @since unreleased
		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_cancellation',
			'gateway_scheduled_payments',
		);

		// Handle subscription payments
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );

		// Handle subscription cancellations
		add_action( 'woocommerce_subscription_pending-cancel_' . $this->id, array( $this, 'subscription_pending_cancel' ) );

		parent::__construct();
	}

	//////////////////////////////////////////////////

	/**
	 * Payment fields
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v1.6.6/templates/checkout/form-pay.php#L66
	 */
	function payment_fields() {
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L181
		parent::payment_fields();

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->config_id );

		if ( $gateway ) {
			$payment_method = $gateway->get_payment_method();

			$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

			echo $gateway->get_input_html();

			$gateway->set_payment_method( $payment_method );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable Direct Debit (mandate via iDEAL)', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = __( 'By using this payment method you authorize us via iDEAL to debit payments from your bank account.', 'pronamic_ideal' );
	}

	//////////////////////////////////////////////////

	/**
	 * Process WooCommerce Subscriptions payment.
	 *
	 * @param WC_Product_Subscription $subscription
	 */
	function process_subscription_payment( $amount, $order ) {
		$this->is_recurring = true;

		$subscriptions = wcs_get_subscriptions_for_order( $order->id );

		if ( wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
		}

		foreach ( $subscriptions as $subscription_id => $subscription ) {
			if ( in_array( 'gateway_scheduled_payments', $this->supports ) ) {
				$order = wcs_create_renewal_order( $subscription );
			}

			$this->process_payment( $order->id );

			Pronamic_WP_Pay_Plugin::update_payment( $this->payment, false );
		}
	}

	/**
	 * Process WooCommerce Subscriptions cancellation.
	 *
	 * @param WC_Product_Subscription $subscription
	 */
	function subscription_pending_cancel( $subscription ) {
		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->config_id );

		if ( $gateway ) {
			$payment = get_pronamic_payment_by_meta( '_pronamic_payment_source_id', $subscription->order->id );

			if ( $payment ) {
				$gateway->cancel_subscription( $payment->get_subscription() );
			}
		}
	}
}
