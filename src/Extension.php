<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Exception;
use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Util as Pay_Util;
use WC_Order;
use WC_Subscriptions_Product;

/**
 * Title: WooCommerce iDEAL Add-On
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.5
 * @since   1.1.0
 */
class Extension {
	/**
	 * Slug
	 *
	 * @var string
	 */
	const SLUG = 'woocommerce';

	/**
	 * Bootstrap
	 */
	public static function bootstrap() {
		add_action( 'init', array( __CLASS__, 'init' ) );

		add_action( 'admin_init', array( __CLASS__, 'admin_init' ), 15 );

		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'payment_gateways' ) );

		add_action( 'woocommerce_thankyou', array( __CLASS__, 'woocommerce_thankyou' ) );
	}

	/**
	 * Initialize
	 */
	public static function init() {
		if ( ! WooCommerce::is_active() ) {
			return;
		}

		add_filter( 'pronamic_payment_redirect_url_' . self::SLUG, array( __CLASS__, 'redirect_url' ), 10, 2 );
		add_action( 'pronamic_payment_status_update_' . self::SLUG, array( __CLASS__, 'status_update' ), 10, 1 );
		add_filter( 'pronamic_payment_source_text_' . self::SLUG, array( __CLASS__, 'source_text' ), 10, 2 );
		add_filter( 'pronamic_payment_source_description_' . self::SLUG, array( __CLASS__, 'source_description' ), 10, 2 );
		add_filter( 'pronamic_payment_source_url_' . self::SLUG, array( __CLASS__, 'source_url' ), 10, 2 );

		add_action( 'pronamic_payment_status_update_' . self::SLUG . '_reserved_to_cancelled', array( __CLASS__, 'reservation_cancelled_note' ), 10, 1 );

		// WooCommerce Subscriptions.
		add_action( 'woocommerce_subscription_status_cancelled', array( __CLASS__, 'subscription_cancelled' ), 10, 1 );
		add_action( 'woocommerce_subscription_status_on-hold', array( __CLASS__, 'subscription_on_hold' ), 10, 1 );
		add_action( 'woocommerce_subscription_status_on-hold_to_active', array( __CLASS__, 'subscription_reactivated' ), 10, 1 );
		add_action( 'woocommerce_subscriptions_switch_completed', array( __CLASS__, 'subscription_switch_completed' ), 10, 1 );

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
					'class'        => __NAMESPACE__ . '\Gateway',
					'check_active' => true,
				)
			);

			if ( $args['check_active'] && isset( $args['payment_method'] ) ) {
				$payment_method = $args['payment_method'];

				if ( ! PaymentMethods::is_active( $payment_method ) ) {
					continue;
				}
			}

			$class = $args['class'];

			$wc_gateways[] = new $class( $args );
		}

		return $wc_gateways;
	}

	/**
	 * Get gateways.
	 *
	 * @return array
	 */
	public static function get_gateways() {
		return array(
			array(
				'id'                 => 'pronamic_pay',
				'method_title'       => __( 'Pronamic', 'pronamic_ideal' ),
				'method_description' => __( "This payment method does not use a predefined payment method for the payment. Some payment providers list all activated payment methods for your account to choose from. Use payment method specific gateways (such as 'iDEAL') to let customers choose their desired payment method at checkout.", 'pronamic_ideal' ),
				'check_active'       => false,
			),
			array(
				'id'             => 'pronamic_pay_afterpay',
				'payment_method' => PaymentMethods::AFTERPAY,
			),
			array(
				'id'             => 'pronamic_pay_alipay',
				'payment_method' => PaymentMethods::ALIPAY,
				'icon'           => plugins_url( 'images/alipay/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_mister_cash',
				'payment_method' => PaymentMethods::BANCONTACT,
				'icon'           => plugins_url( 'images/bancontact/icon-51x32.png', Plugin::$file ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_bank_transfer',
				'payment_method' => PaymentMethods::BANK_TRANSFER,
				'icon'           => plugins_url( 'images/bank-transfer/icon-51x32.png', Plugin::$file ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_belfius',
				'payment_method' => PaymentMethods::BELFIUS,
				'icon'           => plugins_url( 'images/belfius/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_bitcoin',
				'payment_method' => PaymentMethods::BITCOIN,
				'icon'           => plugins_url( 'images/bitcoin/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_bunq',
				'payment_method' => PaymentMethods::BUNQ,
				'icon'           => plugins_url( 'images/bunq/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_credit_card',
				'payment_method' => PaymentMethods::CREDIT_CARD,
				'icon'           => plugins_url( 'images/credit-card/wc-icon.png', Plugin::$file ),
				'check_active'   => false,
				'class'          => __NAMESPACE__ . '\CreditCardGateway',
			),
			array(
				'id'             => 'pronamic_pay_direct_debit',
				'payment_method' => PaymentMethods::DIRECT_DEBIT,
				'icon'           => plugins_url( 'images/direct-debit/icon-51x32.png', Plugin::$file ),
				'check_active'   => false,
			),
			array(
				'id'             => 'pronamic_pay_direct_debit_bancontact',
				'payment_method' => PaymentMethods::DIRECT_DEBIT_BANCONTACT,
				'icon'           => plugins_url( 'images/direct-debit-bancontact/icon-51x32.png', Plugin::$file ),
				'class'          => __NAMESPACE__ . '\DirectDebitBancontactGateway',
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
				'icon'           => plugins_url( 'images/direct-debit-ideal/icon-51x32.png', Plugin::$file ),
				'class'          => __NAMESPACE__ . '\DirectDebitIDealGateway',
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
				'icon'           => plugins_url( 'images/direct-debit-sofort/icon-51x32.png', Plugin::$file ),
				'class'          => __NAMESPACE__ . '\DirectDebitSofortGateway',
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
			),
			array(
				'id'             => 'pronamic_pay_eps',
				'payment_method' => PaymentMethods::EPS,
			),
			array(
				'id'             => 'pronamic_pay_giropay',
				'payment_method' => PaymentMethods::GIROPAY,
				'icon'           => plugins_url( 'images/giropay/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_gulden',
				'payment_method' => PaymentMethods::GULDEN,
				'icon'           => plugins_url( 'images/gulden/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_ideal',
				'payment_method' => PaymentMethods::IDEAL,
				'icon'           => plugins_url( 'images/ideal/icon-51x32.png', Plugin::$file ),
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
				'icon'           => plugins_url( 'images/idealqr/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_in3',
				'payment_method' => PaymentMethods::IN3,
			),
			array(
				'id'             => 'pronamic_pay_kbc',
				'payment_method' => PaymentMethods::KBC,
				'icon'           => plugins_url( 'images/kbc/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_klarna_pay_later',
				'payment_method' => PaymentMethods::KLARNA_PAY_LATER,
			),
			array(
				'id'             => 'pronamic_pay_maestro',
				'payment_method' => PaymentMethods::MAESTRO,
				'icon'           => plugins_url( 'images/maestro/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_payconiq',
				'payment_method' => PaymentMethods::PAYCONIQ,
				'icon'           => plugins_url( 'images/payconiq/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_paypal',
				'payment_method' => PaymentMethods::PAYPAL,
				'icon'           => plugins_url( 'images/paypal/icon-51x32.png', Plugin::$file ),
			),
			array(
				'id'             => 'pronamic_pay_sofort',
				'payment_method' => PaymentMethods::SOFORT,
				'icon'           => plugins_url( 'images/sofort/icon-51x32.png', Plugin::$file ),
			),
		);
	}

	/**
	 * WooCommerce thank you.
	 *
	 * @param string $order_id WooCommerce order ID.
	 */
	public static function woocommerce_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		if ( ! WooCommerce::order_has_status( $order, 'pending' ) ) {
			return;
		}

		// Add notice.
		printf(
			'<div class="woocommerce-info">%s</div>',
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			__( 'We process your order as soon as we have processed your payment.', 'pronamic_ideal' )
		);
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
			case Statuses::CANCELLED:
			case Statuses::EXPIRED:
			case Statuses::FAILURE:
				return WooCommerce::get_order_pay_url( $order );

			case Statuses::SUCCESS:
			case Statuses::OPEN:
			default:
				$gateway = new Gateway();

				return $gateway->get_return_url( $order );
		}
	}

	/**
	 * Add note when reserved payment is cancelled.
	 *
	 * @param Payment $payment Payment.
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
		 * For a payment with status 'reserverd' we add an extra note to inform shop
		 * managers what to do.
		 */
		if ( Statuses::RESERVED === $payment->get_status() ) {
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
		if ( in_array( $payment->get_status(), array( Statuses::EXPIRED, Statuses::FAILURE ), true ) ) {
			$new_status = WooCommerce::ORDER_STATUS_FAILED;
		}

		/**
		 * Add note and update status.
		 */
		$order->add_order_note( $note );

		if ( null !== $new_status ) {
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
		if ( Statuses::FAILURE === $payment->get_status() ) {
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
		if ( Statuses::SUCCESS === $payment->get_status() ) {
			$order->payment_complete( $payment->get_transaction_id() );
		}
	}

	/**
	 * Update subscription status when WooCommerce subscription is set on hold.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_on_hold( $wcs_subscription ) {
		$source_id = WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$note = sprintf(
			/* translators: %s: WooCommerce */
			__( '%s subscription on hold.', 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		$subscription->set_status( Statuses::ON_HOLD );

		$subscription->save();

		$subscription->set_meta( 'next_payment', null );
	}

	/**
	 * Update subscription status and dates when WooCommerce subscription is reactivated.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_reactivated( $wcs_subscription ) {
		$source_id = WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$note = sprintf(
			/* translators: %s: WooCommerce */
			__( '%s subscription reactivated.', 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		$subscription->set_status( Statuses::SUCCESS );

		// Set next payment date.
		$next_payment_date = new DateTime( '@' . $wcs_subscription->get_time( 'next_payment' ) );

		$subscription->set_next_payment_date( $next_payment_date );

		$subscription->set_expiry_date( $next_payment_date );

		$subscription->save();
	}

	/**
	 * Update subscription status when WooCommerce subscription is cancelled.
	 *
	 * @param $wcs_subscription
	 */
	public static function subscription_cancelled( $wcs_subscription ) {
		$source_id = WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( ! $subscription ) {
			return;
		}

		$note = sprintf(
			/* translators: %s: WooCommerce */
			__( '%s subscription cancelled.', 'pronamic_ideal' ),
			__( 'WooCommerce', 'pronamic_ideal' )
		);

		$subscription->add_note( $note );

		$subscription->set_status( Statuses::CANCELLED );

		$subscription->save();
	}

	/**
	 * Update subscription meta and dates when WooCommerce subscription is switched.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscription.php#L1174-L1186
	 *
	 * @param WC_Order $order Order.
	 */
	public static function subscription_switch_completed( $order ) {
		$subscriptions    = wcs_get_subscriptions_for_order( $order );
		$wcs_subscription = array_pop( $subscriptions );

		$source_id = WooCommerce::subscription_source_id( $wcs_subscription );

		$subscription = get_pronamic_subscription_by_meta( '_pronamic_subscription_source_id', $source_id );

		if ( empty( $subscription ) ) {
			return;
		}

		// Find subscription order item.
		foreach ( $order->get_items() as $item ) {
			$product = $order->get_product_from_item( $item );

			if ( ! WC_Subscriptions_Product::is_subscription( $product ) ) {
				continue;
			}

			$subscription->frequency       = WooCommerce::get_subscription_product_length( $product );
			$subscription->interval        = WooCommerce::get_subscription_product_interval( $product );
			$subscription->interval_period = Core_Util::to_period( WooCommerce::get_subscription_product_period( $product ) );

			$subscription->set_total_amount(
				new TaxedMoney(
					$wcs_subscription->get_total(),
					$subscription->get_currency()
				)
			);

			$next_payment_date = new DateTime( '@' . $wcs_subscription->get_time( 'next_payment' ) );

			$subscription->set_next_payment_date( $next_payment_date );

			$subscription->set_expiry_date( $next_payment_date );

			$subscription->save();
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
		if ( ! WooCommerce::is_active() ) {
			return;
		}

		// Plugin settings - WooCommerce.
		add_settings_section(
			'pronamic_pay_woocommerce',
			__( 'WooCommerce', 'pronamic_ideal' ),
			array( __CLASS__, 'settings_section' ),
			'pronamic_pay'
		);

		// Get WooCommerce checkout fields.
		$checkout_fields = array(
			array(
				'options' => array(
					__( '— Select a checkout field —', 'pronamic_ideal' ),
				),
			),
		);

		$checkout_fields = array_merge( $checkout_fields, WooCommerce::get_checkout_fields() );

		// Add settings fields.
		add_settings_field(
			'pronamic_pay_woocommerce_birth_date_field',
			__( 'Date of birth checkout field', 'pronamic_ideal' ),
			array( __CLASS__, 'input_element' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'label_for' => 'pronamic_pay_woocommerce_birth_date_field',
				'type'      => 'select',
				'options'   => $checkout_fields,
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
			array( __CLASS__, 'input_element' ),
			'pronamic_pay',
			'pronamic_pay_woocommerce',
			array(
				'label_for' => 'pronamic_pay_woocommerce_gender_field',
				'type'      => 'select',
				'options'   => $checkout_fields,
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
	 * Input text.
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
		$value = get_option( $name );

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
}
