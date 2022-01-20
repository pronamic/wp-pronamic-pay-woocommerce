<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Exception;
use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\AbstractPluginIntegration;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionInterval;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionPhase;
use Pronamic\WordPress\Pay\Subscriptions\SubscriptionStatus;
use Pronamic\WordPress\Pay\Util as Pay_Util;
use WC_Order;
use WC_Subscriptions_Product;

/**
 * Title: WooCommerce iDEAL Add-On
 * Description:
 * Copyright: 2005-2022 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.0
 * @since   1.1.0
 */
class Extension extends AbstractPluginIntegration {
	/**
	 * Slug
	 *
	 * @var string
	 */
	const SLUG = 'woocommerce';

	/**
	 * Construct WooCommerce plugin integration.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct(
			array(
				'name' => __( 'WooCommerce', 'pronamic_ideal' ),
			)
		);

		// Dependencies.
		$dependencies = $this->get_dependencies();

		$dependencies->add( new WooCommerceDependency() );
	}

	/**
	 * Setup plugin integration.
	 *
	 * @return void
	 */
	public function setup() {
		add_filter( 'pronamic_payment_source_text_' . self::SLUG, array( __CLASS__, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_subscription_source_text_' . self::SLUG, array( __CLASS__, 'subscription_source_text' ), 10, 2 );
		add_filter( 'pronamic_subscription_source_description_' . self::SLUG, array( __CLASS__, 'subscription_source_description' ), 10, 2 );

		// Check if dependencies are met and integration is active.
		if ( ! $this->is_active() ) {
			return;
		}

		add_action( 'init', array( __CLASS__, 'init' ) );

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ), 15 );

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'payment_gateways' ) );

		add_filter( 'woocommerce_thankyou_order_received_text', array( __CLASS__, 'woocommerce_thankyou_order_received_text' ), 20, 2 );

		\add_action( 'pronamic_pay_update_payment', array( $this, 'maybe_update_refunded_payment' ), 15, 1 );

		\add_action(
			'save_post',
			function( $post_id ) {
				if ( 'shop_subscription' !== \get_post_type( $post_id ) ) {
					return;
				}

				$woocommerce_subscription = \wcs_get_subscription( $post_id );

				if ( false === $woocommerce_subscription ) {
					return;
				}

				$subscription_helper = new SubscriptionHelper( $woocommerce_subscription );

				$pronamic_subscription = $subscription_helper->get_pronamic_subscription();

				if ( null === $pronamic_subscription ) {
					return;
				}

				$subscription_updater = new SubscriptionUpdater( $woocommerce_subscription, $pronamic_subscription );

				$subscription_updater->update_pronamic_subscription();

				$pronamic_subscription->save();
			},
			100,
			1 
		);
	}

	/**
	 * Initialize
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG, array( __CLASS__, 'source_url' ), 10, 2 );
		add_filter( 'pronamic_subscription_source_url_' . self::SLUG, array( __CLASS__, 'subscription_source_url' ), 10, 2 );

		add_action( 'pronamic_payment_status_update_' . self::SLUG . '_reserved_to_cancelled', array( __CLASS__, 'reservation_cancelled_note' ), 10, 1 );

		// Currencies.
		add_filter( 'woocommerce_currencies', array( __CLASS__, 'currencies' ), 10, 1 );
		add_filter( 'woocommerce_currency_symbol', array( __CLASS__, 'currency_symbol' ), 10, 2 );

		// Checkout fields.
		add_filter( 'woocommerce_checkout_fields', array( __CLASS__, 'checkout_fields' ), 10, 1 );
		add_action( 'woocommerce_checkout_update_order_meta', array( __CLASS__, 'checkout_update_order_meta' ), 10, 2 );

		self::register_settings();
	}

	/**
	 * Add the gateways to WooCommerce.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/3.5.3/includes/class-wc-payment-gateways.php#L99-L100
	 * @Link https://github.com/wp-pay-extensions/easy-digital-downloads/blob/2.0.2/src/Extension.php#L29-L147
	 *
	 * @param array $wc_gateways WooCommerce payment gateways.
	 * @return array
	 */
	public static function payment_gateways( $wc_gateways ) {
		$gateways = self::get_gateways();

		foreach ( $gateways as $key => $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'id'           => $key,
					'check_active' => true,
				)
			);

			if ( $args['check_active'] && isset( $args['payment_method'] ) ) {
				$payment_method = $args['payment_method'];

				if ( ! PaymentMethods::is_active( $payment_method ) ) {
					continue;
				}
			}

			$wc_gateways[] = new Gateway( $args );
		}

		return $wc_gateways;
	}

	/**
	 * Get gateways.
	 *
	 * @return array
	 */
	public static function get_gateways() {
		$icon_size = 'wc-51x32';

		return array(
			array(
				'id'                 => 'pronamic_pay',
				'method_title'       => __( 'Pronamic', 'pronamic_ideal' ),
				'method_description' => __( "This payment method does not use a predefined payment method for the payment. Some payment providers list all activated payment methods for your account to choose from. Use payment method specific gateways (such as 'iDEAL') to let customers choose their desired payment method at checkout.", 'pronamic_ideal' ),
				'check_active'       => false,
			),
			array(
				'id'                 => 'pronamic_pay_afterpay',
				'payment_method'     => PaymentMethods::AFTERPAY_NL,
				'icon'               => PaymentMethods::get_icon_url( PaymentMethods::AFTERPAY_NL, $icon_size ),
				/**
				 * AfterPay method description.
				 * 
				 * @link https://www.afterpay.nl/en/customers/where-can-i-pay-with-afterpay
				 */
				'method_description' => \__( 'AfterPay is one of the largest and most popular post-payment system in the Benelux. Millions of Dutch and Belgians use AfterPay to pay for products.', 'pronamic_ideal' ),
			),
			array(
				'id'                 => 'pronamic_pay_afterpay_com',
				'payment_method'     => PaymentMethods::AFTERPAY_COM,
				'icon'               => PaymentMethods::get_icon_url( PaymentMethods::AFTERPAY_COM, $icon_size ),
				/**
				 * Afterpay method description.
				 * 
				 * @link https://en.wikipedia.org/wiki/Afterpay
				 */
				'method_description' => \__( 'Afterpay is best known for its "pay later" service that allows in-store and online customers to purchase a product immediately and pay with four equal fortnightly repayments.', 'pronamic_ideal' ),
			),
			array(
				'id'             => 'pronamic_pay_alipay',
				'payment_method' => PaymentMethods::ALIPAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::ALIPAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_american_express',
				'payment_method' => PaymentMethods::AMERICAN_EXPRESS,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::AMERICAN_EXPRESS, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_apple_pay',
				'payment_method' => PaymentMethods::APPLE_PAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::APPLE_PAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_mister_cash',
				'payment_method' => PaymentMethods::BANCONTACT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BANCONTACT, $icon_size ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_bank_transfer',
				'payment_method' => PaymentMethods::BANK_TRANSFER,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BANK_TRANSFER, $icon_size ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_belfius',
				'payment_method' => PaymentMethods::BELFIUS,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BELFIUS, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_bitcoin',
				'payment_method' => PaymentMethods::BITCOIN,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BITCOIN, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_blik',
				'payment_method' => PaymentMethods::BLIK,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BLIK, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_bunq',
				'payment_method' => PaymentMethods::BUNQ,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::BUNQ, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_credit_card',
				'payment_method' => PaymentMethods::CREDIT_CARD,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::CREDIT_CARD, $icon_size ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_direct_debit',
				'payment_method' => PaymentMethods::DIRECT_DEBIT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::DIRECT_DEBIT, $icon_size ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_direct_debit_bancontact',
				'payment_method' => PaymentMethods::DIRECT_DEBIT_BANCONTACT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::DIRECT_DEBIT_BANCONTACT, 'wc-107x32' ),
				'form_fields'    => array(
					'description' => array(
						'default' => sprintf(
							/* translators: %s: payment method */
							__( 'By using this payment method you authorize us via %s to debit payments from your bank account.', 'pronamic_ideal' ),
							__( 'Bancontact', 'pronamic_ideal' )
						),
					),
				),
			),
			array(
				'id'             => 'pronamic_pay_direct_debit_ideal',
				'payment_method' => PaymentMethods::DIRECT_DEBIT_IDEAL,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::DIRECT_DEBIT_IDEAL, 'wc-107x32' ),
				'form_fields'    => array(
					'description' => array(
						'default' => sprintf(
							/* translators: %s: payment method */
							__( 'By using this payment method you authorize us via %s to debit payments from your bank account.', 'pronamic_ideal' ),
							__( 'iDEAL', 'pronamic_ideal' )
						),
					),
				),
			),
			array(
				'id'             => 'pronamic_pay_direct_debit_sofort',
				'payment_method' => PaymentMethods::DIRECT_DEBIT_SOFORT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::DIRECT_DEBIT_SOFORT, 'wc-107x32' ),
				'form_fields'    => array(
					'description' => array(
						'default' => sprintf(
							/* translators: %s: payment method */
							__( 'By using this payment method you authorize us via %s to debit payments from your bank account.', 'pronamic_ideal' ),
							__( 'SOFORT', 'pronamic_ideal' )
						),
					),
				),
			),
			array(
				'id'             => 'pronamic_pay_focum',
				'payment_method' => PaymentMethods::FOCUM,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::FOCUM, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_eps',
				'payment_method' => PaymentMethods::EPS,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::EPS, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_giropay',
				'payment_method' => PaymentMethods::GIROPAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::GIROPAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_google_pay',
				'payment_method' => PaymentMethods::GOOGLE_PAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::GOOGLE_PAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_gulden',
				'payment_method' => PaymentMethods::GULDEN,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::GULDEN, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_ideal',
				'payment_method' => PaymentMethods::IDEAL,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::IDEAL, $icon_size ),
				'form_fields'    => array(
					'description' => array(
						'default' => __( 'With iDEAL you can easily pay online in the secure environment of your own bank.', 'pronamic_ideal' ),
					),
				),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_idealqr',
				'payment_method' => PaymentMethods::IDEALQR,
				'icon'           => PaymentMethods::get_icon_url( 'ideal-qr', $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_in3',
				'payment_method' => PaymentMethods::IN3,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::IN3, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_kbc',
				'payment_method' => PaymentMethods::KBC,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::KBC, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_klarna_pay_later',
				'payment_method' => PaymentMethods::KLARNA_PAY_LATER,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::KLARNA_PAY_LATER, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_maestro',
				'payment_method' => PaymentMethods::MAESTRO,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::MAESTRO, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_mastercard',
				'payment_method' => PaymentMethods::MASTERCARD,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::MASTERCARD, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_mb_way',
				'payment_method' => PaymentMethods::MB_WAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::MB_WAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_payconiq',
				'payment_method' => PaymentMethods::PAYCONIQ,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::PAYCONIQ, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_paypal',
				'payment_method' => PaymentMethods::PAYPAL,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::PAYPAL, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_przelewy24',
				'payment_method' => PaymentMethods::PRZELEWY24,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::PRZELEWY24, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_santander',
				'payment_method' => PaymentMethods::SANTANDER,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::SANTANDER, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_sofort',
				'payment_method' => PaymentMethods::SOFORT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::SOFORT, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_spraypay',
				'payment_method' => PaymentMethods::SPRAYPAY,
			),
			array(
				'id'             => 'pronamic_pay_swish',
				'payment_method' => PaymentMethods::SWISH,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::SWISH, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_twint',
				'payment_method' => PaymentMethods::TWINT,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::TWINT, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_v_pay',
				'payment_method' => PaymentMethods::V_PAY,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::V_PAY, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_vipps',
				'payment_method' => PaymentMethods::VIPPS,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::VIPPS, $icon_size ),
			),
			array(
				'id'             => 'pronamic_pay_visa',
				'payment_method' => PaymentMethods::VISA,
				'icon'           => PaymentMethods::get_icon_url( PaymentMethods::VISA, $icon_size ),
			),
		);
	}

	/**
	 * WooCommerce thank you.
	 *
	 * @param string        $message Thank you message.
	 * @param WC_Order|null $order   WooCommerce order.
	 * @return string
	 * @link https://github.com/woocommerce/woocommerce/blob/5.9.0/templates/checkout/thankyou.php
	 */
	public static function woocommerce_thankyou_order_received_text( $message, $order ) {
		// Check order.
		if ( ! ( $order instanceof WC_Order ) ) {
			return $message;
		}

		// Check order status.
		if ( ! WooCommerce::order_has_status( $order, 'pending' ) ) {
			return $message;
		}

		// Chek supported gateway.
		$gateway = \wp_list_filter(
			self::get_gateways(),
			array(
				'id' => $order->get_payment_method( 'raw' ),
			)
		);

		if ( empty( $gateway ) ) {
			return $message;
		}

		// Add notice.
		$message .= \sprintf(
			'<div class="woocommerce-info">%s</div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			__( 'We process your order as soon as we have processed your payment.', 'pronamic_ideal' )
		);

		return $message;
	}

	/**
	 * Payment redirect URL filter.
	 *
	 * @since 1.1.7
	 *
	 * @param string  $url     Redirect URL.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function redirect_url( $url, Payment $payment ) {
		$source_id = $payment->get_source_id();

		try {
			$order = new WC_Order( (int) $source_id );
		} catch ( Exception $e ) {
			return $url;
		}

		switch ( $payment->get_status() ) {
			case PaymentStatus::CANCELLED:
			case PaymentStatus::EXPIRED:
			case PaymentStatus::FAILURE:
				return WooCommerce::get_order_pay_url( $order );

			case PaymentStatus::SUCCESS:
			case PaymentStatus::OPEN:
			default:
				$gateway = new Gateway();

				return $gateway->get_return_url( $order );
		}
	}

	/**
	 * Add note when reserved payment is cancelled.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public static function reservation_cancelled_note( Payment $payment ) {
		$source_id = $payment->get_source_id();

		$order = new WC_Order( (int) $source_id );

		// Payment method title.
		$payment_method_title = $payment->get_meta( 'woocommerce_payment_method_title' );

		if ( empty( $payment_method_title ) ) {
			$payment_method_title = WooCommerce::get_payment_method_title( $order );
		}

		$order->add_order_note(
			sprintf(
				'%s %s.',
				$payment_method_title,
				__( 'reserved payment cancelled', 'pronamic_ideal' )
			)
		);
	}

	/**
	 * Status update.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public static function status_update( Payment $payment ) {
		$source_id = $payment->get_source_id();

		/**
		 * Retrieve WooCommerce order from payment source ID,
		 * if no order is found return early.
		 *
		 * @link https://docs.woocommerce.com/wc-apidocs/function-wc_get_order.html
		 */
		$order = wc_get_order( $source_id );

		if ( false === $order ) {
			return;
		}

		$new_status = null;

		/**
		 * Payment method title.
		 *
		 * The WooCommerce payment method title should be stored in the payment meta,
		 * if that is not the case we fallback to the payment method stored in the
		 * WooCommerce order.
		 */
		$payment_method_title = $payment->get_meta( 'woocommerce_payment_method_title' );

		if ( empty( $payment_method_title ) ) {
			$payment_method_title = WooCommerce::get_payment_method_title( $order );
		}

		/**
		 * Note.
		 */
		$note = sprintf(
			/* translators: 1: payment URL, 2: payment ID, 3: WooCommerce payment method title, 4: Pronamic payment status */
			__( '<a href="%1$s">Payment #%2$s</a> via "%3$s" updated to "%4$s".', 'pronamic_ideal' ),
			esc_urL( $payment->get_edit_payment_url() ),
			esc_html( $payment->get_id() ),
			esc_html( $payment_method_title ),
			esc_html( $payment->get_status_label() )
		);

		/**
		 * Reservation.
		 *
		 * For a payment with status 'reserved' we add an extra note to inform shop
		 * managers what to do.
		 */
		if ( PaymentStatus::RESERVED === $payment->get_status() ) {
			$gateway = Plugin::get_gateway( $payment->get_config_id() );

			if ( $gateway && $gateway->supports( 'reservation_payments' ) ) {
				$note .= "\r\n";
				$note .= "\r\n";

				$note .= sprintf(
					/* translators: 1: payment URL, 2: payment ID */
					__( 'Create an invoice at payment gateway for <a href="%1$s">payment #%2$s</a> after processing the order.', 'pronamic_ideal' ),
					esc_url( $payment->get_edit_payment_url() ),
					esc_html( $payment->get_id() )
				);
			}

			$new_status = WooCommerce::ORDER_STATUS_PROCESSING;
		}

		/**
		 * Expired or failed.
		 *
		 * WooCommerce PayPal gateway uses 'failed' order status for an 'expired' payment.
		 *
		 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/classes/gateways/class-wc-paypal.php#L557.
		 */
		if ( in_array( $payment->get_status(), array( PaymentStatus::EXPIRED, PaymentStatus::FAILURE ), true ) ) {
			$new_status = WooCommerce::ORDER_STATUS_FAILED;
		}

		/**
		 * Pending payment.
		 */
		if ( PaymentStatus::OPEN === $payment->get_status() ) {
			$new_status = WooCommerce::ORDER_STATUS_PENDING;
		}

		/**
		 * Add note and update status.
		 */
		$order->add_order_note( $note );

		$is_pay_gateway = ( 'pronamic_' === substr( $order->get_payment_method(), 0, 9 ) );

		if ( null !== $new_status && $is_pay_gateway ) {
			// Only update status if order Pronamic payment ID is same as payment.
			$order_payment_id = (int) $order->get_meta( '_pronamic_payment_id' );

			if ( empty( $order_payment_id ) || $payment->get_id() === $order_payment_id ) {
				$order->update_status( $new_status );
			}
		}

		/**
		 * Subscriptions.
		 *
		 * For a failed payment we will let the related subscriptions know by calling
		 * the `payment_failed` function.
		 *
		 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.4.7/includes/class-wc-subscription.php#L1661-L1694
		 *
		 * @todo check if manually updating the subscription is still necessary.
		 */
		if ( PaymentStatus::FAILURE === $payment->get_status() ) {
			$subscriptions = array();

			if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
				$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
			}

			foreach ( $subscriptions as $subscription ) {
				$subscription->payment_failed();
			}
		}

		/**
		 * Success.
		 */
		if ( PaymentStatus::SUCCESS === $payment->get_status() ) {
			$order->payment_complete( $payment->get_transaction_id() );

			// Store payment ID of current payment in WooCommerce order meta.
			$order->update_meta_data( '_pronamic_payment_id', $payment->get_id() );
			$order->save();
		}
	}

	/**
	 * Maybe update refunded payment.
	 *
	 * @param Payment $payment Payment.
	 * @return void
	 */
	public function maybe_update_refunded_payment( Payment $payment ) {
		// Check refunded amount.
		$refunded_amount = $payment->get_refunded_amount();

		if ( null === $refunded_amount ) {
			return;
		}

		// Check source.
		if ( self::SLUG !== $payment->get_source() ) {
			return;
		}

		// Check WooCommerce order.
		$order = \wc_get_order( $payment->get_source_id() );

		if ( false === $order ) {
			return;
		}

		// Check updated refund amount.
		$wc_refunded_amount = (float) $order->get_meta( '_pronamic_amount_refunded', true );

		$refunded_value = $refunded_amount->get_value();

		if ( $wc_refunded_amount === $refunded_value ) {
			return;
		}

		// Create WooCommerce refund.
		$amount_difference = $refunded_amount->subtract( new Money( $wc_refunded_amount, $refunded_amount->get_currency() ) );

		try {
			\wc_create_refund(
				array(
					'amount'   => $amount_difference->get_value(),
					'order_id' => $order->get_id(),
				)
			);

			$order->update_meta_data( '_pronamic_amount_refunded', (string) $refunded_amount->get_value() );

			$order->save();

			// Add order note.
			$note = \sprintf(
				/* translators: 1: refund amount, 2: edit payment url, 3: payment ID */
				__( 'Added refund of %1$s for updated <a href="%2$s" title="Payment #%3$d">payment #%3$d</a>.' ),
				$amount_difference->format_i18n(),
				$payment->get_edit_payment_url(),
				$payment->get_id()
			);

			$order->add_order_note( $note );
		} catch ( \Exception $e ) {
			$payment->add_note(
				\sprintf(
					/* translators: %s: error message */
					\__( 'Unable to create WooCommerce refund: %s', 'pronamic_ideal' ),
					\esc_html( $e->getMessage() )
				)
			);
		}
	}

	/**
	 * Register settings.
	 */
	public static function register_settings() {
		// Date of birth checkout field.
		register_setting(
			'pronamic_pay',
			'pronamic_pay_woocommerce_birth_date_field',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'pronamic_pay',
			'pronamic_pay_woocommerce_birth_date_field_enable',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);

		// Gender checkout field.
		register_setting(
			'pronamic_pay',
			'pronamic_pay_woocommerce_gender_field',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'pronamic_pay',
			'pronamic_pay_woocommerce_gender_field_enable',
			array(
				'type'    => 'boolean',
				'default' => false,
			)
		);
	}

	/**
	 * Admin init.
	 */
	public static function admin_init() {
		// Plugin settings - WooCommerce.
		add_settings_section(
			'pronamic_pay_woocommerce',
			__( 'WooCommerce', 'pronamic_ideal' ),
			array( __CLASS__, 'settings_section' ),
			'pronamic_pay'
		);

		// Add settings fields.
		add_settings_field(
			'pronamic_pay_woocommerce_birth_date_field',
			__( 'Date of birth checkout field', 'pronamic_ideal' ),
			array( __CLASS__, 'input_checkout_fields_select' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'label_for' => 'pronamic_pay_woocommerce_birth_date_field',
			)
		);

		add_settings_field(
			'pronamic_pay_woocommerce_birth_date_field_enable',
			__( 'Add date of birth field', 'pronamic_ideal' ),
			array( __CLASS__, 'input_checkbox' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'legend'      => __( 'Add date of birth field', 'pronamic_ideal' ),
				'description' => __( 'Add date of birth field to billing checkout fields', 'pronamic_ideal' ),
				'label_for'   => 'pronamic_pay_woocommerce_birth_date_field_enable',
				'classes'     => 'regular-text',
				'type'        => 'checkbox',
			)
		);

		add_settings_field(
			'pronamic_pay_woocommerce_gender_field',
			__( 'Gender checkout field', 'pronamic_ideal' ),
			array( __CLASS__, 'input_checkout_fields_select' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'label_for' => 'pronamic_pay_woocommerce_gender_field',
			)
		);

		add_settings_field(
			'pronamic_pay_woocommerce_gender_field_enable',
			__( 'Add gender field', 'pronamic_ideal' ),
			array( __CLASS__, 'input_checkbox' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'legend'      => __( 'Add gender field', 'pronamic_ideal' ),
				'description' => __( 'Add gender field to billing checkout fields', 'pronamic_ideal' ),
				'label_for'   => 'pronamic_pay_woocommerce_gender_field_enable',
				'classes'     => 'regular-text',
				'type'        => 'checkbox',
			)
		);
	}

	/**
	 * Settings section.
	 *
	 * @param array $args Settings section arguments.
	 */
	public static function settings_section( $args ) {
		switch ( $args['id'] ) {
			case 'pronamic_pay_woocommerce':
				echo '<p>';

				esc_html_e(
					'Extra fields are used for post-pay payment methods such as AfterPay and Klarna.',
					'pronamic_ideal'
				);

				echo '</p>';

				break;
		}
	}

	/**
	 * Input checkbox.
	 *
	 * @link https://github.com/WordPress/WordPress/blob/4.9.1/wp-admin/options-writing.php#L60-L68
	 * @link https://github.com/WordPress/WordPress/blob/4.9.1/wp-admin/options-reading.php#L110-L141
	 * @param array $args Arguments.
	 */
	public static function input_checkbox( $args ) {
		$id     = $args['label_for'];
		$name   = $args['label_for'];
		$value  = get_option( $name );
		$legend = $args['legend'];

		echo '<fieldset>';

		printf(
			'<legend class="screen-reader-text"><span>%s</span></legend>',
			esc_html( $legend )
		);

		printf(
			'<label for="%s">',
			esc_attr( $id )
		);

		printf(
			'<input name="%s" id="%s" type="checkbox" value="1" %s />',
			esc_attr( $name ),
			esc_attr( $id ),
			checked( $value, 1, false )
		);

		echo esc_html( $args['description'] );

		echo '</label>';

		echo '</fieldset>';
	}

	/**
	 * Input element.
	 *
	 * @param array $args Arguments.
	 */
	public static function input_element( $args ) {
		$defaults = array(
			'type'        => 'text',
			'classes'     => 'regular-text',
			'description' => '',
			'options'     => array(),
		);

		$args = wp_parse_args( $args, $defaults );

		$name  = $args['label_for'];
		$value = (string) get_option( $name );

		$atts = array(
			'name'  => $name,
			'id'    => $name,
			'type'  => $args['type'],
			'class' => $args['classes'],
			'value' => $value,
		);

		switch ( $args['type'] ) {
			case 'select':
				printf(
					'<select %1$s />%2$s</select>',
					// @codingStandardsIgnoreStart
					Pay_Util::array_to_html_attributes( $atts ),
					Pay_Util::select_options_grouped( $args['options'], $value )
				// @codingStandardsIgnoreEnd
				);

				break;
			default:
				printf(
					'<input %1$s />',
					// @codingStandardsIgnoreStart
					Pay_Util::array_to_html_attributes( $atts )
					// @codingStandardsIgnoreEnd
				);
		}

		if ( ! empty( $args['description'] ) ) {
			printf(
				'<p class="description">%s</p>',
				esc_html( $args['description'] )
			);
		}
	}

	/**
	 * Select input with WooCommerce checkout fields.
	 *
	 * @param array $args Input element arguments.
	 * @return void
	 */
	public static function input_checkout_fields_select( $args ) {
		$options = array(
			array(
				'options' => array(
					__( '— Select a checkout field —', 'pronamic_ideal' ),
				),
			),
		);

		// Get WooCommerce checkout fields.
		try {
			/**
			 * Do action `woocommerce_load_cart_from_session` here to prevent fatal error with non-empty cart during
			 * shutdown, caused by calling undefined (frontend) function `wc_get_cart_item_data_hash()`.
			 *
			 * @link https://github.com/woocommerce/woocommerce/blob/4.3.1/includes/class-wc-cart.php#L609
			 * @link https://github.com/woocommerce/woocommerce/blob/4.3.1/includes/class-wc-cart-session.php#L72
			 * @since 2.1.4
			 */
			\do_action( 'woocommerce_load_cart_from_session' );

			$fields = WooCommerce::get_checkout_fields();
		} catch ( \Error $e ) {
			$fields = array();
		}

		$options = array_merge( $options, $fields );

		$args['type']    = 'select';
		$args['options'] = $options;

		self::input_element( $args );
	}

	/**
	 * Filter WooCommerce checkout fields.
	 *
	 * @param array $fields Checkout fields.
	 *
	 * @link https://docs.woocommerce.com/document/tutorial-customising-checkout-fields-using-actions-and-filters/
	 *
	 * @return array
	 */
	public static function checkout_fields( $fields ) {
		// Add date of birth field if enabled.
		$enable_birth_date_field = get_option( 'pronamic_pay_woocommerce_birth_date_field_enable' );

		if ( $enable_birth_date_field ) {
			$fields['billing']['pronamic_pay_birth_date'] = array(
				'type'     => 'date',
				'label'    => __( 'Date of birth', 'pronamic_ideal' ),
				'priority' => 110,
			);
		}

		// Add gender field if enabled.
		$enable_gender_field = get_option( 'pronamic_pay_woocommerce_gender_field_enable' );

		if ( $enable_gender_field ) {
			$fields['billing']['pronamic_pay_gender'] = array(
				'type'     => 'select',
				'label'    => __( 'Gender', 'pronamic_ideal' ),
				'priority' => 120,
				'options'  => array(
					''  => __( '— Select gender —', 'pronamic_ideal' ),
					'F' => __( 'Female', 'pronamic_ideal' ),
					'M' => __( 'Male', 'pronamic_ideal' ),
					'X' => __( 'Other', 'pronamic_ideal' ),
				),
			);
		}

		// Make fields required.
		$required = array(
			get_option( 'pronamic_pay_woocommerce_birth_date_field' ),
			get_option( 'pronamic_pay_woocommerce_gender_field' ),
		);

		$required = array_filter( $required );

		if ( ! empty( $required ) ) {
			foreach ( $fields as &$fieldset ) {
				foreach ( $fieldset as $field_key => &$field ) {
					if ( ! in_array( $field_key, $required, true ) ) {
						continue;
					}

					$field['required'] = true;
				}
			}
		}

		return $fields;
	}

	/**
	 * Checkout update order meta.
	 *
	 * @param int   $order_id Order ID.
	 * @param array $posted   Posted checkout data.
	 */
	public static function checkout_update_order_meta( $order_id, $posted ) {
		$fields = array(
			'pronamic_pay_gender'     => '_pronamic_pay_gender',
			'pronamic_pay_birth_date' => '_pronamic_pay_birth_date',
		);

		foreach ( $fields as $field_id => $meta_key ) {
			if ( ! filter_has_var( INPUT_POST, $field_id ) ) {
				continue;
			}

			$meta_value = filter_input( INPUT_POST, $field_id, FILTER_SANITIZE_STRING );

			update_post_meta( $order_id, $meta_key, $meta_value );
		}
	}

	/**
	 * Filter currencies.
	 *
	 * @param array $currencies Available currencies.
	 *
	 * @return mixed
	 */
	public static function currencies( $currencies ) {
		if ( PaymentMethods::is_active( PaymentMethods::GULDEN ) ) {
			$currencies['NLG'] = PaymentMethods::get_name( PaymentMethods::GULDEN );
		}

		return $currencies;
	}

	/**
	 * Filter currency symbol.
	 *
	 * @param string $symbol   Symbol.
	 * @param string $currency Currency.
	 *
	 * @return string
	 */
	public static function currency_symbol( $symbol, $currency ) {
		if ( 'NLG' === $currency ) {
			return 'G';
		}

		return $symbol;
	}

	/**
	 * Source text.
	 *
	 * @param string  $text    Source text.
	 * @param Payment $payment Payment.
	 *
	 * @return string
	 */
	public static function source_text( $text, Payment $payment ) {
		$text = __( 'WooCommerce', 'pronamic_ideal' ) . '<br />';

		// Check order post meta for order number.
		$order_number = '#' . $payment->source_id;

		$value = get_post_meta( $payment->source_id, '_order_number', true );

		if ( ! empty( $value ) ) {
			$order_number = $value;
		}

		$text .= sprintf(
			'<a href="%s">%s</a>',
			get_edit_post_link( $payment->source_id ),
			/* translators: %s: order number */
			sprintf( __( 'Order %s', 'pronamic_ideal' ), $order_number )
		);

		return $text;
	}

	/**
	 * Source description.
	 *
	 * @param string  $description Source description.
	 * @param Payment $payment     Payment.
	 *
	 * @return string
	 */
	public static function source_description( $description, Payment $payment ) {
		return __( 'WooCommerce Order', 'pronamic_ideal' );
	}

	/**
	 * Source URL.
	 *
	 * @param string  $url     Source URL.
	 * @param Payment $payment Payment.
	 *
	 * @return null|string
	 */
	public static function source_url( $url, Payment $payment ) {
		return get_edit_post_link( $payment->source_id );
	}

	/**
	 * Subscription source text.
	 *
	 * @param string       $text         Source text.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public static function subscription_source_text( $text, Subscription $subscription ) {
		$text = __( 'WooCommerce', 'pronamic_ideal' ) . '<br />';

		// Check order post meta for order number.
		$source_id = (int) $subscription->get_source_id();

		$order_number = sprintf( '#%s', $source_id );

		$value = get_post_meta( $source_id, '_order_number', true );

		if ( ! empty( $value ) ) {
			$order_number = $value;
		}

		$text .= sprintf(
			'<a href="%s">%s</a>',
			get_edit_post_link( $source_id ),
			/* translators: %s: order number */
			sprintf( __( 'Order %s', 'pronamic_ideal' ), $order_number )
		);

		return $text;
	}

	/**
	 * Subscription source description.
	 *
	 * @param string       $description  Source description.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return string
	 */
	public static function subscription_source_description( $description, Subscription $subscription ) {
		return __( 'WooCommerce Order', 'pronamic_ideal' );
	}

	/**
	 * Subscription source URL.
	 *
	 * @param string       $url          Source URL.
	 * @param Subscription $subscription Subscription.
	 *
	 * @return null|string
	 */
	public static function subscription_source_url( $url, Subscription $subscription ) {
		return get_edit_post_link( (int) $subscription->source_id );
	}
}
