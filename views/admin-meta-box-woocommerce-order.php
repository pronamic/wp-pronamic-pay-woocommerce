<?php
/**
 * WordPress admin meta box WooCommercer order Pronamic Pay
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payment_id = (int) $order->get_meta( '_pronamic_payment_id' );

$payment = get_pronamic_payment( $payment_id );

?>
<div style="margin-top: 12px;">

	<?php if ( null === $payment ) : ?>

		<?php esc_html_e( 'No Pronamic Pay payment found for this WooCommerce order.', 'pronamic-pay-woocommerce' ); ?>

	<?php else : ?>

		<table>
			<tbody>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Payment ID', 'pronamic-pay-woocommerce' ); ?>
					</th>
					<td>
						<?php edit_post_link( $payment->get_id(), '', '', $payment->get_id() ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Amount', 'pronamic-pay-woocommerce' ); ?>
					</th>
					<td>
						<?php echo esc_html( $payment->get_total_amount()->format_i18n() ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Status', 'pronamic-pay-woocommerce' ); ?>
					</th>
					<td>
						<?php echo esc_html( $payment->get_status_label() ); ?>
					</td>
				</tr>
				<tr>
					<th scope="row" style="text-align: left;">
						<?php esc_html_e( 'Transaction ID', 'pronamic-pay-woocommerce' ); ?>
					</th>
					<td>
						<?php

						$transaction_id = (string) $payment->get_transaction_id();

						$url = (string) $payment->get_provider_link();

						if ( '' === $url ) {
							echo esc_html( $transaction_id );
						}

						if ( '' !== $url ) {
							printf(
								'<a href="%s">%s</a>',
								esc_url( $url ),
								esc_html( $transaction_id )
							);
						}

						?>
					</td>
				</tr>
			</tbody>
		</table>

	<?php endif; ?>

</div>
