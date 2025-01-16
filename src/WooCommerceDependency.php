<?php
/**
 * WooCommerce Dependency
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Dependencies\Dependency;

/**
 * WooCommerce Dependency
 *
 * @author  Reüel van der Steege
 * @version 2.1.0
 * @since   2.1.0
 */
class WooCommerceDependency extends Dependency {
	/**
	 * Is met.
	 *
	 * @link
	 * @return bool True if dependency is met, false otherwise.
	 */
	public function is_met() {
		if ( ! \defined( '\WOOCOMMERCE_VERSION' ) ) {
			return false;
		}

		return true;
	}
}
