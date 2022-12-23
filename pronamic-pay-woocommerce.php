<?php
/**
 * Plugin Name: Pronamic Pay WooCommerce Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-woocommerce/
 * Description: Extend the Pronamic Pay plugin with WooCommerce support to receive payments through a variety of payment providers.
 *
 * Version: 4.4.0
 * Requires at least: 5.9
 * Requires PHP: 8.0
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-woocommerce
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * Requires Plugins: pronamic-ideal, woocommerce
 * Depends: wp-pay/core
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-woocommerce
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

add_filter(
	'pronamic_pay_plugin_integrations',
	function ( $integrations ) {
		foreach ( $integrations as $integration ) {
			if ( $integration instanceof \Pronamic\WordPress\Pay\Extensions\WooCommerce\Extension ) {
				return $integrations;
			}
		}

		$integrations[] = new \Pronamic\WordPress\Pay\Extensions\WooCommerce\Extension();

		return $integrations;
	}
);
