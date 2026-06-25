<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Reusable template partials. Pass data into a partial without polluting globals.
 *
 *   VMG\Partials::get('post-card', ['post' => $p]);
 */
final class Partials
{
    public static function init(): void {}

    /** Render a partial from inc/partials/{slug}.php with $data extracted into scope. */
    public static function render(string $slug, array $data = []): void
    {
        $file = get_template_directory() . '/inc/partials/' . $slug . '.php';
        if (!file_exists($file)) {
            return;
        }
        extract($data, EXTR_SKIP);
        include $file;
    }

    /** Return a partial's output as a string. */
    public static function get(string $slug, array $data = []): string
    {
        ob_start();
        self::render($slug, $data);
        return (string) ob_get_clean();
    }
}
