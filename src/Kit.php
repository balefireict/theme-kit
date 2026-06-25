<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Balefire theme-kit bootstrap.
 *
 * A site boots the kit once from functions.php with its config:
 *
 *   Balefire\Kit::boot([
 *       'handle'     => 'vmg',
 *       'textdomain' => 'vmg',
 *       // config-coupled modules land in later slices:
 *       //   dev_host, vite_entries, fonts, favicon_path,
 *       //   menu_locations, image_sizes, conditional_styles, ...
 *   ]);
 *
 * Modules:
 *   Config-free  — Partials · Pagination · Search · Cleanup · Comments · Sidebar · RelatedPosts
 *   Config-coupled — Assets · ThemeSupport · Menus · Options · Admin (read via self::config()).
 */
final class Kit
{
    /** @var array<string,mixed> */
    private static array $config = [];

    /** @param array<string,mixed> $config */
    public static function boot(array $config = []): void
    {
        self::$config = $config;

        // Config-free plumbing.
        Cleanup::init();
        Sidebar::init();
        Search::init();
        Comments::init();
        Partials::init();
        Pagination::init();
        RelatedPosts::init();

        // Config-coupled modules (read self::$config).
        Assets::init();
        ThemeSupport::init();
        Menus::init();
        Options::init();
        Admin::init();
    }

    /** Read a config value supplied at boot. */
    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}
