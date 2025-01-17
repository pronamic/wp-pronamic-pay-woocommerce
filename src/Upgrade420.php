<?php
/**
 * Upgrade 4.2.0
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Upgrades\Upgrade;
use WP_Post;
use WP_Query;

/**
 * Upgrade 4.2.0
 *
 * @author  Reüel van der Steege
 * @version 4.2.0
 * @since   4.2.0
 */
class Upgrade420 extends Upgrade {
	/**
	 * Construct 4.2.0 upgrade.
	 */
	public function __construct() {
		parent::__construct( '4.2.0' );

		if ( $this->is_woocommerce_subscriptions_active() ) {
			\add_action( 'pronamic_pay_schedule_woocommerce_upgrade_4_2_0', [ $this, 'schedule_pages' ] );
			\add_action( 'pronamic_pay_schedule_page_woocommerce_upgrade_4_2_0', [ $this, 'schedule_actions' ], 10, 1 );
			\add_action( 'pronamic_pay_woocommerce_upgrade_4_2_0', [ $this, 'upgrade_subscription' ], 10, 1 );
		}
	}

	/**
	 * Check if WooCommerce subscriptions is active.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/60
	 * @return bool True if active, false otherwise.
	 */
	private function is_woocommerce_subscriptions_active() {
		return \function_exists( '\wcs_get_subscription' );
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute(): void {
		if ( $this->is_woocommerce_subscriptions_active() ) {
			$this->schedule();
		}
	}

	/**
	 * Schedule start action.
	 *
	 * @return void
	 */
	public function schedule(): void {
		$this->enqueue_async_action( 'pronamic_pay_schedule_woocommerce_upgrade_4_2_0' );
	}

	/**
	 * Get WordPress query.
	 *
	 * @param array $args Arguments.
	 * @return WP_Query
	 */
	private function get_query( $args = [] ): WP_Query {
		$args = \wp_parse_args(
			$args,
			[
				'post_type'   => 'pronamic_pay_subscr',
				'post_status' => 'any',
				'order'       => 'DESC',
				'orderby'     => 'ID',
				'meta_query'  => [
					[
						'key'   => '_pronamic_subscription_source',
						'value' => 'woocommerce',
					],
				],
			]
		);

		if ( \array_key_exists( 'paged', $args ) ) {
			$args['no_found_rows'] = true;
		}

		return new WP_Query( $args );
	}

	/**
	 * Schedule pages.
	 *
	 * @return void
	 */
	public function schedule_pages(): void {
		$query = $this->get_query();

		$num_pages = $query->max_num_pages;

		if ( $num_pages > 0 ) {
			$pages = \range( $num_pages, 1 );

			foreach ( $pages as $page ) {
				$this->schedule_page( $page );
			}
		}
	}

	/**
	 * Schedule actions.
	 *
	 * @param int $page Page.
	 * @return void
	 */
	public function schedule_actions( $page ): void {
		$query = $this->get_query( [ 'paged' => $page ] );

		$posts = \array_filter(
			$query->posts,
			function ( $post ) {
				return ( $post instanceof WP_Post );
			}
		);

		foreach ( $posts as $post ) {
			$this->enqueue_async_action(
				'pronamic_pay_woocommerce_upgrade_4_2_0',
				[
					'post_id' => $post->ID,
				]
			);
		}
	}

	/**
	 * Schedule page.
	 *
	 * @param int $page Page.
	 * @return int|null
	 */
	private function schedule_page( $page ): ?int {
		return $this->enqueue_async_action(
			'pronamic_pay_schedule_page_woocommerce_upgrade_4_2_0',
			[
				'page' => $page,
			]
		);
	}

	/**
	 * Enqueue async action.
	 *
	 * @param string $hook Action hook name.
	 * @param array  $args Action arguments.
	 * @return int|null
	 */
	private function enqueue_async_action( string $hook, array $args = [] ): ?int {
		if ( false !== \as_next_scheduled_action( $hook, $args, 'pronamic-pay' ) ) {
			return null;
		}

		return \as_enqueue_async_action( $hook, $args, 'pronamic-pay' );
	}

	/**
	 * Upgrade subscriptions.
	 *
	 * @param string $post_id Post ID.
	 * @return void
	 */
	public function upgrade_subscription( $post_id ): void {
		$subscription_post_id = $post_id;

		/**
		 * Get subscription.
		 *
		 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L158-L180
		 */
		$subscription = \get_pronamic_subscription( $subscription_post_id );

		if ( null === $subscription ) {
			return;
		}

		/**
		 * We have to find matching WooCommerce subscriptions.
		 */
		$woocommerce_subscriptions = [];

		$potential_woocommerce_subscription = \wcs_get_subscription( $subscription->get_source_id() );

		if ( false !== $potential_woocommerce_subscription ) {
			$woocommerce_subscriptions[] = $potential_woocommerce_subscription;
		}

		/**
		 * In previous versions we may have saved the WooCommerce order ID as source ID.
		 */
		if ( empty( $woocommerce_subscriptions ) ) {
			$potential_woocommerce_order_id = $subscription->get_source_id();

			$potential_woocommerce_subscriptions = $this->get_woocommerce_subscriptions_by_order_id( $potential_woocommerce_order_id );

			if ( ! empty( $potential_woocommerce_subscriptions ) ) {
				foreach ( $potential_woocommerce_subscriptions as $woocommerce_subscription ) {
					$woocommerce_subscriptions[] = $woocommerce_subscription;
				}
			}
		}

		/**
		 * No match.
		 */
		if ( empty( $woocommerce_subscriptions ) ) {
			return;
		}

		/**
		 * Update WooCommerce subscription meta.
		 */
		foreach ( $woocommerce_subscriptions as $woocommerce_subscription ) {
			/**
			 * Check existing Pronamic subscription ID meta.
			 */
			$meta_subscription_id = $woocommerce_subscription->get_meta( 'pronamic_subscription_id', true );

			if ( ! empty( $meta_subscription_id ) ) {
				continue;
			}

			/**
			 * Add Pronamic subscription ID meta to WooCommerce subscription.
			 */
			$woocommerce_subscription->add_meta_data( 'pronamic_subscription_id', $subscription_post_id, true );

			$woocommerce_subscription->save();

			$subscription->add_note(
				\sprintf(
					/* translators: WooCommerce subscription ID. */
					__( 'Linked WooCommerce subscription with ID `%s` to this subscription during WooCommerce integration update (version 4.2.0).', 'pronamic-pay-woocommerce' ),
					$woocommerce_subscription->get_id()
				)
			);
		}
	}

	/**
	 * Get WooCommerce subscription by WooCommerce order ID.
	 *
	 * @param int $woocommerce_order_id WooCommerce order ID.
	 * @return \WC_Subscription[]|null
	 */
	private function get_woocommerce_subscriptions_by_order_id( $woocommerce_order_id ): ?array {
		$wc_order = \wc_get_order( $woocommerce_order_id );

		if ( false === $wc_order ) {
			return null;
		}

		$woocommerce_subscriptions = \wcs_get_subscriptions_for_order( $wc_order );

		return $woocommerce_subscriptions;
	}
}
