/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { getSetting } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect } from '@wordpress/element';

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

    useEffect( () => {
        const unsubscribe = onPaymentProcessing( function() {
            return {
                type: 'success',
                meta: {
                    paymentMethodData: {
                        ok: '1234',
                        test: 'abcd'
                    }
                }
            };
        } );

        return unsubscribe;
    }, [ onPaymentProcessing ] );

    fields.forEach( ( field ) => {
        field.options = [];

        field.choices.forEach( ( choice ) => {
            for ( const key in choice.options ) {
                field.options.push( {
                    key: key,
                    value: choice.options[key]
                } );
            }
        } );
    } );

    return <>
        <div>
            <div dangerouslySetInnerHTML={{__html: description}} />

            {fields.map( ( field ) => (
                <div key={field.id}>
                    <strong>{field.label}</strong>

                    <select>
                        {field.options.map( ( option ) => (
                            <option value="{option.key}">{option.value}</option>
                        ) ) }
                    </select>
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
