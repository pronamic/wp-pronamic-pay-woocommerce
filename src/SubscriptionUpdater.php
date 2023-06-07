<?php
/**
 * Subscription updater
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
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
	 *
	 * @return void
	 */
	public function update_pronamic_subscription() {
		$woocommerce_subscription = $this->woocommerce_subscription;
		$pronamic_subscription    = $this->pronamic_subscription;

		// Status.
		$pronamic_subscription->status = WooCommerceSubscriptionStatus::from_subscription( $woocommerce_subscription )->to_pronamic_status();

		// Date.
		$start_date = new \DateTimeImmutable( $woocommerce_subscription->get_date( 'date_created', 'gmt' ), new \DateTimeZone( 'GMT' ) );

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

		// Do not update subscription phases when payment method is updated only.
		if ( \did_action( 'woocommerce_subscription_change_payment_method_via_pay_shortcode' ) ) {
			return;
		}

		// Description.
		$pronamic_subscription->set_description(
			sprintf(
				'Order #%s',
				$woocommerce_subscription->get_id()
			)
		);

		/*
		 * Payment lines and order items.
		 *
		 * WooCommerce has multiple order item types:
		 * `line_item`, `fee`, `shipping`, `tax`, `coupon`
		 * @link https://github.com/woocommerce/woocommerce/search?q=%22extends+WC_Order_Item%22
		 *
		 * For now we handle only the `line_item`, `fee` and `shipping` items,
		 * we consciously don't handle the `tax` and `coupon` items.
		 *
		 * **Order item `coupon`**
		 * Coupon items are also applied to the `line_item` item and line total.
		 * @link https://basecamp.com/1810084/projects/10966871/todos/372490988
		 *
		 * **Order item `tax`**
		 * Tax items are also  applied to the `line_item` item and line total.
		 */
		$items = $woocommerce_subscription->get_items( [ 'line_item', 'fee', 'shipping' ] );

		$tax_percentages = [ 0 ];

		$pronamic_subscription->lines = new PaymentLines();

		foreach ( $items as $item_id => $item ) {
			$line = $pronamic_subscription->lines->new_line();

			$type = OrderItemType::transform( $item );

			// Quantity.
			$quantity = \wc_stock_amount( $item['qty'] );

			if ( PaymentLineType::SHIPPING === $type ) {
				$quantity = 1;
			}

			// Tax.
			$tax_rate_id = WooCommerce::get_order_item_tax_rate_id( $item );

			$percent = is_null( $tax_rate_id ) ? null : \WC_Tax::get_rate_percent_value( $tax_rate_id );

			// Set line properties.
			$line->set_id( (string) $item_id );
			$line->set_sku( WooCommerce::get_order_item_sku( $item ) );
			$line->set_type( (string) $type );
			$line->set_name( $item['name'] );
			$line->set_quantity( $quantity );
			$line->set_unit_price( new TaxedMoney( $woocommerce_subscription->get_item_total( $item, true ), WooCommerce::get_currency(), $woocommerce_subscription->get_item_tax( $item ), $percent ) );
			$line->set_total_amount( new TaxedMoney( $woocommerce_subscription->get_line_total( $item, true ), WooCommerce::get_currency(), $woocommerce_subscription->get_line_tax( $item ), $percent ) );
			$line->set_product_url( WooCommerce::get_order_item_url( $item ) );
			$line->set_image_url( WooCommerce::get_order_item_image( $item ) );
			$line->set_product_category( WooCommerce::get_order_item_category( $item ) );
			$line->set_meta( 'woocommerce_order_item_id', $item_id );
		}

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

		// Next payment date.
		$next_date = $woocommerce_subscription->get_date( 'next_payment' );

		$pronamic_subscription->set_next_payment_date( empty( $next_date ) ? null : new \DateTimeImmutable( $next_date ) );

		// Add phase.
		$pronamic_subscription->add_phase( $regular_phase );
	}

	/**
	 * Maybe update Pronamic subscription for WooCommerce subscription.
	 *
	 * @param int $post_id WooCommerce Subscription post ID.
	 * @return void
	 */
	public static function maybe_update_pronamic_subscription( $post_id ) {
		if ( 'shop_subscription' !== \get_post_type( $post_id ) ) {
			return;
		}

		if ( ! \function_exists( '\wcs_get_subscription' ) ) {
			return;
		}

		// Get WooCommerce subscription.
		$woocommerce_subscription = \wcs_get_subscription( $post_id );

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
