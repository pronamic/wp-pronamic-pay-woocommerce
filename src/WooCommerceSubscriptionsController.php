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

use Pronamic\WordPress\Pay\Subscriptions\Subscription;

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
}
