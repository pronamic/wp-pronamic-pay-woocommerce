<?php
/**
 * Plugin Name: Pronamic Pay WooCommerce Add-On
 * Plugin URI: https://www.pronamic.eu/plugins/pronamic-pay-woocommerce/
 * Description: Extend the Pronamic Pay plugin with WooCommerce support to receive payments through a variety of payment providers.
 *
 * Version: 4.15.1
 * Requires at least: 6.8
 * Requires PHP: 8.3
 *
 * Author: Pronamic
 * Author URI: https://www.pronamic.eu/
 *
 * Text Domain: pronamic-pay-woocommerce
 * Domain Path: /languages/
 *
 * License: GPL-3.0-or-later
 *
 * GitHub URI: https://github.com/pronamic/wp-pronamic-pay-woocommerce
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2026 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Autoload.
 */
require_once __DIR__ . '/vendor/autoload_packages.php';

/**
 * Bootstrap.
 */
\Pronamic\WordPress\Pay\Plugin::instance(
	[
		'file'             => __FILE__,
		'action_scheduler' => __DIR__ . '/packages/woocommerce/action-scheduler/action-scheduler.php',
	]
);

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
