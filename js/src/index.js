/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useState } from '@wordpress/element';
import { SelectControl, TextControl } from '@wordpress/components';

/**
 * Content component
 */
const Content = ( props ) => {
	return ( props.text );
};

/**
 * 
 * @link https://github.com/woocommerce/woocommerce-gutenberg-products-block/blob/trunk/docs/extensibility/checkout-flow-and-events.md#onpaymentprocessing
 */
const PaymentMethodContent = ( { description, fields, eventRegistration } ) => {
	const { onPaymentProcessing } = eventRegistration;

	const [ state, setState ] = useState();

	useEffect( () => {
		const unsubscribe = onPaymentProcessing( function() {
			return {
				type: 'success',
				meta: {
					paymentMethodData: state
				}
			};
		} );

		return unsubscribe;
	}, [ onPaymentProcessing, state ] );

	fields.forEach( ( field ) => {
		if ( 'select' !== field.type ) {
			return;
		}

		field.options = [];

		field.choices.forEach( ( choice ) => {
			for ( const key in choice.options ) {
				field.options.push( {
					value: key,
					label: choice.options[key]
				} );
			}
		} );
	} );

	function renderField( field ) {
		switch( field.type ) {
			case 'select':
				return <SelectControl
					label={ field.label }
					options={ field.options }
					onChange={ ( selection ) => setState( state => ( { ...state, [field.name]: selection } ) ) }
				/>
			case 'date':
				return <TextControl
					label={ field.label }
					type="date"
					onChange={ ( selection ) => setState( state => ( { ...state, [field.name]: selection } ) ) }
				/>
		}
	}

	return <>
		<div>
			<div dangerouslySetInnerHTML={{__html: description}} />

			{fields.map( ( field ) => (
				<div key={field.id}>
					{ renderField( field ) }
				</div>
			) ) }
		</div>
	</>
}

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;

	return <>
		{ '' !== props.icon &&
			<>
				<img src={ props.icon } />&nbsp;
			</>
		}

		{ '' !== props.title &&
			<PaymentMethodLabel text={ decodeEntities( props.title ) } />
		}
	</>;
};

/**
 * Register payment method.
 */
export function registerMethod( paymentMethodId ) {
	const settings = getSetting( paymentMethodId + '_data', false );

	// Bail out if payment method is not enabled.
	if ( false === settings ) {
		return;
	}

	const title = settings.title || '';

	const description = settings.description || '';

	let canMakePayment = true;

	if ( 'pronamic_pay_apple_pay' === paymentMethodId ) {
		canMakePayment = ( undefined !== window.ApplePaySession );
	}

	registerPaymentMethod( {
		name: paymentMethodId,
		label: <Label title={ title } icon={ settings.icon }/>,
		ariaLabel: decodeEntities( title ),
		content: <PaymentMethodContent description={ description } fields={ settings.fields } />,
		edit: <Content text={ description }/>,
		placeOrderButtonLabel: settings.orderButtonLabel || '',
		supports: {
			features: settings?.supports || [ 'products' ]
		},
		canMakePayment: () => canMakePayment
	} );
};
