<?php
/**
 * WooCommerce Subscriptions controller
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use WC_Subscription;
use WC_Subscriptions_Change_Payment_Gateway;

/**
 * WooCommerce Subscriptions controller class
 */
class WooCommerceSubscriptionsController {
	/**
	 * Instance of this class.
	 *
	 * @var self
	 */
	protected static $instance = null;

	/**
	 * Return instance of this class.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Setup.
	 *
	 * @return void
	 */
	public function setup() {
		if ( \has_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] ) ) {
			return;
		}

		\add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ] );
	}

	/**
	 * Plugins loaded.
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		if ( ! \class_exists( '\WC_Subscriptions' ) ) {
			return;
		}

		\add_filter( 'pronamic_subscription_source_text_' . Extension::SLUG, [ __CLASS__, 'subscription_source_text' ], 10, 2 );
		\add_filter( 'pronamic_subscription_source_description_' . Extension::SLUG, [ __CLASS__, 'subscription_source_description' ], 10, 2 );
		\add_filter( 'pronamic_subscription_source_url_' . Extension::SLUG, [ __CLASS__, 'subscription_source_url' ], 10, 2 );

		\add_action( 'woocommerce_update_subscription', [ __NAMESPACE__ . '\SubscriptionUpdater', 'maybe_update_pronamic_subscription' ], 20, 1 );

		\add_filter( 'woocommerce_subscriptions_update_payment_via_pay_shortcode', [ $this, 'maybe_dont_update_payment_method' ], 10, 3 );

		if ( \is_admin() ) {
			\add_action(
				'add_meta_boxes',
				function( $post_type, $post ) {
					if ( 'shop_subscription' !== $post_type ) {
						return;
					}

					$subscription = \wcs_get_subscription( $post );

					if ( ! $subscription instanceof WC_Subscription ) {
						return;
					}

					\add_meta_box(
						'woocommerce-subscription-pronamic-pay',
						\__( 'Pronamic Pay', 'pronamic_ideal' ),
						function( $post ) use ( $subscription ) {
							include __DIR__ . '/../views/admin-meta-box-woocommerce-subscription.php';
						},
						$post_type,
						'side',
						'default'
					);
				},
				10,
				2
			);
		}

		\add_action( 'pronamic_payment_status_update_' . Extension::SLUG, [ $this, 'status_update' ], 10, 1 );
	}

	/**
	 * Subscription source text.
	 *
	 * @param string       $text         Source text.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public static function subscription_source_text( $text, Subscription $subscription ) {
		$source_id = $subscription->get_source_id();

		$subscription_edit_link = \sprintf(
			/* translators: %s: order number */
			\__( 'Subscription %s', 'pronamic_ideal' ),
			$source_id
		);

		if ( function_exists( '\wcs_get_subscription' ) && function_exists( '\wcs_get_edit_post_link' ) ) {
			$woocommerce_subscription = \wcs_get_subscription( $source_id );

			if ( false !== $woocommerce_subscription ) {
				$edit_post_url = \wcs_get_edit_post_link( $source_id );

				if ( null !== $edit_post_url ) {
					$subscription_edit_link = \sprintf(
						'<a href="%1$s" title="%2$s">%2$s</a>',
						$edit_post_url,
						\sprintf(
							/* translators: %s: order number */
							\__( 'Subscription %s', 'pronamic_ideal' ),
							$woocommerce_subscription->get_order_number()
						),
					);
				}
			}
		}

		$text = [
			\__( 'WooCommerce', 'pronamic_ideal' ),
			$subscription_edit_link,
		];

		return implode( '<br>', $text );
	}

	/**
	 * Subscription source description.
	 *
	 * @param string       $description  Source description.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public static function subscription_source_description( $description, Subscription $subscription ) {
		return __( 'WooCommerce Subscription', 'pronamic_ideal' );
	}

	/**
	 * Subscription source URL.
	 *
	 * @param string       $url          Source URL.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return null|string
	 */
	public static function subscription_source_url( $url, Subscription $subscription ) {
		$source_id = $subscription->get_source_id();

		if ( ! function_exists( '\wcs_get_edit_post_link' ) ) {
			return null;
		}

		return \wcs_get_edit_post_link( $source_id );
	}

	/**
	 * Don't update the payment method on checkout when switching to Pronamic Pay.
	 *
	 * @param bool            $update             True if payment method should be updated, false otherwise.
	 * @param string          $new_payment_method Payment method indicator.
	 * @param WC_Subscription $subscription       WooCommerce subscription object.
	 * @return bool
	 */
	public function maybe_dont_update_payment_method( $update, $new_payment_method, $subscription ) {
		if ( \str_starts_with( $new_payment_method, 'pronamic_pay_' ) ) {
			$update = false;
		}

		return $update;
	}

	/**
	 * Status update.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public static function status_update( Payment $payment ) {
		$source_id = $payment->get_source_id();

		/**
		 * Retrieve WooCommerce order from payment source ID,
		 * if no order is found return early.
		 *
		 * @link https://docs.woocommerce.com/wc-apidocs/function-wc_get_order.html
		 */
		$order = \wc_get_order( $source_id );

		if ( false === $order ) {
			return;
		}

		/**
		 * This status update function will not update WooCommerce subscription orders.
		 *
		 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/48
		 */
		if ( 'shop_subscription' !== $order->get_type() ) {
			return;
		}

		/**
		 * Cross-check payment ID.
		 */
		$order_payment_id = (int) $order->get_meta( '_pronamic_payment_id' );

		if ( $order_payment_id !== $payment->get_id() ) {
			return;
		}

		/**
		 * Status check.
		 */
		if ( ! \in_array( $payment->get_status(), [ PaymentStatus::AUTHORIZED, PaymentStatus::SUCCESS ], true ) ) {
			return;
		}

		/**
		 * Payment method check.
		 */
		$payment_method = (string) $payment->get_meta( 'woocommerce_payment_method' );

		if ( '' === $payment_method ) {
			return;
		}

		/**
		 * Update order payment method.
		 */
		WC_Subscriptions_Change_Payment_Gateway::update_payment_method( $order, $payment_method );
	}
}
