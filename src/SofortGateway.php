<?php

/**
 * Title: WooCommerce Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_SofortGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_sofort';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize a gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'SOFORT Banking', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::SOFORT;

		parent::__construct();
	}

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable SOFORT Banking', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
		$this->form_fields['icon']['default']        = plugins_url( 'images/sofort/wc-icon.png', Pronamic_WP_Pay_Plugin::$file );
	}
}
