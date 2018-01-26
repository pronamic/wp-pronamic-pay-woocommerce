<?php
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Plugin;

/**
 * Title: WooCommerce Sofort gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.7
 * @since 1.1.0
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
		$this->payment_method = PaymentMethods::SOFORT;

		// @since 1.2.7
		$this->order_button_text = __( 'Proceed to SOFORT Banking', 'pronamic_ideal' );

		parent::__construct();
	}

	/**
	 * Initialise form fields
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$description_prefix = '';

		if ( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$description_prefix = '<br />';
		}

		$this->form_fields['icon']['default']     = plugins_url( 'images/sofort/wc-icon.png', Plugin::$file );
		$this->form_fields['icon']['description'] = sprintf(
			'%s%s<br />%s',
			$description_prefix,
			__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
			sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
		);
	}
}
