<?php

/**
 * Title: WooCommerce iDEAL gateway
 * Description:
 * Copyright: Copyright (c) 2005 - 2018
 * Company: Pronamic
 *
 * @author Remco Tolsma
 * @version 1.2.8
 * @since 1.0.0
 */
class Pronamic_WP_Pay_Extensions_WooCommerce_Gateway extends WC_Payment_Gateway {
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
	 * @var Pronamic_WP_Pay_Payment
	 */
	protected $payment;

	/**
	 * Is recurring payment
	 *
	 * @var bool
	 */
	public $is_recurring;

	//////////////////////////////////////////////////

	/**
	 * Constructs and initialize an iDEAL gateway
	 */
	public function __construct() {
		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Define user set variables
		$this->icon                = $this->get_pronamic_option( 'icon' );
		$this->title               = $this->get_pronamic_option( 'title' );
		$this->description         = $this->get_pronamic_option( 'description' );
		$this->enabled             = $this->get_pronamic_option( 'enabled' );
		$this->config_id           = $this->get_pronamic_option( 'config_id' );
		$this->payment_description = $this->get_pronamic_option( 'payment_description' );

		// Actions
		$update_action = 'woocommerce_update_options_payment_gateways_' . $this->id;
		if ( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$update_action = 'woocommerce_update_options_payment_gateways';
		}

		add_action( $update_action, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Get Pronamic option
	 *
	 * The WooCommerce settings API only have an 'get_option' function in
	 * WooCommerce version 2 or higher.
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v2.0.0/classes/abstracts/abstract-wc-settings-api.php#L130
	 *
	 * @param string $name
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

		if ( Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::version_compare( '2.0.0', '<' ) ) {
			$description_prefix = '<br />';
		}

		$this->form_fields = array(
			'enabled'             => array(
				'title'   => __( 'Enable/Disable', 'pronamic_ideal' ),
				'type'    => 'checkbox',
				'label'   => sprintf(
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
				'options' => Pronamic_WP_Pay_Plugin::get_config_select_options( $this->payment_method ),
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
					sprintf( __( 'Default: <code>%s</code>.', 'pronamic_ideal' ), Pronamic_WP_Pay_Extensions_WooCommerce_PaymentData::get_default_description() ),
					sprintf( __( 'Tags: %s', 'pronamic_ideal' ), sprintf( '<code>%s</code> <code>%s</code> <code>%s</code>', '{order_number}', '{order_date}', '{blogname}' ) )
				),
				'default'     => Pronamic_WP_Pay_Extensions_WooCommerce_PaymentData::get_default_description(),
			),
		);
	}

	//////////////////////////////////////////////////

	/**
	 * Admin Panel Options
	 * - Options for bits like 'title' and availability on a country-by-country basis
	 *
	 * @see https://github.com/woothemes/woocommerce/blob/v1.0/classes/gateways/gateway.class.php#L72-L80
	 * @see https://github.com/woothemes/woocommerce/blob/v1.2/classes/gateways/gateway.class.php#L96-L104
	 * @see https://github.com/woothemes/woocommerce/blob/v1.3/classes/woocommerce_settings_api.class.php#L18-L26
	 * @see https://github.com/woothemes/woocommerce/blob/v1.3.2/classes/woocommerce_settings_api.class.php#L18-L26
	 * @see https://github.com/woothemes/woocommerce/blob/v1.4/classes/class-wc-settings-api.php#L18-L31
	 * @see https://github.com/woothemes/woocommerce/blob/v1.5/classes/class-wc-settings-api.php#L18-L32
	 *
	 * @since WooCommerce version 1.4 the admin_options() function has an default implementation.
	 */
	public function admin_options() {
		parent::admin_options();
	}

	//////////////////////////////////////////////////

	/**
	 * Process the payment and return the result
	 *
	 * @param string $order_id
	 */
	function process_payment( $order_id ) {
		// Gateway
		$gateway = Pronamic_WP_Pay_Plugin::get_gateway( $this->config_id );

		if ( null === $gateway ) {
			$notice = __( 'The payment gateway could not be found.', 'pronamic_ideal' );

			if ( current_user_can( 'manage_options' ) && empty( $this->config_id ) ) {
				// @see https://github.com/woothemes/woocommerce/blob/v2.1.5/includes/admin/settings/class-wc-settings-page.php#L66
				$notice = sprintf(
					__( 'You have to select an gateway configuration on the <a href="%s">WooCommerce checkout settings page</a>.', 'pronamic_ideal' ),
					add_query_arg( array(
						'page'    => 'wc-settings',
						'tab'     => 'checkout',
						'section' => sanitize_title( __CLASS__ ),
					), admin_url( 'admin.php' ) )
				);
			}

			Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::add_notice( $notice, 'error' );

			return array( 'result' => 'failure' );
		}

		// Order
		$order = new WC_Order( $order_id );

		$data = new Pronamic_WP_Pay_Extensions_WooCommerce_PaymentData( $order, $this, $this->payment_description );

		$this->payment = Pronamic_WP_Pay_Plugin::start( $this->config_id, $gateway, $data, $this->payment_method );

		$error = $gateway->get_error();

		// Set subscription payment method on renewal to account for changed payment method.
		if ( function_exists( 'wcs_order_contains_renewal' ) && wcs_order_contains_renewal( $order ) ) {
			$subscriptions = wcs_get_subscriptions_for_renewal_order( $order );

			foreach ( $subscriptions as $wcs_subscription ) {
				$wcs_subscription->set_payment_method( $this->id );
				$wcs_subscription->save();
			}
		}

		if ( is_wp_error( $error ) ) {
			Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::add_notice( Pronamic_WP_Pay_Plugin::get_default_error_message(), 'error' );

			foreach ( $error->get_error_messages() as $message ) {
				Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::add_notice( $message, 'error' );
			}

			// Remove subscription next payment date for recurring payments
			if ( $this->is_recurring ) {
				$this->payment->get_subscription()->set_next_payment_date( false );
			}

			// @see https://github.com/woothemes/woocommerce/blob/v1.6.6/woocommerce-functions.php#L518
			// @see https://github.com/woothemes/woocommerce/blob/v2.1.5/includes/class-wc-checkout.php#L669
			return array( 'result' => 'failure' );
		}

		// Order note and status
		$new_status_slug = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::ORDER_STATUS_PENDING;

		$note = __( 'Awaiting payment.', 'pronamic_ideal' );

		$order_status = Pronamic_WP_Pay_Extensions_WooCommerce_WooCommerce::order_get_status( $order );

		// Only add order note if status is already pending or if WooCommerce Deposits is activated.
		if ( $new_status_slug === $order_status || isset( $order->wc_deposits_remaining ) ) {
			$order->add_order_note( $note );
		} else {
			// Mark as pending (we're awaiting the payment)
			$order->update_status( $new_status_slug, $note );
		}

		// Return results array
		return array(
			'result'   => 'success',
			'redirect' => $this->payment->get_pay_redirect_url(),
		);
	}

	//////////////////////////////////////////////////

	/**
	 * Process WooCommerce Subscriptions payment.
	 *
	 * @param WC_Product_Subscription $subscription
	 */
	function process_subscription_payment( $amount, $order ) {
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
			if ( ! $subscription->is_manual() ) {
				if ( method_exists( $subscription, 'get_payment_method' ) ) {
					$payment_gateway = $subscription->get_payment_method();
				} else {
					$payment_gateway = $subscription->payment_gateway;
				}

				$order->set_payment_method( $payment_gateway );

				$this->process_payment( $order_id );

				if ( $this->payment ) {
					Pronamic_WP_Pay_Plugin::update_payment( $this->payment, false );
				}
			}
		}
	}

	//////////////////////////////////////////////////

	/**
	 * Print the specified fields.
	 *
	 * @param array $fields
	 */
	public function print_fields( $fields ) {
		foreach ( $fields as &$field ) {
			if ( isset( $field['id'] ) && 'pronamic_ideal_issuer_id' === $field['id'] ) {
				$field['id']   = $this->id . '_issuer_id';
				$field['name'] = $this->id . '_issuer_id';

				break;
			}
		}

		echo Pronamic_WP_Pay_Util::input_fields_html( $fields );
	}
}
