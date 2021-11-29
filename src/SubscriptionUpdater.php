<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use WC_Subscription;

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
	 */
	public function update_pronamic_subscription() {
		$woocommerce_subscription = $this->woocommerce_subscription;
		$pronamic_subscription    = $this->pronamic_subscription;

		// Date.
		$start_date = new \DateTimeImmutable( $woocommerce_subscription->get_date( 'date_created', 'gmt' ), new \DateTimeZone( 'GMT' ) );

		$pronamic_subscription->date = $start_date;

		// Source.
		$pronamic_subscription->set_source( Extension::SLUG );
		$pronamic_subscription->set_source_id( $woocommerce_subscription->get_id() );

		// Method.
		$pronamic_subscription->set_payment_method( $this->payment_method );

		// Description.
		$pronamic_subscription->set_description(
			sprintf(
				'Order #%s',
				$woocommerce_subscription->get_id()
			)
		);

		// Phases.
		$pronamic_subscription->set_phases( array() );

		/**
		 * Trial period.
		 */
		$trial_period = $woocommerce_subscription->get_trial_period();

		if ( '' !== $trial_period ) {
			$trial_end_date = new \DateTimeImmutable( $woocommerce_subscription->get_date( 'trial_end', 'gmt' ), new \DateTimeZone( 'GMT' ) );

			$diff = $start_date->diff( $trial_end_date );

			$trial_phase = new SubscriptionPhase(
				$pronamic_subscription,
				$start_date,
				new SubscriptionInterval( $diff->format( 'P%aD' ) ),
				new Money( $woocommerce_subscription->get_total_initial_payment(), WooCommerce::get_currency() )
			);

			$trial_phase->set_end_date( $trial_end_date );
			$trial_phase->set_trial( true );

			$pronamic_subscription->add_phase( $trial_phase );

			$start_date = $trial_phase->get_end_date();
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

		$end_date = $woocommerce_subscription->get_date( 'end' );

		$regular_phase->set_end_date( empty( $end_date ) ? null : new \DateTimeImmutable( $end_date ) );

		$next_date = $woocommerce_subscription->get_date( 'next_payment' );

		$regular_phase->set_next_date(empty( $next_date ) ? null : new \DateTimeImmutable( $next_date ) );

		$pronamic_subscription->add_phase( $regular_phase );
	}
}
