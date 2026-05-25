<?php
/**
 * Email Campaign Management Class
 * Handles automated post-purchase email campaigns
 * 
 * @package SkillScore_Ebook
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Email_Campaign {

    /**
     * Database table names
     */
    private $campaigns_table;
    private $log_table;

    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->campaigns_table = $wpdb->prefix . 'ebook_email_campaigns';
        $this->log_table = $wpdb->prefix . 'ebook_campaign_log';

        // Create tables on init
        add_action('init', array($this, 'create_tables'), 5);
        
        // Hook into purchase completion
        add_action('skillscore_purchase_completed', array($this, 'trigger_campaign'), 10, 1);
    }

    /**
     * Create database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Campaigns table - UPDATED with delay-based scheduling
        $campaigns_sql = "CREATE TABLE IF NOT EXISTS {$this->campaigns_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ebook_id bigint(20) NOT NULL,
            email_subject varchar(255) NOT NULL,
            email_body longtext NOT NULL,
            delay_value int(11) NOT NULL DEFAULT 0,
            delay_unit varchar(20) NOT NULL DEFAULT 'days',
            is_active tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY ebook_id (ebook_id),
            KEY is_active (is_active)
        ) $charset_collate;";

        // Campaign log table - UPDATED with scheduled_send_at per user
        $log_sql = "CREATE TABLE IF NOT EXISTS {$this->log_table} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) NOT NULL,
            user_email varchar(100) NOT NULL,
            user_name varchar(255) NOT NULL,
            purchase_id bigint(20) NOT NULL,
            purchase_date datetime NOT NULL,
            scheduled_send_at datetime NOT NULL,
            status varchar(50) NOT NULL DEFAULT 'pending',
            sent_at datetime DEFAULT NULL,
            stopped_reason varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY user_email (user_email),
            KEY purchase_id (purchase_id),
            KEY status (status),
            KEY scheduled_send_at (scheduled_send_at),
            UNIQUE KEY unique_campaign_user (campaign_id, user_email)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($campaigns_sql);
        dbDelta($log_sql);

        // Migrate old table structure if needed
        $this->migrate_table_structure();

        error_log('SkillScore Campaign: Tables created/verified');
    }

    /**
     * Migrate table structure from old to new format
     */
    private function migrate_table_structure() {
        global $wpdb;
        
        // Check if old columns exist and migrate
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->campaigns_table}");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        // Add delay columns if missing
        if (!in_array('delay_value', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->campaigns_table} ADD COLUMN delay_value INT(11) NOT NULL DEFAULT 7 AFTER email_body");
        }
        
        if (!in_array('delay_unit', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->campaigns_table} ADD COLUMN delay_unit VARCHAR(20) NOT NULL DEFAULT 'days' AFTER delay_value");
        }
        
        // Check log table
        $log_columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->log_table}");
        $log_column_names = array();
        foreach ($log_columns as $column) {
            $log_column_names[] = $column->Field;
        }
        
        // Add purchase_date if missing
        if (!in_array('purchase_date', $log_column_names)) {
            $wpdb->query("ALTER TABLE {$this->log_table} ADD COLUMN purchase_date DATETIME NOT NULL AFTER purchase_id");
        }
        
        // Add scheduled_send_at if missing
        if (!in_array('scheduled_send_at', $log_column_names)) {
            $wpdb->query("ALTER TABLE {$this->log_table} ADD COLUMN scheduled_send_at DATETIME NOT NULL AFTER purchase_date");
            $wpdb->query("ALTER TABLE {$this->log_table} ADD INDEX scheduled_send_at (scheduled_send_at)");
        }
    }

    /**
     * Trigger campaign when purchase is completed
     * 
     * @param object $purchase Purchase object from database
     */
    public function trigger_campaign($purchase) {
        if (!$purchase || !isset($purchase->ebook_id)) {
            error_log('Campaign: Invalid purchase object');
            return;
        }

        // Get all active campaigns for this ebook
        $campaigns = $this->get_ebook_campaigns($purchase->ebook_id);

        if (empty($campaigns)) {
            error_log('Campaign: No campaigns found for ebook ID: ' . $purchase->ebook_id);
            return;
        }

        // Register each campaign for this user
        foreach ($campaigns as $campaign) {
            $this->register_user_for_campaign($campaign, $purchase);
        }

        error_log('Campaign: Registered ' . count($campaigns) . ' campaigns for user: ' . $purchase->user_email);
    }

    /**
     * Get all active campaigns for an ebook
     * 
     * @param int $ebook_id
     * @return array
     */
    public function get_ebook_campaigns($ebook_id) {
        global $wpdb;

        $campaigns = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->campaigns_table} 
            WHERE ebook_id = %d 
            AND is_active = 1 
            ORDER BY scheduled_datetime ASC",
            $ebook_id
        ));

        return $campaigns ? $campaigns : array();
    }

    /**
     * Register a user for a specific campaign
     * 
     * @param object $campaign Campaign object
     * @param object $purchase Purchase object
     */
    private function register_user_for_campaign($campaign, $purchase) {
        global $wpdb;

        // Check if already registered
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->log_table} 
            WHERE campaign_id = %d AND user_email = %s",
            $campaign->id,
            $purchase->user_email
        ));

        if ($exists) {
            error_log('Campaign: User already registered for campaign ID: ' . $campaign->id);
            return;
        }

        // Calculate scheduled send time based on purchase date + delay
        $purchase_datetime = strtotime($purchase->purchase_date);
        $delay_value = intval($campaign->delay_value);
        $delay_unit = $campaign->delay_unit;
        
        // Calculate send time
        switch ($delay_unit) {
            case 'minutes':
                $scheduled_send_at = date('Y-m-d H:i:s', $purchase_datetime + ($delay_value * 60));
                break;
            case 'hours':
                $scheduled_send_at = date('Y-m-d H:i:s', $purchase_datetime + ($delay_value * 3600));
                break;
            case 'days':
                $scheduled_send_at = date('Y-m-d H:i:s', $purchase_datetime + ($delay_value * 86400));
                break;
            case 'weeks':
                $scheduled_send_at = date('Y-m-d H:i:s', $purchase_datetime + ($delay_value * 604800));
                break;
            default:
                $scheduled_send_at = date('Y-m-d H:i:s', $purchase_datetime + ($delay_value * 86400));
        }

        // Insert into log with individual scheduled send time
        $wpdb->insert(
            $this->log_table,
            array(
                'campaign_id' => $campaign->id,
                'user_email' => $purchase->user_email,
                'user_name' => $purchase->user_name,
                'purchase_id' => $purchase->id,
                'purchase_date' => $purchase->purchase_date,
                'scheduled_send_at' => $scheduled_send_at,
                'status' => 'pending'
            ),
            array('%d', '%s', '%s', '%d', '%s', '%s', '%s')
        );

        error_log("Campaign: User {$purchase->user_email} registered for campaign ID: {$campaign->id} - Will send at: {$scheduled_send_at}");
    }

    /**
     * Create or update a campaign
     * 
     * @param array $data Campaign data
     * @return int|false Campaign ID or false on failure
     */
    public function save_campaign($data) {
        global $wpdb;

        $campaign_data = array(
            'ebook_id' => isset($data['ebook_id']) ? intval($data['ebook_id']) : 0,
            'email_subject' => isset($data['email_subject']) ? sanitize_text_field($data['email_subject']) : '',
            'email_body' => isset($data['email_body']) ? wp_kses_post($data['email_body']) : '',
            'delay_value' => isset($data['delay_value']) ? intval($data['delay_value']) : 7,
            'delay_unit' => isset($data['delay_unit']) ? sanitize_text_field($data['delay_unit']) : 'days',
            'is_active' => isset($data['is_active']) ? intval($data['is_active']) : 1
        );

        // Validate required fields
        if (empty($campaign_data['ebook_id']) || empty($campaign_data['email_subject']) || 
            empty($campaign_data['email_body'])) {
            error_log('Campaign: Missing required fields');
            return false;
        }

        if (isset($data['id']) && $data['id'] > 0) {
            // Update existing campaign
            $wpdb->update(
                $this->campaigns_table,
                $campaign_data,
                array('id' => intval($data['id'])),
                array('%d', '%s', '%s', '%d', '%s', '%d'),
                array('%d')
            );
            return intval($data['id']);
        } else {
            // Insert new campaign
            $wpdb->insert(
                $this->campaigns_table,
                $campaign_data,
                array('%d', '%s', '%s', '%d', '%s', '%d')
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Delete a campaign
     * 
     * @param int $campaign_id
     * @return bool
     */
    public function delete_campaign($campaign_id) {
        global $wpdb;

        // Delete campaign
        $deleted = $wpdb->delete(
            $this->campaigns_table,
            array('id' => $campaign_id),
            array('%d')
        );

        // Delete associated logs
        $wpdb->delete(
            $this->log_table,
            array('campaign_id' => $campaign_id),
            array('%d')
        );

        return $deleted !== false;
    }

    /**
     * Toggle campaign active status
     * 
     * @param int $campaign_id
     * @param bool $is_active
     * @return bool
     */
    public function toggle_campaign($campaign_id, $is_active) {
        global $wpdb;

        $updated = $wpdb->update(
            $this->campaigns_table,
            array('is_active' => $is_active ? 1 : 0),
            array('id' => $campaign_id),
            array('%d'),
            array('%d')
        );

        return $updated !== false;
    }

    /**
     * Get campaign by ID
     * 
     * @param int $campaign_id
     * @return object|null
     */
    public function get_campaign($campaign_id) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->campaigns_table} WHERE id = %d",
            $campaign_id
        ));
    }

    /**
     * Stop campaign for a specific user (called when they purchase via email link)
     * 
     * @param int $campaign_id
     * @param string $user_email
     * @param string $reason
     * @return bool
     */
    public function stop_campaign_for_user($campaign_id, $user_email, $reason = 'Purchased via email link') {
        global $wpdb;

        $updated = $wpdb->update(
            $this->log_table,
            array(
                'status' => 'stopped',
                'stopped_reason' => $reason
            ),
            array(
                'campaign_id' => $campaign_id,
                'user_email' => $user_email
            ),
            array('%s', '%s'),
            array('%d', '%s')
        );

        if ($updated) {
            error_log("Campaign: Stopped for user $user_email - Reason: $reason");
        }

        return $updated !== false;
    }

    /**
     * Mark email as sent
     * 
     * @param int $log_id
     * @return bool
     */
    public function mark_as_sent($log_id) {
        global $wpdb;

        return $wpdb->update(
            $this->log_table,
            array(
                'status' => 'sent',
                'sent_at' => current_time('mysql')
            ),
            array('id' => $log_id),
            array('%s', '%s'),
            array('%d')
        ) !== false;
    }

    /**
     * Get campaign statistics
     * 
     * @param int $campaign_id
     * @return array
     */
    public function get_campaign_stats($campaign_id) {
        global $wpdb;

        $stats = array(
            'total' => 0,
            'pending' => 0,
            'sent' => 0,
            'stopped' => 0
        );

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT status, COUNT(*) as count 
            FROM {$this->log_table} 
            WHERE campaign_id = %d 
            GROUP BY status",
            $campaign_id
        ));

        foreach ($results as $row) {
            $stats[$row->status] = intval($row->count);
            $stats['total'] += intval($row->count);
        }

        return $stats;
    }

    /**
     * Get all recipients for a campaign
     * 
     * @param int $campaign_id
     * @param string $status Optional status filter
     * @return array
     */
    public function get_campaign_recipients($campaign_id, $status = null) {
        global $wpdb;

        if ($status) {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->log_table} 
                WHERE campaign_id = %d AND status = %s 
                ORDER BY scheduled_send_at ASC",
                $campaign_id,
                $status
            ));
        } else {
            $results = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->log_table} 
                WHERE campaign_id = %d 
                ORDER BY scheduled_send_at ASC",
                $campaign_id
            ));
        }

        return $results ? $results : array();
    }

    /**
     * Get all campaigns with stats
     * 
     * @return array
     */
    public function get_all_campaigns_with_stats() {
        global $wpdb;

        $campaigns = $wpdb->get_results(
            "SELECT * FROM {$this->campaigns_table} ORDER BY created_at DESC"
        );

        if (!$campaigns) {
            return array();
        }

        // Add stats and ebook title to each campaign
        foreach ($campaigns as $campaign) {
            $campaign->stats = $this->get_campaign_stats($campaign->id);
            $campaign->ebook_title = get_the_title($campaign->ebook_id);
        }

        return $campaigns;
    }

    /**
     * Get pending emails that need to be sent
     * Used by scheduler
     * 
     * @return array
     */
    public function get_pending_emails() {
        global $wpdb;

        $now = current_time('mysql');

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, c.email_subject, c.email_body, c.ebook_id, c.delay_value, c.delay_unit
            FROM {$this->log_table} l
            INNER JOIN {$this->campaigns_table} c ON l.campaign_id = c.id
            WHERE l.status = 'pending'
            AND c.is_active = 1
            AND l.scheduled_send_at <= %s
            ORDER BY l.scheduled_send_at ASC
            LIMIT 50",
            $now
        ));

        return $results ? $results : array();
    }

    /**
     * Send campaign email with placeholder replacement
     * 
     * @param object $log_entry Log entry object
     * @return bool
     */
    public function send_campaign_email($log_entry) {
        // Get ebook details
        $ebook = get_post($log_entry->ebook_id);
        if (!$ebook) {
            error_log('Campaign: Ebook not found for ID: ' . $log_entry->ebook_id);
            return false;
        }

        // Get purchase details for download link
        global $wpdb;
        $purchase_table = $wpdb->prefix . 'skillscore_ebook_purchases';
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $purchase_table WHERE id = %d",
            $log_entry->purchase_id
        ));

        if (!$purchase) {
            error_log('Campaign: Purchase not found for ID: ' . $log_entry->purchase_id);
            return false;
        }

        // Build download URL with campaign tracking
        $download_url = add_query_arg(array(
            'skillscore_download' => $purchase->download_token,
            'campaign_id' => $log_entry->campaign_id
        ), home_url());

        // Prepare placeholders
        $placeholders = array(
            '{customer_name}' => $log_entry->user_name,
            '{ebook_title}' => $ebook->post_title,
            '{download_link}' => $download_url,
            '{order_date}' => date('F j, Y', strtotime($purchase->purchase_date))
        );

        // Replace placeholders in subject and body
        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $log_entry->email_subject);
        $body = str_replace(array_keys($placeholders), array_values($placeholders), $log_entry->email_body);

        // Prepare email headers
        $headers = array();
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';

        // Convert message to HTML
        $html_body = wpautop($body);

        // Send email
        $sent = wp_mail($log_entry->user_email, $subject, $html_body, $headers);

        if ($sent) {
            $this->mark_as_sent($log_entry->id);
            error_log("Campaign: Email sent successfully to {$log_entry->user_email} for campaign #{$log_entry->campaign_id}");
        } else {
            error_log("Campaign: Failed to send email to {$log_entry->user_email}");
        }

        return $sent;
    }
}