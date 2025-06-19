# Change Log

All notable changes to this project will be documented in this file.

This projects adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com/).

## [Unreleased][unreleased]
-

## [4.12.1] - 2025-06-19

### Commits

- Allow Jetpack autloader 3, 4 and 5 ([616bab1](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/616bab17d9bde445debbe8005abfdec38286bdac))

### Composer

- Changed `automattic/jetpack-autoloader` from `^3.0` to `v5.0.7`.
	Release notes: https://github.com/Automattic/jetpack-autoloader/releases/tag/v5.0.7

Full set of changes: [`4.12.0...4.12.1`][4.12.1]

[4.12.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.12.0...v4.12.1

## [4.12.0] - 2025-06-19

### Changed

- Make sure we retrieve the subscriptions from all order types. ([59949da](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/59949daf471e71575e78f3e221ad727bee803185))
- Set meta `woocommerce_subscription_change_payment_method` on payment method changes for use in payment status updates (https://github.com/pronamic/pronamic.shop/issues/56). ([e169207](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/e169207a56f073909b9b2b377f863e56ac1ebaba))
- Added `wp-slug` in composer.json. ([d30bd18](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/d30bd18e1c5c8037c4e789986847c166fb117df3))

### Composer

- Changed `wp-pay/core` from `^4.19` to `v4.26.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.26.0

Full set of changes: [`4.11.0...4.12.0`][4.12.0]

[4.12.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.11.0...v4.12.0

## [4.11.0] - 2025-02-14

### Commits

- Improved loading translations. ([2f4b2a9](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/2f4b2a9359cb7337067b0e0f6f18303e65df559f))
- Set order status on hold when processing scheduled subscription payment. ([bedd1fa](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/bedd1fabefe3c816952fa81d33fb9d5a386647c3))

Full set of changes: [`4.10.0...4.11.0`][4.11.0]

[4.11.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.10.0...v4.11.0

## [4.10.0] - 2024-12-16

### Added

- Added setting "Show iDEAL issuers", since the launch of the new iDEAL 2.0 platform, it is recommended to no longer show the iDEAL issuer selection field on the WooCommerce checkout form.

### Changed

- Orders paid via the direct debit (SEPA) payment method will now have the status 'on-hold' instead of 'pending'. This status ensures that customers cannot (re)pay for the order during the direct debit, which can take several days to process.

Full set of changes: [`4.9.1...4.10.0`][4.10.0]

[4.10.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.9.1...v4.10.0

## [4.9.1] - 2024-06-19

### Commits

- No longer use inline <style>-element. ([d014181](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/d0141814cb4b4f453edff3bc64a58abc71470fbe))
- Removed sanitize order ID logic for Sisow. ([b24e733](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/b24e733553437510016ef46b50aeaeb85d483af1))

Full set of changes: [`4.9.0...4.9.1`][4.9.1]

[4.9.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.9.0...v4.9.1

## [4.9.0] - 2024-06-07

### Commits

- Added `{payment_lines_name}` tag to description (https://github.com/pronamic/pronamic-pay/issues/100). ([ee9eec2](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/ee9eec263a370a754696afee833331a299717ff2))
- No longer use Composer bin plugin. ([34c4da1](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/34c4da1cccc6f9027fe7067b7a1212dda58743fc))
- Also store Pronamic payment ID in WooCommerce order for subscriptions payments. ([7caf7df](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/7caf7dfb892091d40f24c136a3dd8b296cb41ebc))

### Composer

- Changed `php` from `>=8.0` to `>=8.1`.
- Changed `wp-pay/core` from `^4.16` to `v4.19.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.19.0

Full set of changes: [`4.8.0...4.9.0`][4.9.0]

[4.9.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.8.0...v4.9.0

## [4.8.0] - 2024-03-26

### Changed

- Revised payment gateway icon functionality. ([9c858da](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/9c858dac8bf0168eee676c28ce4394674cc665b9))

### Fixed

- Fixed Pronamic Pay subscription meta box visibility. ([988bca2](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/988bca2236244416fe9af6352d6fac7cd2da2ec4))

### Composer

- Added `automattic/jetpack-autoloader` `^3.0`.
- Added `woocommerce/action-scheduler` `^3.7`.
- Changed `php` from `>=7.4` to `>=8.0`.
- Changed `wp-pay/core` from `^4.9` to `v4.16.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.16.0

Full set of changes: [`4.7.1...4.8.0`][4.8.0]

[4.8.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.7.1...v4.8.0

## [4.7.1] - 2024-02-07

### Fixed

- Fixed "Fatal error: Uncaught Error: Call to undefined function wc_get_order()" in source text if WooCommerce is not active. ([c4ccf37](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/c4ccf3729ea994df23737181c5771abcaf8cd6c6))

Full set of changes: [`4.7.0...4.7.1`][4.7.1]

[4.7.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.7.0...v4.7.1

## [4.7.0] - 2023-12-18

### Commits

- Added BNPL disclaimer to In3 and Klarna. ([58b8309](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/58b8309e11e35ff6aaaff3e57beaa82634361159))
- Store VAT number from "WooCommerce EU VAT Number" plugin in customer/payment. ([ba05c39](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/ba05c394defc850d8d9363ab9385359f03956ef9))
- Added method description for the credit card gateway. ([801598e](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/801598e7596de6c53dddd50733ca2f336dc1009d))
- Added method description for Riverty. ([bae4dac](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/bae4daccb972b312326cd721adbf08566eee72f8))
- Added the Riverty disclaimer. ([96ec8d9](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/96ec8d99a77a8d72822db417689084b955d2c3fa))

Full set of changes: [`4.6.3...4.7.0`][4.7.0]

[4.7.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.6.3...v4.7.0

## [4.6.3] - 2023-11-06

### Changed

- Meta box HPOS compat. ([ed25f45](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/ed25f45c677f3001cb43ef867feae24e31eea85c))
- Added gateway settings field default value (fixes #62). ([d67e020](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/d67e020da8f5c797a088bc4d1505f3c9f2dadb88))

Full set of changes: [`4.6.2...4.6.3`][4.6.3]

[4.6.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.6.2...v4.6.3

## [4.6.2] - 2023-10-30

### Changed

- Improved escaping of some HTML elements.
- Added some missing `if ( ! defined( 'ABSPATH' ) )` statements.

### Composer

- Added `pronamic/wp-html` `^2.2`.

Full set of changes: [`4.6.1...4.6.2`][4.6.2]

[4.6.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.6.1...v4.6.2

## [4.6.1] - 2023-10-18

### Fixed

- The `4.2.0` upgrade script for WooCommerce Subscriptions will now only schedule it's actions when WooCommerce Subscriptions is running. ([60](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/60))
- When paying for subscriptions via Mollie that are manually renewed, the payment is no longer marked to Mollie as a first payment for obtaining a mandate. This makes it possible to also use the Mollie bank transfer payment method to pay for subscriptions that are manually renewed. ([58](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/58))

Full set of changes: [`4.6.0...4.6.1`][4.6.1]

[4.6.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.6.0...v4.6.1

## [4.6.0] - 2023-10-13

### Added

- Added Pronamic Pay meta box on the WooCommerce admin order page.
- Added Pronamic Pay meta box on the WooCommerce Subscriptions admin subscription page.
- Added/improved support for the WooCommerce Subscriptions change payment method feature.

### Changed

- Updated to latest Pronamic coding standards. ([cdf4b84](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/cdf4b84c962cd9577a5eca208eea03ff7cac2983))
- Only set order status to pending payment if order still needs payment and order status is not already pending. ([7892e23](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/7892e239bc22eacd53f67b4dc5e62688be0cdab3))

### Fixed

- HTML is no longer allowed in the WooCommerce thank you order received text. ([cf5ae1b](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/cf5ae1b9faa1c6303b1b15bda7cfae8b901f39b2))

Full set of changes: [`4.5.9...4.6.0`][4.6.0]

[4.6.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.9...v4.6.0

## [4.5.9] - 2023-09-11

### Commits

- Fixed spelling. ([e701e56](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/e701e568d45ce858ca5b5572de404a67e8da919a))

Full set of changes: [`4.5.8...4.5.9`][4.5.9]

[4.5.9]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.8...v4.5.9

## [4.5.8] - 2023-08-23

### Commits

- Fixed some WPCS 3 warnings. ([4d7729a](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/4d7729a32ab77de56139fa068a98c7675232e70a))

Full set of changes: [`4.5.7...4.5.8`][4.5.8]

[4.5.8]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.7...v4.5.8

## [4.5.7] - 2023-07-12

### Commits

- Simplify connecting subscription on WooCommerce payment method change. ([37a7f78](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/37a7f78b335d6d2d8f7e47ff8215b1fc08143d58))
- Added subscription to payment on payment method change. ([5628f74](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/5628f74cd24f162bfacc3b649f6a233dd5c34608))
- Updated subscription on `woocommerce_update_subscription` action instead of `save_post`. ([8e93806](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/8e93806f333a6e2d51412f31b792ce12b403174d))
- Updated order meta instead of post meta on checkout. ([360cacc](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/360caccce8ec875b7ee2d0ae482d66c2082877ff))
- Updated subscription source text to use WooCommerce Subscriptions edit post link and order number. ([d74ce20](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/d74ce205746cec3c85329d7b75e9166491838ae8))
- Use order methods for edit URL and number in source text. ([f0eb04b](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/f0eb04b9f39ed0f8d2f1338bceace2f79065ae19))
- Use order edit URL as source URL. ([ae9d4b8](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/ae9d4b8009a83be1ea275455a530f18c87631050))
- First add phase and then override next payment date. ([133a5ad](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/133a5ad1dcb7a2f95d9b7c6f11926059f9445ef3))
- Added Billie gateway. ([40f8385](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/40f83854b4130133dbab9980330ba9c82e7f9195))
- WooCommerce Subscriptions don't have period information within the renewal orders. ([96228ef](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/96228ef07331243892d20e827a91756732cc1cb8))
- Connect first subscription period to payment. ([4211a28](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/4211a28dbd92c7e62ea3ae2751c8aad1969baf24))
- Use 'start' instead of 'date_created' to fix difference in seconds. ([b04c293](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/b04c29300f634e9a1a5d8f472821c9ebc50558e9))
- Use new `get_current_period` function, we should not advance the subscription to a next period. ([fc0e71f](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/fc0e71f8587fd2ef06bcebc78aaf238621fb96ab))
- Set customer from WooCommerce subscription order. ([bb97664](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/bb97664ffcf80d069c51f762d03aa1f7e4911e4e))
- Updated subscription lines from WooCommerce subscription order. ([3d40213](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/3d40213ebf44792524b80423994471335981b690))
- Use an order helper class to retrieve/build the payment lines from a WooCommerce order. ([5bf96f1](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/5bf96f1e6e0a98625e486ef24c006ac5ee43c298))

Full set of changes: [`4.5.6...4.5.7`][4.5.7]

[4.5.7]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.6...v4.5.7

## [4.5.6] - 2023-06-01

### Commits

- Switch from `pronamic/wp-deployer` to `pronamic/pronamic-cli`. ([9d434ab](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/9d434ab09900bb64afbcab9e2106548072d2af73))
- Complete payment for order when payment is authorized. ([798f345](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/798f3452c0ee04afe45cf7fb0dd96c84c2f719f1))
- Updated .gitattributes ([542633c](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/542633cdbc7d32c1a71716ac20c1699616c581b1))

Full set of changes: [`4.5.5...4.5.6`][4.5.6]

[4.5.6]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.5...v4.5.6

## [4.5.5] - 2023-03-30

### Commits

- Fixed refunded amount check. ([e41c3ea](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/e41c3ea9b2dcb1bbed0de4be6608c9831bd458ba))

Full set of changes: [`4.5.4...4.5.5`][4.5.5]

[4.5.5]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.4...v4.5.5

## [4.5.4] - 2023-03-29
### Changed

- Extended support for refunds.

### Composer

- Changed `wp-pay/core` from `^4.6` to `v4.9.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.9.0
Full set of changes: [`4.5.3...4.5.4`][4.5.4]

[4.5.4]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.3...v4.5.4

## [4.5.3] - 2023-03-13

### Commits

- Set composer package type to "wordpress-plugin". ([2231633](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/22316332ee79d5c6333cc94a588658607f33f34b))
- Set tax percentage for free shipping items. ([61fe876](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/61fe876bf2d2d9d70435148cb027eb3a6c485cee))
- Updated .gitattributes ([a6076ad](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/a6076ad38523537a2aa50e41a45cf82325af657f))

Full set of changes: [`4.5.2...4.5.3`][4.5.3]

[4.5.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.2...v4.5.3

## [4.5.2] - 2023-02-07
### Changed

- Improved default integration arguments. ([cd7aa3c](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/cd7aa3c9300a21b2c5e72326953f8320e56900c0))


Full set of changes: [`4.5.1...4.5.2`][4.5.2]

[4.5.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.1...v4.5.2

## [4.5.1] - 2023-01-31
### Composer

- Changed `php` from `>=8.0` to `>=7.4`.
Full set of changes: [`4.5.0...4.5.1`][4.5.1]

[4.5.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.5.0...v4.5.1

## [4.5.0] - 2023-01-18
### Changed

- Improved support for authorized (afterpay) payments.

### Commits

- Set tax percentage if we there is just 1 tax rate. ([4884116](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/4884116bad3d860d944646bbf4dc6603c5448dd1))
- Happy 2023. ([bb5c112](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/bb5c1128d95c5b829e58058651424cbf8861b516))

Full set of changes: [`4.4.0...4.5.0`][4.5.0]

[4.5.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.4.0...v4.5.0

## [4.4.0] - 2022-12-23

### Commits

- Added new Riverty gateway. ([a4fd8ff](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/a4fd8fff41870cf88e03edd64b88f3e141ca115a))
- Added "Requires Plugins" header. ([bf5c03a](https://github.com/pronamic/wp-pronamic-pay-woocommerce/commit/bf5c03a2fbd44a0fa6d717e679a5c2f3e2086d09))

### Composer

- Changed `php` from `>=5.6.20` to `>=8.0`.
- Changed `wp-pay/core` from `^4.5` to `v4.6.0`.
	Release notes: https://github.com/pronamic/wp-pay-core/releases/tag/v4.3.3
Full set of changes: [`4.3.3...4.4.0`][4.4.0]

[4.4.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/v4.3.3...v4.4.0

## [4.3.3] - 2022-11-29
- Fix creating zero amount refunds. [#31](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/31)

## [4.3.2] - 2022-11-09
- Fixed "Fatal error: Uncaught Error: Call to undefined function wcs_get_subscriptions_for_order()". [#29](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/29)

## [4.3.1] - 2022-11-07
- Fixed "Fatal error: Uncaught Error: Call to undefined function wcs_get_subscription()". Props @jeffreyvr. [#28](https://github.com/pronamic/wp-pronamic-pay-woocommerce/pull/28)

## [4.3.0] - 2022-11-07
- Fixed subscription status not updated if admin reactivates a WooCommerce subscription. [#25](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/25)
- Fixed fatal error while cancelling subscription. Props @knit-pay. [#14](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/14)
- Fixed payment method field errors not displayed in WooCommerce checkout block. [#22](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/22)
- Added MobilePay payment method. [pronamic/wp-pronamic-pay-adyen#16](https://github.com/pronamic/wp-pronamic-pay-adyen/issues/16)

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
- Removed usage of non-existing `shipping_phone` order property ([#8](https://github.com/pronamic/wp-pronamic-pay-woocommerce/issues/8)).

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

[unreleased]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.3.3...HEAD
[4.3.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.3.2...4.3.3
[4.3.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.3.1...4.3.2
[4.3.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.3.0...4.3.1
[4.3.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.2.0...4.3.0
[4.2.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.1.1...4.2.0
[4.1.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.1.0...4.1.1
[4.1.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.0.1...4.1.0
[4.0.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/4.0.0...4.0.1
[4.0.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/3.0.2...4.0.0
[3.0.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/3.0.1...3.0.2
[3.0.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/3.0.0...3.0.1
[3.0.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.3.1...3.0.0
[2.3.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.3.0...2.3.1
[2.3.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.2.1...2.3.0
[2.2.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.2.0...2.2.1
[2.2.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.1.4...2.2.0
[2.1.4]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.10...2.1.0
[2.0.10]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.9...2.0.10
[2.0.9]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.8...2.0.9
[2.0.8]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.7...2.0.8
[2.0.7]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.6...2.0.7
[2.0.6]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.5...2.0.6
[2.0.5]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.4...2.0.5
[2.0.4]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.3...2.0.4
[2.0.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.2...2.0.3
[2.0.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.1...2.0.2
[2.0.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.8...2.0.0
[1.2.8]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.7...1.2.8
[1.2.7]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.6...1.2.7
[1.2.6]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.5...1.2.6
[1.2.5]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.4...1.2.5
[1.2.4]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.3...1.2.4
[1.2.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.2...1.2.3
[1.2.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.1...1.2.2
[1.2.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.2.0...1.2.1
[1.2.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.9...1.2.0
[1.1.9]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.8...1.1.9
[1.1.8]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.7...1.1.8
[1.1.7]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.6...1.1.7
[1.1.6]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.5...1.1.6
[1.1.5]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.4...1.1.5
[1.1.4]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.3...1.1.4
[1.1.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.2...1.1.3
[1.1.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.1...1.1.2
[1.1.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.1.0...1.1.1
[1.1.0]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.0.3...1.1.0
[1.0.3]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.0.2...1.0.3
[1.0.2]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.0.1...1.0.2
[1.0.1]: https://github.com/pronamic/wp-pronamic-pay-woocommerce/compare/1.0.0...1.0.1
