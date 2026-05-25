<?php
/**
 * Ebook Custom Post Type Handler - SIMPLE FILE UPLOAD VERSION
 * Uses direct file inputs instead of WordPress media library
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
        add_action('save_post_ebook', array($this, 'save_meta_boxes'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Handle file uploads
        add_action('admin_init', array($this, 'handle_file_uploads'));
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

        // Audio Preview Upload
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
        wp_nonce_field('ebook_details_save', 'ebook_details_nonce');

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
     * Render file upload meta box - SIMPLE FILE INPUT
     */
    public function render_file_meta_box($post) {
        wp_nonce_field('ebook_file_save', 'ebook_file_nonce');
        
        $file_url = get_post_meta($post->ID, '_ebook_file_url', true);
        ?>
        <div class="ebook-file-upload">
            
            <?php if ($file_url) : ?>
                <!-- Current File Display -->
                <div style="background: #d1f4d1; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0;"><strong>✓ <?php _e('Current File:', 'skillscore-ebook'); ?></strong></p>
                    <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                        📄 <?php echo esc_html(basename($file_url)); ?>
                    </p>
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="button button-small" style="margin-right: 10px;">
                        <?php _e('View File', 'skillscore-ebook'); ?>
                    </a>
                    <label>
                        <input type="checkbox" name="remove_ebook_file" value="1">
                        <?php _e('Remove this file', 'skillscore-ebook'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <!-- File Upload Input -->
            <p>
                <label for="ebook_file_upload" style="display: block; margin-bottom: 10px;">
                    <strong><?php echo $file_url ? __('Replace Ebook File:', 'skillscore-ebook') : __('Upload Ebook File:', 'skillscore-ebook'); ?></strong>
                </label>
                <input type="file" 
                       id="ebook_file_upload" 
                       name="ebook_file_upload" 
                       accept=".pdf,.epub,.mobi"
                       style="display: block; margin-bottom: 5px;">
                <span class="description">
                    <?php _e('Accepted formats: PDF, EPUB, MOBI • Max size: 50MB', 'skillscore-ebook'); ?>
                </span>
            </p>

        </div>
        <?php
    }

    /**
     * Render audio preview meta box - SIMPLE FILE INPUT
     */
    public function render_audio_preview_meta_box($post) {
        wp_nonce_field('ebook_audio_save', 'ebook_audio_nonce');
        
        $enable_audio = get_post_meta($post->ID, '_ebook_enable_audio', true);
        $audio_url = get_post_meta($post->ID, '_ebook_audio_preview_url', true);
        ?>
        <div class="ebook-audio-upload">
            
            <!-- Enable Audio Preview Checkbox -->
            <p>
                <label>
                    <input type="checkbox" 
                           name="ebook_enable_audio" 
                           id="ebook_enable_audio"
                           value="1" 
                           <?php checked($enable_audio, '1'); ?>>
                    <strong><?php _e('Enable audio preview for this ebook', 'skillscore-ebook'); ?></strong>
                </label>
            </p>

            <hr style="margin: 20px 0;">

            <?php if ($audio_url) : ?>
                <!-- Current Audio Display -->
                <div style="background: #d1f4d1; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0;"><strong>✓ <?php _e('Current Audio:', 'skillscore-ebook'); ?></strong></p>
                    <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px;">
                        🎧 <?php echo esc_html(basename($audio_url)); ?>
                    </p>
                    <audio controls style="width: 100%; max-width: 400px; margin-bottom: 10px;">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/wav">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/ogg">
                    </audio>
                    <br>
                    <label>
                        <input type="checkbox" name="remove_audio_preview" value="1">
                        <?php _e('Remove this audio', 'skillscore-ebook'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <!-- Audio Upload Input -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 4px; margin-bottom: 20px;">
                <h4 style="margin-top: 0;">
                    🎵 <?php _e('Upload Audio Preview', 'skillscore-ebook'); ?>
                </h4>
                <p class="description" style="margin-bottom: 15px;">
                    <?php _e('Upload your own pre-recorded audio preview (MP3, WAV, OGG).', 'skillscore-ebook'); ?>
                </p>

                <p>
                    <label for="audio_preview_upload" style="display: block; margin-bottom: 10px;">
                        <strong><?php echo $audio_url ? __('Replace Audio File:', 'skillscore-ebook') : __('Choose Audio File:', 'skillscore-ebook'); ?></strong>
                    </label>
                    <input type="file" 
                           id="audio_preview_upload" 
                           name="audio_preview_upload" 
                           accept=".mp3,.wav,.ogg,audio/*"
                           style="display: block; margin-bottom: 5px;">
                    <span class="description">
                        <?php _e('Accepted formats: MP3, WAV, OGG • Max size: 10MB • Recommended: 30-60 seconds', 'skillscore-ebook'); ?>
                    </span>
                </p>
            </div>

            <!-- Auto-generation Info -->
            <div style="background: #e7f3ff; padding: 20px; border-radius: 4px;">
                <h4 style="margin-top: 0;">
                    🤖 <?php _e('Auto-Generate (Fallback)', 'skillscore-ebook'); ?>
                </h4>
                <p class="description">
                    <?php _e('If no audio file is uploaded, the system will automatically use TTS or browser speech to generate audio from your ebook excerpt.', 'skillscore-ebook'); ?>
                </p>
            </div>

        </div>
        <?php
    }

    /**
     * Render stock management meta box
     */
    public function render_stock_meta_box($post) {
        wp_nonce_field('ebook_stock_save', 'ebook_stock_nonce');

        $unlimited = get_post_meta($post->ID, '_ebook_unlimited', true);
        $quantity = get_post_meta($post->ID, '_ebook_quantity', true);
        ?>
        <p>
            <label>
                <input type="checkbox" 
                       name="ebook_unlimited" 
                       id="ebook_unlimited"
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
                   class="widefat">
        </p>

        <p class="description">
            <?php _e('Set stock quantity or enable unlimited stock.', 'skillscore-ebook'); ?>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#ebook_unlimited').on('change', function() {
                $('#ebook_quantity').prop('disabled', $(this).is(':checked'));
            }).trigger('change');
        });
        </script>
        <?php
    }

    /**
     * Handle file uploads during save
     */
    public function handle_file_uploads() {
        // This is handled in save_meta_boxes
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id, $post) {
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save details
        if (isset($_POST['ebook_details_nonce']) && wp_verify_nonce($_POST['ebook_details_nonce'], 'ebook_details_save')) {
            $this->save_details($post_id);
        }

        // Save file
        if (isset($_POST['ebook_file_nonce']) && wp_verify_nonce($_POST['ebook_file_nonce'], 'ebook_file_save')) {
            $this->save_file($post_id);
        }

        // Save audio
        if (isset($_POST['ebook_audio_nonce']) && wp_verify_nonce($_POST['ebook_audio_nonce'], 'ebook_audio_save')) {
            $this->save_audio($post_id);
        }

        // Save stock
        if (isset($_POST['ebook_stock_nonce']) && wp_verify_nonce($_POST['ebook_stock_nonce'], 'ebook_stock_save')) {
            $this->save_stock($post_id);
        }
    }

    /**
     * Save details
     */
    private function save_details($post_id) {
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
    }

    /**
     * Save file - HANDLES FILE UPLOAD
     */
    private function save_file($post_id) {
        // Handle file removal
        if (isset($_POST['remove_ebook_file']) && $_POST['remove_ebook_file'] == '1') {
            delete_post_meta($post_id, '_ebook_file_url');
            delete_post_meta($post_id, '_ebook_file_id');
            return;
        }

        // Handle file upload
        if (isset($_FILES['ebook_file_upload']) && $_FILES['ebook_file_upload']['error'] == UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $file = $_FILES['ebook_file_upload'];

            // Validate file type
            $allowed_types = array('pdf', 'epub', 'mobi');
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types)) {
                add_filter('redirect_post_location', function($location) {
                    return add_query_arg('ebook_upload_error', 'invalid_type', $location);
                });
                return;
            }

            // Upload file
            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['error'])) {
                add_filter('redirect_post_location', function($location) use ($upload) {
                    return add_query_arg('ebook_upload_error', urlencode($upload['error']), $location);
                });
                return;
            }

            // Save URL
            update_post_meta($post_id, '_ebook_file_url', $upload['url']);

            // Create attachment
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
            update_post_meta($post_id, '_ebook_file_id', $attach_id);

            // Success message
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('ebook_upload_success', '1', $location);
            });
        }
    }

    /**
     * Save audio - HANDLES AUDIO UPLOAD
     */
    private function save_audio($post_id) {
        // Save enable audio checkbox
        if (isset($_POST['ebook_enable_audio']) && $_POST['ebook_enable_audio'] === '1') {
            update_post_meta($post_id, '_ebook_enable_audio', '1');
        } else {
            update_post_meta($post_id, '_ebook_enable_audio', '0');
        }

        // Handle audio removal
        if (isset($_POST['remove_audio_preview']) && $_POST['remove_audio_preview'] == '1') {
            delete_post_meta($post_id, '_ebook_audio_preview_url');
            delete_post_meta($post_id, '_ebook_audio_preview_id');
            return;
        }

        // Handle audio upload
        if (isset($_FILES['audio_preview_upload']) && $_FILES['audio_preview_upload']['error'] == UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            $file = $_FILES['audio_preview_upload'];

            // Validate file type
            $allowed_types = array('mp3', 'wav', 'ogg');
            $file_type = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($file_type, $allowed_types)) {
                add_filter('redirect_post_location', function($location) {
                    return add_query_arg('audio_upload_error', 'invalid_type', $location);
                });
                return;
            }

            // Validate file size (10MB max)
            if ($file['size'] > 10485760) {
                add_filter('redirect_post_location', function($location) {
                    return add_query_arg('audio_upload_error', 'too_large', $location);
                });
                return;
            }

            // Upload file
            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['error'])) {
                add_filter('redirect_post_location', function($location) use ($upload) {
                    return add_query_arg('audio_upload_error', urlencode($upload['error']), $location);
                });
                return;
            }

            // Save URL
            update_post_meta($post_id, '_ebook_audio_preview_url', $upload['url']);

            // Create attachment
            $attachment = array(
                'post_mime_type' => $upload['type'],
                'post_title' => sanitize_file_name($file['name']),
                'post_content' => '',
                'post_status' => 'inherit'
            );

            $attach_id = wp_insert_attachment($attachment, $upload['file'], $post_id);
            update_post_meta($post_id, '_ebook_audio_preview_id', $attach_id);

            // Success message
            add_filter('redirect_post_location', function($location) {
                return add_query_arg('audio_upload_success', '1', $location);
            });
        }
    }

    /**
     * Save stock
     */
    private function save_stock($post_id) {
        if (isset($_POST['ebook_unlimited']) && $_POST['ebook_unlimited'] === '1') {
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

        if (($hook === 'post.php' || $hook === 'post-new.php') && $post_type === 'ebook') {
            wp_enqueue_style(
                'skillscore-ebook-admin',
                SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                SKILLSCORE_EBOOK_VERSION
            );

            // Show upload messages
            add_action('admin_notices', array($this, 'show_upload_notices'));
        }
    }

    /**
     * Show upload success/error messages
     */
    public function show_upload_notices() {
        if (isset($_GET['ebook_upload_success'])) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Ebook file uploaded successfully!</strong></p></div>';
        }

        if (isset($_GET['ebook_upload_error'])) {
            $error = sanitize_text_field($_GET['ebook_upload_error']);
            $message = 'Upload failed: ' . $error;
            echo '<div class="notice notice-error is-dismissible"><p><strong>✗ ' . esc_html($message) . '</strong></p></div>';
        }

        if (isset($_GET['audio_upload_success'])) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✓ Audio preview uploaded successfully!</strong></p></div>';
        }

        if (isset($_GET['audio_upload_error'])) {
            $error = sanitize_text_field($_GET['audio_upload_error']);
            $message = $error === 'too_large' ? 'Audio file too large (max 10MB)' : 'Upload failed: ' . $error;
            echo '<div class="notice notice-error is-dismissible"><p><strong>✗ ' . esc_html($message) . '</strong></p></div>';
        }
    }
}