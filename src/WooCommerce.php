<?php
/**
 * WooCommerce
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Html\Element;
use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use WC_Subscription;
use WC_Subscriptions_Product;
use WP_Term;

/**
 * Title: WooCommerce
 * Description:
 * Copyright: 2005-2025 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.1.0
 * @since   1.0.0
 */
class WooCommerce {
	/**
	 * Order status pending
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L309
	 * @var string
	 */
	const ORDER_STATUS_PENDING = 'pending';

	/**
	 * Order status failed
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L310
	 * @var string
	 */
	const ORDER_STATUS_FAILED = 'failed';

	/**
	 * Order status on-hold
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L311
	 * @var string
	 */
	const ORDER_STATUS_ON_HOLD = 'on-hold';

	/**
	 * Order status processing
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L312
	 * @var string
	 */
	const ORDER_STATUS_PROCESSING = 'processing';

	/**
	 * Order status completed
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L313
	 * @var string
	 */
	const ORDER_STATUS_COMPLETED = 'completed';

	/**
	 * Order status refunded
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L314
	 * @var string
	 */
	const ORDER_STATUS_REFUNDED = 'refunded';

	/**
	 * Order status cancelled
	 *
	 * @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.4/admin/woocommerce-admin-install.php#L315
	 * @var string
	 */
	const ORDER_STATUS_CANCELLED = 'cancelled';

	/**
	 * Version compare.
	 *
	 * @param string $version  Version.
	 * @param string $operator Comparison operator.
	 *
	 * @return bool|mixed
	 */
	public static function version_compare( $version, $operator ) {
		$result = true;

		// @link https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce.php#L140
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
			// @link https://github.com/woocommerce/woocommerce/blob/3.0.0/includes/wc-formatting-functions.php#L518-L525.
			return wc_date_format();
		} elseif ( function_exists( 'woocommerce_date_format' ) ) {
			// @link https://github.com/woothemes/woocommerce/blob/v2.0.20/woocommerce-core-functions.php#L2169.
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
		// @link https://github.com/woothemes/woocommerce/blob/2.0.20/woocommerce-core-functions.php#L692-L700
		// @link https://github.com/woothemes/woocommerce/blob/2.1.0/includes/wc-core-functions.php#L146-L152
		// @link https://github.com/woothemes/woocommerce/blob/2.5.5/includes/wc-core-functions.php#L256-L263
		if ( function_exists( 'get_woocommerce_currency' ) ) {
			return get_woocommerce_currency();
		}

		// @link https://plugins.trac.wordpress.org/browser/woocommerce/tags/1.5.2.1/admin/woocommerce-admin-settings.php#L32
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
		// WooCommerce 2.1+.
		if ( method_exists( $order, 'get_checkout_payment_url' ) ) {
			// @link http://docs.woothemes.com/document/woocommerce-endpoints-2-1/.
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/class-wc-order.php#L1057-L1079.
			return $order->get_checkout_payment_url( false );
		}

		// WooCommerce < 2.1.
		return add_query_arg(
			[
				'order' => self::get_order_id( $order ),
				'key'   => $order->order_key,
			],
			get_permalink( woocommerce_get_page_id( 'pay' ) )
		);
	}

	/**
	 * Add notice.
	 *
	 * @param string $message Message.
	 * @param string $type    Type.
	 * @return void
	 */
	public static function add_notice( $message, $type = 'success' ) {
		global $woocommerce;

		if ( function_exists( 'wc_add_notice' ) ) {
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/wc-notice-functions.php#L54-L71
			wc_add_notice( $message, $type );
		}

		// Check WooCommerce object.
		if ( ! is_object( $woocommerce ) ) {
			return;
		}

		if ( 'error' === $type && method_exists( $woocommerce, 'add_error' ) ) {
			// @link https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1429-L1438
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/woocommerce.php#L797-L804
			$woocommerce->add_error( $message );
		} elseif ( method_exists( $woocommerce, 'add_message' ) ) {
			// @link https://github.com/woothemes/woocommerce/blob/v2.0.0/woocommerce.php#L1441-L1450
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/woocommerce.php#L806-L813
			$woocommerce->add_message( $message );
		}
	}

