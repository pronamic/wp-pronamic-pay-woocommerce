<?php
/**
 * Gateway
 *
 * @author    Pronamic <info@pronamic.eu>
 * @copyright 2005-2023 Pronamic
 * @license   GPL-3.0-or-later
 * @package   Pronamic\WordPress\Pay\Extensions\WooCommerce
 */

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Money\TaxedMoney;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\Core\Field;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Payments\PaymentLineType;
use Pronamic\WordPress\Pay\Payments\PaymentStatus;
use Pronamic\WordPress\Pay\Plugin;
use Pronamic\WordPress\Pay\Region;
use Pronamic\WordPress\Pay\Subscriptions\Subscription;
use WC_Order;
use WC_Payment_Gateway;

/**
 * Title: WooCommerce iDEAL gateway
 * Description:
 * Copyright: 2005-2023 Pronamic
 * Company: Pronamic
 *
 * @link https://github.com/woocommerce/woocommerce/blob/3.5.3/includes/abstracts/abstract-wc-payment-gateway.php
 * @link https://github.com/woocommerce/woocommerce/blob/3.5.3/includes/abstracts/abstract-wc-settings-api.php
 *
 * @author  Remco Tolsma
 * @version 2.1.2
 * @since   1.0.0
 */
class Gateway extends WC_Payment_Gateway {
	/**
	 * The unique ID of this payment gateway
	 *
	 * @var string
	 */
	const ID = 'pronamic_pay_ideal';

	/**
	 * The payment method
	 *
	 * @var string|null
	 */
	protected $payment_method;

	/**
	 * The payment
	 *
	 * @var Payment|null
	 */
	protected $payment;

	/**
	 * Is recurring payment
	 *
	 * @var bool|null
	 */
	public $is_recurring;

	/**
	 * Input fields.
	 *
	 * @var array
	 */
	protected $input_fields;

	/**
	 * Config ID.
	 *
	 * @var string|null
	 */
	protected $config_id;

	/**
	 * Payment description.
	 *
	 * @var string|null
	 */
	protected $payment_description;

	/**
	 * Gateway arguments.
	 *
	 * @var array<string, null|string>
	 */
	protected $gateway_args;

