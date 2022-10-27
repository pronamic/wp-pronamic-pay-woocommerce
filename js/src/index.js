/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useState } from '@wordpress/element';
import { BaseControl, SelectControl, TextControl } from '@wordpress/components';

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

	function renderField( field ) {
		// Set default field value state.
		if ( undefined === state || ! state.hasOwnProperty( field.id ) ) {
			let defaultValue = undefined;

			if ( field.hasOwnProperty( 'options' ) && field.options.length > 0 ) {
				let [ firstOption ] = field.options;

				defaultValue = firstOption.value;
			}

			setState( state => ( { ...state, [ field.id ]: defaultValue } ) );
		}

		switch( field.type ) {
			case 'select':
				return <SelectControl
					id={ field.id }
					options={ field.options }
					onChange={ ( selection ) => setState( state => ( { ...state, [field.id]: selection } ) ) }
				/>
			case 'date':
				return <TextControl
					id={ field.id }
					name={ field.id }
					type="date"
					onChange={ ( value ) => setState( state => ( { ...state, [field.id]: value } ) ) }
				/>
		}
	}

	function renderError( field ) {
		if ( ! field.error ) {
			return;
		}

		return <div>
			{ field.error }
		</div>;
	}

	return <>
		{ ( '' !== description || fields.length > 0 ) && <div>
			{ '' !== description && <div dangerouslySetInnerHTML={{__html: description}} /> }

			{ fields.map( ( field ) => (
				<div key={field.id}>
					<BaseControl
						id={ field.id }
						label={ field.label }
					>
						{ renderField( field ) }
						{ renderError( field ) }
					</BaseControl>
				</div>
			) ) }
		</div> }
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
		canMakePayment: () => true
	} );
};