	/**
	 * Order has status.
	 *
	 * @param WC_Order                  $order  Order.
	 * @param string|array<int, string> $status Status(es).
	 *
	 * @return bool
	 */
	public static function order_has_status( $order, $status ) {
		// WooCommerce 2.7+.
		if ( method_exists( $order, 'has_status' ) ) {
			return $order->has_status( $status );
		}

		// WooCommerce < 2.7.
		if ( is_array( $status ) ) {
			return in_array( $order->status, $status, true );
		}

		return ( $order->status === $status );
	}

	/**
	 * Get order status.
	 *
	 * @since 1.2.1
	 * @param WC_Order $order Order.
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
	 * @return int
	 */
	public static function get_order_id( $order ) {
		$order_id = null;

		// WooCommerce 2.6+.
		if ( is_callable( [ $order, 'get_id' ] ) ) {
			$order_id = $order->get_id();
		}

		// WooCommerce < 2.6.
		if ( null === $order_id ) {
			$order_id = $order->id;
		}

		return $order_id;
	}

	/**
	 * Get order date.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return int|null
	 */
	public static function get_order_date( $order ) {
		$date = null;

		if ( is_callable( [ $order, 'get_date_created' ] ) ) {
			$created = $order->get_date_created();

			if ( null !== $created ) {
				$date = $created->getTimestamp();
			}
		}

		if ( null === $date ) {
			$order_date = strtotime( $order->order_date );

			if ( false !== $order_date ) {
				$date = $order_date;
			}
		}

		return $date;
	}

	/**
	 * Get order total.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return float
	 */
	public static function get_order_total( $order ) {
		$total = null;

		// WooCommerce 3.0+.
		if ( is_callable( [ $order, 'get_total' ] ) ) {
			$total = $order->get_total();
		}

		if ( null === $total ) {
			// WooCommerce < 3.0.
			$total = $order->order_total;
		}

		return $total;
	}

	/**
	 * Get order total tax.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return float
	 */
	public static function get_order_total_tax( $order ) {
		$tax = null;

		// WooCommerce 3.0+.
		if ( is_callable( [ $order, 'get_total_tax' ] ) ) {
			$tax = $order->get_total_tax();
		}

		// WooCommerce < 3.0.
		if ( null === $tax ) {
			$tax = $order->total_tax;
		}

		return $tax;
	}

	/**
	 * Get order shipping total.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_shipping_total( $order ) {
		$total = null;

		// WooCommerce 3.0+.
		if ( is_callable( [ $order, 'get_shipping_total' ] ) ) {
			$total = $order->get_shipping_total();
		}

		// WooCommerce < 3.0.
		if ( null === $total ) {
			$total = $order->shipping_total;
		}

		return $total;
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
		$callable = [
			$order,
			sprintf( 'get_%s', $property ),
		];

		if ( is_callable( $callable ) ) {
			// WooCommerce 3.0+.
			return call_user_func( $callable );
		}

		if ( isset( $order->{$property} ) ) {
			return $order->{$property};
		}

		return null;
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
	 * Get subscription source ID.
	 *
	 * @param WC_Subscription $wcs_subscription Subscription.
	 *
	 * @return int|null
	 */
	public static function subscription_source_id( $wcs_subscription ) {
		if ( ! is_object( $wcs_subscription ) ) {
			return null;
		}

		if ( method_exists( $wcs_subscription, 'get_parent' ) ) {
			return self::get_order_id( $wcs_subscription->get_parent() );
		}

		return self::get_order_id( $wcs_subscription->order );
	}

	/**
	 * Get subscription order parent.
	 *
	 * @param WC_Subscription $wcs_subscription Subscription.
	 *
	 * @return WC_Order|null
	 */
	public static function get_subscription_parent_order( $wcs_subscription ) {
		if ( method_exists( $wcs_subscription, 'get_parent' ) ) {
			// WooCommerce 3.0+.
			return $wcs_subscription->get_parent();
		}

		return $wcs_subscription->order;
	}

