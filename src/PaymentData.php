<?php

/**
 * Title: WooCommerce payment data
 * Description:
 * Copyright: Copyright (c) 2005 - 2017
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.7
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_PaymentData extends Pronamic_WP_Pay_PaymentData {
	/**
	 * Order
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php
	 * @var WC_Order
	 */
	private $order;

	/**
	 * Parent order
	 *
	 * @var WC_Order
	 */
	private $parent_order;

	/**
	 * Gateway
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v2.1.3/includes/abstracts/abstract-wc-payment-gateway.php
	 * @var WC_Payment_Gateway
	 */
	private $gateway;

	/**
	 * Description
	 *
	 * @var string
	 */
	private $description;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initializes an WooCommerce iDEAL data proxy
	 *
	 * @param WC_Order $order
	 */
	public function __construct( $order, $gateway, $description = null ) {
		parent::__construct();

		$this->order   = $order;
		$this->gateway = $gateway;
		$this->recurring = $this->gateway->is_recurring;

		$this->description = ( null === $description ) ? self::get_default_description() : $description;
	}

	//////////////////////////////////////////////////
	// Specific WooCommerce
	//////////////////////////////////////////////////

	/**
	 * Get default description
	 *
	 * @return string
	 */
	public static function get_default_description() {
		return __( 'Order {order_number}', 'pronamic_ideal' );
	}

	//////////////////////////////////////////////////
	// Issuer
	//////////////////////////////////////////////////

	public function get_issuer_id() {
		return filter_input( INPUT_POST, $this->gateway->id . '_issuer_id', FILTER_SANITIZE_STRING );
	}

	//////////////////////////////////////////////////

	/**
	 * Get source indicator
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_source()
	 * @return string
	 */
	public function get_source() {
		return 'woocommerce';
	}

	public function get_source_id() {
		if ( method_exists( $this->order, 'get_id' ) ) {
			// WooCommerce 3.0+
			return $this->order->get_id();
		}

		return $this->order->id;
	}

	//////////////////////////////////////////////////

	public function get_title() {
		return sprintf( __( 'WooCommerce order %s', 'pronamic_ideal' ), $this->get_order_id() );
	}

	/**
	 * Get description
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_description()
	 * @return string
	 */
	public function get_description() {
		// @see https://github.com/woothemes/woocommerce/blob/v2.0.19/classes/emails/class-wc-email-new-order.php
		$find    = array();
		$replace = array();

		$find[]    = '{blogname}';
		$replace[] = $this->get_blogname();

		$find[]    = '{site_title}';
		$replace[] = $this->get_blogname();

		if ( method_exists( $this->order, 'get_date_created' ) ) {
			// WooCommerce 3.0+
			$order_date = $this->order->get_date_created()->getTimestamp();
		} else {
			$order_date = strtotime( $this->order->order_date );
		}

		$find[]    = '{order_date}';
		$replace[] = date_i18n( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::get_date_format(), $order_date );

		$find[]    = '{order_number}';
		$replace[] = $this->order->get_order_number();

		// Description
		$description = str_replace( $find, $replace, $this->description );

		return $description;
	}

	/**
	 * Get order ID
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_order_id()
	 * @return string
	 */
	public function get_order_id() {
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.5.2/classes/class-wc-order.php#L269
		$order_id = $this->order->get_order_number();

		/*
		 * An '#' character can result in the following iDEAL error:
		 * code             = SO1000
		 * message          = Failure in system
		 * detail           = System generating error: issuer
		 * consumer_message = Paying with iDEAL is not possible. Please try again later or pay another way.
		 *
		 * Or in case of Sisow:
		 * <errorresponse xmlns="https://www.sisow.nl/Sisow/REST" version="1.0.0">
		 *     <error>
		 *         <errorcode>TA3230</errorcode>
		 *         <errormessage>No purchaseid</errormessage>
		 *     </error>
		 * </errorresponse>
		 *
		 * @see http://wcdocs.woothemes.com/user-guide/extensions/functionality/sequential-order-numbers/#add-compatibility
		 *
		 * @see page 30 http://pronamic.nl/wp-content/uploads/2012/09/iDEAL-Merchant-Integratie-Gids-NL.pdf
		 *
		 * The use of characters that are not listed above will not lead to a refusal of a batch or post, but the
		 * character will be changed by Equens (formerly Interpay) to a space, question mark or asterisk. The
		 * same goes for diacritical characters (à, ç, ô, ü, ý etcetera).
		 */
		$order_id = str_replace( '#', '', $order_id );

		return $order_id;
	}

	/**
	 * Get items
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_items()
	 * @return Pronamic_IDeal_Items
	 */
	public function get_items() {
		// Items
		$items = new Pronamic_IDeal_Items();

		// Price
		if ( method_exists( $this->order, 'get_total' ) ) {
			// WooCommerce 3.0+
			$price = $this->order->get_total();
		} else {
			// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php#L50
			$price = $this->order->order_total;
		}

		$subscription = $this->get_subscription();

		if ( $this->recurring && $subscription ) {
			$price = $subscription->get_amount();
		}

		// Support part payments with WooCommerce Deposits plugin
		// @since 1.1.6
		if ( method_exists( $this->order, 'has_status' ) && $this->order->has_status( 'partially-paid' ) && isset( $this->order->wc_deposits_remaining ) ) {
			$price = $this->order->wc_deposits_remaining;
		}

		// Item
		// We only add one total item, because iDEAL cant work with negative price items (discount)
		$item = new Pronamic_IDeal_Item();
		$item->setNumber( $this->get_order_id() );
		$item->setDescription( $this->get_description() );
		$item->setPrice( $price );
		$item->setQuantity( 1 );

		$items->addItem( $item );

		return $items;
	}

	//////////////////////////////////////////////////
	// Currency
	//////////////////////////////////////////////////

	/**
	 * Get currency
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_currency_alphabetic_code()
	 * @return string
	 */
	public function get_currency_alphabetic_code() {
		// @see https://github.com/woothemes/woocommerce/blob/2.0.20/woocommerce-core-functions.php#L692-L700
		// @see https://github.com/woothemes/woocommerce/blob/2.1.0/includes/wc-core-functions.php#L146-L152
		// @see https://github.com/woothemes/woocommerce/blob/2.5.5/includes/wc-core-functions.php#L256-L263
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			return get_woocommerce_currency();
		}

		// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/admin/woocommerce-admin-settings.php#L32
		return get_option( 'woocommerce_currency' );
	}

	//////////////////////////////////////////////////
	// Customer
	//////////////////////////////////////////////////

	public function get_email() {
		if ( method_exists( $this->order, 'get_billing_email' ) ) {
			// WooCommerce 3.0+
			return $this->order->get_billing_email();
		}

		// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php#L30
		return $this->order->billing_email;
	}

	public function get_first_name() {
		if ( method_exists( $this->order, 'get_billing_first_name' ) ) {
			return $this->order->get_billing_first_name();
		}

		if ( isset( $this->order->billing_first_name ) ) {
			return $this->order->billing_first_name;
		}
	}

	public function get_last_name() {
		if ( method_exists( $this->order, 'get_billing_last_name' ) ) {
			return $this->order->get_billing_last_name();
		}

		if ( isset( $this->order->billing_last_name ) ) {
			return $this->order->billing_last_name;
		}
	}

	public function get_customer_name() {
		if ( method_exists( $this->order, 'get_billing_first_name' ) ) {
			// WooCommerce 3.0+
			$first_name = $this->order->get_billing_first_name();
			$last_name  = $this->order->get_billing_last_name();
		} else {
			$first_name = $this->order->billing_first_name;
			$last_name  = $this->order->billing_last_name;
		}

		// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php#L21
		return $first_name . ' ' . $last_name;
	}

	public function get_address() {
		if ( method_exists( $this->order, 'get_billing_address_1' ) ) {
			// WooCommerce 3.0+
			return $this->order->get_billing_address_1();
		}

		// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php#L24
		return $this->order->billing_address_1;
	}

	public function get_city() {
		if ( method_exists( $this->order, 'get_billing_city' ) ) {
			// WooCommerce 3.0+
			return $this->order->get_billing_city();
		}

		return $this->order->billing_city;
	}

	public function get_zip() {
		if ( method_exists( $this->order, 'get_billing_postcode' ) ) {
			// WooCommerce 3.0+
			return $this->order->get_billing_postcode();
		}

		// http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/classes/class-wc-order.php#L26
		return $this->order->billing_postcode;
	}

	//////////////////////////////////////////////////
	// URL's
	//////////////////////////////////////////////////

	/**
	 * Get normal return URL.
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v2.1.3/includes/abstracts/abstract-wc-payment-gateway.php#L52
	 * @return string
	 */
	public function get_normal_return_url() {
		return $this->gateway->get_return_url( $this->order );
	}

	public function get_cancel_url() {
		$url = $this->order->get_cancel_order_url();

		/*
		 * The WooCommerce developers changed the `get_cancel_order_url` function in version 2.1.0.
		 * In version 2.1.0 the WooCommerce plugin uses the `wp_nonce_url` function. This WordPress
		 * function uses the WordPress `esc_html` function. The `esc_html` function converts specials
		 * characters to HTML entities. This is causing redirecting issues, so we decode these back
		 * with the `wp_specialchars_decode` function.
		 *
		 * @see https://github.com/WordPress/WordPress/blob/4.1/wp-includes/functions.php#L1325-L1338
		 * @see https://github.com/WordPress/WordPress/blob/4.1/wp-includes/formatting.php#L3144-L3167
		 * @see https://github.com/WordPress/WordPress/blob/4.1/wp-includes/formatting.php#L568-L647
		 *
		 * @see https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/class-wc-order.php#L1112
		 *
		 * @see https://github.com/woothemes/woocommerce/blob/v2.0.20/classes/class-wc-order.php#L1115
		 * @see https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1693-L1703
		 *
		 * @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/class-wc-order.php#L1013
		 * @see https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce.php#L1630
		 */
		$url = wp_specialchars_decode( $url );

		return $url;
	}

	public function get_success_url() {
		return $this->get_normal_return_url();
	}

	public function get_error_url() {
		return $this->order->get_checkout_payment_url();
	}

	//////////////////////////////////////////////////
	// Subscription
	//////////////////////////////////////////////////

	/**
	 * Get subscription.
	 *
	 * @since 1.2.1
	 * @see https://github.com/woothemes/woocommerce/blob/v2.1.3/includes/abstracts/abstract-wc-payment-gateway.php#L52
	 * @see https://github.com/wp-premium/woocommerce-subscriptions/blob/2.0.18/includes/class-wc-subscriptions-renewal-order.php#L371-L398
	 * @return string|bool
	 */
	public function get_subscription() {
		if ( ! class_exists( 'WC_Subscriptions' ) || ! function_exists( 'wcs_order_contains_renewal' ) || ! function_exists( 'wcs_order_contains_subscription' ) ) {
			return false;
		}

		$order = $this->order;

		if ( wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
			$subscription  = array_pop( $subscriptions );

			$order = $subscription->order;

			$this->parent_order = $order;
		}

		if ( ! $order ) {
			return false;
		}

		if ( ! wcs_order_contains_subscription( $order ) ) {
			return false;
		}

		$order_items = $order->get_items();

		// Find subscription order item
		foreach ( $order_items as $order_item ) {
			$product = $order->get_product_from_item( $order_item );

			if ( WC_Subscriptions_Product::is_subscription( $product ) ) {
				$description = sprintf(
					'Order #%s - %s',
					$this->get_source_id(),
					$product->get_title()
				);

				$subscription                     = new Pronamic_Pay_Subscription();
				$subscription->frequency          = $product->subscription_length;
				$subscription->interval           = $product->subscription_period_interval;
				$subscription->interval_period    = Pronamic_WP_Pay_Util::to_period( $product->subscription_period );
				$subscription->amount             = $product->subscription_price;
				$subscription->currency           = $this->get_currency();
				$subscription->description        = $description;

				return $subscription;
			}
		}

		return false;
	}

	/**
	 * Get subscription source ID.
	 *
	 * @since 1.2.1
	 * @return string
	 */
	public function get_subscription_source_id() {
		$subscription = $this->get_subscription();

		if ( ! $subscription ) {
			return false;
		}

		if ( $this->parent_order ) {
			if ( method_exists( $this->parent_order, 'get_id' ) ) {
				// WooCommerce 3.0+
				return $this->parent_order->get_id();
			}

			return $this->parent_order->id;
		}

		return $this->get_source_id();
	}
}
