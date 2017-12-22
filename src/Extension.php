<?php

/**
 * Title: WooCommerce iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.8
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

		add_action( 'woocommerce_thankyou', array( __CLASS__, 'woocommerce_thankyou' ) );
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
			add_filter( 'pronamic_payment_source_description_' . self::SLUG,   array( __CLASS__, 'source_description' ), 10, 2 );
			add_filter( 'pronamic_payment_source_url_' . self::SLUG,   array( __CLASS__, 'source_url' ), 10, 2 );

			// WooCommerce Subscriptions
			add_action( 'woocommerce_subscription_status_cancelled', array( __CLASS__, 'subscription_cancelled' ), 10, 1 );
			add_action( 'woocommerce_subscription_status_on-hold', array( __CLASS__, 'subscription_on_hold' ), 10, 1 );
			add_action( 'woocommerce_subscription_status_on-hold_to_active', array( __CLASS__, 'subscription_reactivated' ), 10, 1 );
			add_action( 'woocommerce_subscriptions_switch_completed', array( __CLASS__, 'subscription_switch_completed' ), 10, 1 );
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

		// @since 1.2.0
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::PAYPAL ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_PayPalGateway';
		}

		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_IDEAL ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitIDealGateway';
		}

		// @since 1.2.1
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::BITCOIN ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_BitcoinGateway';
		}

		// @since 1.2.2
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::MAESTRO ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_MaestroGateway';
		}

		// @since 1.2.3
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::BELFIUS ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_BelfiusGateway';
		}

		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::KBC ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_KbcGateway';
		}

		// @since 1.2.8
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::BUNQ ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_BunqGateway';
		}

		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_BANCONTACT ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitBancontactGateway';
		}

		// @since 1.2.9
		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::PAYCONIQ ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_PayconiqGateway';
		}

		if ( \Pronamic_WP_Pay_PaymentMethods::is_active( \Pronamic_WP_Pay_PaymentMethods::DIRECT_DEBIT_SOFORT ) ) {
			$gateways[] = 'Pronamic_WP_Pay_Extensions_WooCommerce_DirectDebitSofortGateway';
		}

		return $gateways;
	}

	/**
	 * WooCommerce thank you.
	 *
	 * @param string $order_id
	 */
	public static function woocommerce_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::order_has_status( $order, 'pending' ) ) {
			printf(
				'<div class="woocommerce-info">%s</div>',
				__( 'Your order will be processed once we receive the payment.', 'pronamic_ideal' )
			);
		}
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
				$url = $data->get_success_url();

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
		if ( method_exists( $order, 'get_payment_method_title' ) ) {
			// WooCommerce 3.0+
			$payment_method_title = $order->get_payment_method_title();
		} else {
			$payment_method_title = $order->payment_method_title;
		}

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

					// @todo check if manually updating the subscription is still necessary.
					foreach ( $subscriptions as $subscription ) {
						$subscription->payment_failed();
					}

					break;
				case Pronamic_WP_Pay_Statuses::SUCCESS :
					// Payment completed
					$order->add_order_note( sprintf( '%s %s.', $payment_method_title, __( 'payment completed', 'pronamic_ideal' ) ) );

					// Mark order complete
					$order->payment_complete();

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
	 * Update subscription status when WooCommerce subscription is set on hold.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_on_hold( $wcs_subscription ) {
		$source_id = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$note = sprintf(
			__( "%s subscription on hold. Status changed to 'Open'.", 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->update_status( Pronamic_WP_Pay_Statuses::OPEN, $note );

		$can_redirect = false;

		Pronamic_WP_Pay_Plugin::update_subscription( $subscription, $can_redirect );
	}

	/**
	 * Update subscription status and dates when WooCommerce subscription is reactivated.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_reactivated( $wcs_subscription ) {
		$source_id = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$next_payment = $subscription->get_next_payment_date();

		$note = sprintf(
			__( "%s subscription reactivated. Status changed to 'Active'.", 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->update_status( Pronamic_WP_Pay_Statuses::SUCCESS, $note );

		// Set next payment date
		$next_payment_date = new DateTime( '@' . $wcs_subscription->get_time( 'next_payment' ) );

		$subscription->set_next_payment_date( $next_payment_date );

		// Set renewal notice date
		$next_renewal = new DateTime( $next_payment_date->format( DateTime::ISO8601 ) );
		$next_renewal->modify( '-1 week' );

		if ( $next_renewal < $next_payment ) {
			$next_renewal = $next_payment;
		}

		$subscription->set_renewal_notice_date( $next_renewal );

		// Set start date
		$start_date = new DateTime( $next_payment_date->format( DateTime::ISO8601 ) );
		$start_date->modify( sprintf(
			'-%d %s',
			$subscription->get_interval(),
			Pronamic_WP_Util::to_interval_name( $subscription->get_interval_period() )
		) );

		$subscription->set_start_date( $start_date );

		$can_redirect = false;

		Pronamic_WP_Pay_Plugin::update_subscription( $subscription, $can_redirect );
	}

	/**
	 * Update subscription status when WooCommerce subscription is cancelled.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_cancelled( $wcs_subscription ) {
		$source_id = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$note = sprintf(
			__( "%s subscription cancelled. Status changed to 'Cancelled'.", 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->update_status( Pronamic_WP_Pay_Statuses::CANCELLED, $note );

		$can_redirect = false;

		Pronamic_WP_Pay_Plugin::update_subscription( $subscription, $can_redirect );
	}

	/**
	 * Update subscription meta and dates when WooCommerce subscription is switched.
	 *
	 * @param $order
	 */
	public static function subscription_switch_completed( $order ) {
		$subscriptions    = wcs_get_subscriptions_for_order( $order );
		$wcs_subscription = array_pop( $subscriptions );

		$source_id = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		// Find subscription order item
		foreach ( $order->get_items() as $item ) {
			$product = $order->get_product_from_item( $item );

			if ( ! WC_Subscriptions_Product::is_subscription( $product ) ) {
				continue;
			}

			if ( method_exists( $product, 'get_length' ) ) {
				// WooCommerce 3.0+

				$update_meta = array(
					'amount'          => WC_Subscriptions_Product::get_price( $product ),
					'frequency'       => WC_Subscriptions_Product::get_length( $product ),
					'interval'        => WC_Subscriptions_Product::get_interval( $product ),
					'interval_period' => Pronamic_WP_Pay_Util::to_period( WC_Subscriptions_Product::get_period( $product ) ),
				);
			} else {
				$update_meta = array(
					'amount'          => $product->subscription_price,
					'frequency'       => $product->subscription_length,
					'interval'        => $product->subscription_period_interval,
					'interval_period' => Pronamic_WP_Pay_Util::to_period( $product->subscription_period ),
				);
			}

			$next_payment = new DateTime( '@' . $wcs_subscription->get_time( 'next_payment' ) );

			$update_meta['next_payment'] = $next_payment;
			$update_meta['expiry_date']  = $next_payment;

			$subscription->update_meta( $update_meta );
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

	/**
	 * Source description.
	 */
	public static function source_description( $description, Pronamic_Pay_Payment $payment ) {
		$description = __( 'WooCommerce Order', 'pronamic_ideal' );

		return $description;
	}

	/**
	 * Source URL.
	 */
	public static function source_url( $url, Pronamic_Pay_Payment $payment ) {
		$url = get_edit_post_link( $payment->source_id );

		return $url;
	}
}
