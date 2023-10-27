<?php
/**
 * WordPress admin meta box WooCommercer subscription Pronamic Pay
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pronamic_subscription_id = $subscription->get_meta( 'pronamic_subscription_id' );

$pronamic_subscription = get_pronamic_subscription( $pronamic_subscription_id );

?>
<style>
	#woocommerce-subscription-pronamic-pay .inside {
		margin: 0;
		padding: 10px;
	}

	#woocommerce-subscription-pronamic-pay th {
		text-align: left;
	}
</style>

<?php if ( null === $pronamic_subscription ) : ?>

	<?php esc_html_e( 'No Pronamic Pay subscription found for this WooCommerce subscription.', 'pronamic_ideal' ); ?>

<?php else : ?>

	<table>
		<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Subscription ID', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php edit_post_link( $pronamic_subscription->get_id(), '', '', $pronamic_subscription->get_id() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Status', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php

					$status_object = get_post_status_object( $pronamic_subscription->post->post_status );

					$status_label = isset( $status_object, $status_object->label ) ? $status_object->label : '—';

					echo esc_html( $status_label );

					?>
				</td>
			</tr>
		</tbody>
	</table>

<?php endif; ?>
