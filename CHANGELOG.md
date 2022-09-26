# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.2.0] - 2022-09-26
- Added upgrade script to add missing Pronamic subscription ID to WooCommerce subscription meta (pronamic/wp-pronamic-pay-woocommerce#11).
- Updated for new payment methods and fields registration.
- Improved WooCommerce Blocks support.

## [4.1.1] - 2022-04-19
- Added support for gender and birth date fields with WooCommerce Blocks.

## [4.1.0] - 2022-04-11
- Transform expired WooCommerce subscription status to Pronamic status `Completed`.
- Add failure reason notice on 'Pay for order' page (pronamic/wp-pronamic-pay-adyen#2).
- Added support for WooCommerce Blocks (#9).
- Fix resetting trial phase next payment date on payment status update.
- Ignore seconds in calculation of subscription trial phase interval.
- No longer check for gateway error, step towards exceptions only.

## [4.0.1] - 2022-02-16
- Added Klarna Pay Now and Klarna Pay Over Time gateways.
- Added support for multiple subscriptions.
- Fixed adding periods to payments.
- Fixed handling subscription payment method changes.
- Fixed setting input fields only if gateway is enabled.
- Updated AfterPay.nl and Afterpay.com method descriptions to clarify differences in target countries.
- Updated subscription source texts.

## [4.0.0] - 2022-01-10
### Changed
- Updated to https://github.com/pronamic/wp-pay-core/releases/tag/4.0.0.
- Set Swish and Vipps payment method icons.
- Use new AfterPay.nl constant.

### Added
- Added BLIK and MB WAY payment methods.
- Added support for TWINT payment method.

### Fixed
- Fix fatal error if filter `woocommerce_thankyou_order_received_text` is called without valid order.

## [3.0.2] - 2021-09-03
- Set pending order status when awaiting payment.
- Removed usage of non-existing `shipping_phone` order property ([#8](https://github.com/wp-pay-extensions/woocommerce/issues/8)).

## [3.0.1] - 2021-08-16
- Added American Express, Mastercard, V PAY and Visa payment gateways.

## [3.0.0] - 2021-08-05
- Updated to `pronamic/wp-pay-core`  version `3.0.0`.
- Updated to `pronamic/wp-money`  version `2.0.0`.
- Changed `TaxedMoney` to `Money`, no tax info.
- Switched to `pronamic/wp-coding-standards`.
- Added support for SprayPay payment method.

## [2.3.1] - 2021-06-18
- Fixed updating WooCommerce order for refunds in payment update [#130](https://github.com/pronamic/wp-pronamic-pay/issues/130).

## [2.3.0] - 2021-04-26
- Added initial support for refunds.
- Added support for Swish and Vipps payment methods.
- Fixed using default configuration if not set in gateway settings.

## [2.2.1] - 2021-01-14
- Updated logo library to version 1.6.3 for new iDEAL logo.
- Start subscription payment through subscription module instead of plugin.
- Move info message up on thank you page.
- Add Santander payment method.

## [2.2.0] - 2020-11-09
- Updated iDEAL logo.
- Added Przelewy24 payment method.
- Added support for new subscription phases and periods.
- Fixed incorrect 'Awaiting payment' order note for recurring payments in some cases.
- Fixed using default payment description if setting is empty.

## [2.1.4] - 2020-08-05
- Improve loading checkout fields in plugin settings.

## [2.1.3] - 2020-07-22
- Fix compatibility with WooCommerce EU VAT Number plugin.

## [2.1.2] - 2020-07-08
- Store WooCommerce billing company in Pronamic Pay customer.

## [2.1.1] - 2020-04-03
- Set plugin integration name.

## [2.1.0] - 2020-03-19
- Update integration setup with dependencies support.
- Use SVG icons.
- Add Apple Pay payment method.
- Extension extends \Pronamic\WordPress\Pay\AbstractPluginIntegration.
- Added Google Pay support.

## [2.0.10] - 2019-12-22
- Improved error handling with exceptions.
- Updated subscription source details.

## [2.0.9] - 2019-10-07
- Only update order status if order payment method is a WordPress Pay gateway.
- No longer disable 'Direct Debit' gateways when WooCommerce subscriptions is active and cart has no subscriptions [read more](https://github.com/wp-pay-extensions/woocommerce#conditional-payment-gateways).
- Changed redirect URL for cancelled and expired payments from cancel order to order pay URL.
- Allow payment gateway selection for order pay URL.

## [2.0.8] - 2019-08-30
- Fix error "`DatePeriod::__construct()`: The recurrence count '0' is invalid. Needs to be > 0".

## [2.0.7] - 2019-08-26
- Updated packages.

## [2.0.6] - 2019-04-15
- Fix accidentally adding 'Pronamic' to checkout button text.
- Fix fatal error in checkout settings with WooCommerce Subscriptions.
- Fix incorrectly filtering available gateways with WooCommerce Subscriptions.

## [2.0.5] - 2019-03-28
- Improved order notes and payment status updates.
- Added/updated gateway icons.
- More DRY gateway setup.

## [2.0.4] - 2018-12-19
- Improved retrieving WooCommerce checkout fields.

## [2.0.3] - 2018-12-18
- Fixed WooCommerce admin products table not listing all products.

## [2.0.2] - 2018-12-10
- Added AfterPay, Capayable, Focum and Klarna Pay Later payment methods.
- Renamed Capayable to new brand name In3.
- Added support for payment lines, shipping, billing and customer data.

## [2.0.1] - 2018-05-16
- Improved recurring payments support.

## [2.0.0] - 2018-05-14
- Switched to PHP namespaces.

## [1.2.8] - 2017-12-12
- Updated subscription payment data.
- Set subscription payment method on renewal to account for changed payment method.
- Improved WooCommerce 3.0 compatibility.
- Added gateway support for amount and date changes.
- Clear subscription next payment date on gateway error during payment processing.

## [1.2.7] - 2017-09-14
- Added credit card payment fields.
- Added bunq gateway.
- Implemented `get_first_name()` and `get_last_name()`.
- Added `Direct Debit (mandate via Bancontact)` gateway.
- Added a few `order_button_text` labels.

## [1.2.6] - 2017-04-18
- Improved support for WooCommerce 3.0.

## [1.2.5] - 2017-03-15
- Don't set subscriptions 'on hold' due to delay in direct debit status update.
- Removed gateway description about valid mandate, as these mandates are no longer in use.

## [1.2.4] - 2017-01-25
- Fixed Composer requirement.

## [1.2.3] - 2017-01-25
- Added KBC/CBC Payment Button gateway.
- Added Belfius Direct Net gateway.
- Added filter for payment source description and URL.

## [1.2.2] - 2016-11-16
- Added Maestro gateway.
- Filter gateway description to show mandate notice also when description is empty.

## [1.2.1] - 2016-10-20
- Added experimental support for WooCommerce Subscriptions / recurring payments.
- Restore compatibility with WooCommerce versions < 2.2.0.
- Switched to new Bancontact logo.
- Added Bitcoin gateway.

## [1.2.0] - 2016-06-08
- Added PayPal gateway.

## [1.1.9] - 2016-05-16
- Use `get_woocommerce_currency` function so the `woocommerce_currency` filter is applied.

## [1.1.8] - 2016-04-12
- Check existence of WC_Order::has_status() to support older versions of WooCommerce.
- No longer use camelCase for payment data.
- Add clarification to Pronamic gateway with difference compared to regular payment method specific gateways.
- Fix adding 'Awaiting payment' order note if order status is already pending.

## [1.1.7] - 2016-03-23
- Redirect to payment options instead of 'Order received' if payment is not yet completed.
- Implemented new payment redirect URL filter.
- Use the global default config as the WooCommerce default config.

## [1.1.6] - 2016-02-02
- Add support for WooCommerce Deposits plugin

## [1.1.5] - 2015-10-21
- Removed status code 303 from redirect.

## [1.1.4] - 2015-10-15
- Updated WordPress pay core library to version 1.2.2.

## [1.1.3] - 2015-10-14
- Order note "iDEAL payment [status]" now includes the gateway title, instead of "iDEAL".
- Add DirectDebitGateway.
- Add bank transfer gateway.

## [1.1.2] - 2015-04-08
- Added general Pronamic gateway so the iDEAL gateway can be used for iDEAL only.

## [1.1.1] - 2015-03-03
- Changed WordPress pay core library requirement from `~1.0.1` to `>=1.0.1`.

## [1.1.0] - 2015-02-16
- Added SOFORT Banking gateway.
- Removed the word 'iDEAL' from the a few strings.

## [1.0.3] - 2015-01-20
- Require WordPress pay core library version 1.0.0.

## [1.0.2] - 2015-01-16
- Fix - Fixed in issue with WooCommerce cancel order URL and HTML entities.

## [1.0.1] - 2014-12-19
- Tweak - No longer set gateways enabled to yes by default.

## 1.0.0 - 2014-12-19
- First release.

[unreleased]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.2.0...HEAD
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.1.1...4.2.0
[4.1.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/wp-pay-extensions/woocommerce/compare/3.0.2...4.0.0
[3.0.2]: https://github.com/wp-pay-extensions/woocommerce/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/wp-pay-extensions/woocommerce/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/wp-pay-extensions/woocommerce/compare/2.3.1...3.0.0
[2.3.1]: https://github.com/wp-pay-extensions/woocommerce/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/wp-pay-extensions/woocommerce/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/wp-pay-extensions/woocommerce/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/wp-pay-extensions/woocommerce/compare/2.1.4...2.2.0
[2.1.4]: https://github.com/wp-pay-extensions/woocommerce/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/wp-pay-extensions/woocommerce/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/wp-pay-extensions/woocommerce/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/wp-pay-extensions/woocommerce/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.10...2.1.0
[2.0.10]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.9...2.0.10
[2.0.9]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/wp-pay-extensions/woocommerce/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.8...2.0.0
[1.2.8]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.7...1.2.8
[1.2.7]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.6...1.2.7
[1.2.6]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/wp-pay-extensions/woocommerce/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.9...1.2.0
[1.1.9]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.8...1.1.9
[1.1.8]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.7...1.1.8
[1.1.7]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/wp-pay-extensions/woocommerce/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/wp-pay-extensions/woocommerce/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/wp-pay-extensions/woocommerce/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/wp-pay-extensions/woocommerce/compare/1.0.0...1.0.1
