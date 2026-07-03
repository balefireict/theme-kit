<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * ACF options-page accessor. The options page + its field group are
 * shipped via acf-json/ (e.g. group_acffg_general_business.json in the
 * base theme); this class only reads values. Sites expose it through
 * a helper (e.g. bma_biz()).
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
