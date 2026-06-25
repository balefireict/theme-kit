<?php
declare(strict_types=1);

namespace Balefire;

if (!defined('ABSPATH')) exit;

/**
 * Comment list rendering callback.
 */
final class Comments
{
    public static function init(): void {}

    public static function layout(\WP_Comment $comment, array $args, int $depth): void
    {
        $tag = ($args['style'] === 'div') ? 'div' : 'li';
        ?>
        <<?php echo $tag; ?> id="comment-<?php comment_ID(); ?>" <?php comment_class('comment'); ?>>
            <article class="comment__body">
                <header class="comment__meta">
                    <?php echo get_avatar($comment, 48); ?>
                    <span class="comment__author"><?php echo get_comment_author_link(); ?></span>
                    <time class="comment__date" datetime="<?php echo esc_attr(get_comment_time('c')); ?>">
                        <?php echo esc_html(get_comment_date()); ?>
                    </time>
                </header>
                <div class="comment__content"><?php comment_text(); ?></div>
                <?php if ('0' === $comment->comment_approved): ?>
                    <p class="comment__moderation"><?php esc_html_e('Your comment is awaiting moderation.', 'theme-kit'); ?></p>
                <?php endif; ?>
                <?php
                comment_reply_link(array_merge($args, [
                    'depth'     => $depth,
                    'max_depth' => $args['max_depth'],
                    'reply_text' => __('Reply', 'theme-kit'),
                ]));
                ?>
            </article>
        <?php
        // Closing tag is handled by WordPress.
    }
}
