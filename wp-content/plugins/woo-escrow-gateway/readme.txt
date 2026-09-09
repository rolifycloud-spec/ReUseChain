=== Escrow Payments on WooCoomerce ===
Contributors: escrowdotcom
Tags: escrow, payment, gateway, ecommerce, woocommerce
Requires at least: 6.5
Tested up to: 6.8.1
WC requires at least: 8.5.0
WC tested up to: 9.9.5
Requires PHP: 8.0
Stable tag: trunk
License: GNU General Public License v2.0 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The Escrow Payments on WooCoomerce extension allows you to add Escrow.com payments to your WooCommerce store.

== Description ==
Escrow.com provides a secure payment solution for your website, marketplace, classified site, shopping cart or mobile app with no chargebacks, ever.

Escrow.com is especially useful in situations where buyers and sellers do not know each other, do not trust each other, or where large sums of money are exchanging hands. Rates are as low as **0.89%**.

This plugin allows you to add Escrow.com as a payment option on your checkout page in WooCommerce.

== Installation ==

1. Install the **Escrow Payments on WooCoomerce** extension directly via the WordPress plugin directory or download the latest from [our plugin page on WordPress.org](https://wordpress.org/plugins/woo-escrow-gateway/) and upload the **woocommerce-gateway-escrow.zip** file via the WordPress plugin upload page.
1. Activate the plugin through the **Plugins** menu in WordPress.
1. Once you have activated the plugin, navigate to the settings page under **WooCommerce** / **Settings** / **Payments** / **Escrow.com**.
1. The settings page allows you to configure the plugin to your liking.
1. Your Escrow Email is the email address you used to create an account on either escrow.com or escrow-sandbox.com. The escrow.com environment is the production environment and is used for live transactions. The escrow-sandbox.com environment is for testing. Make sure the credentials you enter match the selected environment. These are two separate environments, so creating an account in one environment does not mean that it will be duplicated in the other environment.
1. [Create a production account here](https://www.escrow.com/integrations/signup) and [create a sandbox account here](https://www.escrow-sandbox.com/integrations/signup).
1. Enable the Escrow.com payment option on your checkout page by checking the **Enable Escrow.com Payment Method** check box on the settings page and clicking **Save** at the bottom of the screen.

Further information may be found at:

[www.escrow.com/plugins/woocommerce](https://www.escrow.com/plugins/woocommerce)

== Frequently Asked Questions ==
= What happens after an order has been placed? =

Escrow.com is an offline payment method. Once an order has been placed, the buyer will be instructed to log into Escrow.com to submit payment and get their identity verified if applicable.

As a WooCommerce store owner, you are either the seller in the transaction in single-vendor scenarios or the broker in multi-vendor scenarios. In both of these cases, you will need to log into Escrow.com to move each order forward. In multi-vendor scenarios, each vendor will also need to participate in each transaction that they are a party to in order for those transactions to move forward.

= An error occurs when someone places an order with the Escrow.com payment option. What do I do? =

If an error occurs when a user places an order with the Escrow.com payment option selected, open the WooCommerce admin and navigate to **Status** and then **Logs**. This plugin writes debug and error messages to these logs. They may be under **fatal-** or **log-**. If a **401** or **403** error is being returned from the Escrow.com API, then there is a problem with the Escrow.com credentials. If a **500** error is being returned, there is a problem on the Escrow.com server. When that happens, please email [plugins@escrow.com](mailto:plugins@escrow.com) and we will investigate.

== Privacy Policy ==
Our privacy policy may be found at:

[www.escrow.com/escrow-101/privacy-policy](https://www.escrow.com/escrow-101/privacy-policy)

To assist the customer service process around plugin usage, this plugin sends a notification to Escrow.com upon plugin activation and deactivation. This data is used in accordance with the above privacy policy.

== Changelog ==

= 3.0.1 =
* Updated plugin metadata.

= 3.0.0 =
* Fixed compatibility issues with HPOS and PHP 8.
* Added support for additional Multi-Vendor extensions (free versions).
* Added Canadian Dollar (CAD) as an option in the Currency drop-down on the Transaction Settings page. When selected, all new transactions will now be denominated in CAD.

= 2.4.1 =
* Fixed a bug where commission type is not valid when Multi-Vendor Extension is enabled and WC Vendor Pro is being used.

= 2.4.0 =
* Added a fix when calling the function get_cart in woocommerce admin view. Change the plugin name to Escrow Payments on WooCoomerce.

= 2.3.0 =
* The British pound (GBP) has been added to the Currency drop down list on the Escrow.com settings page. When selected all transactions will be denominated in GBP.

= 2.2.0 =
* The Australian dollar (AUD) has been added to the Currency drop down list on the Escrow.com settings page. When selected all transactions will be denominated in AUD.

= 2.1.0 =
* The buyer's address is no longer required by the Escrow.com extension. If you wish to disable the address fields on your checkout page, you may now make this change to your WooCommerce installation.

= 2.0.0 =
* Buyers placing orders on the WooCommerce checkout page are now redirected to the new streamlined Escrow.com Pay workflow. This allows the buyer to register, verify, and pay all from a single page on Escrow.com. This replaces the old workflow which unnecessarily bounced the buyer around to various parts of Escrow.com.
* When the shopping cart contains products from a single vendor at the time of checkout, the buyer is redirected directly to Escrow.com, not to the WooCommerce order receipt page (the buyer used to have to click through to Escrow.com from here).
* When the shopping cart contains products from multiple vendors at the time of checkout, the buyer is redirected to the WooCommerce order receipt page, which displays a button to the Escrow.com Pay workflow for each vendor in the order.
* The Escrow.com Pay workflow redirects the buyer back to the WooCommerce order detail page when they have finished paying for their order on Escrow.com.
* To avoid a potential issue when upgrading to version 2.0.0, please re-save your settings on the Escrow.com settings page. Make sure that the desired Escrow API URL is selected; note that this now contains only the root URL (e.g. https://api.escrow.com/), not any additional path information (e.g. https://api.escrow.com/2017-09-01/).
* More information about the Escrow.com Pay workflow may be found at [www.escrow.com/pay](https://www.escrow.com/pay)

= 1.5.1 =
* Fixed a bug where a non-one-day inspection period would lead to an HTTP 422 exception when tax was charged on the checkout page.

= 1.5.0 =
* Milestone transactions are now supported. See **Transaction Type** on the Escrow.com settings page.

= 1.4.1 =
* Item description now contains the perma-link to the product in WooCommerce instead of repeating the item name. The **Tax** item is now more accurately called **Tax collected by seller**.

= 1.4.0 =
* Tax total from shopping cart now may be included as a line item in Escrow.com transaction. See **Tax Options** on the Escrow.com settings page.

= 1.3.0 =
* Address data of the buyer is now sent to Escrow.com when an order is placed. This means that new buyers will not need to re-enter address data on Escrow.com. The address data of existing Escrow.com users is not overwritten.

= 1.2.1 =
* Use smaller Escrow.com icon. This works better on themes that do not automatically resize the icons on the checkout page.

= 1.2.0 =
* Buyer experience streamlined via automatic agreement by the vendor and addition of payment button on order receipt that redirects buyer into payment wizard on Escrow.com.

= 1.1.1 =
* Added Escrow.com icon to Escrow.com payment option on checkout page.

= 1.1.0 =
* WC Vendors Pro **Fixed**, **Fixed + Fee**, **Percentage**, and **Percentage + Fee** scenarios are now supported. Commissions set in WC Vendors Pro take precedence over commissions set in WC Vendors which take precedence over commissions set on the Escrow.com settings page.

= 1.0.3 =
* Added setting to display the Escrow.com payment option on the checkout page only when all the items in the shopping cart have been tagged with a custom product attribute named **escrowable** where the value is set to **true**.

= 1.0.2 =
* WooCommerce order status now correctly updates to **Processing** upon order creation.

= 1.0.1 =
* Debug and error messages now written to the WooCommerce logs. These logs
may be viewed in the WooCommerce admin under **WooCommerce** / **Status** / **Logs**.

= 1.0.0 =
* Initial Release
