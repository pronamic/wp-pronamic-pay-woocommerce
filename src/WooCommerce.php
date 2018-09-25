<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use WC_Order;
use WC_Subscriptions_Product;

/**
 * Title: WooCommerce
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
 * @since   1.0.0
 */
class WooCommerce {
	/**
	 * Order status pending
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L309
	 * @var string
	 */
	const ORDER_STATUS_PENDING = 'pending';

	/**
	 * Order status failed
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L310
	 * @var string
	 */
	const ORDER_STATUS_FAILED = 'failed';

	/**
	 * Order status on-hold
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L311
	 * @var string
	 */
	const ORDER_STATUS_ON_HOLD = 'on-hold';

	/**
	 * Order status processing
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L312
	 * @var string
	 */
	const ORDER_STATUS_PROCESSING = 'processing';

	/**
	 * Order status completed
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L313
	 * @var string
	 */
	const ORDER_STATUS_COMPLETED = 'completed';

	/**
	 * Order status refunded
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L314
	 * @var string
	 */
	const ORDER_STATUS_REFUNDED = 'refunded';

	/**
	 * Order status cancelled
	 *
	 * @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L315
	 * @var string
	 */
	const ORDER_STATUS_CANCELLED = 'cancelled';

	/**
	 * Check if WooCommerce is active (Automattic/developer style)
	 *
	 * @see https://github.com/jigoshop/jigoshop/blob/1.8/jigoshop.php#L45
	 * @see https://github.com/Automattic/developer/blob/1.1.2/developer.php#L73
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return defined( 'WOOCOMMERCE_VERSION' );
	}

	/**
	 * Version compare
	 *
	 * @param string $version
	 * @param string $operator
	 *
	 * @return bool|mixed
	 */
	public static function version_compare( $version, $operator ) {
		$result = true;

		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce.php#L140
		if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
			$result = version_compare( WOOCOMMERCE_VERSION, $version, $operator );
		}

