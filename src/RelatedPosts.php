<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Related posts query helper (by shared category). Minimal, no UI assumptions.
 */
final class RelatedPosts
{
    public static function init(): void {}

    /** @return \WP_Post[] */
    public static function get(?int $postId = null, int $limit = 3): array
    {
        $postId = $postId ?? get_the_ID();
        if (!$postId) {
            return [];
        }

        $cats = wp_get_post_categories($postId);
        if (empty($cats)) {
            return [];
        }

        $q = new \WP_Query([
            'category__in'        => $cats,
            'post__not_in'        => [$postId],
            'posts_per_page'      => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ]);

        return $q->posts;
    }
}
