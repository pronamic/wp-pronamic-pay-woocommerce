<?php
/**
 * Upgrade 4.2.0
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2022 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Upgrades
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\QueryActionsScheduler;
use Pronamic\WordPress\Pay\Upgrades\Upgrade;

/**
 * Upgrade 4.2.0
 *
 * @author  Reüel van der Steege
 * @version 4.2.0
 * @since   4.2.0
 */
class Upgrade420 extends Upgrade {
	/**
	 * Query actions scheduler.
	 *
	 * @var QueryActionsScheduler
	 */
	private QueryActionsScheduler $scheduler;

	/**
	 * Query arguments.
	 *
	 * @var array
	 */
	private $query_args;

	/**
	 * Construct 4.2.0 upgrade.
	 */
	public function __construct() {
		parent::__construct( '4.2.0' );

		if ( \defined( '\WP_CLI' ) && \WP_CLI ) {
			$this->cli_init();
		}

		// Query action scheduler.
		$this->query_args = [
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
		];

		$this->scheduler = new QueryActionsScheduler(
			'woocommerce_upgrade_4_2_0',
			$this->query_args,
			[ $this, 'upgrade_subscription' ]
		);
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute() : void {
		// CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$args = \wp_parse_args(
				[
					'nopaging'      => true,
					'no_found_rows' => true,
				],
				$this->query_args
			);

			$query = new \WP_Query( $args );

			foreach ( $query->posts as $post ) {
				$this->upgrade_subscription( $post->ID );
			}

			return;
		}

		// Schedule start action.
		$this->scheduler->schedule();
	}

	/**
	 * WP-CLI initialize.
	 *
	 * @link https://github.com/wp-cli/wp-cli/issues/4818
	 * @return void
	 */
	public function cli_init() {
		\WP_CLI::add_command(
			'pronamic-pay woocommerce upgrade-420 execute',
			function ( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 4.2.0' );

				$this->execute();
			},
			[
				'shortdesc' => 'Execute WooCommerce integration upgrade 4.2.0.',
			]
		);

		\WP_CLI::add_command(
			'pronamic-pay woocommerce upgrade-420 list-subscriptions',
			function ( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 4.2.0 - Subscriptions List' );

				\WP_CLI::debug( 'Query posts to schedule actions for.' );

				$args = \wp_parse_args(
					[
						'nopaging'      => true,
						'no_found_rows' => true,
					],
					$this->query_args
				);

				$query = new \WP_Query( $args );

				\WP_CLI::debug( \sprintf( 'Query executed: `found_posts` = %s, `max_num_pages`: %s.', $query->found_posts, $query->max_num_pages ) );

				\WP_CLI\Utils\format_items(
					'table',
					$query->posts,
					[
						'ID',
						'post_title',
						'post_status',
					]
				);
			},
			[
				'shortdesc' => 'List subscriptions for WooCommerce upgrade 4.2.0.',
			]
		);
	}

	/**
	 * Upgrade subscriptions.
	 *
	 * @param string $post_id Post ID.
	 * @return void
	 */
	public function upgrade_subscription( $post_id ) : void {
		$subscription_post_id = $post_id;

		$this->log( \sprintf( 'Upgading subscription `%s`…', $subscription_post_id ) );

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

					$this->log(
						\sprintf(
							'- Found WooCommerce subscription `%s` through potential WooCommerce order ID `%s`.',
							$woocommerce_subscription->get_id(),
							$potential_woocommerce_order_id
						)
					);
				}
			}
		}

		/**
		 * No match.
		 */
		if ( empty( $woocommerce_subscriptions ) ) {
			$this->log( '- No WooCommerce subscriptions found.' );

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
				$this->log(
					\sprintf(
						'- Found Pronamic subscription ID `%s` in WooCommerce subscription `%s` meta.',
						$meta_subscription_id,
						$woocommerce_subscription->get_id()
					)
				);

				continue;
			}

			/**
			 * Add Pronamic subscription ID meta to WooCommerce subscription.
			 */
			$this->log(
				\sprintf(
					'- No Pronamic subscription ID found in meta of WooCommerce subscription `%s`.',
					$woocommerce_subscription->get_id()
				)
			);

			$woocommerce_subscription->add_meta_data( 'pronamic_subscription_id', $subscription_post_id, true );

			$woocommerce_subscription->save();

			$this->log(
				\sprintf(
					/* translators: 1: WooCommerce subscription ID, 2: Pronamic subscription post ID */
					__( '- Linked WooCommerce subscription with ID `%1$s` to Pronamic subscription `%2$s`.', 'pronamic_ideal' ),
					$woocommerce_subscription->get_id(),
					$subscription_post_id
				)
			);

			$subscription->add_note(
				\sprintf(
					/* translators: WooCommerce subscription ID. */
					__( 'Linked WooCommerce subscription with ID `%s` to this subscription during WooCommerce integration update (version 4.2.0).', 'pronamic_ideal' ),
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
	private function get_woocommerce_subscriptions_by_order_id( $woocommerce_order_id ) : ?array {
		$wc_order = \wc_get_order( $woocommerce_order_id );

		if ( false === $wc_order ) {
			return null;
		}

		$woocommerce_subscriptions = \wcs_get_subscriptions_for_order( $wc_order );

		return $woocommerce_subscriptions;
	}

	/**
	 * Log.
	 *
	 * @link https://make.wordpress.org/cli/handbook/internal-api/wp-cli-log/
	 * @param string $message Message.
	 * @return void
	 */
	private function log( string $message ) {
		if ( method_exists( '\WP_CLI', 'log' ) ) {
			\WP_CLI::log( $message );
		}
	}
}
