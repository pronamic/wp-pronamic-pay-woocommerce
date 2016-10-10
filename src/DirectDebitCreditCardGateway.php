<?php

/**
 * Title: WooCommerce Direct Debit mandate via Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.0.0
 * @since unreleased
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitCreditCardGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_direct_debit_credit_card';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Credit Card gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Direct Debit (mandate via Credit Card)', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_CREDIT_CARD;

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
			$mandate = $gateway->has_valid_mandate();

			if ( $mandate ) {
				echo '<p>';

				printf(
					esc_html__( 'You have given us permission on %s to debit any due amounts from your bank account. This mandate will be used for your (subscription) order.', 'pronamic_ideal' ),
					$gateway->get_first_valid_mandate_datetime()
				);

				echo '</p>';

				return;
			}

			$payment_method = $gateway->get_payment_method();

			$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD );

			$this->print_fields( $gateway->get_input_fields() );

			$gateway->set_payment_method( $payment_method );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable Direct Debit (mandate via Credit Card)', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = __( 'By using this payment method you authorize us via credit card to debit payments from your bank account.', 'pronamic_ideal' );

		//@todo add icon images/sepa-credit-card/wc-sepa-credit-card.png
		$this->form_fields['icon']['default']        = plugins_url( 'images/credit-card/wc-icon.png', Pronamic_WP_Pay_Plugin::$file );
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
			$subscription->update_status( 'on-hold', __( 'Subscription renewal payment due.', 'pronamic_ideal' ) );

			if ( ! $subscription->is_manual() ) {
				$order->set_payment_method( $subscription->payment_gateway );

				$this->process_payment( $order->id );

				if ( $this->payment ) {
					Pronamic_WP_Pay_Plugin::update_payment( $this->payment, false );
				}
			}
		}
	}
}
