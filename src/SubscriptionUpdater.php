<?php
/**
 * Subscription updater
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use WC_Subscription;

/**
 * Subscription updater class
 */
class SubscriptionUpdater {
	/**
	 * WooCommerce subscription.
	 *
	 * @var WC_Subscription
	 */
	private $woocommerce_subscription;

	/**
	 * Pronamic subscription.
	 *
	 * @var Subscription
	 */
	private $pronamic_subscription;

	/**
	 * Construct subscription updater.
	 *
	 * @param WC_Subscription $woocommerce_subscription WooCommerce subscription.
	 * @param Subscription    $pronamic_subscription    Pronamic subscription.
	 */
	public function __construct( WC_Subscription $woocommerce_subscription, Subscription $pronamic_subscription ) {
		$this->woocommerce_subscription = $woocommerce_subscription;
		$this->pronamic_subscription    = $pronamic_subscription;
	}

	/**
	 * Update Pronamic subscription.
	 *
	 * @return void
	 */
	public function update_pronamic_subscription() {
		$woocommerce_subscription = $this->woocommerce_subscription;
		$pronamic_subscription    = $this->pronamic_subscription;

		// Status.
		$pronamic_subscription->status = WooCommerceSubscriptionStatus::from_subscription( $woocommerce_subscription )->to_pronamic_status();

		// Date.
		$start_date = new \DateTimeImmutable( $woocommerce_subscription->get_date( 'start', 'gmt' ), new \DateTimeZone( 'GMT' ) );

		$pronamic_subscription->date = $start_date;

		// Source.
		$pronamic_subscription->set_source( Extension::SLUG );
		$pronamic_subscription->set_source_id( $woocommerce_subscription->get_id() );

		// Method.
		$available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
		$payment_method     = $woocommerce_subscription->get_payment_method( 'raw' );

		if (
			\array_key_exists( $payment_method, $available_gateways )
				&&
			\is_callable( [ $available_gateways[ $payment_method ], 'get_wp_payment_method' ] )
		) {
			$payment_method = $available_gateways[ $payment_method ]->get_wp_payment_method();

			if ( null !== $payment_method ) {
				$pronamic_subscription->set_payment_method( $payment_method );
			}
		}

		// Description.
		$pronamic_subscription->set_description(
			sprintf(
				'Order #%s',
				$woocommerce_subscription->get_id()
			)
		);

		// Order helper.
		$order_helper = new OrderHelper( $woocommerce_subscription );

		// Customer.
		$pronamic_subscription->set_customer( $order_helper->get_customer() );

		// Phases.
		$pronamic_subscription->set_phases( [] );

		/**
		 * Trial period.
		 */
		$trial_period = $woocommerce_subscription->get_trial_period();

		if ( '' !== $trial_period ) {
			$trial_end = $woocommerce_subscription->get_date( 'trial_end', 'gmt' );

			if ( empty( $trial_end ) && $woocommerce_subscription->meta_exists( 'trial_end_pre_cancellation' ) ) {
				$trial_end = $woocommerce_subscription->get_meta( 'trial_end_pre_cancellation' );
			}

			$trial_end_date = new \DateTimeImmutable( $trial_end, new \DateTimeZone( 'GMT' ) );

			$interval_start_date = $start_date->setTime( $start_date->format( 'H' ), $start_date->format( 'i' ) );
			$interval_end_date   = $trial_end_date->setTime( $trial_end_date->format( 'H' ), $trial_end_date->format( 'i' ) );

			$diff = $interval_start_date->diff( $interval_end_date );

			$trial_phase = new SubscriptionPhase(
				$pronamic_subscription,
				$start_date,
				new SubscriptionInterval( $diff->format( 'P%aD' ) ),
				new Money( $woocommerce_subscription->get_total_initial_payment(), WooCommerce::get_currency() )
			);

			$trial_phase->set_end_date( $trial_end_date );
			$trial_phase->set_trial( true );

			$pronamic_subscription->add_phase( $trial_phase );

			$start_date = $trial_end_date;
		}

		/**
		 * Regular phase.
		 */

		/**
		 * WooCommerce subscription billing period, possible values:
		 * - `day`
		 * - `daily`
		 * - `week`
		 * - `month`
		 * - `year`
		 *
		 * @link https://woocommerce.com/document/subscriptions/develop/functions/
		 */
		$billing_period = $woocommerce_subscription->get_billing_period();

		/**
		 * WooCommerce subscription billing interval, numeric string value.
		 *
		 * @link https://woocommerce.com/document/subscriptions/develop/functions/
		 */
		$billing_interval = $woocommerce_subscription->get_billing_interval();

		$regular_phase = new SubscriptionPhase(
			$pronamic_subscription,
			$start_date,
			new SubscriptionInterval(
				\sprintf(
					'P%d%s',
					$billing_interval,
					Util::to_period( $billing_period )
				)
			),
			new Money( $woocommerce_subscription->get_total(), WooCommerce::get_currency() )
		);

		// End date.
		$end_date = $woocommerce_subscription->get_date( 'end' );

		$regular_phase->set_end_date( empty( $end_date ) ? null : new \DateTimeImmutable( $end_date ) );

		// Add phase.
		$pronamic_subscription->add_phase( $regular_phase );

		// Next payment date.
		$next_date = $woocommerce_subscription->get_date( 'next_payment' );

		$pronamic_subscription->set_next_payment_date( empty( $next_date ) ? null : new \DateTimeImmutable( $next_date ) );

		// Lines.
		$pronamic_subscription->lines = $order_helper->get_lines();
	}

	/**
	 * Maybe update Pronamic subscription for WooCommerce subscription.
	 *
	 * @param int $subscription_id WooCommerce Subscription ID.
	 * @return void
	 */
	public static function maybe_update_pronamic_subscription( $subscription_id ) {
		if ( ! \function_exists( '\wcs_get_subscription' ) ) {
			return;
		}

		// Get WooCommerce subscription.
		$woocommerce_subscription = \wcs_get_subscription( $subscription_id );

		if ( false === $woocommerce_subscription ) {
			return;
		}

		// Get Pronamic subscription.
		$subscription_helper = new SubscriptionHelper( $woocommerce_subscription );

		$pronamic_subscription = $subscription_helper->get_pronamic_subscription();

		if ( null === $pronamic_subscription ) {
			return;
		}

		// Update Pronamic subscription.
		$subscription_updater = new SubscriptionUpdater( $woocommerce_subscription, $pronamic_subscription );

		$subscription_updater->update_pronamic_subscription();

		$pronamic_subscription->save();
	}
}
