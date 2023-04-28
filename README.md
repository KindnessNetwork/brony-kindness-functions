# Brony Kindness Functions

WordPress Plugin with miscellanies functions and shortcodes used by the Brony
Kindness Network.

## Features

Features included in this plugin:

### Staff Grid Shortcode

The custom short `bkn-staff-image-grid` code to use the data provided by the
Staff List plugin to display a custom grid of staff. Supports only the
parameters `id` and `category`.

Example usage: `[bkn-staff-image-grid id=1 category=Artists]`

### On Hold Email Reminders

Uses WordPress Cron to check the pending WooCommerce orders every hour for On
Hold orders. If the payment type is direct bank transfer, an email is sent out
after the first 24 hours, and then every 3 days after that, until the order is
no longer on hold.

## Hard dependencies

These plugins are required to use this plugin.

 - [Staff List](https://wordpress.org/plugins/staff-list/) (Free Version)
 - [WooCommerce](https://wordpress.org/plugins/woocommerce/) 
 
<small>Licensed under the GPL.</small>