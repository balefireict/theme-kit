<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Registers widget areas (sidebars).
 */
final class Sidebar
{
    public static function init(): void
    {
        add_action('widgets_init', [self::class, 'register']);
    }

    public static function register(): void
    {
        register_sidebar([
            'name'          => __('Primary Sidebar', 'theme-kit'),
            'id'            => 'sidebar-primary',
            'description'   => __('Main sidebar shown on blog and archive pages.', 'theme-kit'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget__title">',
            'after_title'   => '</h2>',
        ]);
    }
}
