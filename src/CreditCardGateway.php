<?php

/**
 * Title: WooCommerce Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_CreditCardGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_credit_card';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Credit Card gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Credit Card', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD;

		parent::__construct();

		// Recurring subscription payments
		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->config_id );

		if ( $gateway && $gateway->supports( 'recurring_credit_card' ) ) {
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
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable Credit Card', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
		$this->form_fields['icon']['default']        = plugins_url( 'images/credit-card/wc-icon.png', Pronamic_WP_Pay_Plugin::$file );
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

		if ( ! $this->supports( 'subscriptions' ) ) {
			return;
		}

		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->config_id );

		if ( $gateway ) {
			$mandate = $gateway->has_valid_mandate( Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD );

			if ( $mandate ) {
				echo '<p>';

				printf(
					esc_html__( 'You have given us permission on %s to use your credit card for any due amounts. This mandate will be used for your (subscription) order.', 'pronamic_ideal' ),
					$gateway->get_first_valid_mandate_datetime( Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD )
				);

				echo '</p>';

				return;
			}
		}
	}
}
