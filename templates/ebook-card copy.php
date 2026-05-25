<?php
/**
 * Template: Ebook Card (Grid View)
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get additional meta
$pages = get_post_meta($ebook_id, '_ebook_pages', true);
$terms = get_the_terms($ebook_id, 'ebook_category');
$category = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
?>

<div class="ebook-card fade-in">
    <!-- Ebook Cover -->
    <div class="ebook-cover">
        <?php if (has_post_thumbnail($ebook_id)): ?>
            <?php echo get_the_post_thumbnail($ebook_id, 'medium', array('class' => 'ebook-cover-image')); ?>
        <?php else: ?>
            <div class="ebook-cover-placeholder">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                </svg>
            </div>
        <?php endif; ?>

        <!-- Stock Badge -->
        <?php if (!$in_stock): ?>
            <div class="stock-badge">
                <?php _e('Out of Stock', 'skillscore-ebook'); ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Rating (Placeholder - can be enhanced with review system) -->
    <div class="ebook-rating">
        <div class="stars">
            <span class="star">★</span>
            <span class="star">★</span>
            <span class="star">★</span>
            <span class="star">★</span>
            <span class="star empty">★</span>
        </div>
        <span class="rating-value">4.0</span>
    </div>

    <!-- Title -->
    <h3 class="ebook-title">
        <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" style="color: inherit; text-decoration: none;">
            <?php echo esc_html(get_the_title($ebook_id)); ?>
        </a>
    </h3>

    <!-- Author -->
    <?php if (!empty($author)): ?>
        <p class="ebook-author">
            <?php _e('by', 'skillscore-ebook'); ?> <?php echo esc_html($author); ?>
        </p>
    <?php endif; ?>

    <!-- Category -->
    <?php if ($category): ?>
        <p class="ebook-category"><?php echo esc_html($category); ?></p>
    <?php else: ?>
        <p class="ebook-category">&nbsp;</p>
    <?php endif; ?>

    <!-- Price and Pages -->
    <div class="price-section">
        <span class="price-tag"><?php echo esc_html($currency_symbol . number_format($price, 2)); ?></span>
        <?php if ($pages): ?>
            <span class="ebook-pages"><?php echo esc_html($pages); ?> <?php _e('pages', 'skillscore-ebook'); ?></span>
        <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="ebook-actions">
        <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" class="btn-secondary">
            <?php _e('View', 'skillscore-ebook'); ?>
        </a>
        <?php if ($in_stock): ?>
            <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" class="btn-primary">
                <?php _e('Buy Now', 'skillscore-ebook'); ?>
            </a>
        <?php else: ?>
            <button class="btn-primary" style="opacity: 0.5; cursor: not-allowed;" disabled>
                <?php _e('Sold Out', 'skillscore-ebook'); ?>
            </button>
        <?php endif; ?>
    </div>
</div>
