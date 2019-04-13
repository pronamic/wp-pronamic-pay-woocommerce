<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use WC_Subscriptions_Cart;

/**
 * Title: WooCommerce Direct Debit mandate via Sofort gateway
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.5
 * @since   1.2.9
 */
class DirectDebitSofortGateway extends Gateway {
	/**
	 * Constructs and initialize an Direct Debit (mandate via Sofort) gateway
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

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
	}

	/**
	 * Only show gateway if cart or order contains a subscription product.
	 *
	 * @since unreleased
	 *
	 * @param array $available_gateways Available payment gateways.
	 *
	 * @return array
	 */
	public function get_available_payment_gateways( $available_gateways ) {
		if ( ! WooCommerce::is_subscriptions_active() ) {
			return $available_gateways;
		}

		$order_id = filter_input( INPUT_GET, 'order_id', FILTER_SANITIZE_STRING );

		if ( WC_Subscriptions_Cart::cart_contains_subscription() || wcs_cart_contains_renewal() || ( ! empty( $order_id ) && wcs_order_contains_subscription( $order_id ) ) || wcs_cart_contains_failed_renewal_order_payment() ) {
			return $available_gateways;
		}

		if ( isset( $available_gateways[ $this->id ] ) ) {
			unset( $available_gateways[ $this->id ] );
		}

		return $available_gateways;
	}
}
