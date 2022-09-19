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

		\add_action( 'pronamic_pay_schedule_woocommerce_upgrade_4_2_0', [ $this, 'schedule_pages' ] );
		\add_action( 'pronamic_pay_schedule_page_woocommerce_upgrade_4_2_0', [ $this, 'schedule_actions' ], 10, 1 );
		\add_action( 'pronamic_pay_woocommerce_upgrade_4_2_0', [ $this, 'process_action' ], 10, 1 );
	}

	/**
	 * Schedule start action.
	 *
	 * @return void
	 */
	public function schedule() : void {
		$hook = sprintf( 'pronamic_pay_schedule_%s', 'woocommerce_upgrade_4_2_0' );

		$this->enqueue_async_action( $hook );
	}

	/**
	 * Get WordPress query.
	 *
	 * @param array $args Arguments.
	 * @return WP_Query
	 */
	private function get_query( $args = [] ) : WP_Query {
		$args = \wp_parse_args( $args, [
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
		] );

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
	public function schedule_pages() : void {
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
	public function schedule_actions( $page ) : void {
		$query = $this->get_query( [ 'paged' => $page ] );

		$posts = \array_filter(
			$query->posts,
			function( $post ) {
				return ( $post instanceof WP_Post );
			}
		);

		foreach ( $posts as $post ) {
			$this->schedule_action( $post );
		}
	}

	/**
	 * Schedule action.
	 *
	 * @param WP_Post $post Post.
	 * @return int|null
	 */
	private function schedule_action( WP_Post $post ) : ?int {
		$action_id_meta_key = sprintf( 'pronamic_pay_scheduler_%s_action_id', 'woocommerce_upgrade_4_2_0' );

		// Check pending action ID.
		$action_id = \get_post_meta( $post->ID, $action_id_meta_key, true );

		if ( ! empty( $action_id ) ) {
			return $action_id;
		}

		// Enqueue async action.
		$action_id = $this->enqueue_async_action(
			\sprintf( 'pronamic_pay_%s', $this->name ),
			[
				'post_id' => $post->ID,
			]
		);

		if ( ! empty( $action_id ) ) {
			\update_post_meta( $post->ID, $action_id_meta_key, $action_id );
		}

		return $action_id;
	}

	/**
	 * Process action.
	 *
	 * @param string $post_id Post ID.
	 * @return void
	 */
	public function process_action( string $post_id ) : void {
		// Delete action ID post meta.
		$action_id_meta_key = sprintf( 'pronamic_pay_scheduler_%s_action_id', 'woocommerce_upgrade_4_2_0' );

		\delete_post_meta( (int) $post_id, $action_id_meta_key );

		$this->upgrade_subscription( $post_id );
	}

	/**
	 * Schedule page.
	 *
	 * @param int $page Page.
	 * @return int|null
	 */
	private function schedule_page( $page ) : ?int {
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
	private function enqueue_async_action( string $hook, array $args = [] ) : ?int {
		if ( false !== \as_next_scheduled_action( $hook, $args, 'pronamic-pay' ) ) {
			return null;
		}

		return \as_enqueue_async_action( $hook, $args, 'pronamic-pay' );
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
		$this->schedule();
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