	/**
	 * Get subscription payment method.
	 *
	 * @param WC_Subscription $wcs_subscription Subscription.
	 *
	 * @return string
	 */
	public static function get_subscription_payment_method( $wcs_subscription ) {
		if ( method_exists( $wcs_subscription, 'get_payment_method' ) ) {
			// WooCommerce 3.0+.
			return $wcs_subscription->get_payment_method();
		}

		return $wcs_subscription->payment_gateway;
	}

	/**
	 * Get subscription product price.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L384-L404
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return string|null
	 */
	public static function get_subscription_product_price( $product ) {
		$price = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_price' ) ) {
			$price = WC_Subscriptions_Product::get_price( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $price && isset( $product->subscription_price ) ) {
			$price = $product->subscription_price;
		}

		if ( ! empty( $price ) ) {
			return $price;
		}

		return null;
	}

	/**
	 * Get subscription product length.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L464-L473
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return int|null
	 */
	public static function get_subscription_product_length( $product ) {
		$length = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_length' ) ) {
			$length = WC_Subscriptions_Product::get_length( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $length && isset( $product->subscription_length ) ) {
			$length = $product->subscription_length;
		}

		if ( ! empty( $length ) ) {
			return $length;
		}

		return null;
	}

	/**
	 * Get subscription product trial length.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L475-L484
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return int|null
	 */
	public static function get_subscription_product_trial_length( $product ) {
		$length = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_trial_length' ) ) {
			$length = WC_Subscriptions_Product::get_trial_length( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $length && isset( $product->subscription_trial_length ) ) {
			$length = $product->subscription_trial_length;
		}

		if ( ! empty( $length ) ) {
			return $length;
		}

		return null;
	}

	/**
	 * Get subscription product trial period.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L486-L495
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return int|null
	 */
	public static function get_subscription_product_trial_period( $product ) {
		$length = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_trial_period' ) ) {
			$length = WC_Subscriptions_Product::get_trial_period( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $length && isset( $product->subscription_trial_period ) ) {
			$length = $product->subscription_trial_period;
		}

		if ( ! empty( $length ) ) {
			return $length;
		}

		return null;
	}

	/**
	 * Get subscription product interval.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L453-L462
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return int|null
	 */
	public static function get_subscription_product_interval( $product ) {
		$interval = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_interval' ) ) {
			$interval = WC_Subscriptions_Product::get_interval( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $interval && isset( $product->subscription_period_interval ) ) {
			$interval = $product->subscription_period_interval;
		}

		if ( ! empty( $interval ) ) {
			return $interval;
		}

		return null;
	}

	/**
	 * Get subscription product interval.
	 *
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L442-L451
	 *
	 * @param WC_Subscriptions_Product $product Product.
	 * @return string|null
	 */
	public static function get_subscription_product_period( $product ) {
		$period = null;

		// WooCommerce > 3.0.
		if ( method_exists( 'WC_Subscriptions_Product', 'get_period' ) ) {
			$period = WC_Subscriptions_Product::get_period( $product );
		}

		// WooCommerce < 3.0.
		if ( null === $period && isset( $product->subscription_period ) ) {
			$period = $product->subscription_period;
		}

		if ( ! empty( $period ) ) {
			return $period;
		}

		return null;
	}

	/**
	 * Get order item product.
	 *
	 * @param WC_Order_Item|WC_Order_Item_Product $item Order item.
	 *
	 * @return WC_Product|null
	 */
	public static function get_order_item_product( $item ) {
		$product = null;

		if ( is_callable( [ $item, 'get_product' ] ) ) {
			$product = $item->get_product();

			if ( false === $product ) {
				$product = null;
			}
		}

		return $product;
	}

	/**
	 * Get order item URL.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/3.5.1/includes/class-wc-order-item.php#L261
	 * @param WC_Order_Item|WC_Order_Item_Product $item Order item.
	 * @return string|null
	 */
	public static function get_order_item_url( $item ) {
		$product = self::get_order_item_product( $item );

		if ( empty( $product ) ) {
			return null;
		}

		$url = $product->get_permalink();

		if ( empty( $url ) ) {
			return null;
		}

		return $url;
	}

