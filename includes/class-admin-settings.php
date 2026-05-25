<?php
/**
 * Admin Settings - FIXED VERSION
 * Fixes: "Link expired" error on voice upload
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Admin_Settings {

    /**
     * Constructor - register upload handler
     */
   /**
     * Constructor - COMPLETE WITH ALL AJAX HANDLERS
     */
    public function __construct() {
        // Voice upload handler
        add_action('admin_post_skillscore_upload_voice', array($this, 'handle_voice_upload_action'));
        
        // AJAX handlers for shipping and order management
        add_action('wp_ajax_skillscore_get_shipping_address', array($this, 'get_shipping_address'));
        add_action('wp_ajax_skillscore_update_shipping', array($this, 'update_shipping_status'));
        add_action('wp_ajax_skillscore_get_order_details', array($this, 'get_order_details'));
        add_action('wp_ajax_skillscore_resend_order_email', array($this, 'resend_order_email'));
        add_action('wp_ajax_skillscore_revoke_download', array($this, 'revoke_download'));
        add_action('wp_ajax_skillscore_restore_download', array($this, 'restore_download'));
    }

    /**
     * Add admin menu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ebook',
            __('Settings', 'skillscore-ebook'),
            __('Settings', 'skillscore-ebook'),
            'manage_options',
            'skillscore-ebook-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'edit.php?post_type=ebook',
            __('Orders', 'skillscore-ebook'),
            __('Orders', 'skillscore-ebook'),
            'manage_options',
            'skillscore-ebook-orders',
            array($this, 'render_orders_page')
        );

        add_submenu_page(
            'edit.php?post_type=ebook',
            __('Downloads', 'skillscore-ebook'),
            __('Downloads', 'skillscore-ebook'),
            'manage_options',
            'skillscore-ebook-downloads',
            array($this, 'render_downloads_page')
        );

        add_submenu_page(
            'edit.php?post_type=ebook',
            __('Bulk Inquiries', 'skillscore-ebook'),
            __('Bulk Inquiries', 'skillscore-ebook'),
            'manage_options',
            'skillscore-ebook-bulk-inquiries',
            array($this, 'render_bulk_inquiries_page')
        );
    }

    /**
     * Register settings.
     */
    public function register_settings() {
        // General settings
        register_setting('skillscore_ebook_general', 'skillscore_ebook_currency');
        register_setting('skillscore_ebook_general', 'skillscore_ebook_currency_symbol');
        register_setting('skillscore_ebook_general', 'skillscore_ebook_enable_quantity_selector');
        register_setting('skillscore_ebook_general', 'skillscore_ebook_download_limit');
        register_setting('skillscore_ebook_general', 'skillscore_ebook_download_expiry_days');

        // Payment gateways
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_enable_paystack');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_paystack_public_key');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_paystack_secret_key');

        register_setting('skillscore_ebook_payments', 'skillscore_ebook_enable_flutterwave');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_flutterwave_public_key');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_flutterwave_secret_key');

        register_setting('skillscore_ebook_payments', 'skillscore_ebook_enable_stripe');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_stripe_publishable_key');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_stripe_secret_key');

        register_setting('skillscore_ebook_payments', 'skillscore_ebook_enable_paypal');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_paypal_client_id');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_paypal_secret');
        register_setting('skillscore_ebook_payments', 'skillscore_ebook_paypal_mode');

        // Voice preview settings
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_enable_audio_preview');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_use_global_voice');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_global_voice_url');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_tts_engine');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_piper_path');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_piper_model');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_coqui_api_url');
        register_setting('skillscore_ebook_voice', 'skillscore_ebook_ffmpeg_path');

        // Checkout features settings
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_enable_bulk_inquiry');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_bulk_notification_email');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_bulk_min_copies');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_enable_order_bump');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_order_bump_name');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_order_bump_price');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_order_bump_desc');
        register_setting('skillscore_ebook_checkout', 'skillscore_ebook_enable_dynamic_button');
    }

    /**
     * Render settings page.
     */
    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        
        // Show any messages from upload
        settings_errors('skillscore_ebook_messages');
        ?>
        <div class="wrap">
            <h1><?php _e('SkillScore Ebook Settings', 'skillscore-ebook'); ?></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?post_type=ebook&page=skillscore-ebook-settings&tab=general"
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'skillscore-ebook'); ?>
                </a>
                <a href="?post_type=ebook&page=skillscore-ebook-settings&tab=payments"
                   class="nav-tab <?php echo $active_tab === 'payments' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Payment Gateways', 'skillscore-ebook'); ?>
                </a>
                <a href="?post_type=ebook&page=skillscore-ebook-settings&tab=voice"
                   class="nav-tab <?php echo $active_tab === 'voice' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Voice Preview', 'skillscore-ebook'); ?>
                </a>
                <a href="?post_type=ebook&page=skillscore-ebook-settings&tab=checkout"
                   class="nav-tab <?php echo $active_tab === 'checkout' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Checkout Features', 'skillscore-ebook'); ?>
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab) {
                    case 'general':
                        settings_fields('skillscore_ebook_general');
                        $this->render_general_settings();
                        break;
                    case 'payments':
                        settings_fields('skillscore_ebook_payments');
                        $this->render_payment_settings();
                        break;
                    case 'voice':
                        settings_fields('skillscore_ebook_voice');
                        $this->render_voice_settings();
                        break;
                    case 'checkout':
                        settings_fields('skillscore_ebook_checkout');
                        $this->render_checkout_settings();
                        break;
                }
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render general settings.
     */
    private function render_general_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Currency', 'skillscore-ebook'); ?></th>
                <td>
                    <select name="skillscore_ebook_currency">
                        <option value="USD" <?php selected(get_option('skillscore_ebook_currency'), 'USD'); ?>>USD - US Dollar</option>
                        <option value="EUR" <?php selected(get_option('skillscore_ebook_currency'), 'EUR'); ?>>EUR - Euro</option>
                        <option value="GBP" <?php selected(get_option('skillscore_ebook_currency'), 'GBP'); ?>>GBP - British Pound</option>
                        <option value="NGN" <?php selected(get_option('skillscore_ebook_currency'), 'NGN'); ?>>NGN - Nigerian Naira</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Currency Symbol', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_currency_symbol"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_currency_symbol', '$')); ?>"
                           class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Enable Quantity Selector', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_quantity_selector" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_quantity_selector'), '1'); ?> />
                        <?php _e('Allow customers to select quantity', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Download Limit', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="number" name="skillscore_ebook_download_limit"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_download_limit', 5)); ?>"
                           class="small-text" min="-1" />
                    <p class="description"><?php _e('Maximum number of downloads per purchase. -1 for unlimited.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Download Link Expiry', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="number" name="skillscore_ebook_download_expiry_days"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_download_expiry_days', 30)); ?>"
                           class="small-text" min="0" />
                    <p class="description"><?php _e('Number of days until download links expire. 0 for no expiry.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render payment gateway settings.
     */
    private function render_payment_settings() {
        ?>
        <h2><?php _e('Paystack', 'skillscore-ebook'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Paystack', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_paystack" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_paystack'), '1'); ?> />
                        <?php _e('Enable Paystack payment gateway', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Public Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_paystack_public_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_paystack_public_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="password" name="skillscore_ebook_paystack_secret_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_paystack_secret_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('Flutterwave', 'skillscore-ebook'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Flutterwave', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_flutterwave" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_flutterwave'), '1'); ?> />
                        <?php _e('Enable Flutterwave payment gateway', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Public Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_flutterwave_public_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_flutterwave_public_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="password" name="skillscore_ebook_flutterwave_secret_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_flutterwave_secret_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('Stripe', 'skillscore-ebook'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Stripe', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_stripe" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_stripe'), '1'); ?> />
                        <?php _e('Enable Stripe payment gateway', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Publishable Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_stripe_publishable_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_stripe_publishable_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret Key', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="password" name="skillscore_ebook_stripe_secret_key"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_stripe_secret_key')); ?>"
                           class="regular-text" />
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('PayPal', 'skillscore-ebook'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable PayPal', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_paypal" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_paypal'), '1'); ?> />
                        <?php _e('Enable PayPal payment gateway', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Mode', 'skillscore-ebook'); ?></th>
                <td>
                    <select name="skillscore_ebook_paypal_mode">
                        <option value="sandbox" <?php selected(get_option('skillscore_ebook_paypal_mode'), 'sandbox'); ?>>Sandbox</option>
                        <option value="live" <?php selected(get_option('skillscore_ebook_paypal_mode'), 'live'); ?>>Live</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Client ID', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_paypal_client_id"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_paypal_client_id')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Secret', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="password" name="skillscore_ebook_paypal_secret"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_paypal_secret')); ?>"
                           class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render voice preview settings.
     */
    private function render_voice_settings() {
        ?>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Audio Preview', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_audio_preview" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_audio_preview'), '1'); ?> />
                        <?php _e('Enable audio preview for ebooks', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Use Global Voice Sample', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_use_global_voice" value="1"
                               <?php checked(get_option('skillscore_ebook_use_global_voice'), '1'); ?> />
                        <?php _e('Use a single voice sample for all ebooks', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h3><?php _e('Global Voice Sample Upload', 'skillscore-ebook'); ?></h3>
        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" enctype="multipart/form-data">
            <?php wp_nonce_field('skillscore_upload_voice_nonce', 'skillscore_voice_nonce'); ?>
            <input type="hidden" name="action" value="skillscore_upload_voice" />
            <table class="form-table">
                <tr>
                    <th scope="row"><?php _e('Current Voice Sample', 'skillscore-ebook'); ?></th>
                    <td>
                        <?php
                        $voice_url = get_option('skillscore_ebook_global_voice_url');
                        if ($voice_url):
                        ?>
                            <audio controls style="width: 100%; max-width: 400px;">
                                <source src="<?php echo esc_url($voice_url); ?>" type="audio/mpeg">
                            </audio>
                            <br><br>
                            <a href="<?php echo esc_url($voice_url); ?>" target="_blank" class="button button-secondary"><?php _e('Download Current Sample', 'skillscore-ebook'); ?></a>
                        <?php else: ?>
                            <p><?php _e('No voice sample uploaded yet.', 'skillscore-ebook'); ?></p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e('Upload New Sample', 'skillscore-ebook'); ?></th>
                    <td>
                        <input type="file" name="global_voice_sample" accept=".mp3,.wav,.ogg" />
                        <p class="description"><?php _e('Upload an MP3, WAV, or OGG file (max 10MB). This will play as audio preview for all ebooks.', 'skillscore-ebook'); ?></p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit" class="button button-primary"
                       value="<?php _e('Upload Voice Sample', 'skillscore-ebook'); ?>" />
            </p>
        </form>

        <hr>

        <h3><?php _e('TTS Engine Configuration (Advanced)', 'skillscore-ebook'); ?></h3>
        <p class="description"><?php _e('If you don\'t upload a global voice sample, you can use automatic text-to-speech generation.', 'skillscore-ebook'); ?></p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('TTS Engine', 'skillscore-ebook'); ?></th>
                <td>
                    <select name="skillscore_ebook_tts_engine">
                        <option value="none" <?php selected(get_option('skillscore_ebook_tts_engine'), 'none'); ?>>None (Use Global Voice)</option>
                        <option value="piper" <?php selected(get_option('skillscore_ebook_tts_engine'), 'piper'); ?>>Piper TTS</option>
                        <option value="coqui" <?php selected(get_option('skillscore_ebook_tts_engine'), 'coqui'); ?>>Coqui TTS</option>
                        <option value="web_speech" <?php selected(get_option('skillscore_ebook_tts_engine'), 'web_speech'); ?>>Browser TTS</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Piper Executable Path', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_piper_path"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_piper_path', '/usr/local/bin/piper')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Piper Model', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_piper_model"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_piper_model', 'en_US-lessac-medium')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Coqui API URL', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_coqui_api_url"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_coqui_api_url', 'http://localhost:5002/api/tts')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('FFmpeg Path', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_ffmpeg_path"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_ffmpeg_path', '/usr/bin/ffmpeg')); ?>"
                           class="regular-text" />
                    <p class="description"><?php _e('Required for audio conversion.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render checkout features settings.
     */
    private function render_checkout_settings() {
        ?>
        <h2><?php _e('Bulk Inquiry Form', 'skillscore-ebook'); ?></h2>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Allow institutional buyers (schools, organizations, events) to submit bulk copy inquiries instead of going through individual checkout.', 'skillscore-ebook'); ?>
        </p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Bulk Inquiry Form', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_bulk_inquiry" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_bulk_inquiry'), '1'); ?> />
                        <?php _e('Show a purchase type toggle on the checkout page so buyers can choose between individual purchase and bulk/institutional inquiry', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Bulk Inquiry Notification Email', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="email" name="skillscore_ebook_bulk_notification_email"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_bulk_notification_email', get_option('admin_email'))); ?>"
                           class="regular-text" />
                    <p class="description"><?php _e('Email address to receive new bulk inquiry notifications. Defaults to site admin email.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Minimum Copies for Bulk', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="number" name="skillscore_ebook_bulk_min_copies"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_bulk_min_copies', 10)); ?>"
                           class="small-text" min="1" />
                    <p class="description"><?php _e('Minimum number of copies that qualifies as a bulk inquiry.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('Order Bump / Add-On', 'skillscore-ebook'); ?></h2>
        <p class="description" style="margin-bottom: 15px;">
            <?php _e('Show an optional companion product add-on during individual checkout (e.g. the 90-Day No Excuse Journal).', 'skillscore-ebook'); ?>
        </p>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Order Bump', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_order_bump" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_order_bump'), '1'); ?> />
                        <?php _e('Show an optional add-on product on the checkout form', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Add-On Product Name', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="text" name="skillscore_ebook_order_bump_name"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_order_bump_name', '90-Day No Excuse Journal, Discussion & Reflection Guide')); ?>"
                           class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Add-On Price', 'skillscore-ebook'); ?></th>
                <td>
                    <input type="number" name="skillscore_ebook_order_bump_price"
                           value="<?php echo esc_attr(get_option('skillscore_ebook_order_bump_price', '0.00')); ?>"
                           class="small-text" min="0" step="0.01" />
                    <p class="description"><?php _e('Price of the add-on product in the store currency. Set to 0 to hide the price.', 'skillscore-ebook'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Add-On Description', 'skillscore-ebook'); ?></th>
                <td>
                    <textarea name="skillscore_ebook_order_bump_desc" rows="3" class="large-text"><?php echo esc_textarea(get_option('skillscore_ebook_order_bump_desc', 'Take the book deeper with the companion guide designed for personal reflection, reading groups, leadership cohorts, and serious discussion.')); ?></textarea>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php _e('Dynamic Button Text', 'skillscore-ebook'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Dynamic Button Text', 'skillscore-ebook'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="skillscore_ebook_enable_dynamic_button" value="1"
                               <?php checked(get_option('skillscore_ebook_enable_dynamic_button'), '1'); ?> />
                        <?php _e('Change the submit button text based on selected format (e.g. "GET INSTANT ACCESS" for eBook, "COMPLETE MY ORDER" for Printed)', 'skillscore-ebook'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render bulk inquiries admin page.
     */
    public function render_bulk_inquiries_page() {
        global $wpdb;
        $table = $wpdb->prefix . 'skillscore_bulk_inquiries';

        // Check table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
            echo '<div class="wrap"><h1>' . __('Bulk Inquiries', 'skillscore-ebook') . '</h1>';
            echo '<div class="notice notice-info"><p>' . __('No bulk inquiries yet. The table will be created once the first inquiry is submitted.', 'skillscore-ebook') . '</p></div></div>';
            return;
        }

        // Handle status update
        if (isset($_POST['update_inquiry_status']) && isset($_POST['inquiry_id']) && check_admin_referer('update_inquiry_' . $_POST['inquiry_id'])) {
            $inquiry_id = intval($_POST['inquiry_id']);
            $new_status = sanitize_text_field($_POST['inquiry_status']);
            if (in_array($new_status, array('new', 'reviewed', 'responded', 'closed'))) {
                $wpdb->update($table, array('status' => $new_status), array('id' => $inquiry_id), array('%s'), array('%d'));
                echo '<div class="notice notice-success is-dismissible"><p>Inquiry #' . $inquiry_id . ' status updated.</p></div>';
            }
        }

        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        $total = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $total_pages = ceil($total / $per_page);

        $inquiries = $wpdb->get_results($wpdb->prepare(
            "SELECT i.*, p.post_title as ebook_title
             FROM $table i
             LEFT JOIN {$wpdb->posts} p ON i.ebook_id = p.ID
             ORDER BY i.created_at DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));

        $status_colors = array(
            'new'       => '#dc2626',
            'reviewed'  => '#d97706',
            'responded' => '#2563eb',
            'closed'    => '#16a34a',
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Bulk Inquiries', 'skillscore-ebook'); ?>
                <span class="title-count" style="background: #EAB308; color: #000; font-size: 13px; padding: 2px 8px; border-radius: 3px; margin-left: 10px;"><?php echo intval($total); ?></span>
            </h1>

            <?php if (empty($inquiries)) : ?>
                <div class="notice notice-info"><p><?php _e('No bulk inquiries received yet.', 'skillscore-ebook'); ?></p></div>
            <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th><?php _e('Contact', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Organization', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Copies', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Ebook', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Status', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Date', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Actions', 'skillscore-ebook'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inquiries as $inq) :
                        $status = $inq->status ?: 'new';
                        $color = $status_colors[$status] ?? '#666';
                    ?>
                    <tr>
                        <td><strong><?php echo intval($inq->id); ?></strong></td>
                        <td>
                            <strong><?php echo esc_html($inq->contact_name); ?></strong><br>
                            <a href="mailto:<?php echo esc_attr($inq->contact_email); ?>"><?php echo esc_html($inq->contact_email); ?></a>
                            <?php if ($inq->contact_phone) : ?>
                                <br><small><?php echo esc_html($inq->contact_phone); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo esc_html($inq->org_name); ?></strong><br>
                            <small style="color: #666;"><?php echo esc_html($inq->org_type); ?></small>
                        </td>
                        <td style="text-align: center; font-size: 20px; font-weight: 700;"><?php echo intval($inq->copies_needed); ?></td>
                        <td><?php echo esc_html($inq->ebook_title ?: 'Unknown'); ?></td>
                        <td>
                            <span style="background: <?php echo esc_attr($color); ?>; color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase;">
                                <?php echo esc_html($status); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html(date('M j, Y', strtotime($inq->created_at))); ?></td>
                        <td>
                            <button class="button button-small" onclick="document.getElementById('inq-detail-<?php echo intval($inq->id); ?>').style.display = document.getElementById('inq-detail-<?php echo intval($inq->id); ?>').style.display === 'none' ? 'table-row' : 'none'">
                                View Details
                            </button>
                        </td>
                    </tr>
                    <tr id="inq-detail-<?php echo intval($inq->id); ?>" style="display: none; background: #f9f9f9;">
                        <td colspan="8" style="padding: 20px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 900px;">
                                <div>
                                    <h4 style="margin: 0 0 10px;"><?php _e('Purpose / Use Case', 'skillscore-ebook'); ?></h4>
                                    <p style="background: #fff; padding: 12px; border: 1px solid #ddd; border-radius: 4px;"><?php echo nl2br(esc_html($inq->purpose)); ?></p>
                                    <?php if ($inq->notes) : ?>
                                        <h4 style="margin: 10px 0;"><?php _e('Additional Notes', 'skillscore-ebook'); ?></h4>
                                        <p style="background: #fff; padding: 12px; border: 1px solid #ddd; border-radius: 4px;"><?php echo nl2br(esc_html($inq->notes)); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <h4 style="margin: 0 0 10px;"><?php _e('Update Status', 'skillscore-ebook'); ?></h4>
                                    <form method="post">
                                        <?php wp_nonce_field('update_inquiry_' . $inq->id); ?>
                                        <input type="hidden" name="inquiry_id" value="<?php echo intval($inq->id); ?>">
                                        <select name="inquiry_status" style="margin-right: 10px; padding: 5px;">
                                            <option value="new" <?php selected($status, 'new'); ?>>New</option>
                                            <option value="reviewed" <?php selected($status, 'reviewed'); ?>>Reviewed</option>
                                            <option value="responded" <?php selected($status, 'responded'); ?>>Responded</option>
                                            <option value="closed" <?php selected($status, 'closed'); ?>>Closed</option>
                                        </select>
                                        <input type="submit" name="update_inquiry_status" class="button button-primary" value="Update">
                                    </form>
                                    <br>
                                    <a href="mailto:<?php echo esc_attr($inq->contact_email); ?>?subject=Re: Your bulk inquiry for <?php echo esc_attr($inq->ebook_title); ?>" class="button button-secondary">
                                        Reply via Email
                                    </a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1) : ?>
            <div class="tablenav bottom" style="margin-top: 15px;">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base'      => add_query_arg('paged', '%#%'),
                        'format'    => '',
                        'current'   => $current_page,
                        'total'     => $total_pages,
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Handle global voice upload - NEW METHOD
     * FIX: Uses admin_post action to avoid "link expired" error
     */
    public function handle_voice_upload_action() {
        // Verify nonce
        if (!isset($_POST['skillscore_voice_nonce']) || 
            !wp_verify_nonce($_POST['skillscore_voice_nonce'], 'skillscore_upload_voice_nonce')) {
            wp_die(__('Security check failed. Please try again.', 'skillscore-ebook'));
        }

        // Check user permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to upload files.', 'skillscore-ebook'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['global_voice_sample']) || empty($_FILES['global_voice_sample']['name'])) {
            wp_redirect(add_query_arg(
                array('post_type' => 'ebook', 'page' => 'skillscore-ebook-settings', 'tab' => 'voice', 'upload' => 'no_file'),
                admin_url('edit.php')
            ));
            exit;
        }

        // Handle upload
        $voice_preview = new SkillScore_Ebook_Voice_Preview();
        $result = $voice_preview->upload_global_voice_sample($_FILES['global_voice_sample']);

        // Redirect with result
        if ($result) {
            // Set transient for success message
            set_transient('skillscore_voice_upload_success', true, 30);
            
            wp_redirect(add_query_arg(
                array('post_type' => 'ebook', 'page' => 'skillscore-ebook-settings', 'tab' => 'voice', 'upload' => 'success'),
                admin_url('edit.php')
            ));
        } else {
            // Set transient for error message
            set_transient('skillscore_voice_upload_error', true, 30);
            
            wp_redirect(add_query_arg(
                array('post_type' => 'ebook', 'page' => 'skillscore-ebook-settings', 'tab' => 'voice', 'upload' => 'failed'),
                admin_url('edit.php')
            ));
        }
        exit;
    }

    /**
     * Render orders page - SINGLE VERSION (no duplicate)
     */
   /**
     * Render orders page - WITH FORMAT AND SHIPPING
     */
    /**
     * Render orders page - ENHANCED WITH PAGINATION & DELETE
     */
    public function render_orders_page() {
        global $wpdb;
        
        $orders_table = $wpdb->prefix . 'skillscore_ebook_purchases';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$orders_table'") == $orders_table;
        
        if (!$table_exists) {
            ?>
            <div class="wrap">
                <h1><?php _e('Orders', 'skillscore-ebook'); ?></h1>
                <div class="notice notice-error">
                    <p><strong>Error: Orders table not found!</strong></p>
                </div>
            </div>
            <?php
            return;
        }
        
        // Handle delete order
        if (isset($_POST['delete_order']) && isset($_POST['order_id']) && check_admin_referer('delete_order_' . $_POST['order_id'])) {
            $order_id = intval($_POST['order_id']);
            $deleted = $wpdb->delete(
                $orders_table,
                array('id' => $order_id),
                array('%d')
            );
            
            if ($deleted) {
                echo '<div class="notice notice-success is-dismissible"><p>Order #' . $order_id . ' deleted successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to delete order.</p></div>';
            }
        }
        
        // Get filters
        $filter_format = isset($_GET['filter_format']) ? sanitize_text_field($_GET['filter_format']) : '';
        $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
        
        // Build WHERE clause
        $where = array('1=1');
        if ($filter_format) {
            $where[] = $wpdb->prepare('o.format = %s', $filter_format);
        }
        if ($filter_status) {
            if ($filter_status === 'pending_ship') {
                $where[] = "o.format = 'printed' AND o.shipping_status = 'pending'";
            } else {
                $where[] = $wpdb->prepare('o.status = %s', $filter_status);
            }
        }
        $where_sql = implode(' AND ', $where);
        
        // Get total count
        $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table o WHERE $where_sql");
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        $total_pages = ceil($total_orders / $per_page);
        
        // Get orders
        $orders = $wpdb->get_results($wpdb->prepare(
            "SELECT o.*, p.post_title as ebook_title
             FROM $orders_table o
             LEFT JOIN {$wpdb->posts} p ON o.ebook_id = p.ID
             WHERE $where_sql
             ORDER BY o.purchase_date DESC
             LIMIT %d OFFSET %d",
            $per_page, $offset
        ));
        ?>
        <div class="wrap">
            <h1><?php _e('Orders', 'skillscore-ebook'); ?></h1>
            
            <!-- FILTERS -->
            <div class="tablenav top" style="margin: 20px 0;">
                <form method="get" style="display: inline-block;">
                    <input type="hidden" name="post_type" value="ebook">
                    <input type="hidden" name="page" value="skillscore-ebook-orders">
                    
                    <select name="filter_format" style="margin-right: 10px; padding: 5px;">
                        <option value="">All Formats</option>
                        <option value="ebook" <?php selected($filter_format, 'ebook'); ?>>📖 Digital</option>
                        <option value="audio" <?php selected($filter_format, 'audio'); ?>>🎧 Audio</option>
                        <option value="printed" <?php selected($filter_format, 'printed'); ?>>📦 Printed</option>
                    </select>
                    
                    <select name="filter_status" style="margin-right: 10px; padding: 5px;">
                        <option value="">All Statuses</option>
                        <option value="completed" <?php selected($filter_status, 'completed'); ?>>✓ Completed</option>
                        <option value="pending" <?php selected($filter_status, 'pending'); ?>>⏳ Pending</option>
                        <option value="pending_ship" <?php selected($filter_status, 'pending_ship'); ?>>📦 Awaiting Shipment</option>
                    </select>
                    
                    <button type="submit" class="button">Filter</button>
                    <a href="?post_type=ebook&page=skillscore-ebook-orders" class="button">Reset</a>
                </form>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="notice notice-info"><p>No orders found.</p></div>
            <?php else: ?>
                
                <!-- STATS SUMMARY -->
                <div class="skillscore-stats" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: white;">📊 Order Summary</h3>
                    <?php 
                    $completed = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'completed'");
                    $pending = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE status = 'pending'");
                    $printed_pending = $wpdb->get_var("SELECT COUNT(*) FROM $orders_table WHERE format = 'printed' AND shipping_status = 'pending'");
                    $total_revenue = $wpdb->get_var("SELECT SUM(amount) FROM $orders_table WHERE status = 'completed'");
                    ?>
                    <p style="margin: 0; font-size: 15px;">
                        <strong>Total Orders:</strong> <?php echo $total_orders; ?> | 
                        <strong>Completed:</strong> <?php echo $completed; ?> | 
                        <strong>Pending:</strong> <?php echo $pending; ?> | 
                        <strong style="color: #fcd34d;">📦 Awaiting Shipment:</strong> <?php echo $printed_pending; ?> | 
                        <strong>Revenue:</strong> $<?php echo number_format($total_revenue, 2); ?>
                    </p>
                </div>
                
                <!-- ORDERS TABLE -->
                <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Order #</th>
                            <th style="width: 200px;">Customer</th>
                            <th style="width: 300px;">Ebook</th>
                            <th style="width: 100px;">Format</th>
                            <th style="width: 100px;">Amount</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 120px;">Date</th>
                            <th style="width: 150px;">Shipping</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            // Determine format icon
                            $format = $order->format ?: 'ebook';
                            $format_icon = '📖 Digital';
                            if ($format === 'audio') $format_icon = '🎧 Audio';
                            elseif ($format === 'printed') $format_icon = '📦 Printed';
                            
                            // Parse shipping address if exists
                            $shipping = null;
                            if (!empty($order->shipping_address)) {
                                $shipping = json_decode($order->shipping_address, true);
                            }
                            ?>
                            <tr>
                                <!-- Order # -->
                                <td><strong>#<?php echo esc_html($order->id); ?></strong></td>
                                
                                <!-- Customer -->
                                <td>
                                    <strong><?php echo esc_html($order->user_name); ?></strong><br>
                                    <small style="color: #666;"><?php echo esc_html($order->user_email); ?></small>
                                </td>
                                
                                <!-- Ebook -->
                                <td>
                                    <strong><?php echo esc_html($order->ebook_title ?: 'Ebook #' . $order->ebook_id); ?></strong>
                                </td>
                                
                                <!-- Format -->
                                <td>
                                    <span style="font-weight: 600; font-size: 14px;">
                                        <?php echo esc_html($format_icon); ?>
                                    </span>
                                </td>
                                
                                <!-- Amount -->
                                <td>
                                    <strong><?php echo esc_html($order->currency . ' ' . number_format($order->amount, 2)); ?></strong>
                                </td>
                                
                                <!-- Status -->
                                <td>
                                    <?php if ($order->status === 'completed'): ?>
                                        <span style="color: #28a745; font-weight: bold;">✓ Completed</span>
                                    <?php elseif ($order->status === 'pending'): ?>
                                        <span style="color: #ffc107; font-weight: bold;">⏳ Pending</span>
                                    <?php else: ?>
                                        <span><?php echo esc_html(ucfirst($order->status)); ?></span>
                                    <?php endif; ?>
                                    
                                    <!-- Shipping Status Badge -->
                                    <?php if ($format === 'printed' && isset($order->shipping_status)): ?>
                                        <br>
                                        <?php if ($order->shipping_status === 'pending'): ?>
                                            <span style="background: #d63638; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px;">
                                                📦 NOT SHIPPED
                                            </span>
                                        <?php elseif ($order->shipping_status === 'shipped'): ?>
                                            <span style="background: #00a32a; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px;">
                                                ✓ SHIPPED
                                            </span>
                                        <?php elseif ($order->shipping_status === 'delivered'): ?>
                                            <span style="background: #007cba; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; display: inline-block; margin-top: 4px;">
                                                ✓ DELIVERED
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Date -->
                                <td><?php echo esc_html(date('M j, Y g:i a', strtotime($order->purchase_date))); ?></td>
                                
                                <!-- Shipping -->
                                <td>
                                    <?php if ($format === 'printed' && $shipping): ?>
                                        <button type="button" 
                                                class="button button-small view-shipping-address" 
                                                data-order-id="<?php echo esc_attr($order->id); ?>"
                                                style="background: #2271b1; color: white; border: none; margin-bottom: 5px; width: 100%;">
                                            📍 View Address
                                        </button>
                                        
                                        <?php if ($order->shipping_status === 'pending'): ?>
                                        <button type="button" 
                                                class="button button-small mark-as-shipped" 
                                                data-order-id="<?php echo esc_attr($order->id); ?>"
                                                style="background: #00a32a; color: white; border: none; width: 100%;">
                                            ✓ Mark Shipped
                                        </button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">N/A</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Actions -->
                                <td>
                                    <button type="button" 
                                            class="button button-small view-order-details" 
                                            data-order-id="<?php echo esc_attr($order->id); ?>"
                                            style="margin-bottom: 5px; width: 100%;">
                                        📋 Details
                                    </button>
                                    
                                    <form method="post" style="display: inline-block; width: 100%;" 
                                          onsubmit="return confirm('Are you sure you want to delete order #<?php echo $order->id; ?>? This action cannot be undone.');">
                                        <?php wp_nonce_field('delete_order_' . $order->id); ?>
                                        <input type="hidden" name="order_id" value="<?php echo esc_attr($order->id); ?>">
                                        <button type="submit" 
                                                name="delete_order" 
                                                class="button button-small"
                                                style="background: #d63638; color: white; border: none; width: 100%;">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                <div class="tablenav bottom" style="margin-top: 20px;">
                    <div class="tablenav-pages">
                        <span class="displaying-num"><?php echo $total_orders; ?> items</span>
                        <span class="pagination-links">
                            <?php
                            $base_url = add_query_arg(array(
                                'post_type' => 'ebook',
                                'page' => 'skillscore-ebook-orders',
                                'filter_format' => $filter_format,
                                'filter_status' => $filter_status
                            ), admin_url('edit.php'));
                            
                            // First page
                            if ($current_page > 1) {
                                echo '<a class="first-page button" href="' . esc_url(add_query_arg('paged', 1, $base_url)) . '">
                                    <span class="screen-reader-text">First page</span>
                                    <span aria-hidden="true">«</span>
                                </a>';
                                echo '<a class="prev-page button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $base_url)) . '">
                                    <span class="screen-reader-text">Previous page</span>
                                    <span aria-hidden="true">‹</span>
                                </a>';
                            } else {
                                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>';
                                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>';
                            }
                            
                            // Page numbers
                            echo '<span class="paging-input">';
                            echo '<label for="current-page-selector" class="screen-reader-text">Current Page</label>';
                            echo '<input class="current-page" id="current-page-selector" type="text" 
                                         name="paged" value="' . $current_page . '" size="' . strlen($total_pages) . '" 
                                         aria-describedby="table-paging">';
                            echo '<span class="tablenav-paging-text"> of <span class="total-pages">' . $total_pages . '</span></span>';
                            echo '</span>';
                            
                            // Last page
                            if ($current_page < $total_pages) {
                                echo '<a class="next-page button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $base_url)) . '">
                                    <span class="screen-reader-text">Next page</span>
                                    <span aria-hidden="true">›</span>
                                </a>';
                                echo '<a class="last-page button" href="' . esc_url(add_query_arg('paged', $total_pages, $base_url)) . '">
                                    <span class="screen-reader-text">Last page</span>
                                    <span aria-hidden="true">»</span>
                                </a>';
                            } else {
                                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">›</span>';
                                echo '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">»</span>';
                            }
                            ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php endif; ?>
        </div>

        <!-- JAVASCRIPT -->
        <script>
        jQuery(document).ready(function($) {
            
            // View Shipping Address
            $('.view-shipping-address').on('click', function() {
                var orderId = $(this).data('order-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'skillscore_get_shipping_address',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('skillscore_shipping'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#shipping-modal-title').text('📦 Shipping Address - Order #' + orderId);
                            $('#shipping-address-content').html(response.data.html);
                            $('#skillscore-shipping-modal').fadeIn(300);
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to load address'));
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    }
                });
            });
            
            // Mark as Shipped
            $('.mark-as-shipped').on('click', function() {
                if (!confirm('Mark this order as shipped?')) return;
                
                var orderId = $(this).data('order-id');
                var $button = $(this);
                $button.text('Updating...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'skillscore_update_shipping',
                        order_id: orderId,
                        status: 'shipped',
                        nonce: '<?php echo wp_create_nonce('skillscore_update_shipping'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('✓ Order marked as shipped!');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to update'));
                            $button.text('✓ Mark Shipped').prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $button.text('✓ Mark Shipped').prop('disabled', false);
                    }
                });
            });
            
            // View Order Details
            $('.view-order-details').on('click', function() {
                var orderId = $(this).data('order-id');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'skillscore_get_order_details',
                        order_id: orderId,
                        nonce: '<?php echo wp_create_nonce('skillscore_order_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#details-modal-title').text('Order Details #' + orderId);
                            $('#order-details-content').html(response.data.html);
                            $('#skillscore-details-modal').fadeIn(300);
                        } else {
                            alert('Error: ' + (response.data.message || 'Failed to load details'));
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                    }
                });
            });
            
            // Close modal when clicking outside
            $(window).on('click', function(event) {
                if ($(event.target).hasClass('skillscore-modal')) {
                    $(event.target).fadeOut(300);
                }
            });
        });

        // Close modal functions
        function closeShippingModal() {
            jQuery('#skillscore-shipping-modal').fadeOut(300);
        }

        function closeDetailsModal() {
            jQuery('#skillscore-details-modal').fadeOut(300);
        }
        </script>
        <?php
    }

    /**
     * Render downloads page
     */
    public function render_downloads_page() {
        global $wpdb;
        
        // Get tables
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';
        $purchases_table = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        // Check if downloads table exists
        $downloads_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$downloads_table'") == $downloads_table;
        
        if (!$downloads_table_exists) {
            ?>
            <div class="wrap">
                <h1><?php _e('Downloads', 'skillscore-ebook'); ?></h1>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p><strong>Downloads tracking table not found.</strong></p>
                    <p>This feature requires the downloads tracking table to be created.</p>
                    <p>Please deactivate and reactivate the plugin, or run the database setup script.</p>
                </div>
            </div>
            <?php
            return;
        }
        
        // FIXED: Correct SQL query with proper joins and column references
        $downloads = $wpdb->get_results(
            "SELECT d.*, 
                    p.post_title as ebook_title, 
                    pur.user_email,
                    pur.user_name,
                    pur.amount,
                    pur.currency
             FROM $downloads_table d
             LEFT JOIN {$wpdb->posts} p ON d.ebook_id = p.ID
             LEFT JOIN $purchases_table pur ON d.order_id = pur.id
             ORDER BY d.created_at DESC
             LIMIT 100"
        );
        ?>
        <div class="wrap">
            <h1><?php _e('Downloads', 'skillscore-ebook'); ?></h1>
            
            <?php if (empty($downloads)): ?>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p><strong>No downloads tracked yet.</strong></p>
                    <p>Download tracking records will appear here when customers download ebooks.</p>
                </div>
            <?php else: ?>
                
                <!-- Download Stats -->
                <div class="skillscore-stats" style="background: #f6f7f7; border: 1px solid #ccd0d4; padding: 15px; margin: 20px 0; border-radius: 4px;">
                    <h3>Download Statistics</h3>
                    <?php 
                    $total_downloads = $wpdb->get_var("SELECT SUM(download_count) FROM $downloads_table");
                    $unique_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $downloads_table");
                    $active_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $downloads_table WHERE is_revoked = 0");
                    $revoked_downloads = $wpdb->get_var("SELECT COUNT(*) FROM $downloads_table WHERE is_revoked = 1");
                    ?>
                    <p>
                        <strong>Total Downloads:</strong> <?php echo $total_downloads; ?> | 
                        <strong>Unique Tokens:</strong> <?php echo $unique_downloads; ?> | 
                        <strong>Active:</strong> <?php echo $active_downloads; ?> | 
                        <strong>Revoked:</strong> <?php echo $revoked_downloads; ?>
                    </p>
                </div>
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Ebook', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Customer', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Downloads', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Limit', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Created', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Expires', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Last Download', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Status', 'skillscore-ebook'); ?></th>
                            <th><?php _e('Actions', 'skillscore-ebook'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($downloads as $download): ?>
                            <?php 
                            $status_class = '';
                            $status_text = '';
                            
                            if ($download->is_revoked) {
                                $status_class = 'revoked';
                                $status_text = 'Revoked';
                            } elseif ($download->expires_at && strtotime($download->expires_at) < time()) {
                                $status_class = 'expired';
                                $status_text = 'Expired';
                            } elseif ($download->download_limit > 0 && $download->download_count >= $download->download_limit) {
                                $status_class = 'limit-reached';
                                $status_text = 'Limit Reached';
                            } else {
                                $status_class = 'active';
                                $status_text = 'Active';
                            }
                            ?>
                            <tr>
                                <td><?php echo esc_html($download->ebook_title ?: 'Ebook #' . $download->ebook_id); ?></td>
                                <td>
                                    <?php if ($download->user_name): ?>
                                        <?php echo esc_html($download->user_name); ?><br>
                                        <small><?php echo esc_html($download->user_email); ?></small>
                                    <?php elseif ($download->user_email): ?>
                                        <?php echo esc_html($download->user_email); ?>
                                    <?php else: ?>
                                        <em>Unknown</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($download->download_count); ?></strong>
                                    <?php if ($download->download_limit > 0): ?>
                                        / <?php echo esc_html($download->download_limit); ?>
                                    <?php else: ?>
                                        (Unlimited)
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($download->download_limit > 0): ?>
                                        <?php echo esc_html($download->download_limit); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Unlimited</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(date('M j, Y', strtotime($download->created_at))); ?></td>
                                <td>
                                    <?php if ($download->expires_at): ?>
                                        <?php echo esc_html(date('M j, Y', strtotime($download->expires_at))); ?>
                                        <?php if (strtotime($download->expires_at) < time()): ?>
                                            <br><small style="color: #dc3545;">(Expired)</small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($download->last_downloaded_at): ?>
                                        <?php echo esc_html(date('M j, Y g:i a', strtotime($download->last_downloaded_at))); ?>
                                    <?php else: ?>
                                        <span style="color: #999;">Never</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!$download->is_revoked): ?>
                                        <button type="button" class="button button-small revoke-download" 
                                                data-download-id="<?php echo esc_attr($download->id); ?>">
                                            Revoke
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="button button-small restore-download" 
                                                data-download-id="<?php echo esc_attr($download->id); ?>">
                                            Restore
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Revoke download
            $('.revoke-download').on('click', function() {
                var downloadId = $(this).data('download-id');
                
                if (!confirm('Revoke this download link? The customer will no longer be able to download.')) {
                    return;
                }
                
                var $button = $(this);
                var originalText = $button.text();
                $button.text('Revoking...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'skillscore_revoke_download',
                        download_id: downloadId,
                        nonce: '<?php echo wp_create_nonce('skillscore_revoke_download'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Download link revoked successfully.');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                            $button.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
            
            // Restore download
            $('.restore-download').on('click', function() {
                var downloadId = $(this).data('download-id');
                
                if (!confirm('Restore this download link?')) {
                    return;
                }
                
                var $button = $(this);
                var originalText = $button.text();
                $button.text('Restoring...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'skillscore_restore_download',
                        download_id: downloadId,
                        nonce: '<?php echo wp_create_nonce('skillscore_restore_download'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Download link restored successfully.');
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data.message || 'Unknown error'));
                            $button.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });
        });
        </script>
        
        <style>
        .status-badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        .status-limit-reached {
            background: #fff3cd;
            color: #856404;
        }
        .status-revoked {
            background: #e2e3e5;
            color: #383d41;
        }
        </style>
        <?php
    }
    

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'skillscore-ebook') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        wp_enqueue_style(
            'skillscore-ebook-admin',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SKILLSCORE_EBOOK_VERSION
        );

        wp_enqueue_script(
            'skillscore-ebook-admin',
            SKILLSCORE_EBOOK_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SKILLSCORE_EBOOK_VERSION,
            true
        );
    }
    /**
     * AJAX: Get shipping address for order
     */
    public function get_shipping_address() {
        check_ajax_referer('skillscore_shipping', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
        
        if (!$order || empty($order->shipping_address)) {
            wp_send_json_error(array('message' => 'No shipping address found'));
        }
        
        $shipping = json_decode($order->shipping_address, true);
        
        $html = '
        <div style="background: #f0f9ff; border: 2px solid #3b82f6; border-radius: 8px; padding: 20px; margin-bottom: 15px;">
            <h3 style="margin-top: 0; color: #1e40af; font-size: 18px;">
                <span style="font-size: 24px;">📍</span> Shipping Address
            </h3>
            <div style="font-size: 16px; line-height: 1.8; background: white; padding: 15px; border-radius: 6px;">
                <strong style="font-size: 18px; display: block; margin-bottom: 10px;">' . esc_html($order->user_name) . '</strong>
                ' . esc_html($shipping['address']) . '<br>
                ' . esc_html($shipping['city']) . ', ' . esc_html($shipping['state']) . ' ' . esc_html($shipping['postal_code']) . '<br>
                ' . esc_html($shipping['country']) . '<br><br>
                <strong>📞 Phone:</strong> ' . esc_html($shipping['phone']) . '
            </div>
        </div>
        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 4px;">
            <p style="margin: 5px 0;"><strong>📧 Email:</strong> ' . esc_html($order->user_email) . '</p>
            <p style="margin: 5px 0;"><strong>📦 Shipping Status:</strong> ' . esc_html(ucfirst($order->shipping_status ?: 'pending')) . '</p>
            <p style="margin: 5px 0;"><strong>💰 Total Paid:</strong> ' . esc_html($order->currency) . ' ' . number_format($order->amount, 2) . '</p>
        </div>';
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX: Update shipping status
     */
    public function update_shipping_status() {
        check_ajax_referer('skillscore_update_shipping', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$order_id || !in_array($status, array('pending', 'shipped', 'delivered'))) {
            wp_send_json_error(array('message' => 'Invalid parameters'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        $updated = $wpdb->update(
            $table_name,
            array('shipping_status' => $status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            wp_send_json_success(array('message' => 'Status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update status'));
        }
    }

    /**
     * AJAX: Get order details
     */
    public function get_order_details() {
        check_ajax_referer('skillscore_order_details', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $order_id));
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
        }
        
        $ebook = get_post($order->ebook_id);
        $ebook_title = $ebook ? $ebook->post_title : 'Deleted Ebook';
        $format = $order->format ?: 'ebook';
        
        $html = '<table class="widefat" style="width: 100%; border-collapse: collapse;"><tbody>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="width: 30%; padding: 12px; text-align: left; background: #f6f7f7;">Order ID</th><td style="padding: 12px;"><strong>#' . $order->id . '</strong></td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Customer</th><td style="padding: 12px;"><strong>' . esc_html($order->user_name) . '</strong><br>' . esc_html($order->user_email) . '</td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Ebook</th><td style="padding: 12px;">' . esc_html($ebook_title) . '</td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Format</th><td style="padding: 12px;"><strong>' . esc_html(ucfirst($format)) . '</strong></td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Amount</th><td style="padding: 12px;"><strong>' . esc_html($order->currency . ' ' . number_format($order->amount, 2)) . '</strong></td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Gateway</th><td style="padding: 12px;">' . esc_html(ucfirst($order->gateway)) . '</td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Reference</th><td style="padding: 12px;"><code style="background: #f6f7f7; padding: 4px 8px; border-radius: 3px;">' . esc_html($order->transaction_reference) . '</code></td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Status</th><td style="padding: 12px;"><strong>' . esc_html(ucfirst($order->status)) . '</strong></td></tr>';
        $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Purchase Date</th><td style="padding: 12px;">' . esc_html(date('F j, Y g:i a', strtotime($order->purchase_date))) . '</td></tr>';
        
        if ($format !== 'printed') {
            $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Downloads</th><td style="padding: 12px;">' . esc_html($order->download_count) . ' times</td></tr>';
        }
        
        if ($format === 'printed' && !empty($order->shipping_address)) {
            $shipping = json_decode($order->shipping_address, true);
            if ($shipping) {
                $html .= '<tr style="border-bottom: 1px solid #ddd;"><th style="padding: 12px; text-align: left; background: #f6f7f7;">Shipping Address</th><td style="padding: 12px;">';
                $html .= esc_html($shipping['address']) . '<br>';
                $html .= esc_html($shipping['city']) . ', ' . esc_html($shipping['state']) . ' ' . esc_html($shipping['postal_code']) . '<br>';
                $html .= esc_html($shipping['country']) . '<br>';
                $html .= '<strong>Phone:</strong> ' . esc_html($shipping['phone']);
                $html .= '</td></tr>';
                $html .= '<tr><th style="padding: 12px; text-align: left; background: #f6f7f7;">Shipping Status</th><td style="padding: 12px;"><strong>' . esc_html(ucfirst($order->shipping_status ?: 'pending')) . '</strong></td></tr>';
            }
        }
        
        $html .= '</tbody></table>';
        
        wp_send_json_success(array('html' => $html));
    }
}