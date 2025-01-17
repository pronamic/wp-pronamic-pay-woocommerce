<?php
/**
 * Subscription helper
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use WC_Subscription;

/**
 * Subscription helper class
 */
class SubscriptionHelper {
	/**
	 * WooCommerce subscription.
	 *
	 * @var WC_Subscription
	 */
	private $woocommerce_subscription;

	/**
	 * Construct subscription updater.
	 *
	 * @param WC_Subscription $woocommerce_subscription WooCommerce subscription.
	 */
	public function __construct( WC_Subscription $woocommerce_subscription ) {
		$this->woocommerce_subscription = $woocommerce_subscription;
	}

	/**
	 * Get Pronamic subscription.
	 *
	 * @return Subscription|null
	 */
	public function get_pronamic_subscription() {
		$woocommerce_subscription = $this->woocommerce_subscription;

		$pronamic_subscription_id = $woocommerce_subscription->get_meta( 'pronamic_subscription_id' );

		if ( empty( $pronamic_subscription_id ) ) {
			return null;
		}

		return \get_pronamic_subscription( $pronamic_subscription_id );
	}
}
