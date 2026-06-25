<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Light admin tweaks: media auto-titling, an admin-only front-end edit
 * footer, and a dev debug bar (dev domains only).
 *
 * The "Edit with WPBakery" link self-activates only when WPBakery is present
 * (defined('WPB_VC_VERSION')), so this stays correct on Block/non-Bakery
 * sites. The debug-bar label comes from Kit::config('handle').
 */
final class Admin
{
    public static function init(): void
    {
        add_action('add_attachment', [self::class, 'setImageMeta']);
        add_action('wp_footer', [self::class, 'editFooter'], 998);
        add_action('wp_footer', [self::class, 'debugBar'], 999);
    }

    /**
     * Set sensible title/caption/description/alt text when an image is
     * uploaded — turns `charter_bus-interior_01` into `Charter Bus Interior 01`.
     */
    public static function setImageMeta(int $postId): void
    {
        if (!wp_attachment_is_image($postId)) {
            return;
        }

        $attachment = get_post($postId);
        if (!$attachment) {
            return;
        }

        $title = (string) $attachment->post_title;
        $title = preg_replace('%\s*[-_\s]+\s*%', ' ', $title) ?: $title;
        $title = ucwords(strtolower($title));

        wp_update_post([
            'ID'           => $postId,
            'post_title'   => $title,
            'post_excerpt' => $title,
            'post_content' => $title,
        ]);

        update_post_meta($postId, '_wp_attachment_image_alt', $title);
    }

    /** Admin-only front-end edit links. */
    public static function editFooter(): void
    {
        if (!is_user_logged_in() || !current_user_can('administrator') || !is_singular()) {
            return;
        }

        $postId = get_queried_object_id();
        if (!$postId || !current_user_can('edit_post', $postId)) {
            return;
        }

        $postType = get_post_type($postId) ?: 'page';
        $editUrl = get_edit_post_link($postId, 'raw');
        if (!$editUrl) {
            return;
        }

        echo '<style>
.edit-footer {
    width: 100%;
    background-color: #c4c4c4;
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    text-align: center;
    gap: 10px;
    color: #ffffff;
    padding: 15px;
}
.edit-footer .wrapper {
    width: 100%;
    max-width: var(--container-max, 1200px);
    margin-inline: auto;
    padding-inline: var(--container-pad, 1.25rem);
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 10px;
}
a.edit-post-link,
.edit-footer a,
a.vc_inline-link {
    display: inline-flex;
    padding: 0.75rem 1.5rem;
    color: #ffffff;
    background-color: var(--color-primary, #00338f);
    border: 2px solid transparent;
    border-radius: 4px;
    cursor: pointer;
    font-family: inherit;
    font-size: 1.25rem;
    font-weight: 700;
    text-align: center;
    line-height: 1;
    text-decoration: none;
    transition: background-color 0.3s ease, border-color 0.3s ease, transform 0.3s ease-in-out;
    position: relative;
}
a.edit-post-link {
    background-color: var(--color-primary, #00338f);
}
a.edit-post-link:hover,
.edit-footer a:hover,
a.vc_inline-link:hover {
    background-color: var(--color-primary-hover, #00186c);
    color: #ffffff;
    text-decoration: none;
    opacity: 0.95;
    transform: translateY(1px);
}
a.vc_inline-link {
    background-color: var(--color-accent, #c8102e);
}
</style>';

        echo '<div class="edit-footer"><div class="wrapper">';
        printf(
            '<a class="edit-post-link" href="%s">%s</a>',
            esc_url($editUrl),
            esc_html__('Edit Post', 'theme-kit')
        );

        // Bakery inline-edit link — only when WPBakery is active.
        if (defined('WPB_VC_VERSION')) {
            $wpbakeryUrl = add_query_arg(
                [
                    'vc_action' => 'vc_inline',
                    'post_id'   => $postId,
                    'post_type' => $postType,
                ],
                admin_url('post.php')
            );
            printf(
                ' <a href="%s" id="vc_load-inline-editor" class="vc_inline-link">%s</a>',
                esc_url($wpbakeryUrl),
                esc_html__('Edit with WPBakery Page Builder', 'theme-kit')
            );
        }
        echo '</div></div>';
    }

    /** Admin-only dev debug bar (dev domains only). */
    public static function debugBar(): void
    {
        if (!current_user_can('administrator') || !self::isDevelopmentDomain()) {
            return;
        }

        global $template;
        $templateName = basename((string) $template);
        $brand = strtoupper((string) Kit::config('handle', 'theme'));

        $devPort = 5173;
        $host    = isset($_SERVER['HTTP_HOST'])
            ? preg_replace('/:\d+$/', '', (string) $_SERVER['HTTP_HOST'])
            : 'localhost';
        $scheme  = is_ssl() ? 'https' : 'http';
        $viteUrl = sprintf('%s://%s:%d', $scheme, $host, $devPort);

        $vitePing = @file_get_contents($viteUrl . '/@vite/client', false, stream_context_create([
            'http' => ['timeout' => 0.3],
            'ssl'  => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ]));
        $viteRunning = $vitePing !== false;
        $viteDot     = $viteRunning ? '#4ade80' : '#f87171';
        $viteLabel   = $viteRunning ? 'HMR Connected' : 'HMR Offline';
        $devMode     = defined('VITE_DEVELOPMENT') && VITE_DEVELOPMENT === true && $viteRunning;

        echo '<style>
.debug-bar {
    width: 100%;
    background: rgba(0, 0, 0, 0.85);
    color: #fff;
    padding: 4px 12px;
    font-size: 0.8rem;
    font-family: ui-monospace, SFMono-Regular, monospace;
    display: flex;
    gap: 16px;
    align-items: center;
    backdrop-filter: blur(4px);
}
.debug-bar__label { opacity: 0.5; }
.debug-bar__status { display: flex; align-items: center; gap: 5px; }
.debug-bar__dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
.debug-bar__muted { opacity: 0.4; }
</style>';

        printf(
            '<div class="debug-bar">'
            . '<span class="debug-bar__label">%s</span>'
            . '<span>%s</span>'
            . '<span class="debug-bar__status"><span class="debug-bar__dot" style="background:%s;"></span>%s</span>'
            . '<span class="%s">Dev Mode: %s</span>'
            . '<span class="debug-bar__muted">%s:%d</span>'
            . '</div>',
            esc_html($brand),
            esc_html($templateName),
            $viteDot,
            $viteLabel,
            $devMode ? '' : 'debug-bar__muted',
            $devMode ? 'ON' : 'OFF',
            esc_html($host),
            $devPort
        );
    }

    private static function isDevelopmentDomain(): bool
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $devTlds = ['.test', '.local', '.dev', '.localhost'];

        foreach ($devTlds as $tld) {
            if (str_ends_with($host, $tld)) {
                return true;
            }
        }

        return str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');
    }
}
