<?php
/**
 * Shortcodes Handler - COMPLETE THEME OVERRIDE
 * Priority 1 Fix: Forces plugin template to load instead of theme
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Shortcodes {

    public function __construct() {
        // HIGHEST priority template override
        add_filter('template_include', array($this, 'override_single_template'), 9999);
        
        // Remove theme actions
        add_action('template_redirect', array($this, 'remove_theme_hooks'));
        
        // Add body class
        add_filter('body_class', array($this, 'custom_body_class'));
    }

    /**
     * Override single ebook template - FORCE plugin template
     */
    public function override_single_template($template) {
        if (!is_singular('ebook')) {
            return $template;
        }

        // Create standalone template
        $standalone = $this->get_standalone_template();
        
        if (file_exists($standalone)) {
            return $standalone;
        }

        return $template;
    }

    /**
     * Get/create standalone template
     */
    private function get_standalone_template() {
        $template_path = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/single-ebook-standalone.php';
        
        if (file_exists($template_path)) {
            return $template_path;
        }

        // Create template directory
        $template_dir = dirname($template_path);
        if (!file_exists($template_dir)) {
            wp_mkdir_p($template_dir);
        }

        // Create complete standalone template that bypasses theme
        $content = '<?php
/**
 * Standalone Single Ebook Template
 * Bypasses theme completely
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo(\'charset\'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo esc_html(get_the_title()); ?> - <?php bloginfo(\'name\'); ?></title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(\'skillscore-ebook-body\'); ?>>

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
</html>';

        file_put_contents($template_path, $content);
        
        return $template_path;
    }

    /**
     * Render complete single ebook page
     */
    public function render_single_ebook_complete($ebook_id) {
        // Enqueue assets
        $this->enqueue_assets_force();
        
        // Get all data
        $price = get_post_meta($ebook_id, '_ebook_price', true);
        $author = get_post_meta($ebook_id, '_ebook_author', true);
        $publisher = get_post_meta($ebook_id, '_ebook_publisher', true);
        $isbn = get_post_meta($ebook_id, '_ebook_isbn', true);
        $pages = get_post_meta($ebook_id, '_ebook_pages', true);
        $language = get_post_meta($ebook_id, '_ebook_language', true);
        $quantity = get_post_meta($ebook_id, '_ebook_quantity', true);
        $unlimited = get_post_meta($ebook_id, '_ebook_unlimited', true);
        $enable_preview = get_post_meta($ebook_id, '_ebook_enable_preview', true);
        $enable_audio = get_post_meta($ebook_id, '_ebook_enable_audio', true);
        $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');
        $enable_quantity_selector = get_option('skillscore_ebook_enable_quantity_selector', true);

        $in_stock = $unlimited || ($quantity && $quantity > 0);

        // Load ebook single template
        $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/ebook-single.php';
        
        if (file_exists($template)) {
            include $template;
        } else {
            $this->show_template_error($template);
        }
    }

    /**
     * Show template error
     */
    private function show_template_error($expected_path) {
        ?>
        <div style="background: #0D0D0D; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 40px;">
            <div style="background: #fee2e2; border: 3px solid #dc2626; padding: 40px; border-radius: 12px; max-width: 600px; text-align: center;">
                <h2 style="color: #dc2626; margin: 0 0 20px 0; font-size: 24px;">⚠️ Template Missing</h2>
                <p style="font-size: 16px; margin-bottom: 15px;">The single ebook template file is missing.</p>
                <p style="font-size: 14px; color: #666; font-family: monospace;">
                    Expected: <code><?php echo esc_html($expected_path); ?></code>
                </p>
                <p style="margin-top: 20px;">
                    <strong>Solution:</strong> Re-upload the plugin or verify the templates folder exists.
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Remove theme hooks
     */
    public function remove_theme_hooks() {
        if (!is_singular('ebook')) {
            return;
        }

        // Remove theme head/footer
        remove_all_actions('get_header');
        remove_all_actions('get_footer');
        
        // Add inline styles to hide theme elements
        add_action('wp_head', array($this, 'inline_override_styles'), 9999);
    }

    /**
     * Inline override styles
     */
    public function inline_override_styles() {
        ?>
        <style>
            /* FORCE hide all theme elements */
            body.skillscore-ebook-body {
                background: #0D0D0D !important;
                margin: 0 !important;
                padding: 0 !important;
            }

            body.skillscore-ebook-body .site-header,
            body.skillscore-ebook-body .site-footer,
            body.skillscore-ebook-body header:not(.skillscore-header),
            body.skillscore-ebook-body footer:not(.skillscore-footer),
            body.skillscore-ebook-body .sidebar,
            body.skillscore-ebook-body aside,
            body.skillscore-ebook-body .widget-area,
            body.skillscore-ebook-body nav:not(.skillscore-nav),
            body.skillscore-ebook-body .breadcrumbs,
            body.skillscore-ebook-body .entry-header,
            body.skillscore-ebook-body .entry-meta,
            body.skillscore-ebook-body .entry-footer,
            body.skillscore-ebook-body .post-meta,
            body.skillscore-ebook-body .post-navigation,
            body.skillscore-ebook-body .comments-area {
                display: none !important;
            }

            /* Full width containers */
            body.skillscore-ebook-body #page,
            body.skillscore-ebook-body #content,
            body.skillscore-ebook-body .site-content,
            body.skillscore-ebook-body .content-area,
            body.skillscore-ebook-body #primary,
            body.skillscore-ebook-body article {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                background: transparent !important;
            }

            /* Show plugin content */
            body.skillscore-ebook-body .skillscore-ebook-single {
                display: block !important;
                visibility: visible !important;
            }
        </style>
        <?php
    }

    /**
     * Custom body class
     */
    public function custom_body_class($classes) {
        if (is_singular('ebook')) {
            $classes[] = 'skillscore-ebook-body';
            $classes[] = 'single-ebook-page';
        }
        return $classes;
    }

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('skillscore_ebooks', array($this, 'ebooks_list_shortcode'));
        add_shortcode('skillscore_ebook', array($this, 'single_ebook_shortcode'));
    }

    /**
     * Ebooks list shortcode
     */
    public function ebooks_list_shortcode($atts) {
        $this->enqueue_assets_force();

        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'columns' => 3,
        ), $atts);

        $args = array(
            'post_type' => 'ebook',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => $atts['orderby'],
            'order' => $atts['order'],
            'post_status' => 'publish',
        );

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ebook_category',
                    'field' => 'slug',
                    'terms' => $atts['category'],
                ),
            );
        }

        $query = new WP_Query($args);

        ob_start();

        if ($query->have_posts()) {
            echo '<div class="skillscore-ebooks-grid grid grid-cols-1 md:grid-cols-' . intval($atts['columns']) . ' gap-6">';

            while ($query->have_posts()) {
                $query->the_post();
                $this->render_ebook_card(get_the_ID());
            }

            echo '</div>';
            wp_reset_postdata();
        } else {
            echo '<p class="text-gray-600">' . __('No ebooks found.', 'skillscore-ebook') . '</p>';
        }

        return ob_get_clean();
    }

    /**
     * Single ebook shortcode
     */
    public function single_ebook_shortcode($atts) {
        $this->enqueue_assets_force();

        $atts = shortcode_atts(array('id' => 0), $atts);
        $ebook_id = intval($atts['id']);

        if (!$ebook_id || get_post_type($ebook_id) !== 'ebook') {
            return '<p class="text-red-600">' . __('Invalid ebook ID.', 'skillscore-ebook') . '</p>';
        }

        ob_start();
        $this->render_single_ebook_complete($ebook_id);
        return ob_get_clean();
    }

    /**
     * Render ebook card
     */
    private function render_ebook_card($ebook_id) {
        $price = get_post_meta($ebook_id, '_ebook_price', true);
        $author = get_post_meta($ebook_id, '_ebook_author', true);
        $quantity = get_post_meta($ebook_id, '_ebook_quantity', true);
        $unlimited = get_post_meta($ebook_id, '_ebook_unlimited', true);
        $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');

        $in_stock = $unlimited || ($quantity && $quantity > 0);

        $template = locate_template('skillscore-ebook/ebook-card.php');
        if (!$template) {
            $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/ebook-card.php';
        }

        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Force enqueue assets
     */
    private function enqueue_assets_force() {
        static $loaded = false;
        if ($loaded) {
            return;
        }

        // Tailwind CSS
        wp_enqueue_style(
            'tailwind-cdn',
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css',
            array(),
            '3.4.0'
        );

        // Custom CSS
        wp_enqueue_style(
            'skillscore-ebook',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/css/public.css',
            array('tailwind-cdn'),
            time()
        );

        // Custom JS
        wp_enqueue_script(
            'skillscore-ebook',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            time(),
            true
        );

        // Localize
        wp_localize_script(
            'skillscore-ebook',
            'skillscoreEbook',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skillscore_ebook_nonce'),
                'currency' => get_option('skillscore_ebook_currency', 'USD'),
                'currencySymbol' => get_option('skillscore_ebook_currency_symbol', '$'),
            )
        );

        $loaded = true;
    }
}

// Initialize global instance
global $skillscore_shortcodes;
$skillscore_shortcodes = new SkillScore_Ebook_Shortcodes();