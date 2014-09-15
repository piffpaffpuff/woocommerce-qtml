=== WooCommerce qTML ===

Contributors: franticpsyx
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=psyx@somewherewarm.net&item_name=Donation+for+WooCommerce+qTML
Tags: woocommerce, qtranslate, mqtranslate, wc-qtml, woocommerce-qtml
Requires at least: 3.7.0
Tested up to: 4.0.0
Stable tag: 2.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add (m)qTranslate support to WooCommerce.

== Description ==

WooCommerce-qTML:

1. Ensures that (m)qTranslate will retain the selected language when moving between WooCommerce products, pages and endpoints.
2. Prevents switching to the default language in many cases of redirection (after posting product reviews, when returning to the shop after paying/cancelling an order, etc).
3. Adds qTranslate shortcode support to: product attributes, product category names and descriptions, tax descriptions, payment gateways, shipping methods.
4. Supports localized e-mail notifications for new orders, order status updates, new order notes and invoices by storing the active language every time an order is placed.


WooCommerce-qTML is provided with the following limitations:

* As expected, WooCommerce-qTML helps keep the front-end of your shop properly localized. However, depending on your theme and installed WooCommerce extensions, certain strings or parts of your website might not be translated as expected.
* The admin area is not streamlined for multilingual input. No separate input boxes exist for multilingual input – instead, WooCommerce categories & attributes must be entered using (m)qTranslate tags/shortcodes, e.g. [:en]Color[:de]Farbe].
* Support for localized customer e-mails is included since version 1.0.0. However, extensions and 3rd party plugins that add content to e-mail notifications might not work out of the box. If you need assistance with a particular WooCommerce extension, please contact us for a quote.
* qTranslate does *not* include proper support for HTTPS/protocol-less URLs. If you have an SSL certificate installed and want to use HTTPS in your WooCommerce store, you will need to tweak qTranslate in order to support viewing checkout/account pages securely.
* WooCommerce-qTML is only recommended for use by developers, since, in most cases, customizations need to be made according to the needs of each project. The plugin is a good head-start that will save developers a few hours of research – it is not a complete multi-language solution!

Always use the plugin with the latest versions of WooCommerce/Wordress/(m)qTranslate. Using the plugin with older/newer versions of WooCommerce/WordPress/(m)qTranslate is not recommended, since new versions always introduce changes that may break the translation of a few strings, or, in extreme cases, even entire pages. You can always give it a shot and see if another combination works for you. However, we strongly encourage you to:

1. Always backup your entire website before updating WooCommerce or WordPress.
2. Check that a new version of WooCommerce-qTML exists before updating WC or WP and verify that the version you are upgrading to is supported.

WooCommerce-qTML will be updated as long as (m)qTranslate and WooCommerce are active and maintained.

Developers can checkout and contribute to the source code on the plugin's [GitHub Repository](https://github.com/franticpsyx/woocommerce-qtml/).


== Installation ==

1. Upload the plugin to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress


== Changelog ==

= 2.0.10 =
* Fix - 'get_current_screen' error when saving order.

= 2.0.9 =
* Fix - Do not translate terms when creating / editing.

= 2.0.8 =
* Fix - Support for Composite Products v2.4.0.

= 2.0.7 =
* Updated readme info regarding HTTPS. Updated plugin description.

= 2.0.6 =
* Fix - Tax name translation.

= 2.0.4 =
* Fix - Do not remove qTranslate admin options.
* Compatibility - Add support for mqtranslate (untested).
* Compatibility - WooCommerce 2.1.12, WP 3.9.1.

= 2.0.2 =
* Fix - COD description and instructions translation.
* Compatibility - WooCommerce 2.1.10, WP 3.9.1.

= 2.0.1 =
* Fix - woocommerce_attribute translation rollback.
* Compatibility - WooCommerce 2.1.9, WP 3.9.1.

= 2.0.0 =
* .org release
* refactored based on im8-woocommerce-qtranslate structure

= 1.2.0 =
* WC 2.1 support
* shipping methods in WooCommerce 2.1 support multilingual names by using qTranslate tags

= 1.1.7 =
* rollback - when using pre-path qTranslate mode, never tick the "hide language information" option

= 1.1.6 =
* small pre-path mode improvements
* prevent admin language switching

= 1.1.5.5 =
* auto update change

= 1.1.5 =
* additional filters

= 1.1.4 =
* bug fixes

= 1.1.2 =
* bug fixes

= 1.1.1 =
* esc_url tweaks
* SSL improvements in pre-path mode

= 1.1.0 =
* Fixed multilingual e-mail text domain switching
* Fixed esc_url filter
* Fixed js

= 1.0.4 =
* woocommerce_order_item_display_meta_value filter

= 1.0.3 =
* General cleanup

= 1.0.2 =
* Fix wp-admin redirect
* Fix get_current_screen notice in compatible WP versions

= 1.0.1 =
* Version bump & minor fixes
* Experimental ML e-mail support

= 0.21 =
* Auto update

= 0.20 =
* Added pre-path URL mode support
* Added extensive WC back-end translation support for attributes and product titles

= 0.17 =
* Back-end attributes / variations are properly translated
* Back-end language can be changed

= 0.15 =
* Product categories / terms back-end fix

= 0.14 =
* Fix for ajax add to cart

= 0.13 =
* Fix for admin area menu

= 0.12 =
* Support for WordPress v3.4.1, qTranslate v2.5.31 & Woocommerce v1.6.1 verified
* Fix for admin area translations

= 0.11 =
* Initial release
* Support for WordPress v3.3.2, qTranslate v2.5.29 & Woocommerce v1.6.0

== Upgrade Notice ==

= 2.0.10 =
Fixes 'get_current_screen' error when saving order.
