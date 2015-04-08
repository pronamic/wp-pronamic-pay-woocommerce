<?php

/**
 * Title: WooCommerce Pronamic gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2015
 * Company: Pronamic
 * @author Remco Tolsma
 * @version 1.1.2
 * @since 1.1.2
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_PronamicGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		$this->id           = self::ID;
		$this->method_title = __( 'Pronamic', 'pronamic_ideal' );

		parent::__construct();
	}

	/**
	 * Initialise form fields
	 */
	function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['enabled']['label']       = __( 'Enable Pronamic', 'pronamic_ideal' );
		$this->form_fields['description']['default'] = '';
	}
}
