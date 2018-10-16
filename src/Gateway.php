<?php

namespace Pronamic\WordPress\Pay\Extensions\WooCommerce;

use Pronamic\WordPress\DateTime\DateTime;
use Pronamic\WordPress\Money\Money;
use Pronamic\WordPress\Pay\Address;
use Pronamic\WordPress\Pay\Customer;
use Pronamic\WordPress\Pay\ContactName;
use Pronamic\WordPress\Pay\Core\PaymentMethods;
use Pronamic\WordPress\Pay\Core\Util;
use Pronamic\WordPress\Pay\Payments\Payment;
use Pronamic\WordPress\Pay\Payments\PaymentLines;
use Pronamic\WordPress\Pay\Plugin;
use WC_Order;
use WC_Payment_Gateway;
use WC_Product_Subscription;

/**
 * Title: WooCommerce iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author  Remco Tolsma
 * @version 2.0.1
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
	 * @var string
	 */
	protected $payment_method;

	/**
	 * The payment
	 *
	 * @var Payment
	 */
	protected $payment;

	/**
	 * Is recurring payment
	 *
	 * @var bool
	 */
	public $is_recurring;

	/**
	 * Input fields.
	 *
	 * @var array
	 */
	protected $input_fields;

	/**
	 * Constructs and initialize a gateway
	 */
	public function __construct() {
		$this->id           = static::ID;
		$this->method_title = PaymentMethods::get_name( $this->payment_method, __( 'Pronamic', 'pronamic_ideal' ) );

		// @since 1.2.7.
		if ( null !== $this->payment_method ) {
			$this->order_button_text = sprintf(
				/* translators: %s: payment method title */
				__( 'Proceed to %s', 'pronamic_ideal' ),
				$this->method_title
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

		// Actions.
		$update_action = 'woocommerce_update_options_payment_gateways_' . $this->id;

		if ( WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$update_action = 'woocommerce_update_options_payment_gateways';
		}

		add_action( $update_action, array( $this, 'process_admin_options' ) );

		// Has fields?
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( $gateway ) {
			$first_payment_method = PaymentMethods::get_first_payment_method( $this->payment_method );

			$gateway->set_payment_method( $first_payment_method );

			$this->input_fields = $gateway->get_input_fields();

			if ( ! empty( $this->input_fields ) ) {
				$this->has_fields = true;
			}
		}
	}

	/**
	 * Get Pronamic option
	 *
	 * The WooCommerce settings API only have an 'get_option' function in
	 * WooCommerce version 2 or higher.
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v2.0.0/classes/abstracts/abstract-wc-settings-api.php#L130
	 *
	 * @param string $key Option key.
	 *
	 * @return bool
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
	 * Initialise form fields
	 */
	public function init_form_fields() {
		$description_prefix = '';

		if ( WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$description_prefix = '<br />';
		}

		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable/Disable', 'pronamic_ideal' ),
				'type'    => 'checkbox',
				'label'   => sprintf(
					/* translators: %s: payment method title */
					__( 'Enable %s', 'pronamic_ideal' ),
					$this->method_title
				),
				'default' => 'no',
			),
			'title'               => array(
				'title'       => __( 'Title', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => $description_prefix . __( 'This controls the title which the user sees during checkout.', 'pronamic_ideal' ),
				'default'     => $this->method_title,
			),
			'description'         => array(
				'title'       => __( 'Description', 'pronamic_ideal' ),
				'type'        => 'textarea',
				'description' => $description_prefix . sprintf(
					/* translators: %s: payment method title */
					__( 'Give the customer instructions for paying via %s, and let them know that their order won\'t be shipping until the money is received.', 'pronamic_ideal' ),
					$this->method_title
				),
				'default'     => '',
			),
			'icon'                => array(
				'title'       => __( 'Icon', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => sprintf(
					'%s%s',
					$description_prefix,
					__( 'This controls the icon which the user sees during checkout.', 'pronamic_ideal' )
				),
				'default'     => '',
			),
			'config_id'           => array(
				'title'   => __( 'Configuration', 'pronamic_ideal' ),
				'type'    => 'select',
				'default' => get_option( 'pronamic_pay_config_id' ),
				'options' => Plugin::get_config_select_options( $this->payment_method ),
			),
			'payment'             => array(
				'title'       => __( 'Payment Options', 'pronamic_ideal' ),
				'type'        => 'title',
				'description' => '',
			),
			'payment_description' => array(
				'title'       => __( 'Payment Description', 'pronamic_ideal' ),
				'type'        => 'text',
				'description' => sprintf(
					'%s%s<br />%s<br />%s',
					$description_prefix,
					__( 'This controls the payment description.', 'pronamic_ideal' ),
					sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), __( 'Order {order_number}', 'pronamic_ideal' ) ),
					sprintf( __( 'Tags: %s', 'pronamic_ideal' ), sprintf( '<code>%s</code> <code>%s</code> <code>%s</code>', '{order_number}', '{order_date}', '{blogname}' ) )
				),
				'default'     => __( 'Order {order_number}', 'pronamic_ideal' ),
			),
		);
	}

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.0/classes/gateways/gateway.class.php#L72-L80
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.2/classes/gateways/gateway.class.php#L96-L104
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.3/classes/woocommerce_settings_api.class.php#L18-L26
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.3.2/classes/woocommerce_settings_api.class.php#L18-L26
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.4/classes/class-wc-settings-api.php#L18-L31
	 * @see   https://github.com/woothemes/woocommerce/blob/v1.5/classes/class-wc-settings-api.php#L18-L32
	 *
	 * @since WooCommerce version 1.4 the admin_options() function has an default implementation.
	 */
	public function admin_options() {
		parent::admin_options();
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param string $order_id WooCommerce order ID.
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		// Gateway.
		$gateway = Plugin::get_gateway( $this->config_id );

		if ( null === $gateway ) {
			$notice = __( 'The payment gateway could not be found.', 'pronamic_ideal' );

			if ( current_user_can( 'manage_options' ) && empty( $this->config_id ) ) {
				// @see https://github.com/woothemes/woocommerce/blob/v2.1.5/includes/admin/settings/class-wc-settings-page.php#L66
				$notice = sprintf(
					/* translators: %s: WooCommerce checkout settings URL */
					__( 'You have to select an gateway configuration on the <a href="%s">WooCommerce checkout settings page</a>.', 'pronamic_ideal' ),
					add_query_arg(
						array(
							'page'    => 'wc-settings',
							'tab'     => 'checkout',
							'section' => sanitize_title( __CLASS__ ),
						),
						admin_url( 'admin.php' )
					)
				);
			}

			WooCommerce::add_notice( $notice, 'error' );

			return array( 'result' => 'failure' );
		}

		// Order.
		$order = new WC_Order( $order_id );

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
		$replacements = array(
			'{blogname}'     => $blogname,
			'{site_title}'   => $blogname,
			'{order_date}'   => date_i18n( WooCommerce::get_date_format(), WooCommerce::get_order_date( $order ) ),
			'{order_number}' => $order->get_order_number(),
		);

		if ( null === $this->payment_description ) {
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

		// Billing address.
		$billing_address = new Address();
		$billing_address->set_name( $contact_name );
		$billing_address->set_company_name( WooCommerce::get_billing_company( $order ) );
		$billing_address->set_line_1( WooCommerce::get_billing_address_1( $order ) );
		$billing_address->set_line_2( WooCommerce::get_billing_address_2( $order ) );
		$billing_address->set_postal_code( WooCommerce::get_billing_postcode( $order ) );
		$billing_address->set_city( WooCommerce::get_billing_city( $order ) );
		$billing_address->set_region( WooCommerce::get_billing_state( $order ) );
		$billing_address->set_country_name( WooCommerce::get_billing_country( $order ) );
		$billing_address->set_email( WooCommerce::get_billing_email( $order ) );
		$billing_address->set_phone( WooCommerce::get_billing_phone( $order ) );

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
		$shipping_address->set_country_name( WooCommerce::get_shipping_country( $order ) );
		$shipping_address->set_email( WooCommerce::get_shipping_email( $order ) );
		$shipping_address->set_phone( WooCommerce::get_shipping_phone( $order ) );

		// Issuer.
		$issuer = filter_input( INPUT_POST, $this->id . '_issuer_id', FILTER_SANITIZE_STRING );

		// Start payment.
		if ( $this->is_recurring ) {
			$subscription = get_pronamic_subscription( $data->get_subscription_id() );

			if ( null === $subscription ) {
				return array( 'result' => 'failure' );
			}

			$this->payment = Plugin::start_recurring( $subscription, $gateway, $data );
		} else {
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
			 * @see http://wcdocs.woothemes.com/user-guide/extensions/functionality/sequential-order-numbers/#add-compatibility
			 *
			 * @see page 30 http://pronamic.nl/wp-content/uploads/2012/09/iDEAL-Merchant-Integratie-Gids-NL.pdf
			 *
			 * The use of characters that are not listed above will not lead to a refusal of a batch or post, but the
			 * character will be changed by Equens (formerly Interpay) to a space, question mark or asterisk. The
			 * same goes for diacritical characters (à, ç, ô, ü, ý etcetera).
			 */
			$payment->order_id = str_replace( '#', '', $order->get_order_number() );

			$payment->title       = $title;
			$payment->description = $description;
			$payment->config_id   = $this->config_id;
			$payment->user_id     = $order->get_user_id();
			$payment->source      = Extension::SLUG;
			$payment->source_id   = WooCommerce::get_order_id( $order );
			$payment->method      = $this->payment_method;
			$payment->issuer      = $issuer;
			$payment->recurring   = $this->is_recurring;
			//$payment->subscription           = $data->get_subscription();
			//$payment->subscription_id        = $data->get_subscription_id();
			//$payment->subscription_source_id = $data->get_subscription_source_id();

			$payment->set_customer( $customer );
			$payment->set_billing_address( $billing_address );
			$payment->set_shipping_address( $shipping_address );

			$amount = WooCommerce::get_order_total( $order );

			/*
			 * WooCommerce Deposits remaining amount.
			 * @since 1.1.6
			 */
			if ( WooCommerce::order_has_status( $order, 'partially-paid' ) && isset( $order->wc_deposits_remaining ) ) {
				$amount = $order->wc_deposits_remaining;
			}

			$payment->set_amount(
				new Money(
					$amount,
					WooCommerce::get_currency()
				)
			);

			// Payment lines.
			$items = $order->get_items();

			$payment->lines = new PaymentLines();

			foreach ( $items as $item_id => $item ) {
				$line = $payment->lines->new_line();

				$line->set_id( $item_id );
				$line->set_name( $item['name'] );
				$line->set_quantity( wc_stock_amount( $item['qty'] ) );
				$line->set_unit_price( new Money( $order->get_item_total( $item, false, false ), WooCommerce::get_currency() ) );
			}

			// Start payment.
			$this->payment = Plugin::start_payment( $payment );
		}

		$error = $gateway->get_error();

		// Set subscription payment method on renewal to account for changed payment method.
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

			foreach ( $subscriptions as $wcs_subscription ) {
				$wcs_subscription->set_payment_method( $this->id );
				$wcs_subscription->save();
			}
		}

		// Set payment start and end date on subscription switch.
		if ( function_exists( 'wcs_order_contains_switch' ) && wcs_order_contains_switch( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_order( $order );

			$wcs_subscription = array_pop( $subscriptions );

			$start_date = new DateTime( '@' . $wcs_subscription->get_time( 'start_date' ) );

			$this->payment->start_date = $start_date;

			$end_date = clone $start_date;
			$end_date->add( new \DateInterval( 'P' . $data->get_subscription()->interval . $data->get_subscription()->interval_period ) );

			$this->payment->end_date = $end_date;

			$this->payment->save();
		}

		if ( is_wp_error( $error ) ) {
			WooCommerce::add_notice( Plugin::get_default_error_message(), 'error' );

			foreach ( $error->get_error_messages() as $message ) {
				WooCommerce::add_notice( $message, 'error' );
			}

			// Remove subscription next payment date for recurring payments.
			if ( isset( $subscription ) ) {
				$subscription->set_meta( 'next_payment', null );
			}

			// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce-functions.php#L518
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.5/includes/class-wc-checkout.php#L669
			return array( 'result' => 'failure' );
		}

		// Order note and status.
		$new_status_slug = WooCommerce::ORDER_STATUS_PENDING;

		$note = __( 'Awaiting payment.', 'pronamic_ideal' );

		$order_status = WooCommerce::order_get_status( $order );

		// Only add order note if status is already pending or if WooCommerce Deposits is activated.
		if ( $new_status_slug === $order_status || isset( $order->wc_deposits_remaining ) ) {
			$order->add_order_note( $note );
		} else {
			// Mark as pending (we're awaiting the payment).
			$order->update_status( $new_status_slug, $note );
		}

		// Return results array.
		return array(
			'result'   => 'success',
			'redirect' => $this->payment->get_pay_redirect_url(),
		);
	}

	/**
	 * Process WooCommerce Subscriptions payment.
	 *
	 * @param $amount
	 * @param $order
	 */
	public function process_subscription_payment( $amount, $order ) {
		$this->is_recurring = true;

		if ( method_exists( $order, 'get_id' ) ) {
			$order_id = $order->get_id();
		} else {
			$order_id = $order->id;
		}

		$subscriptions = wcs_get_subscriptions_for_order( $order_id );

		if ( wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );
		}

		foreach ( $subscriptions as $subscription_id => $subscription ) {
			if ( $subscription->is_manual() ) {
				continue;
			}

			if ( method_exists( $subscription, 'get_payment_method' ) ) {
				$payment_gateway = $subscription->get_payment_method();
			} else {
				$payment_gateway = $subscription->payment_gateway;
			}

			$order->set_payment_method( $payment_gateway );

			$this->process_payment( $order_id );

			if ( $this->payment ) {
				Plugin::update_payment( $this->payment, false );
			}
		}
	}

	/**
	 * Payment fields
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v1.6.6/templates/checkout/form-pay.php#L66
	 */
	public function payment_fields() {
		// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/classes/gateways/class-wc-payment-gateway.php#L181
		parent::payment_fields();

		if ( empty( $this->input_fields ) ) {
			return;
		}

		$fields = $this->input_fields;

		// Prevent duplicate input fields, by removing fields for which
		// a checkout field has been set in plugin settings.
		$remove_fields = array(
			'pronamic_pay_gender'     => get_option( 'pronamic_pay_woocommerce_gender_field' ),
			'pronamic_pay_birth_date' => get_option( 'pronamic_pay_woocommerce_birth_date_field' ),
		);

		foreach ( $remove_fields as $field_id => $field_setting ) {
			if ( empty( $field_setting ) ) {
				continue;
			}

			// Field setting has been set, filter input fields.
			$fields = wp_list_filter( $this->input_fields, array( 'id' => $field_id ), 'NOT' );
		}

		// Print fields.
		$this->print_fields( $fields );
	}

	/**
	 * Print the specified fields.
	 *
	 * @param array $fields Fields to print.
	 */
	public function print_fields( $fields ) {
		foreach ( $fields as &$field ) {
			if ( ! isset( $field['id'] ) ) {
				continue;
			}

			if ( 'pronamic_' !== substr( $field['id'], 0, 9 ) ) {
				continue;
			}

			$input_ids = array(
				'pronamic_ideal_issuer_id'       => 'issuer_id',
				'pronamic_credit_card_issuer_id' => 'issuer_id',
				'pronamic_pay_gender'            => 'gender',
				'pronamic_pay_birth_date'        => 'birth_date',
			);

			foreach ( $input_ids as $input_id => $input_id_suffix ) {
				if ( $input_id !== $field['id'] ) {
					continue;
				}

				$field['id']   = sprintf( '%1$s_%2$s', $this->id, $input_id_suffix );
				$field['name'] = $field['id'];

				if ( isset( $field['required'] ) && $field['required'] ) {
					$field['label'] = sprintf( '%s *', $field['label'] );
				}
			}
		}

		echo Util::input_fields_html( $fields ); // WPCS: xss ok.
	}

	/**
	 * Validate required payment method input fields after checkout.
	 *
	 * @param array    $data   Posted data.
	 * @param WP_Error $errors Checkout validation errors.
	 */
	public function after_checkout_validation( $data, $errors ) {
		if ( ! isset( $data['payment_method'] ) || $this->id !== $data['payment_method'] ) {
			return;
		}

		$input_ids = array(
			'gender',
			'birth_date',
		);

		foreach ( $input_ids as $input_id ) {
			$input_name = sprintf( '%s_%s', $this->id, $input_id );

			if ( ! filter_has_var( INPUT_POST, $input_name ) ) {
				continue;
			}

			$input_value = filter_input( INPUT_POST, $input_name, FILTER_SANITIZE_STRING );

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
