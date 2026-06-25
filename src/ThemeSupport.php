<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Core theme supports + nav-menu registration + excerpt helper.
 *
 * Config (via Kit::boot):
 *   'menus'        => [ location => label, ... ]   (registered nav locations)
 *   'textdomain'   => string                       (loaded from /languages)
 *   'image_sizes'  => [ name => [w, h, crop], ... ] (optional)
 *   'supports'     => [ feature => args|true, ... ] (optional override of defaults)
 */
final class ThemeSupport
{
    public static function init(): void
    {
        add_action('after_setup_theme', [self::class, 'setup']);
    }

    public static function setup(): void
    {
        $supports = (array) Kit::config('supports', [
            'title-tag'        => true,
            'post-thumbnails'  => true,
            'html5'            => ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'],
            'automatic-feed-links' => true,
            'customize-selective-refresh-widgets' => true,
            'responsive-embeds' => true,
        ]);
        foreach ($supports as $feature => $args) {
            $args === true ? add_theme_support($feature) : add_theme_support($feature, $args);
        }

        $menus = (array) Kit::config('menus', []);
        if ($menus) {
            register_nav_menus($menus);
        }

        foreach ((array) Kit::config('image_sizes', []) as $name => $size) {
            add_image_size($name, $size[0] ?? 0, $size[1] ?? 0, $size[2] ?? false);
        }

        $textdomain = (string) Kit::config('textdomain', '');
        if ($textdomain !== '') {
            load_theme_textdomain($textdomain, get_template_directory() . '/languages');
        }
    }

    public static function excerpt(int $words = 30): string
    {
        return wp_trim_words(get_the_excerpt(), $words, '&hellip;');
    }
}
