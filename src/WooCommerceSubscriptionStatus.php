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
		return match ( $this->value ) {
			'active' => SubscriptionStatus::ACTIVE,
			'cancelled', 'pending-cancel' => SubscriptionStatus::CANCELLED,
			'expired' => SubscriptionStatus::COMPLETED,
			'on-hold' => SubscriptionStatus::ON_HOLD,
			default => null,
		};
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
