<?php
/**
 * Order helper
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use WC_Order;
use WC_Tax;

/**
 * Order helper class
 */
class OrderHelper {
	/**
	 * WooCommerce order.
	 *
	 * @var WC_Order
	 */
	private $woocommerce_order;

	/**
	 * Construct order helper.
	 *
	 * @param WC_Order $woocommerce_order WooCommerce order.
	 */
	public function __construct( WC_Order $woocommerce_order ) {
		$this->woocommerce_order = $woocommerce_order;
	}

	/**
	 * Get contact name.
	 *
	 * @return ContactName
	 */
	public function get_contact_name() {
		$order = $this->woocommerce_order;

		$contact_name = new ContactName();
		$contact_name->set_first_name( WooCommerce::get_billing_first_name( $order ) );
		$contact_name->set_last_name( WooCommerce::get_billing_last_name( $order ) );

		return $contact_name;
	}

	/**
	 * Get customer.
	 *
	 * @return Customer
	 */
	public function get_customer() {
		$order = $this->woocommerce_order;

		$customer = new Customer();
		$customer->set_name( $this->get_contact_name() );
		$customer->set_email( WooCommerce::get_billing_email( $order ) );
		$customer->set_phone( WooCommerce::get_billing_phone( $order ) );
		$customer->set_user_id( $order->get_user_id() );

		// Company name.
		$company_name = WooCommerce::get_billing_company( $order );

		if ( ! empty( $company_name ) ) {
			$customer->set_company_name( $company_name );
		}

		/**
		 * VAT Number.
		 *
		 * @link https://woo.com/products/eu-vat-number/
		 * @link https://github.com/pronamic/woocommerce-eu-vat-number/blob/v2.8.3/includes/class-wc-eu-vat-number.php#L648
		 * @link https://github.com/pronamic/wp-pronamic-pay-easy-digital-downloads/blob/v4.3.4/src/Gateway.php#L398-L407
		 */
		$vat_number = (string) $order->get_meta( '_billing_vat_number' );

		if ( '' !== $vat_number ) {
			$customer->set_vat_number( $vat_number );
		}

		return $customer;
	}

	/**
	 * Get lines.
	 *
	 * @return PaymentLines|null
	 */
	public function get_lines() {
		/*
		 * Payment lines and order items.
		 *
		 * WooCommerce has multiple order item types:
		 * `line_item`, `fee`, `shipping`, `tax`, `coupon`
		 * @link https://github.com/woocommerce/woocommerce/search?q=%22extends+WC_Order_Item%22
		 *
		 * For now we handle only the `line_item`, `fee` and `shipping` items,
		 * we consciously don't handle the `tax` and `coupon` items.
		 *
		 * **Order item `coupon`**
		 * Coupon items are also applied to the `line_item` item and line total.
		 * @link https://basecamp.com/1810084/projects/10966871/todos/372490988
		 *
		 * **Order item `tax`**
		 * Tax items are also  applied to the `line_item` item and line total.
		 */
		$order = $this->woocommerce_order;

		$items = $order->get_items( [ 'line_item', 'fee', 'shipping' ] );

		$tax_percentages = [ 0 ];

		$lines = new PaymentLines();

		foreach ( $items as $item_id => $item ) {
			$line = $lines->new_line();

			$type = OrderItemType::transform( $item );

			// Quantity.
			$quantity = \wc_stock_amount( $item['qty'] );

			if ( PaymentLineType::SHIPPING === $type ) {
				$quantity = 1;
			}

			// Tax.
			$tax_rate_id = WooCommerce::get_order_item_tax_rate_id( $item );

			$percent = is_null( $tax_rate_id ) ? null : WC_Tax::get_rate_percent_value( $tax_rate_id );

			// Set line properties.
			$line->set_id( (string) $item_id );
			$line->set_sku( WooCommerce::get_order_item_sku( $item ) );
			$line->set_type( (string) $type );
			$line->set_name( $item['name'] );
			$line->set_quantity( $quantity );
			$line->set_unit_price( new TaxedMoney( $order->get_item_total( $item, true ), WooCommerce::get_currency(), $order->get_item_tax( $item ), $percent ) );
			$line->set_total_amount( new TaxedMoney( $order->get_line_total( $item, true ), WooCommerce::get_currency(), $order->get_line_tax( $item ), $percent ) );
			$line->set_product_url( WooCommerce::get_order_item_url( $item ) );
			$line->set_image_url( WooCommerce::get_order_item_image( $item ) );
			$line->set_product_category( WooCommerce::get_order_item_category( $item ) );
			$line->set_meta( 'woocommerce_order_item_id', $item_id );
		}

		return $lines;
	}
}
