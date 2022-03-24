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
	 * The payment method
	 *
	 * @var string|null
	 */
	protected $payment_method;

	/**
	 * Supported features.
	 *
	 * @var string[]
	 */
	protected $supports;

	/**
	 * Gateway arguments.
	 *
	 * @var array<string, mixed>
	 */
	protected $gateway_args;

	/**
	 * Payment method type constructor.
	 *
	 * @param array<string, mixed> $args Arguments.
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'name'           => null,
				'payment_method' => null,
				'supports'       => array( 'products' ),
			)
		);

		if ( empty( $args['name'] ) ) {
			return;
		}

		$this->gateway_args = $args;

		$this->name           = $args['name'];
		$this->payment_method = $args['payment_method'];
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

		// Return data.
		return array(
			'title'            => $this->get_setting( 'title' ),
			'description'      => $this->get_setting( 'description' ),
			'icon'             => $this->get_setting( 'icon' ),
			'orderButtonLabel' => $order_button_label,
			'supports'         => $this->get_supported_features(),
		);
	}
}
