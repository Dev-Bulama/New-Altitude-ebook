<?php
/**
 * Shortcodes Handler - FIXED for Single Template
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Shortcodes {

    /**
     * Constructor - Add template filter
     */
    public function __construct() {
        // Hook into single ebook template
        add_filter('the_content', array($this, 'single_ebook_content'), 20);
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        add_shortcode('skillscore_ebooks', array($this, 'ebooks_list_shortcode'));
        add_shortcode('skillscore_ebook', array($this, 'single_ebook_shortcode'));
    }

    /**
     * Inject single ebook content into the_content for single ebook pages
     */
    public function single_ebook_content($content) {
        // Only on single ebook pages
        if (!is_singular('ebook')) {
            return $content;
        }

        // Ensure assets are loaded
        $this->enqueue_assets();

        // Get the ebook ID
        $ebook_id = get_the_ID();

        // Render the single ebook template
        ob_start();
        $this->render_single_ebook($ebook_id);
        $single_content = ob_get_clean();

        // Return the custom template content
        return $single_content;
    }

    /**
     * Ebooks list shortcode.
     *
     * Usage: [skillscore_ebooks limit="12" category="fiction" orderby="date"]
     */
    public function ebooks_list_shortcode($atts) {
        // Ensure assets are loaded
        $this->enqueue_assets();

        $atts = shortcode_atts(array(
            'limit' => 12,
            'category' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'columns' => 3,
        ), $atts, 'skillscore_ebooks');

        $args = array(
            'post_type' => 'ebook',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => sanitize_text_field($atts['order']),
            'post_status' => 'publish',
        );

        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ebook_category',
                    'field' => 'slug',
                    'terms' => sanitize_text_field($atts['category']),
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
     * Single ebook shortcode.
     *
     * Usage: [skillscore_ebook id="123"]
     */
    public function single_ebook_shortcode($atts) {
        // Ensure assets are loaded
        $this->enqueue_assets();

        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'skillscore_ebook');

        $ebook_id = intval($atts['id']);

        if (!$ebook_id || get_post_type($ebook_id) !== 'ebook') {
            return '<p class="text-red-600">' . __('Invalid ebook ID.', 'skillscore-ebook') . '</p>';
        }

        ob_start();
        $this->render_single_ebook($ebook_id);
        return ob_get_clean();
    }

    /**
     * Render ebook card.
     */
    private function render_ebook_card($ebook_id) {
        $price = get_post_meta($ebook_id, '_ebook_price', true);
        $author = get_post_meta($ebook_id, '_ebook_author', true);
        $quantity = get_post_meta($ebook_id, '_ebook_quantity', true);
        $unlimited = get_post_meta($ebook_id, '_ebook_unlimited', true);
        $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');

        $in_stock = $unlimited || ($quantity && $quantity > 0);

        // Check for template in theme first
        $template = locate_template('skillscore-ebook/ebook-card.php');
        if (!$template) {
            $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/ebook-card.php';
        }

        if (file_exists($template)) {
            include $template;
        }
    }

    /**
     * Render single ebook view - ENHANCED
     */
    private function render_single_ebook($ebook_id) {
        // Get all meta data
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

        // Check for template in theme first
        $template = locate_template('skillscore-ebook/ebook-single.php');
        if (!$template) {
            $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/ebook-single.php';
        }

        if (file_exists($template)) {
            include $template;
        } else {
            // Fallback if template is missing
            echo '<div class="ebook-template-error" style="background: #fee2e2; border: 2px solid #dc2626; padding: 20px; margin: 20px 0; border-radius: 8px;">';
            echo '<h3 style="color: #dc2626; margin-top: 0;">⚠️ Template File Missing</h3>';
            echo '<p>The ebook single template file is missing. Please ensure <code>templates/ebook-single.php</code> exists in the plugin directory.</p>';
            echo '<p><strong>Expected location:</strong> <code>' . esc_html(SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/ebook-single.php') . '</code></p>';
            echo '</div>';
        }
    }

    /**
     * Enqueue assets when shortcode is used - ENHANCED
     */
    private function enqueue_assets() {
        // Only enqueue once
        static $assets_enqueued = false;
        if ($assets_enqueued) {
            return;
        }

        // Tailwind CSS via CDN
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
            SKILLSCORE_EBOOK_VERSION
        );

        // Custom JS
        wp_enqueue_script(
            'skillscore-ebook',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            SKILLSCORE_EBOOK_VERSION,
            true
        );

        // Localize script
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

        $assets_enqueued = true;
    }
}