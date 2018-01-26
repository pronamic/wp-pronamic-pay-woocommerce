<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WooCommerce Bitcoin gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.1
 * @since 1.2.1
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_BitcoinGateway extends Pronamic_WP_Pay_Extensions_WooCommerce_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_bitcoin';

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an bank transfer gateway
	 */
	public function __construct() {
		$this->id             = self::ID;
		$this->method_title   = __( 'Bitcoin', 'pronamic_ideal' );
		$this->payment_method = PaymentMethods::BITCOIN;

		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L24
		$this->has_fields = false;

		parent::__construct();
	}
}
