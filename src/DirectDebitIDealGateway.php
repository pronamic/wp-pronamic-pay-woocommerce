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
			//'gateway_scheduled_payments',
			'subscriptions',
			'subscription_cancellation',
			'subscription_reactivation',
			'subscription_suspension',
			//'subscription_payment_method_change_customer',
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
			$mandate = $gateway->has_valid_mandate();

			if ( $mandate ) {
				printf(
					esc_html__( 'You have given us permission at %s to debit any due amounts from your bank account. This mandate will be used for your (subscription) order.', 'pronamic_ideal' ),
					$gateway->get_first_valid_mandate_datetime()
				);

				return;
			}

			$payment_method = $gateway->get_payment_method();

			$gateway->set_payment_method( Pronamic_WP_Pay_PaymentMethods::IDEAL );

			$fields = $gateway->get_input_fields();

			foreach ( $fields as &$field ) {
				if ( isset( $field['id'] ) && 'pronamic_ideal_issuer_id' === $field['id'] ) {
					$field['id'] = $this->id . '_issuer_id';
					$field['name'] = $this->id . '_issuer_id';

					break;
				}
			}

			echo $gateway->get_input_html( $fields );

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
			$subscription->update_status( 'on-hold', __( 'Subscription renewal payment due.', 'pronamic-ideal' ) );

			if ( in_array( 'gateway_scheduled_payments', $this->supports, true ) ) {
				$order = wcs_create_renewal_order( $subscription );

				if ( is_wp_error( $order ) ) {
					// Try again
					$order = wcs_create_renewal_order( $subscription );

					if ( is_wp_error( $order ) ) {
						throw new Exception( __( 'Error: Unable to create renewal order from scheduled payment. Please try again.', 'pronamic-ideal' ) );
					}
				}
			}

			if ( ! $subscription->is_manual() ) {
				$order->set_payment_method( $subscription->payment_gateway );

				$this->process_payment( $order->id );

				Pronamic_WP_Pay_Plugin::update_payment( $this->payment, false );
			}
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
