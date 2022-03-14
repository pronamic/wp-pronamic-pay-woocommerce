/**
 * Internal dependencies.
 */
import { registerMethod } from './payment-method';
import { default as paymentMethods } from './payment-methods.json';

// Register payment methods.
paymentMethods.forEach( registerMethod );
