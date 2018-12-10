<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Core\PaymentMethods;

/**
 * Title: WooCommerce In3 gateway.
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.1
 * @since   2.0.1
 */
class In3Gateway extends Gateway {
	/**
	 * The unique ID of this payment gateway.
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_in3';

	/**
	 * Payment method.
	 *
	 * @var string
	 */
	protected $payment_method = PaymentMethods::IN3;
}
