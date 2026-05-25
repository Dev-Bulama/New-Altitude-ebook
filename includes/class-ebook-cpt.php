<?php
/**
 * Ebook Custom Post Type - COMPLETE WITH FORMAT SELECTION
 * Includes: Digital Ebook, Full Audiobook Upload, Printed Book with Shipping
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
        
        // CRITICAL: Add enctype to post form for file uploads
        add_action('post_edit_form_tag', array($this, 'add_form_enctype'));
    }

    /**
     * Add enctype to form - CRITICAL FOR FILE UPLOADS
     */
    public function add_form_enctype() {
        global $post;
        if ($post && $post->post_type === 'ebook') {
            echo ' enctype="multipart/form-data"';
        }
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
        add_meta_box(
            'ebook_details',
            __('Ebook Details', 'skillscore-ebook'),
            array($this, 'render_details_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // NEW: Format Selection Meta Box
        add_meta_box(
            'ebook_formats',
            __('ðŸ“¦ Available Formats', 'skillscore-ebook'),
            array($this, 'render_formats_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        add_meta_box(
            'ebook_file',
            __('ðŸ“– Digital Ebook File', 'skillscore-ebook'),
            array($this, 'render_file_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // NEW: Full Audiobook Upload
        add_meta_box(
            'ebook_audiobook',
            __('ðŸŽ§ Full Audiobook File', 'skillscore-ebook'),
            array($this, 'render_audiobook_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        add_meta_box(
            'ebook_audio_preview',
            __('Audio Preview (Sample)', 'skillscore-ebook'),
            array($this, 'render_audio_preview_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        // NEW: Shipping Settings for Printed Books
        add_meta_box(
            'ebook_shipping',
            __('ðŸ“¦ Shipping Settings (Printed Books)', 'skillscore-ebook'),
            array($this, 'render_shipping_meta_box'),
            'ebook',
            'normal',
            'high'
        );

        add_meta_box(
            'ebook_stock',
            __('Stock Management', 'skillscore-ebook'),
            array($this, 'render_stock_meta_box'),
            'ebook',
            'side',
            'default'
        );

        // NEW: Purchase Email & Thank You Page Settings
        add_meta_box(
            'ebook_purchase_settings',
            __('📧 Purchase Email & Thank You Page Settings', 'skillscore-ebook'),
            array($this, 'render_purchase_settings_meta_box'),
            'ebook',
            'normal',
            'high'
        );
    }

    /**
     * NEW: Render format selection meta box
     */
    public function render_formats_meta_box($post) {
        wp_nonce_field('ebook_formats_save', 'ebook_formats_nonce');

        $format_ebook = get_post_meta($post->ID, '_ebook_format_ebook', true);
        $format_audio = get_post_meta($post->ID, '_ebook_format_audio', true);
        $format_printed = get_post_meta($post->ID, '_ebook_format_printed', true);
        ?>
        <div style="background: #f0f9ff; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px;">
            <p style="margin-top: 0; font-weight: 600; color: #1e40af;">
                Select which formats are available for this ebook:
            </p>
            
            <p style="margin: 15px 0;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 12px; background: white; border-radius: 6px; cursor: pointer; border: 2px solid #e5e7eb;">
                    <input type="checkbox" 
                           name="ebook_format_ebook" 
                           value="1" 
                           <?php checked($format_ebook, '1'); ?>
                           style="width: 20px; height: 20px;">
                    <span style="font-size: 24px;">ðŸ“–</span>
                    <strong style="font-size: 16px;">Digital Ebook</strong>
                    <span style="color: #6b7280; margin-left: auto;">(PDF/EPUB/MOBI)</span>
                </label>
            </p>

            <p style="margin: 15px 0;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 12px; background: white; border-radius: 6px; cursor: pointer; border: 2px solid #e5e7eb;">
                    <input type="checkbox" 
                           name="ebook_format_audio" 
                           value="1" 
                           <?php checked($format_audio, '1'); ?>
                           style="width: 20px; height: 20px;">
                    <span style="font-size: 24px;">ðŸŽ§</span>
                    <strong style="font-size: 16px;">Audiobook</strong>
                    <span style="color: #6b7280; margin-left: auto;">(MP3/M4A)</span>
                </label>
            </p>

            <p style="margin: 15px 0 0 0;">
                <label style="display: flex; align-items: center; gap: 10px; padding: 12px; background: white; border-radius: 6px; cursor: pointer; border: 2px solid #e5e7eb;">
                    <input type="checkbox" 
                           name="ebook_format_printed" 
                           value="1" 
                           <?php checked($format_printed, '1'); ?>
                           style="width: 20px; height: 20px;">
                    <span style="font-size: 24px;">ðŸ“¦</span>
                    <strong style="font-size: 16px;">Printed Book</strong>
                    <span style="color: #6b7280; margin-left: auto;">(Physical delivery)</span>
                </label>
            </p>

            <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 15px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; font-size: 14px; color: #92400e;">
                    <strong>ðŸ’¡ Note:</strong> Upload the appropriate files below for each format you enable.
                </p>
            </div>
        </div>
        <?php
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
                    <p class="description"><?php _e('Base price for all formats', 'skillscore-ebook'); ?></p>
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
     * Render digital ebook file upload meta box
     */
    public function render_file_meta_box($post) {
        wp_nonce_field('ebook_file_save', 'ebook_file_nonce');
        
        $file_url = get_post_meta($post->ID, '_ebook_file_url', true);
        ?>
        <div class="ebook-file-upload">
            
            <?php if ($file_url) : ?>
                <div style="background: #d1f4d1; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0;"><strong>âœ“ <?php _e('Current File:', 'skillscore-ebook'); ?></strong></p>
                    <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                        ðŸ“„ <?php echo esc_html(basename($file_url)); ?>
                    </p>
                    <a href="<?php echo esc_url($file_url); ?>" target="_blank" class="button button-small" style="margin-right: 10px;">
                        <?php _e('View File', 'skillscore-ebook'); ?>
                    </a>
                    <label style="display: inline-block;">
                        <input type="checkbox" name="remove_ebook_file" value="1">
                        <?php _e('Remove this file', 'skillscore-ebook'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <p>
                <label for="ebook_file_upload" style="display: block; margin-bottom: 10px;">
                    <strong><?php echo $file_url ? __('Replace Digital Ebook:', 'skillscore-ebook') : __('Upload Digital Ebook:', 'skillscore-ebook'); ?></strong>
                </label>
                <input type="file" 
                       id="ebook_file_upload" 
                       name="ebook_file_upload" 
                       accept=".pdf,.epub,.mobi"
                       style="display: block; margin-bottom: 5px;">
                <span class="description">
                    <?php _e('For Digital Ebook format â€¢ PDF, EPUB, MOBI â€¢ Max 50MB', 'skillscore-ebook'); ?>
                </span>
            </p>

        </div>
        <?php
    }

    /**
     * NEW: Render full audiobook upload meta box
     */
    public function render_audiobook_meta_box($post) {
        wp_nonce_field('ebook_audiobook_save', 'ebook_audiobook_nonce');
        
        $audiobook_url = get_post_meta($post->ID, '_ebook_audiobook_url', true);
        ?>
        <div class="ebook-audiobook-upload">
            
            <?php if ($audiobook_url) : ?>
                <div style="background: #d1f4d1; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0;"><strong>âœ“ <?php _e('Current Audiobook:', 'skillscore-ebook'); ?></strong></p>
                    <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px; word-break: break-all;">
                        ðŸŽ§ <?php echo esc_html(basename($audiobook_url)); ?>
                    </p>
                    <audio controls style="width: 100%; max-width: 400px; margin-bottom: 10px;">
                        <source src="<?php echo esc_url($audiobook_url); ?>" type="audio/mpeg">
                        <source src="<?php echo esc_url($audiobook_url); ?>" type="audio/mp4">
                    </audio>
                    <br>
                    <a href="<?php echo esc_url($audiobook_url); ?>" target="_blank" class="button button-small" style="margin-right: 10px;">
                        <?php _e('Download File', 'skillscore-ebook'); ?>
                    </a>
                    <label style="display: inline-block;">
                        <input type="checkbox" name="remove_audiobook" value="1">
                        <?php _e('Remove this audiobook', 'skillscore-ebook'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div style="background: #f0f9ff; padding: 20px; border-radius: 4px; border: 2px solid #3b82f6;">
                <p style="margin-top: 0;">
                    <strong>ðŸŽ§ <?php echo $audiobook_url ? __('Replace Full Audiobook:', 'skillscore-ebook') : __('Upload Full Audiobook:', 'skillscore-ebook'); ?></strong>
                </p>
                <p>
                    <input type="file" 
                           id="audiobook_upload" 
                           name="audiobook_upload" 
                           accept=".mp3,.m4a,.m4b,audio/*"
                           style="display: block; margin-bottom: 5px;">
                    <span class="description">
                        <?php _e('Complete audiobook file â€¢ MP3, M4A, M4B â€¢ Max 500MB', 'skillscore-ebook'); ?>
                    </span>
                </p>
                <div style="background: #fef3c7; padding: 12px; border-radius: 6px; margin-top: 10px;">
                    <p style="margin: 0; font-size: 13px; color: #92400e;">
                        <strong>Note:</strong> This is the FULL audiobook that customers will download when they purchase the Audio format.
                    </p>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Render audio preview meta box (for sample/preview)
     */
    public function render_audio_preview_meta_box($post) {
        wp_nonce_field('ebook_audio_save', 'ebook_audio_nonce');
        
        $enable_audio = get_post_meta($post->ID, '_ebook_enable_audio', true);
        $audio_url = get_post_meta($post->ID, '_ebook_audio_preview_url', true);
        ?>
        <div class="ebook-audio-upload">
            
            <p>
                <label>
                    <input type="checkbox" 
                           name="ebook_enable_audio" 
                           value="1" 
                           <?php checked($enable_audio, '1'); ?>>
                    <strong><?php _e('Enable audio preview/sample', 'skillscore-ebook'); ?></strong>
                </label>
            </p>

            <hr style="margin: 20px 0;">

            <?php if ($audio_url) : ?>
                <div style="background: #d1f4d1; padding: 15px; border-radius: 4px; border-left: 4px solid #28a745; margin-bottom: 15px;">
                    <p style="margin: 0 0 10px 0;"><strong>âœ“ <?php _e('Current Preview:', 'skillscore-ebook'); ?></strong></p>
                    <p style="margin: 0 0 10px 0; font-family: monospace; font-size: 12px;">
                        ðŸŽµ <?php echo esc_html(basename($audio_url)); ?>
                    </p>
                    <audio controls style="width: 100%; max-width: 400px; margin-bottom: 10px;">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/mpeg">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/wav">
                        <source src="<?php echo esc_url($audio_url); ?>" type="audio/ogg">
                    </audio>
                    <br>
                    <label style="display: inline-block;">
                        <input type="checkbox" name="remove_audio_preview" value="1">
                        <?php _e('Remove this preview', 'skillscore-ebook'); ?>
                    </label>
                </div>
            <?php endif; ?>

            <div style="background: #f8f9fa; padding: 20px; border-radius: 4px;">
                <p style="margin-top: 0;">
                    <strong>ðŸŽµ <?php echo $audio_url ? __('Replace Preview Sample:', 'skillscore-ebook') : __('Upload Preview Sample:', 'skillscore-ebook'); ?></strong>
                </p>
                <p>
                    <input type="file" 
                           id="audio_preview_upload" 
                           name="audio_preview_upload" 
                           accept=".mp3,.wav,.ogg,audio/*"
                           style="display: block; margin-bottom: 5px;">
                    <span class="description">
                        <?php _e('Short preview/sample â€¢ MP3, WAV, OGG â€¢ Max 10MB', 'skillscore-ebook'); ?>
                    </span>
                </p>
            </div>

        </div>
        <?php
    }

    /**
     * NEW: Render shipping settings meta box
     */
    public function render_shipping_meta_box($post) {
        wp_nonce_field('ebook_shipping_save', 'ebook_shipping_nonce');

        $shipping_fee = get_post_meta($post->ID, '_ebook_shipping_fee', true);
        $shipping_type = get_post_meta($post->ID, '_ebook_shipping_type', true);
        ?>
        <div style="background: #fef3c7; padding: 15px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #f59e0b;">
            <p style="margin: 0; font-size: 14px; color: #92400e;">
                <strong>ðŸ“¦ Note:</strong> These settings only apply when "Printed Book" format is enabled and selected by customer.
            </p>
        </div>

        <table class="form-table">
            <tr>
                <th><label for="ebook_shipping_fee"><?php _e('Shipping Fee', 'skillscore-ebook'); ?></label></th>
                <td>
                    <input type="number" 
                           id="ebook_shipping_fee" 
                           name="ebook_shipping_fee" 
                           value="<?php echo esc_attr($shipping_fee); ?>" 
                           step="0.01" 
                           min="0"
                           class="regular-text"
                           placeholder="0.00">
                    <p class="description"><?php _e('Additional fee for shipping (in your default currency)', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="ebook_shipping_type"><?php _e('Shipping Type', 'skillscore-ebook'); ?></label></th>
                <td>
                    <select name="ebook_shipping_type" id="ebook_shipping_type" class="regular-text">
                        <option value=""><?php _e('Select shipping type', 'skillscore-ebook'); ?></option>
                        <option value="standard" <?php selected($shipping_type, 'standard'); ?>><?php _e('Standard (5-7 days)', 'skillscore-ebook'); ?></option>
                        <option value="express" <?php selected($shipping_type, 'express'); ?>><?php _e('Express (2-3 days)', 'skillscore-ebook'); ?></option>
                        <option value="overnight" <?php selected($shipping_type, 'overnight'); ?>><?php _e('Overnight (1 day)', 'skillscore-ebook'); ?></option>
                        <option value="international" <?php selected($shipping_type, 'international'); ?>><?php _e('International (10-14 days)', 'skillscore-ebook'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
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
        <?php
    }

    /**
     * NEW: Render Purchase Email & Thank You Page Settings meta box
     */
    public function render_purchase_settings_meta_box($post) {
        wp_nonce_field('ebook_purchase_settings_save', 'ebook_purchase_settings_nonce');

        // Get saved values
        $enable_custom_email = get_post_meta($post->ID, '_ebook_enable_custom_email', true);
        $custom_email_subject = get_post_meta($post->ID, '_ebook_custom_email_subject', true);
        $custom_email_message = get_post_meta($post->ID, '_ebook_custom_email_message', true);
        $enable_thank_you_redirect = get_post_meta($post->ID, '_ebook_enable_thank_you_redirect', true);
        $thank_you_page_url = get_post_meta($post->ID, '_ebook_thank_you_page_url', true);

        // Default values
        if (empty($custom_email_subject)) {
            $custom_email_subject = 'Thank you for your purchase!';
        }
        ?>
        <div style="background: #f0fdf4; border: 2px solid #10b981; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <p style="margin-top: 0; font-weight: 600; color: #065f46; font-size: 15px;">
                📧 Customize the email sent to customers when they purchase THIS specific ebook
            </p>
        </div>

        <!-- Custom Email Section -->
        <div style="background: #ffffff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #1f2937; border-bottom: 2px solid #3b82f6; padding-bottom: 10px;">
                ✉️ Custom Purchase Email
            </h3>
            
            <p style="margin: 15px 0;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" 
                           name="ebook_enable_custom_email" 
                           id="ebook_enable_custom_email"
                           value="1" 
                           <?php checked($enable_custom_email, '1'); ?>
                           style="width: 18px; height: 18px;">
                    <strong style="font-size: 15px;">Enable Custom Email for this Ebook</strong>
                </label>
                <span style="display: block; margin-left: 28px; color: #6b7280; font-size: 13px; margin-top: 5px;">
                    When enabled, customers will receive this custom email instead of the default purchase email
                </span>
            </p>

            <div id="custom_email_fields" style="<?php echo $enable_custom_email != '1' ? 'display: none;' : ''; ?> margin-top: 20px;">
                
                <!-- Email Subject -->
                <p>
                    <label for="ebook_custom_email_subject" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                        Email Subject Line:
                    </label>
                    <input type="text" 
                           id="ebook_custom_email_subject" 
                           name="ebook_custom_email_subject" 
                           value="<?php echo esc_attr($custom_email_subject); ?>" 
                           class="widefat"
                           placeholder="Thank you for your purchase!"
                           style="padding: 8px;">
                    <span style="display: block; color: #6b7280; font-size: 13px; margin-top: 5px;">
                        The subject line of the email that will be sent to customers
                    </span>
                </p>

                <!-- Email Message -->
                <p>
                    <label for="ebook_custom_email_message" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                        Email Message:
                    </label>
                    <?php
                    $editor_settings = array(
                        'textarea_name' => 'ebook_custom_email_message',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                        'teeny' => false,
                        'tinymce' => array(
                            'toolbar1' => 'bold,italic,underline,link,unlink,bullist,numlist,alignleft,aligncenter,alignright',
                        ),
                        'quicktags' => array('buttons' => 'strong,em,link,ul,ol,li')
                    );
                    wp_editor($custom_email_message, 'ebook_custom_email_message', $editor_settings);
                    ?>
                    <span style="display: block; color: #6b7280; font-size: 13px; margin-top: 5px;">
                        💡 <strong>Available placeholders:</strong> {customer_name}, {ebook_title}, {download_link}, {order_date}
                    </span>
                </p>

                <div style="background: #fef3c7; padding: 15px; border-radius: 6px; border-left: 4px solid #f59e0b; margin-top: 15px;">
                    <p style="margin: 0; font-size: 13px; color: #92400e;">
                        <strong>💡 Pro Tip:</strong> Personalize your message using placeholders. Example:<br>
                        <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-size: 12px;">
                            Hi {customer_name}, thank you for purchasing {ebook_title}! Click here to download: {download_link}
                        </code>
                    </p>
                </div>
            </div>
        </div>

        <!-- Thank You Page Redirect Section -->
        <div style="background: #ffffff; border: 1px solid #e5e7eb; padding: 20px; border-radius: 6px;">
            <h3 style="margin-top: 0; color: #1f2937; border-bottom: 2px solid #3b82f6; padding-bottom: 10px;">
                🎉 Custom Thank You Page Redirect
            </h3>
            
            <p style="margin: 15px 0;">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" 
                           name="ebook_enable_thank_you_redirect" 
                           id="ebook_enable_thank_you_redirect"
                           value="1" 
                           <?php checked($enable_thank_you_redirect, '1'); ?>
                           style="width: 18px; height: 18px;">
                    <strong style="font-size: 15px;">Enable Custom Thank You Page</strong>
                </label>
                <span style="display: block; margin-left: 28px; color: #6b7280; font-size: 13px; margin-top: 5px;">
                    Redirect customers to a custom page after successful purchase of this ebook
                </span>
            </p>

            <div id="thank_you_redirect_fields" style="<?php echo $enable_thank_you_redirect != '1' ? 'display: none;' : ''; ?> margin-top: 20px;">
                <p>
                    <label for="ebook_thank_you_page_url" style="display: block; margin-bottom: 8px; font-weight: 600; color: #374151;">
                        Thank You Page URL:
                    </label>
                    <input type="url" 
                           id="ebook_thank_you_page_url" 
                           name="ebook_thank_you_page_url" 
                           value="<?php echo esc_url($thank_you_page_url); ?>" 
                           class="widefat"
                           placeholder="https://yoursite.com/thank-you"
                           style="padding: 8px;">
                    <span style="display: block; color: #6b7280; font-size: 13px; margin-top: 5px;">
                        Enter the full URL where customers should be redirected after purchase
                    </span>
                </p>

                <div style="background: #dbeafe; padding: 15px; border-radius: 6px; border-left: 4px solid #3b82f6; margin-top: 15px;">
                    <p style="margin: 0; font-size: 13px; color: #1e40af;">
                        <strong>ℹ️ Note:</strong> If disabled, customers will see the default download page. Make sure your custom thank you page includes download instructions or links.
                    </p>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Toggle custom email fields
            $('#ebook_enable_custom_email').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#custom_email_fields').slideDown();
                } else {
                    $('#custom_email_fields').slideUp();
                }
            });

            // Toggle thank you redirect fields
            $('#ebook_enable_thank_you_redirect').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#thank_you_redirect_fields').slideDown();
                } else {
                    $('#thank_you_redirect_fields').slideUp();
                }
            });
        });
        </script>
        <?php
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

        // Save formats
        if (isset($_POST['ebook_formats_nonce']) && wp_verify_nonce($_POST['ebook_formats_nonce'], 'ebook_formats_save')) {
            $this->save_formats($post_id);
        }

        // Save details
        if (isset($_POST['ebook_details_nonce']) && wp_verify_nonce($_POST['ebook_details_nonce'], 'ebook_details_save')) {
            $this->save_details($post_id);
        }

        // Save ebook file
        if (isset($_POST['ebook_file_nonce']) && wp_verify_nonce($_POST['ebook_file_nonce'], 'ebook_file_save')) {
            $this->save_file($post_id);
        }

        // Save audiobook file
        if (isset($_POST['ebook_audiobook_nonce']) && wp_verify_nonce($_POST['ebook_audiobook_nonce'], 'ebook_audiobook_save')) {
            $this->save_audiobook($post_id);
        }

        // Save audio preview
        if (isset($_POST['ebook_audio_nonce']) && wp_verify_nonce($_POST['ebook_audio_nonce'], 'ebook_audio_save')) {
            $this->save_audio($post_id);
        }

        // Save shipping
        if (isset($_POST['ebook_shipping_nonce']) && wp_verify_nonce($_POST['ebook_shipping_nonce'], 'ebook_shipping_save')) {
            $this->save_shipping($post_id);
        }

        // Save stock
        if (isset($_POST['ebook_stock_nonce']) && wp_verify_nonce($_POST['ebook_stock_nonce'], 'ebook_stock_save')) {
            $this->save_stock($post_id);
        }

        // Save purchase settings (custom email & thank you page)
        if (isset($_POST['ebook_purchase_settings_nonce']) && wp_verify_nonce($_POST['ebook_purchase_settings_nonce'], 'ebook_purchase_settings_save')) {
            $this->save_purchase_settings($post_id);
        }
    }

    /**
     * NEW: Save format selections
     */
    private function save_formats($post_id) {
        $format_ebook = isset($_POST['ebook_format_ebook']) && $_POST['ebook_format_ebook'] == '1' ? '1' : '0';
        $format_audio = isset($_POST['ebook_format_audio']) && $_POST['ebook_format_audio'] == '1' ? '1' : '0';
        $format_printed = isset($_POST['ebook_format_printed']) && $_POST['ebook_format_printed'] == '1' ? '1' : '0';

        update_post_meta($post_id, '_ebook_format_ebook', $format_ebook);
        update_post_meta($post_id, '_ebook_format_audio', $format_audio);
        update_post_meta($post_id, '_ebook_format_printed', $format_printed);
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
     * Save digital ebook file
     */
    private function save_file($post_id) {
        // Handle removal
        if (isset($_POST['remove_ebook_file']) && $_POST['remove_ebook_file'] == '1') {
            delete_post_meta($post_id, '_ebook_file_url');
            delete_post_meta($post_id, '_ebook_file_id');
            return;
        }

        // Handle upload
        if (isset($_FILES['ebook_file_upload']) && $_FILES['ebook_file_upload']['error'] == UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $file = $_FILES['ebook_file_upload'];

            // Validate file type
            $allowed = array('pdf', 'epub', 'mobi');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                return;
            }

            // Upload
            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['url'])) {
                update_post_meta($post_id, '_ebook_file_url', $upload['url']);
            }
        }
    }

    /**
     * NEW: Save full audiobook file
     */
    private function save_audiobook($post_id) {
        // Handle removal
        if (isset($_POST['remove_audiobook']) && $_POST['remove_audiobook'] == '1') {
            delete_post_meta($post_id, '_ebook_audiobook_url');
            delete_post_meta($post_id, '_ebook_audiobook_id');
            return;
        }

        // Handle upload
    // Handle upload
if (isset($_FILES['audiobook_upload']) && $_FILES['audiobook_upload']['error'] == UPLOAD_ERR_OK) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    $file = $_FILES['audiobook_upload'];

    // Validate file type
    $allowed = array('mp3', 'm4a', 'm4b', 'ogg', 'wav');
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        add_settings_error('audiobook_upload', 'invalid_type', 'Invalid file type. Only MP3, M4A, M4B allowed.');
        return;
    }

    // Check size (max 500MB)
    if ($file['size'] > 524288000) {
        add_settings_error('audiobook_upload', 'file_too_large', 'File too large. Maximum 500MB.');
        return;
    }

    // Set WordPress upload filters to allow audio files
    add_filter('upload_mimes', function($mimes) {
        $mimes['mp3'] = 'audio/mpeg';
        $mimes['m4a'] = 'audio/mp4';
        $mimes['m4b'] = 'audio/mp4';
        return $mimes;
    });

    // Upload with custom overrides
    $upload = wp_handle_upload($file, array(
        'test_form' => false,
        'test_type' => false,
        'mimes' => array(
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'm4b' => 'audio/mp4'
        )
    ));

    if (isset($upload['error'])) {
        error_log('Audiobook upload error: ' . $upload['error']);
        add_settings_error('audiobook_upload', 'upload_failed', 'Upload failed: ' . $upload['error']);
        return;
    }

    if (isset($upload['url'])) {
        update_post_meta($post_id, '_ebook_audiobook_url', $upload['url']);
        error_log('Audiobook uploaded successfully: ' . $upload['url']);
    }
}
    }

    /**
     * Save audio preview
     */
    private function save_audio($post_id) {
        // Save checkbox
        $enable = isset($_POST['ebook_enable_audio']) && $_POST['ebook_enable_audio'] == '1' ? '1' : '0';
        update_post_meta($post_id, '_ebook_enable_audio', $enable);

        // Handle removal
        if (isset($_POST['remove_audio_preview']) && $_POST['remove_audio_preview'] == '1') {
            delete_post_meta($post_id, '_ebook_audio_preview_url');
            delete_post_meta($post_id, '_ebook_audio_preview_id');
            return;
        }

        // Handle upload
        if (isset($_FILES['audio_preview_upload']) && $_FILES['audio_preview_upload']['error'] == UPLOAD_ERR_OK) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');

            $file = $_FILES['audio_preview_upload'];

            // Validate
            $allowed = array('mp3', 'wav', 'ogg');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                return;
            }

            if ($file['size'] > 10485760) { // 10MB
                return;
            }

            // Upload
            $upload = wp_handle_upload($file, array('test_form' => false));

            if (isset($upload['url'])) {
                update_post_meta($post_id, '_ebook_audio_preview_url', $upload['url']);
            }
        }
    }

    /**
     * NEW: Save shipping settings
     */
    private function save_shipping($post_id) {
        if (isset($_POST['ebook_shipping_fee'])) {
            update_post_meta($post_id, '_ebook_shipping_fee', floatval($_POST['ebook_shipping_fee']));
        }

        if (isset($_POST['ebook_shipping_type'])) {
            update_post_meta($post_id, '_ebook_shipping_type', sanitize_text_field($_POST['ebook_shipping_type']));
        }
    }

    /**
     * Save stock
     */
    private function save_stock($post_id) {
        $unlimited = isset($_POST['ebook_unlimited']) && $_POST['ebook_unlimited'] == '1' ? '1' : '0';
        update_post_meta($post_id, '_ebook_unlimited', $unlimited);

        if (isset($_POST['ebook_quantity'])) {
            update_post_meta($post_id, '_ebook_quantity', absint($_POST['ebook_quantity']));
        }
    }

    /**
     * NEW: Save purchase email & thank you page settings
     */
    private function save_purchase_settings($post_id) {
        // Save custom email enable/disable
        $enable_custom_email = isset($_POST['ebook_enable_custom_email']) && $_POST['ebook_enable_custom_email'] == '1' ? '1' : '0';
        update_post_meta($post_id, '_ebook_enable_custom_email', $enable_custom_email);

        // Save custom email subject
        if (isset($_POST['ebook_custom_email_subject'])) {
            update_post_meta($post_id, '_ebook_custom_email_subject', sanitize_text_field($_POST['ebook_custom_email_subject']));
        }

        // Save custom email message (allow HTML with wp_kses_post)
        if (isset($_POST['ebook_custom_email_message'])) {
            update_post_meta($post_id, '_ebook_custom_email_message', wp_kses_post($_POST['ebook_custom_email_message']));
        }

        // Save thank you redirect enable/disable
        $enable_thank_you_redirect = isset($_POST['ebook_enable_thank_you_redirect']) && $_POST['ebook_enable_thank_you_redirect'] == '1' ? '1' : '0';
        update_post_meta($post_id, '_ebook_enable_thank_you_redirect', $enable_thank_you_redirect);

        // Save thank you page URL
        if (isset($_POST['ebook_thank_you_page_url'])) {
            update_post_meta($post_id, '_ebook_thank_you_page_url', esc_url_raw($_POST['ebook_thank_you_page_url']));
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
        }
    }
}