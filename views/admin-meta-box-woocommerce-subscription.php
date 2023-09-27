<?php
/**
 * WordPress admin meta box WooCommercer subscription Pronamic Pay
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay
 */

$pronamic_subscription_id = $subscription->get_meta( 'pronamic_subscription_id' );

$pronamic_subscription = get_pronamic_subscription( $pronamic_subscription_id );

$pronamic_payment_id = (int) $subscription->get_meta( '_pronamic_payment_id' );

$pronamic_payment = get_pronamic_payment( $pronamic_payment_id );

?>
<style>
	#woocommerce-subscription-pronamic-pay .inside {
		margin: 0;
		padding: 10px;
	}

	#woocommerce-subscription-pronamic-pay h3 {
		font-size: 14px;
		margin: 0;
	}

	#woocommerce-subscription-pronamic-pay th,
	#woocommerce-subscription-pronamic-pay td {
		text-align: left;

		width: 50%;
	}
</style>

<h3><?php esc_html_e( 'Subscription', 'pronamic_ideal' ); ?></h3>

<?php if ( null === $pronamic_subscription ) : ?>

	<?php esc_html_e( 'No Pronamic Pay subscription found for this WooCommerce subscription.', 'pronamic_ideal' ); ?>

<?php else : ?>

	<table>
		<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'ID', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php edit_post_link( $pronamic_subscription->get_id(), '', '', $pronamic_subscription->get_id() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Date', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $pronamic_subscription->date->format_i18n() ); ?>
				</td>
			</tr>
		</tbody>
	</table>

<?php endif; ?>

<hr>

<h3><?php esc_html_e( 'Payment', 'pronamic_ideal' ); ?></h3>

<?php if ( null === $pronamic_payment ) : ?>

	<?php esc_html_e( 'No Pronamic Pay payment found for this WooCommerce subscription.', 'pronamic_ideal' ); ?>

<?php else : ?>

	<table>
		<tbody>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'ID', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php edit_post_link( $pronamic_payment->get_id(), '', '', $pronamic_payment->get_id() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Date', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $pronamic_payment->date->format_i18n() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Amount', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $pronamic_payment->get_total_amount()->format_i18n() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Status', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php echo esc_html( $pronamic_payment->get_status_label() ); ?>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Transaction ID', 'pronamic_ideal' ); ?>
				</th>
				<td>
					<?php

					$transaction_id = (string) $pronamic_payment->get_transaction_id();

					$url = (string) $pronamic_payment->get_provider_link();

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
