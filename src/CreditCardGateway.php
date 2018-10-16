<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WooCommerce Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class CreditCardGateway extends Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_credit_card';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::CREDIT_CARD;

	/**
	 * Constructs and initialize an Credit Card gateway
	 */
	public function __construct() {
		parent::__construct();

		// Recurring subscription payments.
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( $gateway && $gateway->supports( 'recurring_credit_card' ) ) {
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
		}
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

		$this->form_fields['icon']['default']     = plugins_url( 'images/credit-card/wc-icon.png', Plugin::$file );
		$this->form_fields['icon']['description'] = sprintf(
			'%s%s<br />%s',
			$description_prefix,
			__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
			/* translators: %s: default icon URL */
			sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
		);
	}
}
