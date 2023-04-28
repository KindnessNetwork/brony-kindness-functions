<?php
/**
 * @package Brony_Kindness_Functions
 * @version 0.2.0
 * @noinspection PhpIllegalPsrClassPathInspection, PhpMissingParamTypeInspection
 */
/*
Plugin Name: Brony Kindness Functions
Plugin URI: https://github.com/KindnessNetwork/brony-kindness-functions
Author: Brony Kindness Network, LinuxPony
Author URI: https://bronykindness.net/
Description: WordPress Plugin with miscellanies functions and shortcodes used by the Brony Kindness Network.
Version: 0.2.0
License: GNU General Public License
License URI: https://www.gnu.org/licenses/gpl.html
Text Domain: bkn
*/

define('BKN_PLUGIN_VER', '0.2.0');
define('BKN_PLUGIN_SLUG', 'bkn');

class Bkn_Functions {

    /**
     * Initialize and register all necessary hooks
     */
    public static function init() {
        // Required to load this early so we can call is_plugin_active()
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }

        // On Hold Email Reminders
        if (is_plugin_active('woocommerce/woocommerce.php')) {
            add_action(BKN_PLUGIN_SLUG . '_check_orders_and_maybe_send_emails', array(static::class, 'check_orders_and_maybe_send_emails'));
            static::schedule_email_check();
            add_action('admin_menu', array(static::class, 'register_admin_menu'));
        } else {
            add_action('admin_notices', array(static::class, 'woocommerce_not_available'));
        }
        register_deactivation_hook(__FILE__, array(static::class, 'plugin_deactivation_hook'));

