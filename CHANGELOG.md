# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

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
- Changed WordPress pay core library requirment from ~1.0.1 to >=1.0.1.

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

[unreleased]: https://github.com/wp-pay-extensions/woocommerce/compare/1.1.7...HEAD
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
