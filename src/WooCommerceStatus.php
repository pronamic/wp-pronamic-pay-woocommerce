<?php
/**
 * WooCommerce status
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2021 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;

/**
 * WooCommerce status
 */
class WooCommerceStatus {
	/**
	 * Status value.
	 *
	 * @var string
	 */
	private $value;

	/**
	 * Construct WooCommerce status object.
	 *
	 * @param $value Status value.
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
				return SubscriptionStatus::EXPIRED;
			default:
				return null;
		}
	}

	/**
	 * Get status from WooCommerce order.
	 *
	 * @param \WC_Order $order WooCommerce order
	 * @return WooCommerceStatus
	 */
	public static function from_order( \WC_Order $order ) {
		return new self( $order->get_status() );
	}
}
