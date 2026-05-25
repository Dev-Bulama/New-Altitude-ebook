<?php
/**
 * Ebook Card Template - REDESIGNED SIMPLE VERSION
 * Clean, minimal card for ebook grid
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="skillscore-ebook-card" 
     style="background: #1A1A1A; border-radius: 12px; overflow: hidden; transition: all 0.3s; border: 2px solid transparent;">
    
    <!-- Book Cover -->
    <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" 
       style="display: block; position: relative; overflow: hidden; background: #0D0D0D;">
        <?php if (has_post_thumbnail($ebook_id)) : ?>
            <?php echo get_the_post_thumbnail($ebook_id, 'medium', array(
                'style' => 'width: 100%; height: 350px; object-fit: cover; transition: transform 0.3s;',
                'class' => 'ebook-card-image'
            )); ?>
        <?php else : ?>
            <div style="width: 100%; height: 350px; display: flex; align-items: center; justify-content: center; background: #2A2A2A;">
                <span style="color: #666; font-size: 48px;">📚</span>
            </div>
        <?php endif; ?>

        <!-- Stock Badge -->
        <?php if ($in_stock) : ?>
            <div style="position: absolute; top: 12px; right: 12px; background: rgba(16, 185, 129, 0.9); color: #FFFFFF; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                In Stock
            </div>
        <?php else : ?>
            <div style="position: absolute; top: 12px; right: 12px; background: rgba(239, 68, 68, 0.9); color: #FFFFFF; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                Out of Stock
            </div>
        <?php endif; ?>

        <!-- Price Badge -->
        <div style="position: absolute; bottom: 12px; left: 12px; background: rgba(234, 179, 8, 0.95); color: #0D0D0D; padding: 8px 16px; border-radius: 8px; font-size: 18px; font-weight: 700;">
            <?php echo esc_html($currency_symbol . number_format($price, 2)); ?>
        </div>
    </a>

    <!-- Card Content -->
    <div style="padding: 20px;">
        
        <!-- Title -->
        <h3 style="margin: 0 0 8px 0;">
            <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" 
               style="color: #FFFFFF; text-decoration: none; font-size: 18px; font-weight: 700; line-height: 1.3; display: block;">
                <?php echo esc_html(get_the_title($ebook_id)); ?>
            </a>
        </h3>

        <!-- Author -->
        <?php if ($author) : ?>
            <p style="color: #9CA3AF; font-size: 14px; margin: 0 0 15px 0;">
                by <?php echo esc_html($author); ?>
            </p>
        <?php endif; ?>

        <!-- Buy Button -->
        <a href="<?php echo esc_url(get_permalink($ebook_id)); ?>" 
           class="btn-buy-card"
           style="display: block; text-align: center; padding: 12px; background: #EAB308; color: #0D0D0D; text-decoration: none; border-radius: 8px; font-weight: 600; font-size: 15px; transition: all 0.3s;">
            View Details →
        </a>

    </div>

</div>

<style>
    /* Card Hover Effect */
    .skillscore-ebook-card:hover {
        border-color: #EAB308 !important;
        transform: translateY(-4px);
        box-shadow: 0 8px 30px rgba(234, 179, 8, 0.2);
    }

    /* Image Zoom on Hover */
    .skillscore-ebook-card:hover .ebook-card-image {
        transform: scale(1.05);
    }

    /* Button Hover */
    .btn-buy-card:hover {
        background: #FCD34D !important;
        box-shadow: 0 4px 12px rgba(234, 179, 8, 0.3);
    }

    /* Title Hover */
    .skillscore-ebook-card h3 a:hover {
        color: #EAB308 !important;
    }
</style>