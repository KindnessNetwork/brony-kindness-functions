<?php
/**
 * @package Brony_Kindness_Functions
 * @version 1.7.2
 */
/*
Plugin Name: Brony Kindness Functions
Plugin URI: https://github.com/KindnessNetwork/brony-kindness-functions
Author: Brony Kindness Network, LinuxPony
Author URI: https://bronykindness.net/
Description: WordPress Plugin with miscellanies functions and shortcodes used by the Brony Kindness Network.
Version: 0.1.2
License: GNU General Public License
License URI: https://www.gnu.org/licenses/gpl.html
Text Domain: bkn
*/

define('BKN_PLUGIN_VER', '0.1.3');

class Bkn_Functions {
    public static function init() {
        add_shortcode('bkn-staff-image-grid', [static::class, 'render_staff_image_grid']);
        add_action('init', [static::class, 'register_includes']);
    }
    public static function register_includes() {
        wp_register_style("bkn-functions-style", plugins_url("functions.css", __FILE__), [], BKN_PLUGIN_VER);
    }
    public static function render_staff_image_grid($scodeArgs) {
        if(!is_plugin_active("staff-list/staff-list.php")){
            return sprintf('<p>%s</p>', __("The Staff List plugin is required, but is either not installed or not enabled.", "bkn"));
        }

        $placeholder_image = get_post_meta($scodeArgs['id'], '_pImgIDL', true);
        if(empty($placeholder_image)) {
            return sprintf('<p>%s</p>', __("Please Provide a valid staff template id, or make sure a placeholder image is specified.", "bkn"));
        }
        $default_image = @wp_get_attachment_image($placeholder_image, 'medium', false, ['class' => 'bkn-staff-picture']);
        unset($placeholder_image);

        $postIDs = abcfsl_db_all_staff_ids_sorted($scodeArgs['id'], [
            "scodeOrder" => "",
            "sortType" => "M",
            "dSort" => "",
            "dSortOrder" => "",
            "cSort" => "",
            "cSortOrder" => "",
            "scodeCat" => $scodeArgs['category'] ?? "",
            "scodeCatExcl" => "",
            "hiddenFields" => "0",
            "hiddenRecords" => "0",
            "privateFields" => "0"
        ]);

        if(count($postIDs) <= 0) {
            return sprintf('<p>%s</p>', __("No staff to display.", "bkn"));
        }

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

        wp_enqueue_style("bkn-functions-style");
        return sprintf('<div class="bkn-staff-grid">%s</div>', $items);
    }
}

Bkn_Functions::init();

