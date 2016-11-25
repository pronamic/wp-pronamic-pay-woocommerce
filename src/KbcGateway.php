<?php

/**
 * Title: WooCommerce KBC/CBC Payment Button gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.1
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_KbcGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_kbc';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an KBC/CBC Payment Button gateway.
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'KBC/CBC Payment Button', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::KBC;

		parent::__construct();
	}

	/**
	 * Initialise form fields.
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable KBC/CBC Payment Button', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
		$this->form_fields['icon']['default']        = '';
	}
}
