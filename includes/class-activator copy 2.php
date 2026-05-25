<?php
/**
 * Fired during plugin activation - FIXED VERSION
 * Creates wp_skillscore_ebook_purchases table (not wp_skillscore_orders)
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
     * Create custom database tables - FIXED TABLE NAMES
     */
    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // PURCHASES TABLE (was "orders" - now "ebook_purchases" to match payment handler)
        $purchases_table = $wpdb->prefix . 'skillscore_ebook_purchases';
        $sql_purchases = "CREATE TABLE IF NOT EXISTS $purchases_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ebook_id bigint(20) NOT NULL,
            user_name varchar(255) NOT NULL,
            user_email varchar(100) NOT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(10) NOT NULL DEFAULT 'USD',
            gateway varchar(50) NOT NULL,
            transaction_reference varchar(255) NOT NULL,
            download_token varchar(255) NOT NULL,
            download_count int(11) NOT NULL DEFAULT 0,
            status varchar(50) NOT NULL DEFAULT 'pending',
            purchase_date datetime NOT NULL,
            last_download_date datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY download_token (download_token),
            KEY transaction_reference (transaction_reference),
            KEY user_email (user_email),
            KEY ebook_id (ebook_id),
            KEY status (status)
        ) $charset_collate;";

        // DOWNLOADS TABLE (keeps same name)
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
        
        // Create purchases table (NEW NAME)
        dbDelta($sql_purchases);
        
        // Create downloads table
        dbDelta($sql_downloads);
        
        // Log table creation
        if ($wpdb->get_var("SHOW TABLES LIKE '$purchases_table'") == $purchases_table) {
            error_log('✅ Table created successfully: ' . $purchases_table);
        } else {
            error_log('❌ Failed to create table: ' . $purchases_table);
        }
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