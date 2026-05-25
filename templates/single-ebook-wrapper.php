<?php
/**
 * Single Ebook Page Wrapper
 * This is used when viewing a single ebook directly
 *
 * @package SkillScore_Ebook
 */

get_header(); ?>

<div id="primary" class="content-area" style="background-color: #0D0D0D; min-height: 100vh; padding: 2rem 0;">
    <main id="main" class="site-main">
        <?php
        while (have_posts()) :
            the_post();
            
            // The content will be automatically added by the plugin's filter
            the_content();
            
        endwhile;
        ?>
    </main>
</div>

<?php get_footer(); ?>