<?php
/**
 * Bootstrap tests
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

putenv( 'WP_PHPUNIT__TESTS_CONFIG=tests/wp-config.php' );

require_once __DIR__ . '/../vendor/autoload.php';

require_once getenv( 'WP_PHPUNIT__DIR' ) . '/includes/functions.php';

/**
 * Manually load plugin.
 */
function _manually_load_plugin() {
	global $pronamic_ideal;

	// Load WooCommerce and WooCommerce Subscriptions.
	require __DIR__ . '/../wp-content/plugins/woocommerce/woocommerce.php';
	require __DIR__ . '/../wp-content/plugins/woocommerce-subscriptions/woocommerce-subscriptions.php';

	$pronamic_ideal = pronamic_pay_plugin();
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Bootstrap.
require getenv( 'WP_PHPUNIT__DIR' ) . '/includes/bootstrap.php';
