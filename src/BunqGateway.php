<?php

/**
 * Title: WooCommerce bunq gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.7
 * @since 1.2.7
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_BunqGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_bunq';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an bank transfer gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'bunq', 'pronamic_ideal' );
		$this->payment_method = Pronamic_WP_Pay_PaymentMethods::BUNQ;

		// @since 1.2.7
		$this->order_button_text = __( 'Proceed to bunq', 'pronamic_ideal' );

		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = false;

		parent::__construct();
	}
}
