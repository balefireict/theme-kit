<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * ACF options-page accessor. The options page + its field group are
 * UI-created in ACF and synced via acf-json/ like any field group; this
 * class only reads values. Sites expose it through a helper (e.g. vmg_biz()).
 */
final class Options
{
    public static function init(): void
    {
        // Options page + field group are registered from acf-json/ (JSON sync).
    }

    /**
     * Fetch an options-page field with a fallback default.
     *
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $field, $default = '')
    {
        if (!function_exists('get_field')) {
            return $default;
        }
        $value = get_field($field, 'option');
        return ($value === null || $value === '' || $value === false) ? $default : $value;
    }
}
