<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Asset loading with a Vite dev/prod switch — config-driven via Kit::boot:
 *
 *   'handle' => 'vmg',
 *   'assets' => [
 *       'dev_host'    => 'https://my-site.test:5173',
 *       'entries'     => ['resources/css/app.css', 'resources/js/main.js'],
 *       'fonts'       => 'https://fonts.googleapis.com/css2?...',   // optional
 *       'favicon_dir' => 'resources/img/favicon',                   // optional
 *       'conditional_styles' => [                                   // optional
 *           'leaflet' => ['src' => 'resources/css/leaflet.css', 'when' => fn() => is_post_type_archive('service-area')],
 *       ],
 *   ]
 *
 * Dev (VITE_DEVELOPMENT true AND dev server reachable): load from Vite + HMR.
 * Prod (default): hashed files from dist/ via manifest.json.
 */
final class Assets
{
    private static ?bool $devServerUp = null;

    public static function init(): void
    {
        add_action('wp_enqueue_scripts', [self::class, 'enqueue']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueConditional']);
        add_action('wp_head', [self::class, 'favicons'], 2);
        add_filter('wp_resource_hints', [self::class, 'resourceHints'], 10, 2);
        if (self::isDev()) {
            add_action('wp_head', [self::class, 'viteClient'], 1);
        }
    }

    /* ---- config accessors ---- */

    private static function handle(): string
    {
        return (string) Kit::config('handle', 'theme');
    }

    /** @return mixed */
    private static function cfg(string $key, $default = null)
    {
        $assets = (array) Kit::config('assets', []);
        return $assets[$key] ?? $default;
    }

    private static function devHost(): string
    {
        return (string) self::cfg('dev_host', '');
    }

    /** @return string[] */
    private static function entries(): array
    {
        return (array) self::cfg('entries', []);
    }

    private static function devPort(): int
    {
        return (int) (parse_url(self::devHost(), PHP_URL_PORT) ?: 5173);
    }

    /* ---- head bits ---- */

    public static function favicons(): void
    {
        $dir = (string) self::cfg('favicon_dir', '');
        if ($dir === '') {
            return;
        }
        $uri = get_template_directory_uri() . '/' . ltrim($dir, '/');

        echo '<link rel="icon" type="image/png" href="' . esc_url($uri . '/favicon-96x96.png') . '" sizes="96x96">' . "\n";
        echo '<link rel="icon" type="image/svg+xml" href="' . esc_url($uri . '/favicon.svg') . '">' . "\n";
        echo '<link rel="shortcut icon" href="' . esc_url($uri . '/favicon.ico') . '">' . "\n";
        echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url($uri . '/apple-touch-icon.png') . '">' . "\n";
        echo '<link rel="manifest" href="' . esc_url($uri . '/site.webmanifest') . '">' . "\n";
    }

    public static function resourceHints(array $urls, string $relationType): array
    {
        if ($relationType !== 'preconnect' || self::cfg('fonts', '') === '') {
            return $urls;
        }

        $urls[] = 'https://fonts.googleapis.com';
        $urls[] = ['href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous'];

        return $urls;
    }

    public static function viteClient(): void
    {
        echo '<script type="module" src="' . esc_url(self::devHost() . '/@vite/client') . '"></script>' . "\n";
    }

    /* ---- dev detection ---- */

    private static function isDev(): bool
    {
        if (!defined('VITE_DEVELOPMENT') || VITE_DEVELOPMENT !== true || self::devHost() === '') {
            return false;
        }
        return self::devServerUp();
    }

    private static function devServerUp(): bool
    {
        if (self::$devServerUp !== null) {
            return self::$devServerUp;
        }
        $conn = @fsockopen('127.0.0.1', self::devPort(), $errno, $errstr, 0.1);
        if ($conn) {
            fclose($conn);
            self::$devServerUp = true;
        } else {
            self::$devServerUp = false;
        }
        return self::$devServerUp;
    }

    /* ---- enqueue ---- */

    public static function enqueue(): void
    {
        $fonts = (string) self::cfg('fonts', '');
        if ($fonts !== '') {
            wp_enqueue_style(self::handle() . '-fonts', $fonts, [], null);
        }

        if (self::isDev()) {
            foreach (self::entries() as $entry) {
                wp_enqueue_script(
                    self::handle() . '-' . sanitize_title($entry),
                    self::devHost() . '/' . $entry,
                    [],
                    null,
                    true
                );
            }
            add_filter('script_loader_tag', [self::class, 'moduleTag'], 10, 3);
            return;
        }

        self::enqueueProd();
    }

    /** Conditional, page-scoped stylesheets (e.g. Leaflet on a map archive). */
    public static function enqueueConditional(): void
    {
        foreach ((array) self::cfg('conditional_styles', []) as $suffix => $def) {
            $when = $def['when'] ?? null;
            if (is_callable($when) && !$when()) {
                continue;
            }
            if (empty($def['src'])) {
                continue;
            }
            wp_enqueue_style(
                self::handle() . '-' . sanitize_title((string) $suffix),
                get_template_directory_uri() . '/' . ltrim((string) $def['src'], '/'),
                [],
                null
            );
        }
    }

    public static function moduleTag(string $tag, string $handle, string $src): string
    {
        if (str_starts_with($handle, self::handle() . '-')) {
            return '<script type="module" src="' . esc_url($src) . '"></script>' . "\n";
        }
        return $tag;
    }

    private static function enqueueProd(): void
    {
        $manifestPath = get_template_directory() . '/dist/.vite/manifest.json';
        if (!file_exists($manifestPath)) {
            $manifestPath = get_template_directory() . '/dist/manifest.json';
        }
        if (!file_exists($manifestPath)) {
            return;
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($manifest)) {
            return;
        }

        $distUri = get_template_directory_uri() . '/dist/';

        foreach (self::entries() as $entry) {
            if (empty($manifest[$entry])) {
                continue;
            }
            $chunk  = $manifest[$entry];
            $handle = self::handle() . '-' . sanitize_title($entry);

            foreach (($chunk['css'] ?? []) as $css) {
                wp_enqueue_style($handle . '-css', $distUri . $css, [], null);
            }

            $file = $chunk['file'] ?? '';
            if ($file === '') {
                continue;
            }
            if (str_ends_with($file, '.css')) {
                wp_enqueue_style($handle, $distUri . $file, [], null);
            } else {
                wp_enqueue_script($handle, $distUri . $file, [], null, true);
                add_filter('script_loader_tag', [self::class, 'prodModuleTag'], 10, 3);
            }
        }
    }

    public static function prodModuleTag(string $tag, string $handle, string $src): string
    {
        if (str_starts_with($handle, self::handle() . '-')) {
            return '<script type="module" src="' . esc_url($src) . '"></script>' . "\n";
        }
        return $tag;
    }
}
