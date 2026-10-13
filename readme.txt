=== PK Manual Payments for WooCommerce ===
Contributors: your-wporg-username
Tags: woocommerce, bank transfer, manual payment, pakistan, mobile wallet
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept bank transfer and mobile wallet payments in WooCommerce with payment instructions and a customer transaction ID field.

== Description ==

Many Pakistani stores take payment by bank transfer, JazzCash or Easypaisa, then confirm each payment by hand. This plugin gives that workflow a proper place in WooCommerce checkout.

Customers choose how they paid, see your account details and instructions, and enter their transaction ID. You get the details on the order screen and in the new-order email, then mark the order paid once you have checked it.

**Features**

* Three payment options in one gateway: JazzCash, Easypaisa and bank transfer. Switch each on or off.
* Account title, account number or IBAN, bank name and extra instructions for each option.
* Customer transaction ID field, required or optional.
* Optional "sent from" number or account field.
* Duplicate check: reject a transaction ID that is already attached to another live order.
* Instructions repeated on the thank-you page and in the on-hold order email, with the order number as the payment reference.
* Submitted method, transaction ID and sender shown on the admin order screen and in the admin new-order email.
* Transaction ID column in the Orders list, and search orders by transaction ID.
* "Verify manual payment and mark as paid" order action. It marks the order paid, records who verified it and when, adds an order note and sends the usual customer emails.
* Works with the block-based Cart and Checkout and the classic shortcode checkout.
* Compatible with High-Performance Order Storage (HPOS).

**Important**

This plugin does not connect to any payment provider and does not verify payments automatically. It collects the customer's transaction ID so you can check it against your own bank or wallet statement. JazzCash and Easypaisa are trademarks of their respective owners. This plugin is not affiliated with or endorsed by them.

== Installation ==

1. Install and activate WooCommerce.
2. Upload the plugin folder to `/wp-content/plugins/`, or install it from the Plugins screen, and activate it.
3. Go to WooCommerce > Settings > Payments > Manual Payments (Pakistan).
4. Enable the gateway, switch on the payment methods you accept and fill in your account details.
5. Place a test order to see the checkout.

== Frequently Asked Questions ==

= Does it verify payments automatically? =

No. You check the transaction ID against your own account, then change the order status yourself.

= Does it use the JazzCash or Easypaisa API? =

No. It is a manual workflow. Customers send money using their own app and enter the transaction ID at checkout.

= Does it work with the block checkout? =

Yes. It supports both the block-based checkout and the classic shortcode checkout.

= Where do I see the transaction ID? =

In a column on the Orders list, on the order edit screen under the billing address, in the order notes, and in the new-order email sent to the store owner.

= Can I search orders by transaction ID? =

Yes. Type the transaction ID into the search box on the Orders screen.

= How do I confirm a payment? =

Check the transaction ID against your bank or wallet statement. Then open the order and choose "Verify manual payment and mark as paid" in the Order actions box and click Update. The order moves to Processing (or Completed for virtual orders), and a note records who verified it.

= Can I stop customers reusing a transaction ID? =

Yes. The duplicate check is on by default. Cancelled and failed orders are ignored, so a customer can retry after a cancelled order.

= What happens to the order after checkout? =

It is set to On hold by default, with stock reduced. You can switch this to Pending payment in the settings.

== Changelog ==

= 1.1.0 =
* New: Transaction ID column in the Orders list (HPOS and legacy screens).
* New: Search orders by transaction ID.
* New: "Verify manual payment and mark as paid" order action, with verifier and time saved on the order.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.0 =
Adds a transaction ID column, search by transaction ID and a verify-and-mark-paid order action.

= 1.0.0 =
Initial release.
