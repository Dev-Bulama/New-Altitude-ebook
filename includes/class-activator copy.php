<?php
/**
 * Fired during plugin activation.
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Activator {

    /**
     * Activation logic.
     */
    public static function activate() {
        // Create custom database tables
        self::create_tables();

        // Create uploads directory for ebooks
        self::create_upload_directories();

        // Set default options
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create custom database tables.
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Orders table
        $orders_table = $wpdb->prefix . 'skillscore_orders';
        $sql_orders = "CREATE TABLE IF NOT EXISTS $orders_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_reference varchar(100) NOT NULL,
            ebook_id bigint(20) UNSIGNED NOT NULL,
            user_email varchar(100) NOT NULL,
            user_name varchar(255) DEFAULT NULL,
            user_id bigint(20) UNSIGNED DEFAULT NULL,
            quantity int(11) NOT NULL DEFAULT 1,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            payment_gateway varchar(50) NOT NULL,
            payment_status varchar(50) NOT NULL DEFAULT 'pending',
            transaction_id varchar(255) DEFAULT NULL,
            order_date datetime NOT NULL,
            PRIMARY KEY (id),
            KEY order_reference (order_reference),
            KEY ebook_id (ebook_id),
            KEY user_email (user_email),
            KEY payment_status (payment_status)
        ) $charset_collate;";

        // Downloads table
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';
        $sql_downloads = "CREATE TABLE IF NOT EXISTS $downloads_table (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            order_id bigint(20) UNSIGNED NOT NULL,
            ebook_id bigint(20) UNSIGNED NOT NULL,
            download_token varchar(255) NOT NULL,
            download_count int(11) NOT NULL DEFAULT 0,
            download_limit int(11) NOT NULL DEFAULT -1,
            expires_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            last_downloaded_at datetime DEFAULT NULL,
            ip_address varchar(100) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            is_revoked tinyint(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY download_token (download_token),
            KEY order_id (order_id),
            KEY ebook_id (ebook_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_orders);
        dbDelta($sql_downloads);
    }

    /**
     * Create upload directories.
     */
    private static function create_upload_directories() {
        $upload_dir = wp_upload_dir();
        $ebook_dir = $upload_dir['basedir'] . '/skillscore-ebooks';
        $audio_dir = $upload_dir['basedir'] . '/skillscore-audio';

        if (!file_exists($ebook_dir)) {
            wp_mkdir_p($ebook_dir);
            // Add .htaccess to protect direct access
            file_put_contents($ebook_dir . '/.htaccess', 'deny from all');
            file_put_contents($ebook_dir . '/index.php', '<?php // Silence is golden');
        }

        if (!file_exists($audio_dir)) {
            wp_mkdir_p($audio_dir);
            file_put_contents($audio_dir . '/index.php', '<?php // Silence is golden');
        }
    }

    /**
     * Set default plugin options.
     */
    private static function set_default_options() {
        $default_options = array(
            'download_limit' => 5,
            'download_expiry_days' => 30,
            'enable_quantity_selector' => true,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'enable_paystack' => false,
            'enable_flutterwave' => false,
            'enable_stripe' => false,
            'enable_paypal' => false,
            'enable_audio_preview' => true,
            'audio_preview_duration' => 60,
        );

        foreach ($default_options as $key => $value) {
            $option_name = 'skillscore_ebook_' . $key;
            if (get_option($option_name) === false) {
                add_option($option_name, $value);
            }
        }
    }
}
