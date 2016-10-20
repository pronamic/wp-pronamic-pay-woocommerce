<?php

/**
 * Title: WooCommerce Direct Debit mandate via iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.2.1
 * @since 1.2.1
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
			$mandate = $gateway->has_valid_mandate( Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT );

			if ( $mandate ) {
				echo '<p>';

				printf(
					esc_html__( 'You have given us permission on %s to debit any due amounts from your bank account. This mandate will be used for your (subscription) order.', 'pronamic_ideal' ),
					$gateway->get_first_valid_mandate_datetime( Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT )
				);

				echo '</p>';

				return;
			}

			$payment_method = $gateway->get_payment_method();

			$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

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

		$this->form_fields['enabled']['label']       = __( 'Enable Direct Debit (mandate via iDEAL)', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = __( 'By using this payment method you authorize us via iDEAL to debit payments from your bank account.', 'pronamic_ideal' );
		$this->form_fields['icon']['default']        = plugins_url( 'images/sepa-ideal/wc-sepa-ideal.png', Pronamic_WP_Pay_Plugin::$file );
	}
}
