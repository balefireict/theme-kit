<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Trim WordPress head bloat. Conservative — only safe, common removals.
 */
final class Cleanup
{
    public static function init(): void
    {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        remove_action('wp_head', 'rsd_link');
        remove_action('wp_head', 'wp_shortlink_wp_head');
        remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10);

        // Disable emojis.
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');

        // Strip srcset/sizes from SVGs (WP generates bogus responsive attrs).
        add_filter('wp_get_attachment_image_attributes', static function (array $attr): array {
            if (isset($attr['src']) && str_ends_with(strtolower($attr['src']), '.svg')) {
                unset($attr['srcset'], $attr['sizes']);
            }
            return $attr;
        });
    }
}
