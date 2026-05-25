<?php
/**
 * Standalone Single Ebook Template
 * Bypasses theme completely
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_the_title()); ?> - <?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('skillscore-ebook-body'); ?>>

<?php
// Get ebook shortcodes instance
global $skillscore_shortcodes;
if (!$skillscore_shortcodes) {
    $skillscore_shortcodes = new SkillScore_Ebook_Shortcodes();
}

while (have_posts()) {
    the_post();
    $skillscore_shortcodes->render_single_ebook_complete(get_the_ID());
}
?>

<?php wp_footer(); ?>
</body>
</html>