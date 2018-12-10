<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;
use WC_Subscriptions_Cart;

/**
 * Title: WooCommerce Direct Debit mandate via Bancontact gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.0
 * @since   1.2.7
 */
class DirectDebitBancontactGateway extends Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_direct_debit_bancontact';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::DIRECT_DEBIT_BANCONTACT;

	/**
	 * Constructs and initialize an Direct Debit (mandate via Bancontact) gateway
	 */
	public function __construct() {
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

		// Handle subscription payments.
		add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );

		// Filters.
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'get_available_payment_gateways' ) );

		parent::__construct();
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

		$this->form_fields['description']['default'] = sprintf(
			/* translators: %s: payment method */
			__( 'By using this payment method you authorize us via %s to debit payments from your bank account.', 'pronamic_ideal' ),
			__( 'Bancontact', 'pronamic_ideal' )
		);
		$this->form_fields['icon']['default']     = plugins_url( 'images/sepa-bancontact/wc-sepa-bancontact.png', Plugin::$file );
		$this->form_fields['icon']['description'] = sprintf(
			'%s%s<br />%s',
			$description_prefix,
			__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
			/* translators: %s: default icon URL */
			sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
		);
	}

	/**
	 * Only show gateway if cart or order contains a subscription product.
	 *
	 * @since unreleased
	 *
	 * @param array $available_gateways Available payment gateway.
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
