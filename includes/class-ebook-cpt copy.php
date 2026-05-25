<?php
/**
 * Ebook Custom Post Type Handler - WITH MANUAL AUDIO UPLOAD
 * Adds audio file upload field to ebook editor
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_CPT {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomy'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_ebook', array($this, 'save_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Register ebook post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => __('Ebooks', 'skillscore-ebook'),
            'singular_name' => __('Ebook', 'skillscore-ebook'),
            'add_new' => __('Add New', 'skillscore-ebook'),
            'add_new_item' => __('Add New Ebook', 'skillscore-ebook'),
            'edit_item' => __('Edit Ebook', 'skillscore-ebook'),
            'new_item' => __('New Ebook', 'skillscore-ebook'),
            'view_item' => __('View Ebook', 'skillscore-ebook'),
            'search_items' => __('Search Ebooks', 'skillscore-ebook'),
            'not_found' => __('No ebooks found', 'skillscore-ebook'),
        );

        $args = array(
            'labels' => $labels,
            'public' => true,
            'has_archive' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-book',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt'),
            'rewrite' => array('slug' => 'ebook'),
            'capability_type' => 'post',
        );

        register_post_type('ebook', $args);
    }

    /**
     * Register ebook category taxonomy
     */
    public function register_taxonomy() {
        $labels = array(
            'name' => __('Categories', 'skillscore-ebook'),
            'singular_name' => __('Category', 'skillscore-ebook'),
        );

        $args = array(
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'rewrite' => array('slug' => 'ebook-category'),
        );

        register_taxonomy('ebook_category', array('ebook'), $args);
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Basic Details
        add_meta_box(
            'ebook_details',
            __('Ebook Details', 'skillscore-ebook'),
            array($this, 'render_details_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // File Upload
        add_meta_box(
            'ebook_file',
            __('Ebook File', 'skillscore-ebook'),
            array($this, 'render_file_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // Audio Preview Upload - NEW
        add_meta_box(
            'ebook_audio_preview',
            __('Audio Preview', 'skillscore-ebook'),
            array($this, 'render_audio_preview_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // Stock Management
        add_meta_box(
            'ebook_stock',
            __('Stock Management', 'skillscore-ebook'),
            array($this, 'render_stock_meta_box'),
            'ebook',
            'side',
            'default'
        );
    }

    /**
     * Render details meta box
     */
    public function render_details_meta_box($post) {
        wp_nonce_field('ebook_details_nonce', 'ebook_details_nonce_field');

        $price = get_post_meta($post->ID, '_ebook_price', true);
        $author = get_post_meta($post->ID, '_ebook_author', true);
        $publisher = get_post_meta($post->ID, '_ebook_publisher', true);
        $isbn = get_post_meta($post->ID, '_ebook_isbn', true);
        $pages = get_post_meta($post->ID, '_ebook_pages', true);
        $language = get_post_meta($post->ID, '_ebook_language', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="ebook_price"><?php _e('Price', 'skillscore-ebook'); ?> *</label></th>
                <td>
                    <input type="number" 
                           id="ebook_price" 
                           name="ebook_price" 
                           value="<?php echo esc_attr($price); ?>" 
                           step="0.01" 
                           min="0"
                           class="regular-text"
                           required>
                    <p class="description"><?php _e('Enter the price in your default currency', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ebook_author"><?php _e('Author', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="text" 
                           id="ebook_author" 
                           name="ebook_author" 
                           value="<?php echo esc_attr($author); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ebook_publisher"><?php _e('Publisher', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="text" 
                           id="ebook_publisher" 
                           name="ebook_publisher" 
                           value="<?php echo esc_attr($publisher); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ebook_isbn"><?php _e('ISBN', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="text" 
                           id="ebook_isbn" 
                           name="ebook_isbn" 
                           value="<?php echo esc_attr($isbn); ?>" 
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ebook_pages"><?php _e('Number of Pages', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="number" 
                           id="ebook_pages" 
                           name="ebook_pages" 
                           value="<?php echo esc_attr($pages); ?>" 
                           min="1"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><label for="ebook_language"><?php _e('Language', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="text" 
                           id="ebook_language" 
                           name="ebook_language" 
                           value="<?php echo esc_attr($language); ?>" 
                           class="regular-text"
                           placeholder="e.g., English">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render file upload meta box
     */
    public function render_file_meta_box($post) {
        $file_url = get_post_meta($post->ID, '_ebook_file_url', true);
        $file_id = get_post_meta($post->ID, '_ebook_file_id', true);
        ?>
        <div class="ebook-file-upload">
            <input type="hidden" id="ebook_file_url" name="ebook_file_url" value="<?php echo esc_attr($file_url); ?>">
            <input type="hidden" id="ebook_file_id" name="ebook_file_id" value="<?php echo esc_attr($file_id); ?>">
            
            <div id="ebook-file-display" style="margin-bottom: 15px;">
                <?php if ($file_url) : ?>
                    <div style="background: #f0f0f1; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1;">
                        <p style="margin: 0 0 10px 0;"><strong><?php _e('Current File:', 'skillscore-ebook'); ?></strong></p>
                        <p style="margin: 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                            📄 <?php echo esc_html(basename($file_url)); ?>
                        </p>
                    </div>
                <?php else : ?>
                    <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">
                        <p style="margin: 0; color: #856404;">
                            ⚠️ <?php _e('No file uploaded yet', 'skillscore-ebook'); ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>

            <button type="button" class="button button-primary" id="upload-ebook-file-btn">
                <?php echo $file_url ? __('Replace Ebook File', 'skillscore-ebook') : __('Upload Ebook File', 'skillscore-ebook'); ?>
            </button>

            <?php if ($file_url) : ?>
                <button type="button" class="button button-secondary" id="remove-ebook-file-btn" style="margin-left: 10px;">
                    <?php _e('Remove File', 'skillscore-ebook'); ?>
                </button>
            <?php endif; ?>

            <p class="description">
                <?php _e('Upload the ebook file (PDF, EPUB, MOBI). This file will be delivered to customers after purchase.', 'skillscore-ebook'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render audio preview meta box - NEW
     */
    public function render_audio_preview_meta_box($post) {
        $enable_audio = get_post_meta($post->ID, '_ebook_enable_audio', true);
        $audio_url = get_post_meta($post->ID, '_ebook_audio_preview_url', true);
        $audio_id = get_post_meta($post->ID, '_ebook_audio_preview_id', true);
        ?>
        <div class="ebook-audio-upload">
            
            <!-- Enable Audio Preview Checkbox -->
            <p>
                <label>
                    <input type="checkbox" 
                           name="ebook_enable_audio" 
                           value="1" 
                           <?php checked($enable_audio, '1'); ?>>
                    <?php _e('Enable audio preview for this ebook', 'skillscore-ebook'); ?>
                </label>
            </p>

            <hr style="margin: 20px 0;">

            <h4><?php _e('Audio Preview Options:', 'skillscore-ebook'); ?></h4>
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Choose one of the following options:', 'skillscore-ebook'); ?>
            </p>

            <!-- Option 1: Manual Upload -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="margin-top: 0;">
                    🎵 <?php _e('Option 1: Upload Audio File (Recommended)', 'skillscore-ebook'); ?>
                </h4>
                <p class="description">
                    <?php _e('Upload your own pre-recorded audio preview (MP3, WAV, OGG).', 'skillscore-ebook'); ?>
                </p>

                <input type="hidden" id="ebook_audio_preview_url" name="ebook_audio_preview_url" value="<?php echo esc_attr($audio_url); ?>">
                <input type="hidden" id="ebook_audio_preview_id" name="ebook_audio_preview_id" value="<?php echo esc_attr($audio_id); ?>">
                
                <div id="ebook-audio-display" style="margin: 15px 0;">
                    <?php if ($audio_url) : ?>
                        <div style="background: #d1e7dd; padding: 15px; border-radius: 4px; border-left: 4px solid #198754;">
                            <p style="margin: 0 0 10px 0;"><strong><?php _e('Current Audio:', 'skillscore-ebook'); ?></strong></p>
                            <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                                🎧 <?php echo esc_html(basename($audio_url)); ?>
                            </p>
                            <audio controls style="width: 100%; max-width: 400px;">
                                <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                                <source src="<?php echo esc_url($audio_url); ?>" type="audio/wav">
                                <source src="<?php echo esc_url($audio_url); ?>" type="audio/ogg">
                            </audio>
                        </div>
                    <?php else : ?>
                        <div style="background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #ffc107;">
                            <p style="margin: 0; color: #856404;">
                                ℹ️ <?php _e('No audio file uploaded. Auto-generation or browser TTS will be used.', 'skillscore-ebook'); ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="button button-primary" id="upload-audio-preview-btn">
                    <?php echo $audio_url ? __('Replace Audio Preview', 'skillscore-ebook') : __('Upload Audio Preview', 'skillscore-ebook'); ?>
                </button>

                <?php if ($audio_url) : ?>
                    <button type="button" class="button button-secondary" id="remove-audio-preview-btn" style="margin-left: 10px;">
                        <?php _e('Remove Audio', 'skillscore-ebook'); ?>
                    </button>
                <?php endif; ?>

                <p class="description" style="margin-top: 10px;">
                    <?php _e('Accepted formats: MP3, WAV, OGG • Max size: 10MB • Recommended: 30-60 seconds', 'skillscore-ebook'); ?>
                </p>
            </div>

            <!-- Option 2: Auto-generation -->
            <div style="background: #e7f3ff; padding: 20px; border-radius: 4px;">
                <h4 style="margin-top: 0;">
                    🤖 <?php _e('Option 2: Auto-Generate from Text', 'skillscore-ebook'); ?>
                </h4>
                <p class="description" style="margin-bottom: 10px;">
                    <?php _e('If no audio file is uploaded, the system will automatically:', 'skillscore-ebook'); ?>
                </p>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Use your configured TTS engine (Piper, Coqui) if available', 'skillscore-ebook'); ?></li>
                    <li><?php _e('Fall back to browser TTS (Web Speech API)', 'skillscore-ebook'); ?></li>
                    <li><?php _e('Generate audio from the ebook excerpt or first 500 characters', 'skillscore-ebook'); ?></li>
                </ul>
                <p class="description">
                    <strong><?php _e('Configure TTS settings:', 'skillscore-ebook'); ?></strong> 
                    <a href="<?php echo admin_url('edit.php?post_type=ebook&page=skillscore-ebook-settings'); ?>">
                        <?php _e('Go to Settings → Voice Preview', 'skillscore-ebook'); ?>
                    </a>
                </p>
            </div>

        </div>
        <?php
    }

    /**
     * Render stock management meta box
     */
    public function render_stock_meta_box($post) {
        wp_nonce_field('ebook_stock_nonce', 'ebook_stock_nonce_field');

        $unlimited = get_post_meta($post->ID, '_ebook_unlimited', true);
        $quantity = get_post_meta($post->ID, '_ebook_quantity', true);
        ?>
        <p>
            <label>
                <input type="checkbox" 
                       name="ebook_unlimited" 
                       value="1" 
                       <?php checked($unlimited, '1'); ?>>
                <?php _e('Unlimited Stock', 'skillscore-ebook'); ?>
            </label>
        </p>

        <p>
            <label for="ebook_quantity"><?php _e('Quantity Available', 'skillscore-ebook'); ?></label>
            <input type="number" 
                   id="ebook_quantity" 
                   name="ebook_quantity" 
                   value="<?php echo esc_attr($quantity); ?>" 
                   min="0"
                   class="widefat"
                   <?php echo $unlimited ? 'disabled' : ''; ?>>
        </p>

        <p class="description">
            <?php _e('Set stock quantity or enable unlimited stock.', 'skillscore-ebook'); ?>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('input[name="ebook_unlimited"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#ebook_quantity').prop('disabled', true);
                } else {
                    $('#ebook_quantity').prop('disabled', false);
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Check nonces
        if (!isset($_POST['ebook_details_nonce_field']) || 
            !wp_verify_nonce($_POST['ebook_details_nonce_field'], 'ebook_details_nonce')) {
            return;
        }

        if (!isset($_POST['ebook_stock_nonce_field']) || 
            !wp_verify_nonce($_POST['ebook_stock_nonce_field'], 'ebook_stock_nonce')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save basic details
        $fields = array(
            'ebook_price' => 'sanitize_text_field',
            'ebook_author' => 'sanitize_text_field',
            'ebook_publisher' => 'sanitize_text_field',
            'ebook_isbn' => 'sanitize_text_field',
            'ebook_pages' => 'absint',
            'ebook_language' => 'sanitize_text_field',
        );

        foreach ($fields as $field => $sanitize_func) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, '_' . $field, $sanitize_func($_POST[$field]));
            }
        }

        // Save file URLs
        if (isset($_POST['ebook_file_url'])) {
            update_post_meta($post_id, '_ebook_file_url', esc_url_raw($_POST['ebook_file_url']));
        }
        if (isset($_POST['ebook_file_id'])) {
            update_post_meta($post_id, '_ebook_file_id', absint($_POST['ebook_file_id']));
        }

        // Save audio preview - NEW
        if (isset($_POST['ebook_enable_audio'])) {
            update_post_meta($post_id, '_ebook_enable_audio', '1');
        } else {
            update_post_meta($post_id, '_ebook_enable_audio', '0');
        }

        if (isset($_POST['ebook_audio_preview_url'])) {
            update_post_meta($post_id, '_ebook_audio_preview_url', esc_url_raw($_POST['ebook_audio_preview_url']));
        }
        if (isset($_POST['ebook_audio_preview_id'])) {
            update_post_meta($post_id, '_ebook_audio_preview_id', absint($_POST['ebook_audio_preview_id']));
        }

        // Save stock
        if (isset($_POST['ebook_unlimited'])) {
            update_post_meta($post_id, '_ebook_unlimited', '1');
        } else {
            update_post_meta($post_id, '_ebook_unlimited', '0');
        }

        if (isset($_POST['ebook_quantity'])) {
            update_post_meta($post_id, '_ebook_quantity', absint($_POST['ebook_quantity']));
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        global $post_type;

        if ($post_type !== 'ebook') {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_script(
            'skillscore-ebook-admin',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SKILLSCORE_EBOOK_VERSION,
            true
        );
    }
}