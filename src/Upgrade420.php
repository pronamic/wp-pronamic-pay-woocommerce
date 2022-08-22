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
	 * Construct 4.2.0 upgrade.
	 */
	public function __construct() {
		parent::__construct( '4.2.0' );

		if ( \defined( '\WP_CLI' ) && \WP_CLI ) {
			$this->cli_init();
		}
	}

	/**
	 * Execute.
	 *
	 * @return void
	 */
	public function execute() {
		$this->upgrade_subscriptions();
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
				'shortdesc' => 'Execute WooCommerce upgrade 4.2.0.',
			]
		);

		\WP_CLI::add_command(
			'pronamic-pay woocommerce upgrade-420 list-subscriptions',
			function ( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 4.2.0 - Subscriptions List' );

				$posts = $this->get_subscription_posts();

				\WP_CLI\Utils\format_items( 'table', $posts, [ 'ID', 'post_title', 'post_status' ] );
			},
			[
				'shortdesc' => 'List subscriptions for WooCommerce upgrade 4.2.0.',
			]
		);

		\WP_CLI::add_command(
			'pronamic-pay woocommerce upgrade-420 upgrade-subscriptions',
			function ( $args, $assoc_args ) {
				\WP_CLI::log( 'Upgrade 4.2.0 - Subscriptions' );

				$this->upgrade_subscriptions(
					[
						'dry-run'  => \WP_CLI\Utils\get_flag_value( $assoc_args, 'dry-run', true ),
						'post__in' => \WP_CLI\Utils\get_flag_value( $assoc_args, 'post__in', null ),
					]
				);
			},
			[
				'shortdesc' => 'Upgrade subscriptions for WooCommerce upgrade 4.2.0.',
			]
		);
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

	/**
	 * Get subscription posts to upgrade.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	private function get_subscription_posts( $args = [] ) {
		$args['post_type']     = 'pronamic_pay_subscr';
		$args['post_status']   = 'any';
		$args['nopaging']      = true;
		$args['no_found_rows'] = true;
		$args['order']         = 'DESC';
		$args['orderby']       = 'ID';
		$args['meta_query']    = [
			[
				'key'   => '_pronamic_subscription_source',
				'value' => 'woocommerce',
			],
		];

		$query = new \WP_Query( $args );

		return $query->posts;
	}

	/**
	 * Upgrade subscriptions.
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function upgrade_subscriptions( array $args = [] ) : void {
		$args = \wp_parse_args(
			$args,
			[
				'dry-run'  => false,
				'post__in' => null,
			]
		);

		$dry_run = \filter_var( $args['dry-run'], FILTER_VALIDATE_BOOLEAN );

		$query_args = [];

		if ( null !== $args['post__in'] ) {
			$query_args['post__in'] = \explode( ',', $args['post__in'] );
		}

		$subscription_posts = $this->get_subscription_posts( $query_args );

		$this->log(
			\sprintf(
				'Processing %d subscription posts…',
				\number_format_i18n( \count( $subscription_posts ) )
			)
		);

		foreach ( $subscription_posts as $subscription_post ) {
			$subscription_post_id = $subscription_post->ID;

			$this->log(
				\sprintf(
					'Subscription post %s',
					$subscription_post_id
				)
			);

			/**
			 * Get subscription.
			 *
			 * @link https://github.com/wp-pay/core/blob/2.2.4/includes/functions.php#L158-L180
			 */
			$subscription = \get_pronamic_subscription( $subscription_post_id );

			if ( null === $subscription ) {
				continue;
			}

			/**
			 * Get source.
			 */
			$subscription_source_id = \get_post_meta( $subscription_post_id, '_pronamic_subscription_source_id', true );

			/**
			 * We have to find matching WooCommerce subscriptions.
			 */
			$woocommerce_subscriptions = [];

			$potential_woocommerce_subscription = \wcs_get_subscription( $subscription_source_id );

			if ( false !== $potential_woocommerce_subscription ) {
				$woocommerce_subscriptions[] = $potential_woocommerce_subscription;
			}

			/**
			 * In previous versions we may have saved the WooCommerce order ID as source ID.
			 */
			if ( empty( $woocommerce_subscriptions ) ) {
				$potential_woocommerce_order_id = $subscription_source_id;

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

				continue;
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

				if ( false === $dry_run ) {
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
}
