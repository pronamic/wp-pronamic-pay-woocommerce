<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;
use WC_Subscriptions_Cart;

/**
 * Title: WooCommerce Direct Debit mandate via iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.2.1
 */
class DirectDebitIDealGateway extends Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_direct_debit_ideal';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_IDEAL;

	/**
	 * Constructs and initialize an Direct Debit (mandate via iDEAL) gateway
	 */
	public function __construct() {
		parent::__construct();

		// The iDEAL payment gateway has an issuer select field in case of the iDEAL advanced variant
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = true;

		// @since unreleased
		$this->supports = array(
			'products',
			'subscriptions',
			'subscription_amount_changes',
			'subscription_cancellation',
			'subscription_date_changes',
			'subscription_payment_method_change_customer',
			'subscription_reactivation',
			'subscription_suspension',
		);

		// Handle subscription payments
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );

		// Filters
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'get_available_payment_gateways' ) );
	}

	/**
	 * Payment fields
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v1.6.6/templates/checkout/form-pay.php#L66
	 */
	public function payment_fields() {
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L181
		parent::payment_fields();

		$gateway = Plugin::get_gateway( $this->config_id );

		if ( ! $gateway ) {
			return;
		}

		$payment_method = $gateway->get_payment_method();

		$gateway->set_payment_method( PaymentMethods::IDEAL );

		$this->print_fields( $gateway->get_input_fields() );

		$gateway->set_payment_method( $payment_method );
	}

	/**
	 * Initialise form fields
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$description_prefix = '';

		if ( WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$description_prefix = '<br />';
		}

		$this->form_fields['description']['default'] = __( 'By using this payment method you authorize us via iDEAL to debit payments from your bank account.', 'pronamic_ideal' );
		$this->form_fields['icon']['default']        = plugins_url( 'images/sepa-ideal/wc-sepa-ideal.png', Plugin::$file );
		$this->form_fields['icon']['description']    = sprintf(
			'%s%s<br />%s',
			$description_prefix,
			__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
			sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
		);
	}

	/**
	 * Only show gateway if cart or order contains a subscription product.
	 *
	 * @since unreleased
	 *
	 * @param array $available_gateways
	 *
	 * @return array
	 */
	public function get_available_payment_gateways( $available_gateways ) {
		if ( ! class_exists( 'WC_Subscriptions_Cart' ) || ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return $available_gateways;
		}

		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_STRING );

		if ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() || wcs_order_contains_subscription( $order_id ) || wcs_cart_contains_failed_renewal_order_payment() ) {
			return $available_gateways;
		}

		if ( isset( $available_gateways[ self::ID ] ) ) {
			unset( $available_gateways[ self::ID ] );
		}

		return $available_gateways;
	}
}
