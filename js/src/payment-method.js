/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Content component
 */
const Content = ( props ) => {
	return decodeEntities( props.text );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
	const { PaymentMethodLabel } = props.components;

	return <>
		<img src={ props.icon } />

		{ '' !== props.title &&
			<>
				&nbsp;<PaymentMethodLabel text={ decodeEntities( props.title ) }/>
			</>
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
		content: <Content text={ description }/>,
		edit: <Content text={ description }/>,
		placeOrderButtonLabel: settings.orderButtonLabel || '',
		supports: {
			features: settings?.supports || [ 'products' ]
		},
		canMakePayment: () => true
	} );
};
