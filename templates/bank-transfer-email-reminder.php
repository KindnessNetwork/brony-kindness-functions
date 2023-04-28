<?php
/**
 * Bank Transfer Email Reminder
 *
 * Based on the Customer note email template
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 3.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php /* translators: %s: Customer first name */ ?>

<p><?php esc_html_e('Hello,', BKN_PLUGIN_SLUG) ?></p>

<p><?php printf(
    esc_html__('Order #%s was placed on %s with the total amount %s by user %s via payment method "%s"', BKN_PLUGIN_SLUG),
    $order->get_id(),
    $order->get_date_created()->setTimezone(new DateTimeZone(wp_timezone_string()))->date('l, F jS, Y \a\\t h:i A') . ' (' . wp_timezone_string() . ')',
    $order->get_formatted_order_total(),
    esc_html($order->get_billing_email()),
    $order->get_payment_method_title()
); ?></p>

<p><?php esc_html_e('The order is currently marked as "On Hold" - likely because the customer paid via bank transfer', BKN_PLUGIN_SLUG) ?></p>

<p><?php printf(
    esc_html__('Please check your business bank account for a transaction of at least %s containing the reference #%s or any other reference which appears to link back to this order.', BKN_PLUGIN_SLUG),
    $order->get_formatted_order_total(),
    $order->get_id(),
); ?></p>

<p><?php printf(
    esc_html__('If the transaction has been received, please %slog into the WooCommerce admin panel%s, mark order #%s as "Processing", and then ship the item as soon as possible. Once the item is shipped, you can change the order status to "Completed".', BKN_PLUGIN_SLUG),
    '<a href="' . get_admin_url(null, '/post.php?post=' . $order->get_id() . '&action=edit') . '">',
    '</a>',
    $order->get_id()
); ?></p>

<p><?php esc_html_e('If the transaction has NOT been received, you\'ll receive another automated reminder email in 3 days from now.', BKN_PLUGIN_SLUG) ?></p>

<p><?php esc_html_e('Thank you! This is an automated email from your WooCommerce store, do not reply to this email.', BKN_PLUGIN_SLUG) ?></p>

<hr>

<h2><?php esc_html_e('Order Details', BKN_PLUGIN_SLUG); ?></h2>

<?php

/*
 * @hooked WC_Emails::order_details() Shows the order details table.
 * @hooked WC_Structured_Data::generate_order_data() Generates structured data.
 * @hooked WC_Structured_Data::output_structured_data() Outputs structured data.
 * @since 2.5.0
 */
do_action('woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::order_meta() Shows order meta data.
 */
do_action('woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email);

/*
 * @hooked WC_Emails::customer_details() Shows customer details
 * @hooked WC_Emails::email_address() Shows email address
 */
do_action('woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email);

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/*
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );