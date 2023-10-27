<?php
/**
 * WordPress admin meta box WooCommercer order Pronamic Pay
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$payment_id = (int) $order->get_meta( '_pronamic_payment_id' );

$payment = get_pronamic_payment( $payment_id );

?>
<style>
	#woocommerce-order-pronamic-pay .inside {
		margin: 0;
		padding: 10px;
	}

	#woocommerce-order-pronamic-pay th {
		text-align: left;
	}
</style>

<?php if ( null === $payment ) : ?>

	<?php esc_html_e( 'No Pronamic Pay payment found for this WooCommerce order.', 'pronamic_ideal' ); ?>

<?php else : ?>

	<table>
		<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Payment ID', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php edit_post_link( $payment->get_id(), '', '', $payment->get_id() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Amount', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $payment->get_total_amount()->format_i18n() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Status', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $payment->get_status_label() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Transaction ID', 'pronamic_ideal' ); ?>
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
