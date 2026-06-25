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
 * Slice 1 owns the rendering-agnostic, config-free plumbing:
 *   Partials · Pagination · Search · Cleanup · Comments · Sidebar · RelatedPosts
 *
 * Config-coupled modules (Assets, ThemeSupport, Menus, Options, Admin) move
 * over in later slices and will read values via self::config().
 */
final class Kit
{
    /** @var array<string,mixed> */
    private static array $config = [];

    /** @param array<string,mixed> $config */
    public static function boot(array $config = []): void
    {
        self::$config = $config;

        // Modules with real hooks.
        Cleanup::init();
        Sidebar::init();

        // No-op / on-demand modules (kept explicit so the boot list is the
        // single source of truth for what the kit owns).
        Search::init();
        Comments::init();
        Partials::init();
        Pagination::init();
        RelatedPosts::init();
    }

    /** Read a config value supplied at boot. */
    public static function config(string $key, mixed $default = null): mixed
    {
        return self::$config[$key] ?? $default;
    }
}
