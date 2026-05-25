<?php
/**
 * The core plugin class.
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     */
    protected $version;

    /**
     * Initialize the core plugin.
     */
    public function __construct() {
        $this->version = SKILLSCORE_EBOOK_VERSION;
        $this->plugin_name = 'skillscore-ebook';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies.
     */
    private function load_dependencies() {
        // Core classes
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-ebook-cpt.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-shortcodes.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-payment-handler.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-download-handler.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-voice-preview.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-admin-settings.php';
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-sample-generator.php';

        // Elementor widget - only load when Elementor is active
        // Don't load here, load it via hook when Elementor initializes
    }

    /**
     * Register admin-specific hooks.
     */
    private function define_admin_hooks() {
        // Register custom post type
        $ebook_cpt = new SkillScore_Ebook_CPT();
        add_action('init', array($ebook_cpt, 'register_post_type'));
        add_action('add_meta_boxes', array($ebook_cpt, 'add_meta_boxes'));
        add_action('save_post_ebook', array($ebook_cpt, 'save_meta_boxes'), 10, 2);

        // Admin settings
        $admin_settings = new SkillScore_Ebook_Admin_Settings();
        add_action('admin_menu', array($admin_settings, 'add_admin_menu'));
        add_action('admin_init', array($admin_settings, 'register_settings'));
        add_action('admin_enqueue_scripts', array($admin_settings, 'enqueue_admin_assets'));

        // Sample generator
        add_action('admin_menu', array('SkillScore_Sample_Generator', 'register_admin_page'));

        // Handle file uploads
        add_action('admin_post_skillscore_upload_ebook_file', array($ebook_cpt, 'handle_file_upload'));
    }

    /**
     * Register public-facing hooks.
     */
    private function define_public_hooks() {
        // Shortcodes
        $shortcodes = new SkillScore_Ebook_Shortcodes();
        add_action('init', array($shortcodes, 'register_shortcodes'));

        // Payment handler
        $payment_handler = new SkillScore_Ebook_Payment_Handler();
        add_action('wp_ajax_skillscore_initiate_payment', array($payment_handler, 'initiate_payment'));
        add_action('wp_ajax_nopriv_skillscore_initiate_payment', array($payment_handler, 'initiate_payment'));
        add_action('init', array($payment_handler, 'handle_payment_webhook'));

        // Download handler
        $download_handler = new SkillScore_Ebook_Download_Handler();
        add_action('init', array($download_handler, 'handle_download_request'));

        // Voice preview
        $voice_preview = new SkillScore_Ebook_Voice_Preview();
        add_action('wp_ajax_skillscore_get_audio_preview', array($voice_preview, 'get_audio_preview'));
        add_action('wp_ajax_nopriv_skillscore_get_audio_preview', array($voice_preview, 'get_audio_preview'));

        // Elementor widget - only register if Elementor is active
        add_action('elementor/widgets/register', array($this, 'register_elementor_widgets'));

        // Enqueue public assets
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
    }

    /**
     * Register Elementor widgets.
     */
    public function register_elementor_widgets($widgets_manager) {
        // Check if Elementor is active before loading widget class
        if (!class_exists('Elementor\Widget_Base')) {
            return;
        }

        // Load the widget class file
        require_once SKILLSCORE_EBOOK_PLUGIN_DIR . 'includes/class-elementor-widget.php';

        // Register the widget
        $widgets_manager->register(new SkillScore_Elementor_Ebook_Widget());
    }

    /**
     * Enqueue public-facing assets.
     */
    public function enqueue_public_assets() {
        // Tailwind CSS via CDN
        wp_enqueue_style(
            'tailwind-cdn',
            'https://cdn.jsdelivr.net/npm/tailwindcss@3.4.0/dist/tailwind.min.css',
            array(),
            '3.4.0'
        );

        // Custom CSS
        wp_enqueue_style(
            $this->plugin_name,
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/css/public.css',
            array('tailwind-cdn'),
            $this->version
        );

        // Custom JS
        wp_enqueue_script(
            $this->plugin_name,
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            $this->version,
            true
        );

        // Localize script
        wp_localize_script(
            $this->plugin_name,
            'skillscoreEbook',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skillscore_ebook_nonce'),
                'currency' => get_option('skillscore_ebook_currency', 'USD'),
                'currencySymbol' => get_option('skillscore_ebook_currency_symbol', '$'),
            )
        );
    }

    /**
     * Run the plugin.
     */
    public function run() {
        // Plugin is now loaded and ready
    }
}