		return $result;
	}

	/**
	 * Get WooCommerce date format
	 *
	 * @return string
	 */
	public static function get_date_format() {
		if ( function_exists( 'wc_date_format' ) ) {
			// WooCommerce 3.0+
			// @see https://github.com/woocommerce/woocommerce/blob/3.0.0/includes/wc-formatting-functions.php#L518-L525

			return wc_date_format();
		} elseif ( function_exists( 'woocommerce_date_format' ) ) {
			// @see https://github.com/woothemes/woocommerce/blob/v2.0.20/woocommerce-core-functions.php#L2169

			return woocommerce_date_format();
		}

		return get_option( 'date_format' );
	}

	/**
	 * Get currency.
	 *
	 * @see Pronamic_Pay_PaymentDataInterface::get_currency_alphabetic_code()
	 * @return string
	 */
	public static function get_currency() {
		// @see https://github.com/woothemes/woocommerce/blob/2.0.20/woocommerce-core-functions.php#L692-L700
		// @see https://github.com/woothemes/woocommerce/blob/2.1.0/includes/wc-core-functions.php#L146-L152
		// @see https://github.com/woothemes/woocommerce/blob/2.5.5/includes/wc-core-functions.php#L256-L263
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			return get_woocommerce_currency();
		}

		// @see http://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/admin/woocommerce-admin-settings.php#L32
		return get_option( 'woocommerce_currency' );
	}

	/**
	 * Get order pay URL for backwards compatibility.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return string the pay URL
	 */
	public static function get_order_pay_url( $order ) {
		$url = null;

		if ( method_exists( $order, 'get_checkout_payment_url' ) ) {
			// WooCommerce >= 2.1
			// @see http://docs.woothemes.com/document/woocommerce-endpoints-2-1/
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/class-wc-order.php#L1057-L1079
			$url = $order->get_checkout_payment_url( true );
		} else {
			// WooCommerce < 2.1
			$url = add_query_arg( array(
				'order' => $order->id,
				'key'   => $order->order_key,
			), get_permalink( woocommerce_get_page_id( 'pay' ) ) );
		}

		return $url;
	}

	/**
	 * Add notice.
	 *
	 * @param string $message
	 * @param string $type
	 */
	public static function add_notice( $message, $type = 'success' ) {
		global $woocommerce;

		if ( function_exists( 'wc_add_notice' ) ) {
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/wc-notice-functions.php#L54-L71
			wc_add_notice( $message, $type );
		} elseif ( 'error' === $type && method_exists( $woocommerce, 'add_error' ) ) {
			// @see https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1429-L1438
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.0/woocommerce.php#L797-L804
			$woocommerce->add_error( $message );
		} elseif ( method_exists( $woocommerce, 'add_message' ) ) {
			// @see https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1441-L1450
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.0/woocommerce.php#L806-L813
			$woocommerce->add_message( $message );
		}
	}

	/**
	 * Order has status.
	 *
	 * @param WC_Order     $order
	 * @param string|array $status
	 *
	 * @return bool
	 */
	public static function order_has_status( $order, $status ) {
		if ( method_exists( $order, 'has_status' ) ) {
			return $order->has_status( $status );
		}

		if ( is_array( $status ) ) {
			return in_array( $order->status, $status, true );
		}

		return ( $order->status === $status );
	}

	/**
	 * Get order status.
	 *
	 * @since 1.2.1
	 *
	 * @param WC_Order $order
	 *
	 * @return string
	 */
	public static function order_get_status( $order ) {
		if ( method_exists( $order, 'get_status' ) ) {
			return $order->get_status();
		}

		return $order->status;
	}

	/**
	 * Get order id.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_id( $order ) {
		if ( is_callable( $order, 'get_id' ) ) {
			return $order->get_id();
		}

		return $order->id;
	}

	/**
	 * Get order date.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_date( $order ) {
		if ( is_callable( $order, 'get_date_created' ) ) {
			return $order->get_date_created()->getTimestamp();
		}

		return strtotime( $order->order_date );
	}

	/**
	 * Get order total.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_total( $order ) {
		if ( is_callable( $order, 'get_total' ) ) {
			// WooCommerce 3.0+.
			return $order->get_total();
		}

		return $order->order_total;
	}

	/**
	 * Get order property.
	 *
	 * @param WC_Order $order    WooCommerce order.
	 * @param string   $property Property.
	 *
	 * @return mixed
	 */
	public static function get_order_property( $order, $property ) {
		$callable = array(
			$order,
			sprintf( 'get_%s', $property ),
		);

		if ( is_callable( $callable ) ) {
			// WooCommerce 3.0+.
			return call_user_func( $callable );
		}

		return $order->{$property};
	}

	/**
	 * Get payment method title.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_payment_method_title( WC_Order $order ) {
		return self::get_order_property( $order, 'payment_method_title' );
	}

	/**
	 * Get billing first name.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_first_name( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_first_name' );
	}

	/**
	 * Get billing last name.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_last_name( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_last_name' );
	}

	/**
	 * Get billing company.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_company( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_company' );
	}

	/**
	 * Get billing address 1.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_address_1( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_address_1' );
	}

	/**
	 * Get billing address 2.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_address_2( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_address_2' );
	}

	/**
	 * Get billing postcode.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_postcode( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_postcode' );
	}

	/**
	 * Get billing city.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_city( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_city' );
	}

	/**
	 * Get billing state.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_state( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_state' );
	}

	/**
	 * Get billing country.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_country( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_country' );
	}

	/**
	 * Get billing email.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_email( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_email' );
	}

	/**
	 * Get billing phone.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_billing_phone( WC_Order $order ) {
		return self::get_order_property( $order, 'billing_phone' );
	}

	/**
	 * Get shipping first name.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_first_name( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_first_name' );
	}

	/**
	 * Get shipping last name.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_last_name( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_last_name' );
	}

	/**
	 * Get shipping company.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_company( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_company' );
	}

	/**
	 * Get shipping address 1.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_address_1( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_address_1' );
	}

	/**
	 * Get shipping address 2.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_address_2( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_address_2' );
	}

	/**
	 * Get shipping postcode.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_postcode( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_postcode' );
	}

	/**
	 * Get shipping city.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_city( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_city' );
	}

	/**
	 * Get shipping state.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_state( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_state' );
	}

	/**
	 * Get shipping country.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_country( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_country' );
	}

	/**
	 * Get shipping email.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_email( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_email' );
	}

	/**
	 * Get shipping phone.
	 *
	 * @param WC_Order $order WooCommerce order.
	 *
	 * @return mixed
	 */
	public static function get_shipping_phone( WC_Order $order ) {
		return self::get_order_property( $order, 'shipping_phone' );
	}

	public static function subscription_source_id( $wcs_subscription ) {
		if ( ! is_object( $wcs_subscription ) ) {
			return;
		}

		if ( method_exists( $wcs_subscription, 'get_parent' ) ) {
			return self::get_order_id( $wcs_subscription->get_parent() );
		}

		return self::get_order_id( $wcs_subscription->order );
	}

	/**
	 * Get subscription product price.
	 *
	 * @see https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L384-L404
	 *
	 * @var WC_Subscriptions_Product
	 * @return float
	 */
	public static function get_subscription_product_price( $product ) {
		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_price' ) ) {
			return WC_Subscriptions_Product::get_price( $product );
		}

		// WooCommerce < 3.0.
		if ( isset( $product->subscription_price ) ) {
			return $product->subscription_price;
		}
	}

	/**
	 * Get subscription product length.
	 *
	 * @see https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L464-L473
	 *
	 * @var WC_Subscriptions_Product
	 * @return int
	 */
	public static function get_subscription_product_length( $product ) {
		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_length' ) ) {
			return WC_Subscriptions_Product::get_length( $product );
		}

		// WooCommerce < 3.0.
		if ( isset( $product->subscription_length ) ) {
			return $product->subscription_length;
		}
	}

	/**
	 * Get subscription product interval.
	 *
	 * @see https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L453-L462
	 *
	 * @var WC_Subscriptions_Product
	 * @return int
	 */
	public static function get_subscription_product_interval( $product ) {
		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_interval' ) ) {
			return WC_Subscriptions_Product::get_interval( $product );
		}

		// WooCommerce < 3.0.
		if ( isset( $product->subscription_period_interval ) ) {
			return $product->subscription_period_interval;
		}
	}

	/**
	 * Get subscription product interval.
	 *
	 * @see https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L442-L451
	 *
	 * @var WC_Subscriptions_Product
	 * @return int
	 */
	public static function get_subscription_product_period( $product ) {
		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_period' ) ) {
			return WC_Subscriptions_Product::get_period( $product );
		}

		// WooCommerce < 3.0.
		if ( isset( $product->subscription_period ) ) {
			return $product->subscription_period;
		}
	}
}
