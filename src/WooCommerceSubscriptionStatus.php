<?php
/**
 * WooCommerce subscription status
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;

/**
 * WooCommerce status
 */
class WooCommerceSubscriptionStatus {
	/**
	 * Status value.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * Construct WooCommerce status object.
	 *
	 * @param string $value Status value.
	 */
	public function __construct( $value ) {
		$this->value = $value;
	}

	/**
	 * Convert this WooCommerce status to a Pronamic status.
	 *
	 * @return string|null
	 */
	public function to_pronamic_status() {
		switch ( $this->value ) {
			case 'active':
				return SubscriptionStatus::ACTIVE;
			case 'cancelled':
			case 'pending-cancel':
				return SubscriptionStatus::CANCELLED;
			case 'expired':
				// @link https://woocommerce.com/document/subscriptions/statuses/#section-6
				return SubscriptionStatus::COMPLETED;
			case 'on-hold':
				return SubscriptionStatus::ON_HOLD;
			default:
				return null;
		}
	}

	/**
	 * Get status from WooCommerce subscription.
	 *
	 * @param \WC_Subscription $subscription WooCommerce subscription.
	 * @return WooCommerceSubscriptionStatus
	 */
	public static function from_subscription( \WC_Subscription $subscription ) {
		return new self( $subscription->get_status() );
	}
}
