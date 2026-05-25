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
    public function __construct() {
        // Register upload handler as admin_post action
        add_action('admin_post_skillscore_upload_voice', array($this, 'handle_voice_upload_action'));
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
     * Render orders page.
     */
    public function render_orders_page() {
        global $wpdb;
        $orders_table = $wpdb->prefix . 'skillscore_orders';

        $orders = $wpdb->get_results(
            "SELECT o.*, p.post_title as ebook_title
             FROM $orders_table o
             LEFT JOIN {$wpdb->posts} p ON o.ebook_id = p.ID
             ORDER BY o.order_date DESC
             LIMIT 100"
        );

        ?>
        <div class="wrap">
            <h1><?php _e('Orders', 'skillscore-ebook'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Order #', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Ebook', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Customer', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Amount', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Quantity', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Gateway', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Status', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Date', 'skillscore-ebook'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders): ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo esc_html($order->order_reference); ?></td>
                                <td><?php echo esc_html($order->ebook_title); ?></td>
                                <td>
                                    <?php echo esc_html($order->user_name); ?><br>
                                    <small><?php echo esc_html($order->user_email); ?></small>
                                </td>
                                <td><?php echo esc_html($order->currency . ' ' . number_format($order->amount, 2)); ?></td>
                                <td><?php echo esc_html($order->quantity); ?></td>
                                <td><?php echo esc_html(ucfirst($order->payment_gateway)); ?></td>
                                <td>
                                    <span class="order-status status-<?php echo esc_attr($order->payment_status); ?>">
                                        <?php echo esc_html(ucfirst($order->payment_status)); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date('Y-m-d H:i', strtotime($order->order_date))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8"><?php _e('No orders found.', 'skillscore-ebook'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render downloads page.
     */
    public function render_downloads_page() {
        global $wpdb;
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';

        $downloads = $wpdb->get_results(
            "SELECT d.*, p.post_title as ebook_title, o.user_email
             FROM $downloads_table d
             LEFT JOIN {$wpdb->posts} p ON d.ebook_id = p.ID
             LEFT JOIN {$wpdb->prefix}skillscore_orders o ON d.order_id = o.id
             ORDER BY d.created_at DESC
             LIMIT 100"
        );

        ?>
        <div class="wrap">
            <h1><?php _e('Downloads', 'skillscore-ebook'); ?></h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Ebook', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Customer', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Downloads', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Limit', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Created', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Expires', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Status', 'skillscore-ebook'); ?></th>
                        <th><?php _e('Actions', 'skillscore-ebook'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($downloads): ?>
                        <?php foreach ($downloads as $download): ?>
                            <tr>
                                <td><?php echo esc_html($download->ebook_title); ?></td>
                                <td><?php echo esc_html($download->user_email); ?></td>
                                <td><?php echo esc_html($download->download_count); ?></td>
                                <td><?php echo esc_html($download->download_limit == -1 ? 'Unlimited' : $download->download_limit); ?></td>
                                <td><?php echo esc_html(date('Y-m-d', strtotime($download->created_at))); ?></td>
                                <td><?php echo esc_html($download->expires_at ? date('Y-m-d', strtotime($download->expires_at)) : 'Never'); ?></td>
                                <td>
                                    <?php
                                    if ($download->is_revoked) {
                                        echo '<span style="color: red;">Revoked</span>';
                                    } elseif ($download->expires_at && strtotime($download->expires_at) < time()) {
                                        echo '<span style="color: orange;">Expired</span>';
                                    } else {
                                        echo '<span style="color: green;">Active</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if (!$download->is_revoked): ?>
                                        <a href="#" class="button button-small">Revoke</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8"><?php _e('No downloads found.', 'skillscore-ebook'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Enqueue admin assets.
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
        
        // Show upload messages
        $this->show_upload_messages();
    }
    
    /**
     * Show upload success/error messages
     */
    private function show_upload_messages() {
        if (get_transient('skillscore_voice_upload_success')) {
            add_settings_error(
                'skillscore_ebook_messages',
                'skillscore_ebook_message',
                __('Voice sample uploaded successfully!', 'skillscore-ebook'),
                'updated'
            );
            delete_transient('skillscore_voice_upload_success');
        }
        
        if (get_transient('skillscore_voice_upload_error')) {
            add_settings_error(
                'skillscore_ebook_messages',
                'skillscore_ebook_message',
                __('Failed to upload voice sample. Please check file size (max 10MB) and format (MP3, WAV, OGG).', 'skillscore-ebook'),
                'error'
            );
            delete_transient('skillscore_voice_upload_error');
        }
    }
}