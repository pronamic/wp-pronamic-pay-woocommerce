<?php
/**
 * Plugin Name: wp-env helper
 * Requires Plugins: pronamic-ideal, pronamic-pay-woocommerce, woocommerce-subscriptions
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

add_action(
	'plugins_loaded',
	function () {
		activate_plugin( 'pronamic-ideal/pronamic-ideal.php' );
		activate_plugin( 'pronamic-pay-woocommerce/pronamic-pay-woocommerce.php' );
		activate_plugin( 'woocommerce-subscriptions/woocommerce-subscriptions.php' );
	}
);
