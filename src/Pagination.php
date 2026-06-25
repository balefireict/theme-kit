<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Paginated navigation for archives and the blog index.
 *
 * Renders a mobile prev/next bar and a desktop row with a results
 * summary and a numbered nav with arrows + gap ellipsis.
 */
final class Pagination
{
    public static function init(): void {}

    public static function render(?\WP_Query $query = null, array $args = []): void
    {
        $query = $query ?? $GLOBALS['wp_query'];
        if (!$query || $query->max_num_pages < 2) {
            return;
        }

        $current = max(1, (int) get_query_var('paged'));
        $total   = (int) $query->max_num_pages;
        $per_page = (int) ($query->query_vars['posts_per_page'] ?: get_option('posts_per_page'));
        $found   = (int) $query->found_posts;
        $from    = ($current - 1) * $per_page + 1;
        $to      = min($current * $per_page, $found);

        $prev_url = $current > 1 ? self::pageUrl($current - 1) : '';
        $next_url = $current < $total ? self::pageUrl($current + 1) : '';

        $items = self::buildItems($current, $total);

        ?>
        <nav class="pagination-shell" aria-label="<?php echo esc_attr__('Pagination', 'theme-kit'); ?>">
            <div class="pagination-mobile">
                <?php if ($prev_url): ?>
                    <a href="<?php echo esc_url($prev_url); ?>" class="pagination-button"><?php esc_html_e('Previous', 'theme-kit'); ?></a>
                <?php else: ?>
                    <span class="pagination-button pagination-disabled" aria-disabled="true"><?php esc_html_e('Previous', 'theme-kit'); ?></span>
                <?php endif; ?>
                <?php if ($next_url): ?>
                    <a href="<?php echo esc_url($next_url); ?>" class="pagination-button"><?php esc_html_e('Next', 'theme-kit'); ?></a>
                <?php else: ?>
                    <span class="pagination-button pagination-disabled" aria-disabled="true"><?php esc_html_e('Next', 'theme-kit'); ?></span>
                <?php endif; ?>
            </div>
            <div class="pagination-desktop">
                <p class="pagination-summary">
                    <?php
                    printf(
                        /* translators: 1: from, 2: to, 3: total */
                        esc_html__('Showing %1$s to %2$s of %3$s results', 'theme-kit'),
                        '<span>' . number_format_i18n($from) . '</span>',
                        '<span>' . number_format_i18n($to) . '</span>',
                        '<span>' . number_format_i18n($found) . '</span>'
                    );
                    ?>
                </p>
                <div>
                    <nav class="pagination-nav" aria-label="<?php echo esc_attr__('Pagination', 'theme-kit'); ?>">
                        <?php if ($prev_url): ?>
                            <a href="<?php echo esc_url($prev_url); ?>" class="pagination-item pagination-arrow">
                                <span class="sr-only"><?php esc_html_e('Previous', 'theme-kit'); ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                            </a>
                        <?php else: ?>
                            <span class="pagination-item pagination-arrow pagination-disabled" aria-disabled="true">
                                <span class="sr-only"><?php esc_html_e('Previous', 'theme-kit'); ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                            </span>
                        <?php endif; ?>

                        <?php foreach ($items as $item):
                            if ($item['type'] === 'current'): ?>
                                <span class="pagination-item pagination-current" aria-current="page"><?php echo esc_html($item['page']); ?></span>
                            <?php elseif ($item['type'] === 'gap'): ?>
                                <span class="pagination-item pagination-gap" aria-hidden="true">&hellip;</span>
                            <?php else: ?>
                                <a href="<?php echo esc_url($item['url']); ?>" class="pagination-item pagination-page"><?php echo esc_html($item['page']); ?></a>
                            <?php endif;
                        endforeach; ?>

                        <?php if ($next_url): ?>
                            <a href="<?php echo esc_url($next_url); ?>" class="pagination-item pagination-arrow">
                                <span class="sr-only"><?php esc_html_e('Next', 'theme-kit'); ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                            </a>
                        <?php else: ?>
                            <span class="pagination-item pagination-arrow pagination-disabled" aria-disabled="true">
                                <span class="sr-only"><?php esc_html_e('Next', 'theme-kit'); ?></span>
                                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd"></path></svg>
                            </span>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </nav>
        <?php
    }

    /**
     * Build the ordered list of page items: pages, current page, and gaps.
     * Always shows first and last pages with a gap when the range is large.
     *
     * @return array<int, array{type:string, page?:int, url?:string}>
     */
    private static function buildItems(int $current, int $total): array
    {
        $mid   = 1;          // pages either side of current
        $start = max(2, $current - $mid);
        $end   = min($total - 1, $current + $mid);

        $items = [];
        $items[] = ['type' => 'page', 'page' => 1, 'url' => self::pageUrl(1)];

        if ($start > 2) {
            $items[] = ['type' => 'gap'];
        }

        for ($i = $start; $i <= $end; $i++) {
            if ($i === 1 || $i === $total) {
                continue;
            }
            if ($i === $current) {
                $items[] = ['type' => 'current', 'page' => $i];
            } else {
                $items[] = ['type' => 'page', 'page' => $i, 'url' => self::pageUrl($i)];
            }
        }

        if ($end < $total - 1) {
            $items[] = ['type' => 'gap'];
        }

        if ($total > 1) {
            $items[] = ['type' => 'page', 'page' => $total, 'url' => self::pageUrl($total)];
        }

        return $items;
    }

    private static function pageUrl(int $page): string
    {
        return esc_url(get_pagenum_link($page));
    }
}
