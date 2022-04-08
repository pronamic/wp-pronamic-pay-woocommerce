<?php
/**
 * Payment method type
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util;

/**
 * Title: WooCommerce payment method type
 * Description:
 * Copyright: 2005-2021 Pronamic
 * Company: Pronamic
 *
 * @link https://github.com/woocommerce/woocommerce/blob/3.5.3/includes/abstracts/abstract-wc-payment-gateway.php
 * @link https://github.com/woocommerce/woocommerce/blob/3.5.3/includes/abstracts/abstract-wc-settings-api.php
 *
 * @author  Reüel van der Steege
 * @version 4.1.0
 * @since   4.1.0
 */
class PaymentMethodType extends AbstractPaymentMethodType {
	/**
	 * Flag to track if the inline script was added.
	 * 
	 * @var bool
	 */
	private $added_inline_script = false;

	/**
	 * The payment method
	 *
	 * @var string|null
	 */
	protected $payment_method;

	/**
	 * Gateway arguments.
	 *
	 * @var array<string, mixed>
	 */
	protected $gateway_args;

	/**
	 * Payment method type constructor.
	 *
	 * @param string               $name           Name.
	 * @param string|null          $payment_method Payment method.
	 * @param array<string, mixed> $gateway_args   Gateway arguments.
	 */
	public function __construct( $name, $payment_method, $gateway_args ) {
		$this->name           = $name;
		$this->payment_method = $payment_method;
		$this->gateway_args   = $gateway_args;
	}

	/**
	 * Payment method type activation.
	 *
	 * @return bool
	 */
	public function is_active() {
		$enabled = $this->get_setting( 'enabled', false );

		return \filter_var( $enabled, \FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Payment method type initialization.
	 *
	 * @return void
	 */
	public function initialize() {
		$this->settings = \get_option( 'woocommerce_' . $this->name . '_settings', array() );
	}

	/**
	 * Returns an array of supported features.
	 *
	 * @return string[]
	 */
	public function get_supported_features() {
		$gateway = new Gateway( $this->gateway_args );

		$features = array_filter( $gateway->supports, array( $gateway, 'supports' ) );

		return $features;
	}

	/**
	 * Payment method type script handles.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles() {
		$asset_file = include __DIR__ . '/../js/dist/index.asset.php';

		\wp_register_script(
			'pronamic-pay-wc-payment-method-block',
			\plugins_url( '../js/dist/index.js', __FILE__ ),
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		if ( ! $this->added_inline_script ) {
			\wp_add_inline_script(
				'pronamic-pay-wc-payment-method-block',
				'PronamicPayWooCommerce.registerMethod( "' . $this->name . '" );'
			);

			$this->added_inline_script = true;
		}

		return array( 'pronamic-pay-wc-payment-method-block' );
	}

	/**
	 * Payment method type data.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		// Order button label.
		$order_button_label = null;

		if ( null !== $this->payment_method ) {
			$order_button_label = sprintf(
				/* translators: %s: payment method title */
				__( 'Proceed to %s', 'pronamic_ideal' ),
				PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) )
			);
		}

		$description = $this->get_setting( 'description' );

		$gateway = new Gateway( $this->gateway_args );

		// Return data.
		return array(
			'title'            => $this->get_setting( 'title' ),
			'description'      => $description,
			'fields'           => array_values( (array) $gateway->get_input_fields() ),
			'icon'             => $this->get_setting( 'icon' ),
			'orderButtonLabel' => $order_button_label,
			'supports'         => $this->get_supported_features(),
		);
	}
}
