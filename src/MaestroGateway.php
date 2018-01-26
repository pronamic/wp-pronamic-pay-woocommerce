<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WooCommerce Maestro gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.2
 * @since 1.2.2
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_MaestroGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_maestro';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Maestro', 'pronamic_ideal' );
		$this->payment_method = PaymentMethods::MAESTRO;

		parent::__construct();
	}
}
