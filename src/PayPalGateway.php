<?php

/**
 * Title: WooCommerce PayPal gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.2.0
 * @since 1.2.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_PayPalGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_paypal';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize a gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'PayPal', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::PAYPAL;

		parent::__construct();
	}

	//////////////////////////////////////////////////

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable PayPal', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
		$this->form_fields['icon']['default']        = plugins_url( 'images/paypal/wc-icon.png', Pronamic_WP_Pay_Plugin::$file );
	}
}
