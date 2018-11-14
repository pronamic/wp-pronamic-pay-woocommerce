<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Exception;
use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Statuses;
use Pronamic\WordPress\Pay\Core\Util as Core_Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Util as Pay_Util;
use WC_Order;
use WC_Subscriptions_Product;

/**
 * Title: WooCommerce iDEAL Add-On
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
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
	 * @param array $wc_gateways WooCommerce payment gateways.
	 *
	 * @return array
	 */
	public static function payment_gateways( $wc_gateways ) {
		// Default gateways.
		$gateways = array(
			'PronamicGateway',
			'BancontactGateway',
			'BankTransferGateway',
			'CreditCardGateway',
			'DirectDebitGateway',
			'IDealGateway',
			'SofortGateway',
		);

		foreach ( $gateways as $gateway ) {
			$wc_gateways[] = __NAMESPACE__ . '\\' . $gateway;
		}

		// Gateways based on payment method activation.
		$gateways = array(
			PaymentMethods::AFTERPAY                => 'AfterPayGateway',
			PaymentMethods::ALIPAY                  => 'AlipayGateway',
			PaymentMethods::BELFIUS                 => 'BelfiusGateway',
			PaymentMethods::BITCOIN                 => 'BitcoinGateway',
			PaymentMethods::BUNQ                    => 'BunqGateway',
			PaymentMethods::IN3                     => 'In3Gateway',
			PaymentMethods::DIRECT_DEBIT_BANCONTACT => 'DirectDebitBancontactGateway',
			PaymentMethods::DIRECT_DEBIT_IDEAL      => 'DirectDebitIDealGateway',
			PaymentMethods::DIRECT_DEBIT_SOFORT     => 'DirectDebitSofortGateway',
			PaymentMethods::FOCUM                   => 'FocumGateway',
			PaymentMethods::GIROPAY                 => 'GiropayGateway',
			PaymentMethods::GULDEN                  => 'GuldenGateway',
			PaymentMethods::IDEALQR                 => 'IDealQRGateway',
			PaymentMethods::KBC                     => 'KbcGateway',
			PaymentMethods::KLARNA_PAY_LATER        => 'KlarnaPayLaterGateway',
			PaymentMethods::MAESTRO                 => 'MaestroGateway',
			PaymentMethods::PAYCONIQ                => 'PayconiqGateway',
			PaymentMethods::PAYPAL                  => 'PayPalGateway',
		);

		foreach ( $gateways as $payment_method => $gateway ) {
			if ( ! PaymentMethods::is_active( $payment_method ) ) {
				continue;
			}

			$wc_gateways[] = __NAMESPACE__ . '\\' . $gateway;
		}

		return $wc_gateways;
	}

	/**
	 * WooCommerce thank you.
	 *
	 * @param string $order_id WooCommerce order ID.
	 */
	public static function woocommerce_thankyou( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( $order && WooCommerce::order_has_status( $order, 'pending' ) ) {
			printf( // WPCS: xss ok.
				'<div class="woocommerce-info">%s</div>',
				__( 'Your order will be processed once we receive the payment.', 'pronamic_ideal' )
			);
		}
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
			case Statuses::FAILURE:
				return WooCommerce::get_order_pay_url( $order );

			case Statuses::CANCELLED:
			case Statuses::EXPIRED:
				$url = $order->get_cancel_order_url();

				/*
				 * The WooCommerce developers changed the `get_cancel_order_url` function in version 2.1.0.
				 * In version 2.1.0 the WooCommerce plugin uses the `wp_nonce_url` function. This WordPress
				 * function uses the WordPress `esc_html` function. The `esc_html` function converts specials
				 * characters to HTML entities. This is causing redirecting issues, so we decode these back
				 * with the `wp_specialchars_decode` function.
				 *
				 * @link https://github.com/WordPress/WordPress/blob/4.1/wp-includes/functions.php#L1325-L1338
				 * @link https://github.com/WordPress/WordPress/blob/4.1/wp-includes/formatting.php#L3144-L3167
				 * @link https://github.com/WordPress/WordPress/blob/4.1/wp-includes/formatting.php#L568-L647
				 *
				 * @link https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/class-wc-order.php#L1112
				 *
				 * @link https://github.com/woothemes/woocommerce/blob/v2.0.20/classes/class-wc-order.php#L1115
				 * @link https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1693-L1703
				 *
				 * @link https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/class-wc-order.php#L1013
				 * @link https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce.php#L1630
				 */
				return wp_specialchars_decode( $url );

			case Statuses::SUCCESS:
			case Statuses::OPEN:
			default:
				$gateway = new Gateway();

				return $gateway->get_return_url( $order );
		}
	}

	/**
	 * Update lead status of the specified payment
	 *
	 * @param Payment $payment Payment.
	 */
	public static function status_update( Payment $payment ) {
		$source_id = $payment->get_source_id();

		$order = new WC_Order( (int) $source_id );

		// Only update if order is not 'processing' or 'completed'
		// @link https://github.com/woothemes/woocommerce/blob/v2.0.0/classes/class-wc-order.php#L1279.
		$should_update = ! WooCommerce::order_has_status(
			$order,
			array(
				WooCommerce::ORDER_STATUS_COMPLETED,
				WooCommerce::ORDER_STATUS_PROCESSING,
			)
		);

		if ( ! $should_update ) {
			return;
		}

		$subscriptions = array();

		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
		}

		switch ( $payment->get_status() ) {
			case Statuses::CANCELLED:
				// Nothing to do?
				break;
			case Statuses::EXPIRED:
				$note = sprintf( '%s %s.', WooCommerce::get_payment_method_title( $order ), __( 'payment expired', 'pronamic_ideal' ) );

				// WooCommerce PayPal gateway uses 'failed' order status for an 'expired' payment
				// @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/classes/gateways/class-wc-paypal.php#L557.
				$order->update_status( WooCommerce::ORDER_STATUS_FAILED, $note );

				break;
			case Statuses::FAILURE:
				$note = sprintf( '%s %s.', WooCommerce::get_payment_method_title( $order ), __( 'payment failed', 'pronamic_ideal' ) );

				$order->update_status( WooCommerce::ORDER_STATUS_FAILED, $note );

				// @todo check if manually updating the subscription is still necessary.
				foreach ( $subscriptions as $subscription ) {
					$subscription->payment_failed();
				}

				break;
			case Statuses::SUCCESS:
				// Payment completed.
				$order->add_order_note(
					sprintf(
						'%s %s.',
						WooCommerce::get_payment_method_title( $order ),
						__( 'payment completed', 'pronamic_ideal' )
					)
				);

				// Mark order complete.
				$order->payment_complete();

				break;
			case Statuses::OPEN:
				$order->add_order_note(
					sprintf(
						'%s %s.',
						WooCommerce::get_payment_method_title( $order ),
						__( 'payment open', 'pronamic_ideal' )
					)
				);

				break;
			default:
				$order->add_order_note(
					sprintf(
						'%s %s.',
						WooCommerce::get_payment_method_title( $order ),
						__( 'payment unknown', 'pronamic_ideal' )
					)
				);

				break;
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

		$subscription->set_status( Statuses::OPEN );

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
	 * @param $order
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

			$subscription->set_amount(
				new Money(
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
		WC()->customer = new \WC_Customer();

		$checkout_fields = array(
			array(
				'options' => array(
					__( '— Select a checkout field —', 'pronamic_ideal' ),
				),
			),
		);

		foreach ( WC()->checkout()->get_checkout_fields() as $fieldset_key => $fieldset ) {
			$checkout_fields[ $fieldset_key ] = array(
				'name'    => ucfirst( $fieldset_key ),
				'options' => array(),
			);

			foreach ( $fieldset as $field_key => $field ) {
				if ( empty( $field['label'] ) || strstr( $field_key, 'password' ) ) {
					continue;
				}

				$checkout_fields[ $fieldset_key ]['options'][ $field_key ] = $field['label'];
			}
		}

		// Add settings fields.
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
		// Add gender field if enabled.
		$enable_gender_field = get_option( 'pronamic_pay_woocommerce_gender_field_enable' );

		if ( $enable_gender_field ) {
			$fields['billing']['pronamic_pay_gender'] = array(
				'type'    => 'select',
				'label'   => __( 'Gender', 'pronamic_ideal' ),
				'options' => array(
					''  => __( '— Select gender —', 'pronamic_ideal' ),
					'F' => __( 'Female', 'pronamic_ideal' ),
					'M' => __( 'Male', 'pronamic_ideal' ),
					'X' => __( 'Other', 'pronamic_ideal' ),
				),
			);
		}

		// Add date of birth field if enabled.
		$enable_birth_date_field = get_option( 'pronamic_pay_woocommerce_birth_date_field_enable' );

		if ( $enable_birth_date_field ) {
			$fields['billing']['pronamic_pay_birth_date'] = array(
				'type'  => 'date',
				'label' => __( 'Date of birth', 'pronamic_ideal' ),
			);
		}

		// Make fields required.
		$required = array(
			get_option( 'pronamic_pay_woocommerce_gender_field' ),
			get_option( 'pronamic_pay_woocommerce_birth_date_field' ),
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