	/**
	 * Get order item image.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/3.5.1/includes/class-wc-order-item.php#L261
	 * @param WC_Order_Item $item Order item.
	 * @return string|null
	 */
	public static function get_order_item_image( $item ) {
		$product = self::get_order_item_product( $item );

		if ( empty( $product ) ) {
			return null;
		}

		$image_url = wp_get_attachment_url( (int) $product->get_image_id() );

		if ( empty( $image_url ) ) {
			return null;
		}

		return $image_url;
	}

	/**
	 * Get order item category.
	 *
	 * @param WC_Order_Item $item Order item.
	 * @return string|null
	 */
	public static function get_order_item_category( $item ) {
		$product = self::get_order_item_product( $item );

		if ( empty( $product ) ) {
			return null;
		}

		/*
		 * Yoast SEO primary term support.
		 * @link https://github.com/Yoast/wordpress-seo/blob/8.4/inc/wpseo-functions.php#L62-L81
		 */
		if ( function_exists( 'yoast_get_primary_term' ) ) {
			$name = yoast_get_primary_term( 'product_cat', $product->get_id() );

			return empty( $name ) ? null : $name;
		}

		/*
		 * WordPress core.
		 * @link https://developer.wordpress.org/reference/functions/wp_get_post_terms/
		 */
		if ( ! is_callable( [ $product, 'get_category_ids' ] ) ) {
			return null;
		}

		$category_ids = $product->get_category_ids();

		if ( ! is_array( $category_ids ) ) {
			return null;
		}

		$category_id = reset( $category_ids );

		$term = get_term( $category_id );

		if ( $term instanceof WP_Term ) {
			return $term->name;
		}

		return null;
	}

	/**
	 * Get order item SKU.
	 *
	 * @param WC_Order_Item $item Order item.
	 *
	 * @return string|null
	 */
	public static function get_order_item_sku( $item ) {
		$product = self::get_order_item_product( $item );

		if ( empty( $product ) ) {
			return null;
		}

		$sku = $product->get_sku();

		if ( empty( $sku ) ) {
			return null;
		}

		return $sku;
	}

	/**
	 * Get checkout fields.
	 *
	 * @return array<array<string, string|array<int|string, string>>>
	 */
	public static function get_checkout_fields() {
		$fields = [];

		// Make sure to have a valid WooCommerce session, customer and cart.
		if ( null === \WC()->session || null === \WC()->cart ) {
			if ( ! \function_exists( '\wc_load_cart' ) ) {
				/**
				 * WooCommerce versions < 3.6.4.
				 *
				 * @link https://github.com/woocommerce/woocommerce/blob/4.3.1/includes/wc-core-functions.php#L2408-L2423
				 */
				return $fields;
			}

			\wc_load_cart();
		}

		// Get checkout fields.
		foreach ( \WC()->checkout()->get_checkout_fields() as $fieldset_key => $fieldset ) {
			$optgroup = new Element(
				'optgroup',
				[
					'label' => \ucfirst( $fieldset_key ),
				]
			);

			foreach ( $fieldset as $field_key => $field ) {
				if ( empty( $field['label'] ) || strstr( $field_key, 'password' ) ) {
					continue;
				}

				$option = new Element(
					'option',
					[
						'value' => $field_key,
					]
				);

				$option->children[] = (string) $field['label'];

				$optgroup->children[] = $option;
			}

			$fields[] = $optgroup;
		}

		return $fields;
	}

	/**
	 * WooCommerce order item tax rate ID.
	 *
	 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/wiki/WooCommerce-order-item-tax-percent
	 * @param WC_Order_Item $order_item WooCommerce order item.
	 * @return int|null
	 */
	public static function get_order_item_tax_rate_id( WC_Order_Item $order_item ) {
		if ( ! \method_exists( $order_item, 'get_taxes' ) ) {
			return null;
		}

		$taxes = $order_item->get_taxes();

		$rates = \reset( $taxes );

		if ( false === $rates ) {
			return null;
		}

		if ( \count( $rates ) > 1 ) {
			return null;
		}

		return \array_key_first( $rates );
	}
}
