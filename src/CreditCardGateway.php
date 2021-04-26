<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WooCommerce Credit Card gateway
 * Description:
 * Copyright: 2005-2021 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.5
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * Constructs and initialize an Credit Card gateway
	 *
	 * @param array<string, string> $args Arguments.
	 */
	public function __construct( $args = array() ) {
		parent::__construct( $args );

		// Recurring subscription payments.
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( $gateway && $gateway->supports( 'recurring_credit_card' ) ) {
			// @since unreleased
			$this->supports = \array_merge(
				array(
					'subscriptions',
					'subscription_amount_changes',
					'subscription_cancellation',
					'subscription_date_changes',
					'subscription_payment_method_change_customer',
					'subscription_reactivation',
					'subscription_suspension',
				),
				$this->supports
			);

			// Handle subscription payments.
			add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, array( $this, 'process_subscription_payment' ), 10, 2 );
		}
	}
}
