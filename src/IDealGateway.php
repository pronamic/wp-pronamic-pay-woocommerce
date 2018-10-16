<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WooCommerce iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.0
 * @since   1.0.0
 */
class IDealGateway extends Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_ideal';


	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::IDEAL;

	/**
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		// The iDEAL payment gateway has an issuer select field in case of the iDEAL advanced variant
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = true;

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

		$this->form_fields['description']['default'] = __( 'With iDEAL you can easily pay online in the secure environment of your own bank.', 'pronamic_ideal' );
		$this->form_fields['icon']['default']        = plugins_url( 'images/ideal/wc-icon.png', Plugin::$file );
		$this->form_fields['icon']['description']    = sprintf(
			'%s%s<br />%s',
			$description_prefix,
			__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
			/* translators: %s: default icon URL */
			sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
		);
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
}