	/**
	 * Constructs and initialize a gateway
	 *
	 * @param array<string, string> $args Arguments.
	 */
	public function __construct( $args = [] ) {
		$this->gateway_args = wp_parse_args(
			$args,
			[
				'id'                 => null,
				'method_title'       => null,
				'method_description' => null,
				// Custom.
				'payment_method'     => null,
				'icon'               => null,
			]
		);

		$this->id = isset( $this->gateway_args['id'] ) ? $this->gateway_args['id'] : static::ID;

		if ( isset( $this->gateway_args['payment_method'] ) ) {
			$this->payment_method = $this->gateway_args['payment_method'];
		}

		$this->method_title = $this->gateway_args['method_title'];

		if ( null === $this->method_title ) {
			$this->method_title = sprintf(
				/* translators: 1: Gateway admin label prefix, 2: Gateway admin label */
				__( '%1$s - %2$s', 'pronamic_ideal' ),
				__( 'Pronamic', 'pronamic_ideal' ),
				PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) )
			);
		}

		if ( isset( $this->gateway_args['method_description'] ) ) {
			$this->method_description = $this->gateway_args['method_description'];
		}

		/**
		 * Set order button text if payment method is known.
		 *
		 * @since 1.2.7
		 */
		if ( null !== $this->payment_method ) {
			$this->order_button_text = sprintf(
				/* translators: %s: payment method title */
				__( 'Proceed to %s', 'pronamic_ideal' ),
				PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) )
			);
		}

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables.
		$this->icon                = $this->get_pronamic_option( 'icon' );
		$this->title               = $this->get_pronamic_option( 'title' );
		$this->description         = $this->get_pronamic_option( 'description' );
		$this->enabled             = $this->get_pronamic_option( 'enabled' );
		$this->config_id           = $this->get_pronamic_option( 'config_id' );
		$this->payment_description = $this->get_pronamic_option( 'payment_description' );

		// Use default config ID if empty.
		if ( empty( $this->config_id ) ) {
			$this->config_id = \get_option( 'pronamic_pay_config_id' );
		}

		// Maybe support refunds (uses config ID setting).
		$this->maybe_add_refunds_support();

		// Actions.
		$update_action = 'woocommerce_update_options_payment_gateways_' . $this->id;

		if ( WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$update_action = 'woocommerce_update_options_payment_gateways';
		}

		add_action( $update_action, [ $this, 'process_admin_options' ] );

		add_action( 'woocommerce_after_checkout_validation', [ $this, 'after_checkout_validation' ], 10, 2 );

		// Has fields?
		if ( 'yes' === $this->enabled ) {
			$fields = $this->get_input_fields();

			$this->has_fields = ! empty( $fields );
		}

		/**
		 * WooCommerce Subscriptions.
		 *
		 * @link https://woocommerce.com/document/subscriptions/develop/action-reference/
		 */
		$this->maybe_add_subscriptions_support();

		if ( $this->supports( 'subscriptions' ) ) {
			\add_action( 'woocommerce_scheduled_subscription_payment_' . $this->id, [ $this, 'process_subscription_payment' ], 10, 2 );
		}
	}

	/**
	 * Get Pronamic option
	 *
	 * The WooCommerce settings API only have an 'get_option' function in
	 * WooCommerce version 2 or higher.
	 *
	 * @link https://github.com/woothemes/woocommerce/blob/v2.0.0/classes/abstracts/abstract-wc-settings-api.php#L130
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed
	 */
	public function get_pronamic_option( $key ) {
		$value = false;

		if ( method_exists( $this, 'get_option' ) ) {
			$value = parent::get_option( $key );
		} elseif ( isset( $this->settings[ $key ] ) ) {
			$value = $this->settings[ $key ];
		}

		return $value;
	}

	/**
	 * Get WordPress Pay payment method.
	 *
	 * @return string|null
	 */
	public function get_wp_payment_method() {
		return $this->payment_method;
	}

	/**
	 * Initialise form fields
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$description_prefix = '';

		if ( WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$description_prefix = '<br />';
		}

		$this->form_fields = [
			'enabled'             => [
				'title'   => __( 'Enable/Disable', 'pronamic_ideal' ),
				'type'    => 'checkbox',
				'label'   => sprintf(
					/* translators: %s: payment method title */
					__( 'Enable %s', 'pronamic_ideal' ),
					$this->method_title
				),
				'default' => 'no',
			],
			'title'               => [
				'title'       => __( 'Title', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => $description_prefix . __( 'This controls the title which the user sees during checkout.', 'pronamic_ideal' ),
				'default'     => PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) ),
			],
			'description'         => [
				'title'       => __( 'Description', 'pronamic_ideal' ),
				'type'        => 'textarea',
				'description' => $description_prefix . sprintf(
					/* translators: %s: payment method title */
					__( 'Give the customer instructions for paying via %s, and let them know that their order won\'t be shipping until the money is received.', 'pronamic_ideal' ),
					$this->method_title
				),
				'default'     => '',
			],
			'icon'                => [
				'title'       => __( 'Icon', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => sprintf(
					'%s%s',
					$description_prefix,
					__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' )
				),
				'default'     => '',
			],
			'config_id'           => [
				'title'   => __( 'Configuration', 'pronamic_ideal' ),
				'type'    => 'select',
				'default' => get_option( 'pronamic_pay_config_id' ),
				'options' => Plugin::get_config_select_options( $this->payment_method ),
			],
			'payment'             => [
				'title'       => __( 'Payment Options', 'pronamic_ideal' ),
				'type'        => 'title',
				'description' => '',
			],
			'payment_description' => [
				'title'       => __( 'Payment Description', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => sprintf(
					'%s%s<br />%s<br />%s',
					$description_prefix,
					__( 'This controls the payment description.', 'pronamic_ideal' ),
					/* translators: %s: default code */
					sprintf( __( 'Default: <code>%s</code>', 'pronamic_ideal' ), __( 'Order {order_number}', 'pronamic_ideal' ) ),
					/* translators: %s: tags */
					sprintf( __( 'Tags: %s', 'pronamic_ideal' ), sprintf( '<code>%s</code> <code>%s</code> <code>%s</code>', '{order_number}', '{order_date}', '{blogname}' ) )
				),
				'default'     => __( 'Order {order_number}', 'pronamic_ideal' ),
			],
		];

		if ( isset( $this->gateway_args['icon'] ) ) {
			$this->form_fields['icon']['default'] = $this->gateway_args['icon'];

			$this->form_fields['icon']['description'] = sprintf(
				'%s%s<br />%s',
				$description_prefix,
				__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' ),
				/* translators: %s: default code */
				sprintf( __( 'Default: <code>%s</code>', 'pronamic_ideal' ), $this->form_fields['icon']['default'] )
			);
		}

		if ( isset( $this->gateway_args['form_fields'] ) && is_array( $this->gateway_args['form_fields'] ) ) {
			foreach ( $this->gateway_args['form_fields'] as $name => $field ) {
				if ( ! isset( $this->form_fields[ $name ] ) ) {
					$this->form_fields[ $name ] = [];
				}

				foreach ( $field as $key => $value ) {
					$this->form_fields[ $name ][ $key ] = $value;
				}
			}
		}
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param int $order_id WooCommerce order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// Gateway.
		$gateway = Plugin::get_gateway( (int) $this->config_id );

		if ( null === $gateway ) {
			$notice = __( 'The payment gateway could not be found.', 'pronamic_ideal' );

			if ( current_user_can( 'manage_options' ) && empty( $this->config_id ) ) {
				// @link https://github.com/woothemes/woocommerce/blob/v2.1.5/includes/admin/settings/class-wc-settings-page.php#L66
				$notice = sprintf(
					/* translators: %s: WooCommerce checkout settings URL */
					__( 'You have to select an gateway configuration on the <a href="%s">WooCommerce checkout settings page</a>.', 'pronamic_ideal' ),
					add_query_arg(
						[
							'page'    => 'wc-settings',
							'tab'     => 'checkout',
							'section' => sanitize_title( __CLASS__ ),
						],
						admin_url( 'admin.php' )
					)
				);
			}

			WooCommerce::add_notice( $notice, 'error' );

			return [ 'result' => 'failure' ];
		}

		// Order.
		$order = wc_get_order( $order_id );

		// Make sure this is a valid order.
		if ( ! ( $order instanceof \WC_Order ) ) {
			return [ 'result' => 'failure' ];
		}

		$payment = $this->new_pronamic_payment_from_wc_order( $order );

		/**
		 * Subscriptions.
		 */
		$subscriptions = $this->get_pronamic_subscriptions( $order );

		foreach ( $subscriptions as $subscription ) {
			// Add subscription and period.
			$payment->add_subscription( $subscription );

			$period = $subscription->next_period();

			if ( null !== $period ) {
				$payment->add_period( $period );
			}

			$payment->set_meta( 'mollie_sequence_type', 'first' );

			$subscription->save();

			$woocommerce_subscription_id = $subscription->get_source_id();

			$woocommerce_subscription = \wcs_get_subscription( $woocommerce_subscription_id );

			if ( false !== $woocommerce_subscription ) {
				$woocommerce_subscription->add_meta_data( 'pronamic_subscription_id', $subscription->get_id(), true );

				$woocommerce_subscription->save();
			}
		}

		$this->connect_subscription_payment_renewal( $payment, $order );

		// Set Mollie sequence type on payment method change.
		if ( \did_action( 'woocommerce_subscription_change_payment_method_via_pay_shortcode' ) ) {
			$payment->set_meta( 'mollie_sequence_type', 'first' );
		}

		// Start payment.
		try {
			$this->payment = Plugin::start_payment( $payment );
		} catch ( \Exception $exception ) {
			WooCommerce::add_notice( Plugin::get_default_error_message(), 'error' );

			/**
			 * We will rethrow the exception so WooCommerce can also handle the exception.
			 *
			 * @link https://github.com/woocommerce/woocommerce/blob/3.7.1/includes/class-wc-checkout.php#L1129-L1131
			 */
			throw $exception;
		}

		// Store WooCommerce gateway in payment meta.
		$this->payment->set_meta( 'woocommerce_payment_method', $order->get_payment_method() );
		$this->payment->set_meta( 'woocommerce_payment_method_title', $order->get_payment_method_title() );

		// Store payment ID in WooCommerce order meta.
		$order->update_meta_data( '_pronamic_payment_id', (string) $payment->get_id() );

		$order->save();

		// Reload order for actual status (could be paid already; i.e. through recurring credit card payment).
		$order = \wc_get_order( $order );

		// Order note and status.
		$new_status_slug = WooCommerce::ORDER_STATUS_PENDING;

		$note = __( 'Awaiting payment.', 'pronamic_ideal' );

		$order_status = WooCommerce::order_get_status( $order );

		// Only add order note if status is already pending or if WooCommerce Deposits is activated.
		if ( $new_status_slug === $order_status || isset( $order->wc_deposits_remaining ) ) {
			$order->add_order_note( $note );
		} elseif ( PaymentStatus::SUCCESS !== $payment->get_status() ) {
			// Mark as pending (we're awaiting the payment).
			try {
				$order->update_status( $new_status_slug, $note );
			} catch ( \Exception $exception ) {
				// Nothing to do.
			}
		}

		// Return results array.
		return [
			'result'   => 'success',
			'redirect' => $this->payment->get_pay_redirect_url(),
		];
	}

	/**
	 * New Pronamic payment from WooCommerce order.
	 *
	 * @param WC_Order $order
	 * @return Payment
	 */
	private function new_pronamic_payment_from_wc_order( WC_Order $order ) {
		// Blog name.
		$blogname = get_option( 'blogname' );

		if ( empty( $blogname ) ) {
			$blogname = '';
		}

		// @link https://github.com/WordPress/WordPress/blob/3.8.1/wp-includes/pluggable.php#L1085.
		// The blogname option is escaped with `esc_html` on the way into the database in sanitize_option
		// we want to reverse this for the gateways.
		$blogname = wp_specialchars_decode( $blogname, ENT_QUOTES );

		// Title.
		$title = sprintf(
			/* translators: %s: payment data title */
			__( 'Payment for %s', 'pronamic_ideal' ),
			sprintf(
				/* translators: %s: order id */
				__( 'WooCommerce order %s', 'pronamic_ideal' ),
				$order->get_order_number()
			)
		);

		// Description.
		// @link https://github.com/woothemes/woocommerce/blob/v2.0.19/classes/emails/class-wc-email-new-order.php.
		$replacements = [
			'{blogname}'     => $blogname,
			'{site_title}'   => $blogname,
			'{order_date}'   => date_i18n( WooCommerce::get_date_format(), (int) WooCommerce::get_order_date( $order ) ),
			'{order_number}' => $order->get_order_number(),
		];

		if ( empty( $this->payment_description ) ) {
			$this->payment_description = $this->form_fields['payment_description']['default'];
		}

		$description = strtr( $this->payment_description, $replacements );

		// Contact.
		$contact_name = new ContactName();
		$contact_name->set_first_name( WooCommerce::get_billing_first_name( $order ) );
		$contact_name->set_last_name( WooCommerce::get_billing_last_name( $order ) );

		$customer = new Customer();
		$customer->set_name( $contact_name );
		$customer->set_email( WooCommerce::get_billing_email( $order ) );
		$customer->set_phone( WooCommerce::get_billing_phone( $order ) );
		$customer->set_user_id( $order->get_user_id() );

		// Company name.
		$company_name = WooCommerce::get_billing_company( $order );

		if ( ! empty( $company_name ) ) {
			$customer->set_company_name( $company_name );
		}

		// Customer gender.
		$gender = null;

		$key = $this->id . '_gender';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( \array_key_exists( $key, $_POST ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$gender = \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) );
		}

		$gender_field = \get_option( 'pronamic_pay_woocommerce_gender_field' );

		if ( ! empty( $gender_field ) ) {
			$gender = $order->get_meta( '_' . $gender_field, true );
		}

		if ( ! empty( $gender ) ) {
			$customer->set_gender( $gender );
		}

		// Customer birth date.
		$birth_date = null;

		$key = $this->id . '_birth_date';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( \array_key_exists( $key, $_POST ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$birth_date = \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) );
		}

		$birth_date_field = \get_option( 'pronamic_pay_woocommerce_birth_date_field' );

		if ( ! empty( $birth_date_field ) ) {
			$birth_date = $order->get_meta( '_' . $birth_date_field, true );
		}

		if ( ! empty( $birth_date ) ) {
			$customer->set_birth_date( new DateTime( $birth_date ) );
		}

		// Billing address.
		$billing_address = new Address();
		$billing_address->set_name( $contact_name );
		$billing_address->set_company_name( WooCommerce::get_billing_company( $order ) );
		$billing_address->set_line_1( WooCommerce::get_billing_address_1( $order ) );
		$billing_address->set_line_2( WooCommerce::get_billing_address_2( $order ) );
		$billing_address->set_postal_code( WooCommerce::get_billing_postcode( $order ) );
		$billing_address->set_city( WooCommerce::get_billing_city( $order ) );
		$billing_address->set_email( WooCommerce::get_billing_email( $order ) );
		$billing_address->set_phone( WooCommerce::get_billing_phone( $order ) );

		$region = new Region();

		$region->set_code( WooCommerce::get_billing_state( $order ) );

		$billing_address->set_region( $region );

		$billing_country = WooCommerce::get_billing_country( $order );

		if ( ! empty( $billing_country ) ) {
			$billing_address->set_country_code( $billing_country );
			$billing_address->set_country_name( WC()->countries->countries[ $billing_country ] );
		}

		// Shipping address.
		$shipping_name = new ContactName();
		$shipping_name->set_first_name( WooCommerce::get_shipping_first_name( $order ) );
		$shipping_name->set_last_name( WooCommerce::get_shipping_last_name( $order ) );

		$shipping_address = new Address();
		$shipping_address->set_name( $shipping_name );
		$shipping_address->set_company_name( WooCommerce::get_shipping_company( $order ) );
		$shipping_address->set_line_1( WooCommerce::get_shipping_address_1( $order ) );
		$shipping_address->set_line_2( WooCommerce::get_shipping_address_2( $order ) );
		$shipping_address->set_postal_code( WooCommerce::get_shipping_postcode( $order ) );
		$shipping_address->set_city( WooCommerce::get_shipping_city( $order ) );
		$shipping_address->set_region( WooCommerce::get_shipping_state( $order ) );
		$shipping_address->set_email( WooCommerce::get_shipping_email( $order ) );

		$shipping_country = WooCommerce::get_shipping_country( $order );

		if ( ! empty( $shipping_country ) ) {
			$shipping_address->set_country_code( $shipping_country );
			$shipping_address->set_country_name( WC()->countries->countries[ $shipping_country ] );
		}

		$payment = new Payment();

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
		 * @link http://wcdocs.woothemes.com/user-guide/extensions/functionality/sequential-order-numbers/#add-compatibility
		 *
		 * @see page 30 http://pronamic.nl/wp-content/uploads/2012/09/iDEAL-Merchant-Integratie-Gids-NL.pdf
		 *
		 * The use of characters that are not listed above will not lead to a refusal of a batch or post, but the
		 * character will be changed by Equens (formerly Interpay) to a space, question mark or asterisk. The
		 * same goes for diacritical characters (à, ç, ô, ü, ý etcetera).
		 */
		$payment->order_id = str_replace( '#', '', $order->get_order_number() );

		$payment->title = $title;

		$payment->set_config_id( (int) $this->config_id );
		$payment->set_description( $description );

		$payment->set_payment_method( $this->payment_method );

		// Issuer.
		$key = $this->id . '_issuer_id';

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( \array_key_exists( $key, $_POST ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			$issuer = \sanitize_text_field( \wp_unslash( $_POST[ $key ] ) );

			$payment->set_meta( 'issuer', $issuer );
		}

		$payment->set_source( Extension::SLUG );
		$payment->set_source_id( WooCommerce::get_order_id( $order ) );

		$payment->set_customer( $customer );
		$payment->set_billing_address( $billing_address );
		$payment->set_shipping_address( $shipping_address );

		$amount          = WooCommerce::get_order_total( $order );
		$tax_amount      = WooCommerce::get_order_total_tax( $order );
		$shipping_amount = WooCommerce::get_order_shipping_total( $order );

		/*
		 * WooCommerce Deposits remaining amount.
		 * @since 1.1.6
		 */
		if ( WooCommerce::order_has_status( $order, 'partially-paid' ) && isset( $order->wc_deposits_remaining ) ) {
			$amount          = $order->wc_deposits_remaining;
			$tax_amount      = null;
			$shipping_amount = null;
		}

		// Set shipping amount.
		$payment->set_shipping_amount(
			new Money(
				$shipping_amount,
				WooCommerce::get_currency()
			)
		);

		// Set total amount.
		$payment->set_total_amount(
			new TaxedMoney(
				$amount,
				WooCommerce::get_currency(),
				$tax_amount
			)
		);

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
		$items = $order->get_items( [ 'line_item', 'fee', 'shipping' ] );

		$payment->lines = new PaymentLines();

		foreach ( $items as $item_id => $item ) {
			$line = $payment->lines->new_line();

			$type = OrderItemType::transform( $item );

			// Quantity.
			$quantity = wc_stock_amount( $item['qty'] );

			if ( PaymentLineType::SHIPPING === $type ) {
				$quantity = 1;
			}

			$taxes = $item->get_taxes();

			/**
			 * WooCommerce order item tax percent.
			 * 
			 * @link https://github.com/pronamic/wp-pronamic-pay-woocommerce/wiki/WooCommerce-order-item-tax-percent
			 */
			$percent = null;

			foreach ( $taxes as $type => $rates ) {
				if ( count( $rates ) > 1 ) {
					continue;
				}

				foreach ( $rates as $key => $value ) {
					$percent = \WC_Tax::get_rate_percent_value( $key );
				}
			}

			// Set line properties.
			$line->set_id( $item_id );
			$line->set_sku( WooCommerce::get_order_item_sku( $item ) );
			$line->set_type( (string) $type );
			$line->set_name( $item['name'] );
			$line->set_quantity( $quantity );
			$line->set_unit_price( new TaxedMoney( $order->get_item_total( $item, true ), WooCommerce::get_currency(), $order->get_item_tax( $item ), $percent ) );
			$line->set_total_amount( new TaxedMoney( $order->get_line_total( $item, true ), WooCommerce::get_currency(), $order->get_line_tax( $item ), $percent ) );
			$line->set_product_url( WooCommerce::get_order_item_url( $item ) );
			$line->set_image_url( WooCommerce::get_order_item_image( $item ) );
			$line->set_product_category( WooCommerce::get_order_item_category( $item ) );
		}

		return $payment;
	}

	/**
	 * Get Pronamic subscriptions.
	 *
	 * @param WC_Order $order WooCommerce order.
	 * @return Subscription[]
	 */
	private function get_pronamic_subscriptions( WC_Order $order ) {
		$pronamic_subscriptions = [];

		if ( ! \function_exists( '\wcs_get_subscriptions_for_order' ) ) {
			return $pronamic_subscriptions;
		}

		$woocommerce_subscriptions = \wcs_get_subscriptions_for_order( $order );

		foreach ( $woocommerce_subscriptions as $woocommerce_subscription ) {
			$pronamic_subscription = new Subscription();

			$subscription_updater = new SubscriptionUpdater( $woocommerce_subscription, $pronamic_subscription );

			$subscription_updater->update_pronamic_subscription();

			$pronamic_subscriptions[] = $pronamic_subscription;
		}

		return $pronamic_subscriptions;
	}

	/**
	 * Connection subscription payment renewal.
	 *
	 * @param Payment  $payment Payment.
	 * @param WC_Order $order   WooCommerce order.
	 * @return void
	 */
	private function connect_subscription_payment_renewal( $payment, $order ) {
		if ( ! \function_exists( '\wcs_get_subscriptions_for_order' ) ) {
			return;
		}

		$woocommerce_subscriptions = \wcs_get_subscriptions_for_order( $order, [ 'order_type' => 'renewal' ] );

		foreach ( $woocommerce_subscriptions as $woocommerce_subscription ) {
			$subscription_helper = new SubscriptionHelper( $woocommerce_subscription );

			$pronamic_subscription = $subscription_helper->get_pronamic_subscription();

			if ( null !== $pronamic_subscription ) {
				// Add subscription and period.
				$payment->add_subscription( $pronamic_subscription );

				$period = $pronamic_subscription->next_period();

				if ( null !== $period ) {
					$payment->add_period( $period );
				}
			}
		}
	}

	/**
	 * Process WooCommerce Subscriptions payment.
	 *
	 * This method is hooked in to the 'woocommerce_scheduled_subscription_payment_{$payment_method}' action.
	 *
	 * @param float    $amount Subscription payment amount.
	 * @param WC_Order $order  WooCommerce order.
	 * @return void
	 * @throws \WC_Data_Exception Throws exception when invalid order data is found.
	 */
	public function process_subscription_payment( $amount, $order ) {
		$payment = $this->new_pronamic_payment_from_wc_order( $order );

		$this->connect_subscription_payment_renewal( $payment, $order );

		$payment->set_meta( 'mollie_sequence_type', 'recurring' );

		Plugin::start_payment( $payment );
	}

	/**
	 * Maybe add refunds support.
	 */
	public function maybe_add_refunds_support() {
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( null !== $gateway && $gateway->supports( 'refunds' ) ) {
			$this->supports[] = 'refunds';
		}
	}

	/**
	 * Has Pronamic subscriptions support.
	 *
	 * @return bool
	 */
	private function has_pronamic_subscriptions_support() {
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( null === $gateway ) {
			return false;
		}

		if ( null === $this->payment_method ) {
			return false;
		}

		$payment_method_object = $gateway->get_payment_method( $this->payment_method );

		if ( null === $payment_method_object ) {
			return false;
		}

		return $payment_method_object->supports( 'recurring' );
	}

	/**
	 * Maybe add subscriptions support.
	 *
	 * @return void
	 */
	public function maybe_add_subscriptions_support() {
		if ( ! $this->has_pronamic_subscriptions_support() ) {
			return;
		}

		$this->supports[] = 'subscriptions';
		$this->supports[] = 'subscription_amount_changes';
		$this->supports[] = 'subscription_cancellation';
		$this->supports[] = 'subscription_date_changes';
		$this->supports[] = 'subscription_payment_method_change_customer';
		$this->supports[] = 'subscription_reactivation';
		$this->supports[] = 'subscription_suspension';
		$this->supports[] = 'multiple_subscriptions';
	}

	/**
	 * Process refund.
	 *
	 * @param int        $order_id
	 * @param float|null $amount
	 * @param string     $reason
	 * @return bool|\WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		// Check gateway.
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( null === $gateway ) {
			return new \WP_Error(
				'pronamic-pay-woocommerce-refund-gateway',
				__( 'Unable to process refund as gateway configuration does not exist.', 'pronamic_ideal' )
			);
		}

		// Create refund.
		$order = \wc_get_order( $order_id );

		$amount = new Money( $amount, $order->get_currency( 'raw' ) );

		try {
			$refund_reference = Plugin::create_refund( $order->get_transaction_id(), $gateway, $amount, $reason );

			if ( null !== $refund_reference ) {
				$note = \sprintf(
					/* translators: 1: formatted refund amount, 2: refund gateway reference */
					\__( 'Created refund of %1$s with gateway reference `%2$s`.', 'pronamic_ideal' ),
					\esc_html( $amount->format_i18n() ),
					\esc_html( $refund_reference )
				);

				$order->add_order_note( $note );
			}

			$order->update_meta_data( '_pronamic_amount_refunded', (string) $amount->get_value() );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'pronamic-pay-woocommerce-refund',
				$e->getMessage()
			);
		}

		return true;
	}

	/**
	 * Payment fields
	 *
	 * @link https://github.com/woothemes/woocommerce/blob/v1.6.6/templates/checkout/form-pay.php#L66
	 * @api https://woocommerce.com/document/payment-gateway-api/
	 * @return void
	 */
	public function payment_fields() {
		// @link https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L181
		parent::payment_fields();

		$fields = $this->get_input_fields();

		$this->print_fields( $fields );
	}


	/**
	 * Filtered payment fields.
	 *
	 * @internal Pronamic internal helper function to get input fields, also used for
	 *           the WooCommerce checkout block.
	 * @return Field[]
	 */
	public function get_input_fields() {
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( null === $gateway ) {
			return [];
		}

		$payment_method_object = $gateway->get_payment_method( $this->payment_method );

		if ( null === $payment_method_object ) {
			return [];
		}

		$fields = \array_filter(
			$payment_method_object->get_fields(),
			function ( $field ) {
				switch ( $field->get_id() ) {
					case 'pronamic_pay_birth_date':
						return '1' !== get_option( 'pronamic_pay_woocommerce_birth_date_field_enable' );

					case 'pronamic_pay_gender':
						return '1' !== get_option( 'pronamic_pay_woocommerce_gender_field_enable' );
				}

				return true;
			}
		);

		return $fields;
	}

	/**
	 * Print the specified fields.
	 *
	 * @internal Pronamic internal helper function to print fields.
	 * @param array $fields Fields to print.
	 * @return void
	 */
	private function print_fields( $fields ) {
		if ( empty( $fields ) ) {
			return;
		}

		?>

		<fieldset id="<?php echo esc_attr( $this->id ); ?>-form" class="wc-payment-form">
			<?php

			foreach ( $fields as $field ) {
				echo '<p class="form-row form-row-wide">';

				$label = $field->get_label();

				if ( ! empty( $label ) ) {
					\printf(
						'<label for="%s">%s</label> ',
						\esc_attr( $field->get_id() ),
						\esc_html( $label )
					);
				}

				try {
					$field->output();
				} catch ( \Exception $e ) {
					echo \esc_html( $e->getMessage() );
				}

				echo '</p>';
			}

			?>

			<div class="clear"></div>
		</fieldset>

		<?php
	}

	/**
	 * Validate required payment method input fields after checkout.
	 *
	 * @param array     $data   Posted data.
	 * @param \WP_Error $errors Checkout validation errors.
	 * @return void
	 */
	public function after_checkout_validation( $data, $errors ) {
		if ( ! isset( $data['payment_method'] ) || $this->id !== $data['payment_method'] ) {
			return;
		}

		$input_ids = [
			'gender',
			'birth_date',
		];

		foreach ( $input_ids as $input_id ) {
			$input_name = sprintf( '%s_%s', $this->id, $input_id );

			if ( ! array_key_exists( $input_name, $data ) ) {
				continue;
			}

			$input_value = $data[ $input_name ];

			// Add error for empty input value.
			if ( empty( $input_value ) ) {
				$error = sprintf(
					/* translators: %s: payment method title */
					__( 'A required field for the %s payment method is empty.', 'pronamic_ideal' ),
					$this->method_title
				);

				$errors->add( $this->id, $error );
			}
		}
	}
}
