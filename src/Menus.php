<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Nav-menu rendering helpers. Thin wrappers around wp_nav_menu so templates
 * stay clean. Falls back gracefully when a menu location is unassigned.
 *
 * Locations are registered by ThemeSupport from Kit::config('menus').
 * Footer columns are derived from any configured location whose key starts
 * with "footer".
 */
final class Menus
{
    public static function init(): void
    {
        add_filter('nav_menu_item_title', [self::class, 'itemIcon'], 10, 2);
    }

    /**
     * Prepend an inline icon to menu items carrying an `icon-*` CSS class
     * (set per-item in Appearance → Menus). Currently: icon-mail.
     */
    public static function itemIcon(string $title, object $item): string
    {
        $classes = (array) ($item->classes ?? []);

        if (in_array('icon-mail', $classes, true)) {
            $svg = '<svg class="menu-item__icon" width="14" height="11" viewBox="0 0 13.333 10" fill="currentColor" aria-hidden="true"><path transform="translate(0 -3)" d="M0,3V13H13.333V3ZM3.679,7.405,1.111,10.578V5.324L3.679,7.405Zm-2.3-3.294H11.954L6.667,8.4,1.379,4.111ZM4.542,8.1,6.667,9.826,8.794,8.1l3.118,3.787H1.479Zm5.116-.7,2.564-2.078v5.193L9.658,7.4Z"/></svg>';
            return $svg . '<span class="menu-item__label">' . $title . '</span>';
        }

        return $title;
    }

    public static function primaryNav(array $args = []): void
    {
        self::render('primary', array_merge([
            'menu_class'      => 'nav__list',
            'container'       => 'nav',
            'container_class' => 'nav nav--primary',
            'depth'           => 0,
            'fallback_cb'     => false,
        ], $args));
    }

    public static function utilityNav(array $args = []): void
    {
        self::render('utility', array_merge([
            'menu_class'      => 'topbar__nav-list',
            'container'       => 'nav',
            'container_class' => 'topbar__nav',
            'depth'           => 1,
            'fallback_cb'     => false,
        ], $args));
    }

    public static function footerNav(array $args = []): void
    {
        self::render('footer', array_merge([
            'menu_class'      => 'footer-nav__list',
            'container'       => 'nav',
            'container_class' => 'footer-nav',
            'depth'           => 1,
            'fallback_cb'     => false,
        ], $args));
    }

    /**
     * Footer menu columns. Each assigned footer location renders as a column
     * whose heading is the menu's name.
     */
    public static function footerColumns(): void
    {
        $locations = get_nav_menu_locations();

        foreach (self::footerLocations() as $location) {
            if (!has_nav_menu($location)) {
                continue;
            }

            $menu = wp_get_nav_menu_object($locations[$location]);

            echo '<div class="site-footer__col">';
            if ($menu) {
                echo '<h2 class="site-footer__heading">' . esc_html($menu->name) . '</h2>';
            }
            wp_nav_menu([
                'theme_location'  => $location,
                'menu_class'      => 'footer-nav__list',
                'container'       => 'nav',
                'container_class' => 'footer-nav',
                'depth'           => 1,
                'fallback_cb'     => false,
            ]);
            echo '</div>';
        }
    }

    /** @return string[] configured locations whose key starts with "footer". */
    private static function footerLocations(): array
    {
        $menus  = (array) Kit::config('menus', []);
        $footer = array_values(array_filter(
            array_keys($menus),
            static fn (string $key): bool => str_starts_with($key, 'footer')
        ));

        return $footer ?: ['footer', 'footer_2', 'footer_3'];
    }

    private static function render(string $location, array $args): void
    {
        if (!has_nav_menu($location)) {
            return;
        }
        wp_nav_menu(array_merge(['theme_location' => $location], $args));
    }
}
