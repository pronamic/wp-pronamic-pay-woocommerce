<?php
/**
 * WordPress admin meta box WooCommercer subscription Pronamic Pay
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pronamic_subscription_id = $subscription->get_meta( 'pronamic_subscription_id' );

$pronamic_subscription = get_pronamic_subscription( $pronamic_subscription_id );

?>
<div style="margin-top: 12px;">

	<?php if ( null === $pronamic_subscription ) : ?>

		<?php esc_html_e( 'No Pronamic Pay subscription found for this WooCommerce subscription.', 'pronamic-pay-woocommerce' ); ?>

	<?php else : ?>

		<table>
			<tbody>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Subscription ID', 'pronamic-pay-woocommerce' ); ?>
					</th>
					<td>
						<?php edit_post_link( $pronamic_subscription->get_id(), '', '', $pronamic_subscription->get_id() ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Status', 'pronamic-pay-woocommerce' ); ?>
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

</div>
