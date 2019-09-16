# WordPress Pay Extension: WooCommerce

**WooCommerce driver for the WordPress payment processing library.**

## Links

*	[WooThemes](http://www.woothemes.com/)
*	[WooCommerce](http://www.woothemes.com/woocommerce/)
*	[GitHub WooCommerce](https://github.com/woothemes/woocommerce)

## WooCommerce Subscriptions

*	[Testing Subscription Renewal Payments](https://docs.woocommerce.com/document/testing-subscription-renewal-payments/)

## Conditional Payment Gateways

In version `<= 2.0.8` of this extension we had some code in place which disabled
the "Direct Debit" gateways if the WooCommerce Subscriptions plugin was active
and the shopping cart did not contain a subscription. We removed this feature 
from this extensions, this can now be achieved with the
[Conditional Shipping and Payments][1] plugin ([documentation][2]).

[1]: https://woocommerce.com/products/woocommerce-conditional-shipping-and-payments/
[2]: https://docs.woocommerce.com/document/woocommerce-conditional-shipping-and-payments/