        // Staff Grid Shortcode
        add_shortcode('bkn-staff-image-grid', [static::class, 'render_staff_image_grid']);
        add_action('init', [static::class, 'register_includes']);

    }

    /**
     * register styles that will be included on the site frontend
     */
    public static function register_includes() {
        wp_register_style("bkn-functions-style", plugins_url("functions.css", __FILE__), [], BKN_PLUGIN_VER);
    }

    /**
     * A short code that hooks into the data in the staff-list plugin and displays a custom staff grid
     *
     * @param $args array the arguments passed in from the call to the shortcode.
     *      $args = [
     *          'id'        => (int) Staff template ID to display staff from.
     *          'category'  => (string) The target category to display staff from.
     *      ]
     * @return string the HTML code to embed on the page for this shortcode.
     */
    public static function render_staff_image_grid($args) {
        // Verify the staff list plugin is active
        if(!is_plugin_active("staff-list/staff-list.php")){
            return sprintf('<p>%s</p>', __("The Staff List plugin is required, but is either not installed or not enabled.", BKN_PLUGIN_SLUG));
        }

        // Get the placeholder image for the provided staff template ID. This is a sanity check to make sure it exists.
        // We do also need this in case a staff member is without a picture.
        $placeholder_image = get_post_meta($args['id'], '_pImgIDL', true);
        if(empty($placeholder_image)) {
            return sprintf('<p>%s</p>', __("Please Provide a valid staff template id, or make sure a placeholder image is specified.", BKN_PLUGIN_SLUG));
        }
        $default_image = @wp_get_attachment_image($placeholder_image, 'medium', false, ['class' => 'bkn-staff-picture']);
        unset($placeholder_image);

        // Get the Post IDs of the staff members that match our query using the built in method.
        $postIDs = abcfsl_db_all_staff_ids_sorted($args['id'], [
            "scodeOrder" => "",
            "sortType" => "M",
            "dSort" => "",
            "dSortOrder" => "",
            "cSort" => "",
            "cSortOrder" => "",
            "scodeCat" => $args['category'] ?? "",
            "scodeCatExcl" => "",
            "hiddenFields" => "0",
            "hiddenRecords" => "0",
            "privateFields" => "0"
        ]);

        // Nothing returned? Nothing to do, return early.
        if(count($postIDs) <= 0) {
            return sprintf('<p>%s</p>', __("No staff to display.", BKN_PLUGIN_SLUG));
        }

        // Start the main rendering loop
        $items = '';

        foreach ($postIDs as $id) {
            $post_meta = get_post_meta($id);
            $image = @wp_get_attachment_image($post_meta['_imgIDL'][0], 'medium', false, ['class' => 'bkn-staff-picture']);
            if(empty($image)) {
                $image = $default_image;
            }

            $image = sprintf('<div class="bkn-staff-picture-wrapper">%s</div>', $image);

            $link_url = $post_meta['_mp2_F7'][0] ?? "#";
            $link_icon = ($post_meta['_mp1_F7'][0] ?? "fas fa-globe") . ' fa-fw';

            /** @noinspection HtmlUnknownTarget */
            $link = sprintf('<div class="bkn-staff-link"><a href="%s"><i class="%s"></i> %s</a></div>', $link_url, $link_icon, $post_meta['_mp1_F1'][0]);
            $items .= sprintf('<div class="bkn-staff">%s</div>',$image . $link);
        }

        // We now have our rendered block. Enqueue the style we previously registered, and return the block.
        wp_enqueue_style("bkn-functions-style");
        return sprintf('<div class="bkn-staff-grid">%s</div>', $items);
    }

    /**
     * Displays an error at the top of the admin page that says WooCommerce isn't active.
     */
    public static function woocommerce_not_available() {
        printf('<div class="error notice is-dismissible notice-info"><p><span>%s</span></p></div>',
            wptexturize(__('WooCommerce is not active. Please install and activate WooCommerce to use the Brony Kindness Functions plugin.', BKN_PLUGIN_SLUG))
        );
    }

    /**
     * Registers our cron task to monitor and send emails
     */
    public static function schedule_email_check() {
        $schedules = wp_get_schedules();
        if($schedules['hourly'] && !wp_next_scheduled(BKN_PLUGIN_SLUG . '_check_orders_and_maybe_send_emails')){
            wp_schedule_event(time() + $schedules['hourly']['interval'], 'hourly', BKN_PLUGIN_SLUG . '_check_orders_and_maybe_send_emails');
        }
    }

    /**
     * Plugin Activation Hook
     */
    public static function plugin_deactivation_hook() {
        static::unschedule_email_check();
    }

    /**
     * Attempts to deregister the product sync.
     */
    public static function unschedule_email_check() {
        $next_timestamp = wp_next_scheduled(BKN_PLUGIN_SLUG . '_check_orders_and_maybe_send_emails');
        if ($next_timestamp){ // If sync is scheduled
            wp_unschedule_event($next_timestamp, BKN_PLUGIN_SLUG . '_check_orders_and_maybe_send_emails');
        }
    }

    /**
     * Checks all on hold bank transfer orders and send out emails as appropriate.
     */
    public static function check_orders_and_maybe_send_emails() {
        // Grab all on-hold orders
        $on_hold_orders = wc_get_orders(array(
            'limit' => -1,
            'status' => 'on-hold',
        ));

        foreach ($on_hold_orders as $order) {
            // Ignore on hold orders that are not Direct bank transfer (id basc)
            if($order->get_payment_method() !== 'bacs') continue;

            $should_send = false;
            $last_email = $order->get_meta('_last_email_update', true); // timestamp or false

            // If no last email was sent and it's been at least an hour since the order, we should send an email.
            if($last_email == false && $order->get_date_created()->diff(new DateTime('now'))->d >= 1) {
                $should_send = true;
            } else {
                // If there was an email sent before, check if it was more then 3 days ago. If so, we should send another.
                $last_email = DateTime::createFromFormat('U', $last_email);
                if ($last_email->diff(new DateTime('now'))->d >= 3) {
                    $should_send = true;
                }
            }

            if (!$should_send) continue;

            // load the mailer class
            $mailer = WC()->mailer();

            //format the email
            $recipient = get_option('bank_transfer_reminder_email') ?? get_option('admin_email');
            $subject = sprintf(__("[%s]: Please check your bank account for order #%s", BKN_PLUGIN_SLUG), get_bloginfo( 'name' ), $order->get_id());
            $content = wc_get_template_html('bank-transfer-email-reminder.php', array(
                'order'         => $order,
                'email_heading' => sprintf(__("Please check your bank account for order #%s", BKN_PLUGIN_SLUG), $order->get_id()),
                'sent_to_admin' => false,
                'plain_text'    => false,
                'email'         => $mailer
            ), '', untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/');
            $headers = "Content-Type: text/html\r\n";

            //send the email through wordpress
            $success = $mailer->send($recipient, $subject, $content, $headers);
            if($success) {
                update_post_meta($order->get_id(), '_last_email_update', (new DateTime('now'))->getTimestamp());
            }
        }
    }

    /**
     * Register a settings page
     */
    public static function register_admin_menu() {
        add_menu_page('BKN Functions', 'BKN Functions', 'manage_options', 'bkn-functions', array(static::class, 'render_admin_menu'));
    }

    /**
     * Settings page rendering
     */
    public static function render_admin_menu() {
        global $title;
        print "<div class='wrap'><h1>$title</h1>";
        print "<p>Nothing here yet</p>";
//        static::check_orders_and_maybe_send_emails();
        print '</div>';
    }
}

// Initialize to register our hooks.
Bkn_Functions::init();

