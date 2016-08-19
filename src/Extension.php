<?php

/**
 * Title: WooCommerce iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2016
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.1.7
 * @since 1.1.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_Extension {
	/**
	 * Slug
	 *
	 * @var string
	 */
	const SLUG = 'woocommerce';

	//////////////////////////////////////////////////

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'init' ) );

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'payment_gateways' ) );
	}

	//////////////////////////////////////////////////

	/**
	 * Initialize
	 */
	public static function init() {
		if ( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::is_active() ) {
			add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
			add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );
			add_filter( 'pronamic_payment_source_text_' . self::SLUG,   array( __CLASS__, 'source_text' ), 10, 2 );
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Add the gateway to WooCommerce
	 */
	public static function payment_gateways( $gateways ) {
		// @since 1.1.3
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_BankTransferGateway';
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitGateway';

		// @since 1.1.2
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_PronamicGateway';

		// @since 1.0.0
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_CreditCardGateway';
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_IDealGateway';
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_MisterCashGateway';

		// @since 1.1.0
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_SofortGateway';

		// @since unreleased
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_PayPalGateway';
		$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitIDealGateway';

		return $gateways;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @since 1.1.7
	 * @param string                  $url
	 * @param Pronamic_WP_Pay_Payment $payment
	 * @return string
	 */
	public static function redirect_url( $url, $payment ) {
		$source_id = $payment->get_source_id();

		$order   = new WC_Order( (int) $source_id );
		$gateway = new Pronamic_WP_Pay_Extensions_WooCommerce_IDealGateway();

		$data = new Pronamic_WP_Pay_Extensions_WooCommerce_PaymentData( $order, $gateway );

		$url = $data->get_normal_return_url();

		switch ( $payment->get_status() ) {
			case Pronamic_WP_Pay_Statuses::CANCELLED :
				$url = $data->get_cancel_url();

				break;
			case Pronamic_WP_Pay_Statuses::EXPIRED :
				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::FAILURE :
				$url = $data->get_error_url();

				break;
			case Pronamic_WP_Pay_Statuses::SUCCESS :
				$url = $data->get_success_url();

				break;
			case Pronamic_WP_Pay_Statuses::OPEN :
				$url = $data->get_error_url();

				break;
		}

		return $url;
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Pronamic_Pay_Payment $payment
	 */
	public static function status_update( Pronamic_Pay_Payment $payment ) {
		$source_id = $payment->get_source_id();

		$order   = new WC_Order( (int) $source_id );

		// Only update if order is not 'processing' or 'completed'
		// @see https://github.com/woothemes/woocommerce/blob/v2.0.0/classes/class-wc-order.php#L1279
		$should_update = ! Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::order_has_status( $order, array(
			Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::ORDER_STATUS_COMPLETED,
			Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::ORDER_STATUS_PROCESSING,
		) );

		// Defaults
		$payment_method_title = $order->payment_method_title;

		if ( $should_update ) {
			$subscriptions = array();

			if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
				$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
			}

			switch ( $payment->get_status() ) {
				case Pronamic_WP_Pay_Statuses::CANCELLED :
					// Nothing to do?

					break;
				case Pronamic_WP_Pay_Statuses::EXPIRED :
					$note = sprintf( '%s %s.', $payment_method_title, __( 'payment expired', 'pronamic_ideal' ) );

					// WooCommerce PayPal gateway uses 'failed' order status for an 'expired' payment
					// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/classes/gateways/class-wc-paypal.php#L557
					$order->update_status( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::ORDER_STATUS_FAILED, $note );

					break;
				case Pronamic_WP_Pay_Statuses::FAILURE :
					$note = sprintf( '%s %s.', $payment_method_title, __( 'payment failed', 'pronamic_ideal' ) );

					$order->update_status( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::ORDER_STATUS_FAILED, $note );

					if ( 0 < count( $subscriptions ) ) {
						foreach ( $subscriptions as $subscription ) {
							$subscription->payment_failed();
						}
					}

					break;
				case Pronamic_WP_Pay_Statuses::SUCCESS :
					// Payment completed
					$order->add_order_note( sprintf( '%s %s.', $payment_method_title, __( 'payment completed', 'pronamic_ideal' ) ) );

					// Mark order complete
					$order->payment_complete();

					if ( 0 < count( $subscriptions ) ) {

						foreach ( $subscriptions as $subscription ) {
							$subscription->payment_complete();
						}
					}

					break;
				case Pronamic_WP_Pay_Statuses::OPEN :
					$order->add_order_note( sprintf( '%s %s.', $payment_method_title, __( 'payment open', 'pronamic_ideal' ) ) );

					break;
				default:
					$order->add_order_note( sprintf( '%s %s.', $payment_method_title, __( 'payment unknown', 'pronamic_ideal' ) ) );

					break;
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Source column
	 */
	public static function source_text( $text, Pronamic_WP_Pay_Payment $payment ) {
		$text  = '';

		$text .= __( 'WooCommerce', 'pronamic_ideal' ) . '<br />';

		// Check order post meta for order number
		$order_number = '#' . $payment->source_id;

		$value = get_post_meta( $payment->source_id, '_order_number', true );

		if ( ! empty( $value ) ) {
			$order_number = $value;
		}

		$text .= sprintf(
			'<a href="%s">%s</a>',
			get_edit_post_link( $payment->source_id ),
			sprintf( __( 'Order %s', 'pronamic_ideal' ), $order_number )
		);

		return $text;
	}
}
