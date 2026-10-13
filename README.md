# PK Manual Payments for WooCommerce

Accept **bank transfer, JazzCash and Easypaisa** payments in WooCommerce with payment instructions and a customer **transaction ID** field. You verify each payment manually, then mark the order paid.

## Features

- JazzCash, Easypaisa and bank transfer in one gateway, each switched on or off
- Account title, number/IBAN, bank name and extra instructions per method
- Customer transaction ID field (required or optional) and optional "sent from" field
- Duplicate check: rejects a transaction ID already used on another live order
- Instructions on the thank-you page and the on-hold email, with the order number as reference
- Submitted details on the admin order screen and in the admin new-order email
- Transaction ID column in the Orders list, and search orders by transaction ID
- "Verify manual payment and mark as paid" order action that records who verified and when
- Works with the block Cart/Checkout and the classic checkout
- HPOS compatible

> This plugin does not connect to any payment provider and does not verify payments automatically. JazzCash and Easypaisa are trademarks of their owners; this project is not affiliated with them.

## Requirements

WordPress 6.5+, PHP 7.4+, WooCommerce 8.0+

## Install

1. Download the latest release zip, or zip the `pk-manual-payments` folder.
2. In WordPress go to **Plugins → Add New → Upload Plugin** and activate it.
3. Go to **WooCommerce → Settings → Payments → Manual Payments (Pakistan)** and enter your account details.

## Structure

```
pk-manual-payments.php             bootstrap, HPOS/blocks compatibility
includes/class-pkmp-gateway.php    gateway, validation, emails, admin display
includes/class-pkmp-blocks-support.php   block checkout integration
includes/class-pkmp-admin.php      Orders column, search, verify action
assets/js/blocks.js                block checkout UI
assets/js/classic.js               classic checkout state handling
```

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
