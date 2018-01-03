<?php

/**
 * Title: WooCommerce Payconiq gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Reüel van der Steege
 * @version 1.2.9
 * @since 1.2.9
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_PayconiqGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_payconiq';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an Payconiq gateway
	 */
	public function __construct() {
		$this->id                = self::ID;
		$this->method_title      = __( 'Payconiq', 'pronamic_ideal' );
		$this->payment_method    = Pronamic_WP_Pay_PaymentMethods::PAYCONIQ;
		$this->order_button_text = __( 'Proceed to Payconiq', 'pronamic_ideal' );

		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = false;

		parent::__construct();
	}
}
