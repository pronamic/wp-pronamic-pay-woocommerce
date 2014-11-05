<?php

/**
 * Title: WooCommerce Credit Card gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2014
 * Company: Pronamic
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
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Credit Card', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::CREDIT_CARD;

		parent::__construct();
	}

	/**
     * Initialise form fields
     */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label'] = __( 'Enable Credit Card', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
		$this->form_fields['icon']['default'] = plugins_url( 'images/credit-card/icon-24x128.png', Pronamic_WP_Pay_Plugin::$file );
	}
}
