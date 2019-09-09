<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use WC_Order;
use WC_Order_Item;
use WC_Order_Item_Product;
use WC_Product;
use WC_Subscriptions_Product;
use WP_Error;

/**
 * Title: WooCommerce
 * Description:
 * Copyright: 2005-2019 Pronamic
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.5
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
	 * Check if WooCommerce is active (Automattic/developer style)
	 *
	 * @link https://github.com/jigoshop/jigoshop/blob/1.8/jigoshop.php#L45
	 * @link https://github.com/Automattic/developer/blob/1.1.2/developer.php#L73
	 *
	 * @return boolean
	 */
	public static function is_active() {
		return defined( 'WOOCOMMERCE_VERSION' );
	}

	/**
	 * Check if WooCommerce Subscriptions 2.0+ is active.
	 *
	 * @return boolean
	 */
	public static function is_subscriptions_active() {
		return (
			class_exists( 'WC_Subscriptions' )
				&&
			version_compare( \WC_Subscriptions::$version, '2.0', '>=' )
				&&
			post_type_exists( 'shop_subscription' )
		);
	}

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
		$url = null;

		if ( method_exists( $order, 'get_checkout_payment_url' ) ) {
			// WooCommerce >= 2.1.
			// @link http://docs.woothemes.com/document/woocommerce-endpoints-2-1/.
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/class-wc-order.php#L1057-L1079.
			return $order->get_checkout_payment_url( false );
		}

		// WooCommerce < 2.1.
		return add_query_arg(
			array(
				'order' => $order->id,
				'key'   => $order->order_key,
			),
			get_permalink( woocommerce_get_page_id( 'pay' ) )
		);
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
			// @link https://github.com/woothemes/woocommerce/blob/v2.1.0/includes/wc-notice-functions.php#L54-L71
			wc_add_notice( $message, $type );
		} elseif ( 'error' === $type && method_exists( $woocommerce, 'add_error' ) ) {
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
		if ( is_callable( array( $order, 'get_id' ) ) ) {
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
		if ( is_callable( array( $order, 'get_date_created' ) ) ) {
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
		if ( is_callable( array( $order, 'get_total' ) ) ) {
			// WooCommerce 3.0+.
			return $order->get_total();
		}

		return $order->order_total;
	}

	/**
	 * Get order total tax.
	 *
	 * @since 2.0.2
	 *
	 * @param WC_Order $order Order.
	 *
	 * @return string
	 */
	public static function get_order_total_tax( $order ) {
		if ( is_callable( array( $order, 'get_total_tax' ) ) ) {
			// WooCommerce 3.0+.
			return $order->get_total_tax();
		}

		return $order->total_tax;
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
		if ( is_callable( array( $order, 'get_shipping_total' ) ) ) {
			// WooCommerce 3.0+.
			return $order->get_shipping_total();
		}

		return $order->shipping_total;
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
	 * Get subscription order parent.
	 *
	 * @param \WC_Subscription $wcs_subscription
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
	 * @param \WC_Subscription $wcs_subscription
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
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L464-L473
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
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L453-L462
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
	 * @link https://github.com/wp-premium/woocommerce-subscriptions/blob/2.2.18/includes/class-wc-subscriptions-product.php#L442-L451
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

	/**
	 * Get order item product.
	 *
	 * @param WC_Order_Item|WC_Order_Item_Product $item Order item.
	 *
	 * @return null|WC_Product
	 */
	public static function get_order_item_product( $item ) {
		if ( ! is_callable( array( $item, 'get_product' ) ) ) {
			return null;
		}

		$product = $item->get_product();

		if ( false === $product ) {
			return null;
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
		if ( ! is_callable( array( $product, 'get_category_ids' ) ) ) {
			return null;
		}

		$category_ids = $product->get_category_ids();

		if ( ! is_array( $category_ids ) ) {
			return null;
		}

		$category_id = reset( $category_ids );

		$term = get_term( $category_id );

		if ( empty( $term ) || $term instanceof WP_Error ) {
			return null;
		}

		return $term->name;
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
	 * @return array
	 *
	 * @throws Exception
	 */
	public static function get_checkout_fields() {
		$fields = array();

		if ( ! class_exists( '\WC_Session_Handler' ) ) {
			return $fields;
		}

		if ( ! class_exists( '\WC_Customer' ) ) {
			return $fields;
		}

		$wc_session  = WC()->session;
		$wc_customer = WC()->customer;

		if ( null === $wc_session ) {
			WC()->session = new \WC_Session_Handler();
		}

		if ( null === $wc_customer ) {
			WC()->customer = new \WC_Customer();
		}

		foreach ( WC()->checkout()->get_checkout_fields() as $fieldset_key => $fieldset ) {
			$fields[ $fieldset_key ] = array(
				'name'    => ucfirst( $fieldset_key ),
				'options' => array(),
			);

			foreach ( $fieldset as $field_key => $field ) {
				if ( empty( $field['label'] ) || strstr( $field_key, 'password' ) ) {
					continue;
				}

				$fields[ $fieldset_key ]['options'][ $field_key ] = $field['label'];
			}
		}

		WC()->customer = $wc_customer;
		WC()->session  = $wc_session;

		return $fields;
	}
}
