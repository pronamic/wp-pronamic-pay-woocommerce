<?php
/**
 * Order item type.
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2025 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use WC_Order_Item;

/**
 * Title: WooCommerce order item type
 * Description:
 * Copyright: 2005-2025 Pronamic
 * Company: Pronamic
 *
 * @author  Reüel van der Steege
 * @version 2.0.5
 * @since   2.0.2
 */
class OrderItemType {
	/**
	 * Constant for 'coupon' type.
	 *
	 * @var string
	 */
	const COUPON = 'coupon';

	/**
	 * Constant for 'fee' type.
	 *
	 * @var string
	 */
	const FEE = 'fee';

	/**
	 * Constant for 'line_item' type (products).
	 *
	 * @var string
	 */
	const LINE_ITEM = 'line_item';

	/**
	 * Constant for 'shipping' type.
	 *
	 * @var string
	 */
	const SHIPPING = 'shipping';

	/**
	 * Constant for 'tax' type.
	 *
	 * @var string
	 */
	const TAX = 'tax';

	/**
	 * Transform WooCommerce order item type to general payment line type.
	 *
	 * @param WC_Order_Item $item WooCommerce order item type.
	 *
	 * @return null|string
	 */
	public static function transform( $item ) {
		switch ( $item->get_type() ) {
			case self::COUPON:
				return PaymentLineType::DISCOUNT;

			case self::FEE:
				return PaymentLineType::FEE;

			case self::LINE_ITEM:
				if ( is_callable( [ $item, 'get_product' ] ) ) {
					$product = $item->get_product();

					if ( $product->is_virtual() ) {
						return PaymentLineType::DIGITAL;
					}
				}

				return PaymentLineType::PHYSICAL;

			case self::SHIPPING:
				return PaymentLineType::SHIPPING;

			case self::TAX:
				return PaymentLineType::TAX;

			default:
				return null;
		}
	}
}
