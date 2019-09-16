# WordPress Pay Extension: WooCommerce

**WooCommerce driver for the WordPress payment processing library.**

## Links

*	[WooThemes](http://www.woothemes.com/)
*	[WooCommerce](http://www.woothemes.com/woocommerce/)
*	[GitHub WooCommerce](https://github.com/woothemes/woocommerce)

## WooCommerce Subscriptions

*	[Testing Subscription Renewal Payments](https://docs.woocommerce.com/document/testing-subscription-renewal-payments/)

## Conditional Payment Gateways

In version [`>= 1.2.7 <= 2.0.8`][commit link] of this extension we had some code in place which disabled
the "Direct Debit" gateways if the WooCommerce Subscriptions plugin was active
and the shopping cart did not contain a subscription. We removed this feature 
from this extensions, this can now be achieved with the
[Conditional Shipping and Payments][product link] plugin ([documentation][documentation link]).

[commit link]: https://github.com/wp-pay-extensions/woocommerce/commit/a2b8405e60f38060580004c4d1d92e1f0bc55503#diff-781d37e729cd426435d60e6df5204557
[product link]: https://woocommerce.com/products/woocommerce-conditional-shipping-and-payments/
[documentation link]: https://docs.woocommerce.com/document/woocommerce-conditional-shipping-and-payments/
