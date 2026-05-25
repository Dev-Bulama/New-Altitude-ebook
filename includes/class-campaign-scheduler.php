<?php
/**
 * Campaign Scheduler Class
 * Handles WP-Cron scheduling for automated email campaigns
 * 
 * @package SkillScore_Ebook
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Campaign_Scheduler {

    /**
     * Cron hook name
     */
    const CRON_HOOK = 'skillscore_send_campaign_emails';

    /**
     * Campaign manager instance
     */
    private $campaign_manager;

    /**
     * Constructor
     */
    public function __construct() {
        // Initialize campaign manager
        $this->campaign_manager = new SkillScore_Email_Campaign();

        // Schedule cron on activation
        add_action('wp', array($this, 'schedule_cron_event'));
        
        // Hook the cron event to our processing function
        add_action(self::CRON_HOOK, array($this, 'process_pending_emails'));
        
        // Cleanup on deactivation
        register_deactivation_hook(SKILLSCORE_EBOOK_PLUGIN_BASENAME, array($this, 'clear_scheduled_event'));
    }

    /**
     * Schedule the cron event if not already scheduled
     */
    public function schedule_cron_event() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            // Schedule to run every 15 minutes
            wp_schedule_event(time(), 'every_15_minutes', self::CRON_HOOK);
            error_log('Campaign Scheduler: Cron event scheduled');
        }
    }

    /**
     * Add custom cron schedule interval
     */
    public static function add_cron_intervals($schedules) {
        if (!isset($schedules['every_15_minutes'])) {
            $schedules['every_15_minutes'] = array(
                'interval' => 900, // 15 minutes in seconds
                'display' => __('Every 15 Minutes', 'skillscore-ebook')
            );
        }
        
        if (!isset($schedules['every_5_minutes'])) {
            $schedules['every_5_minutes'] = array(
                'interval' => 300, // 5 minutes in seconds
                'display' => __('Every 5 Minutes', 'skillscore-ebook')
            );
        }
        
        return $schedules;
    }

    /**
     * Process pending emails that are due to be sent
     */
    public function process_pending_emails() {
        error_log('Campaign Scheduler: Processing pending emails...');

        // Get pending emails
        $pending_emails = $this->campaign_manager->get_pending_emails();

        if (empty($pending_emails)) {
            error_log('Campaign Scheduler: No pending emails to send');
            return;
        }

        error_log('Campaign Scheduler: Found ' . count($pending_emails) . ' pending emails');

        $sent_count = 0;
        $failed_count = 0;

        // Process each email
        foreach ($pending_emails as $email) {
            // Check if user already purchased via this campaign (auto-stop logic)
            if ($this->should_stop_email($email)) {
                $this->campaign_manager->stop_campaign_for_user(
                    $email->campaign_id,
                    $email->user_email,
                    'User purchased via campaign email'
                );
                continue;
            }

            // Send the email
            $result = $this->campaign_manager->send_campaign_email($email);

            if ($result) {
                $sent_count++;
            } else {
                $failed_count++;
            }

            // Small delay to avoid overwhelming the mail server
            usleep(250000); // 0.25 seconds
        }

        error_log("Campaign Scheduler: Completed. Sent: $sent_count, Failed: $failed_count");
    }

    /**
     * Check if email should be stopped (user purchased via email link)
     * 
     * @param object $email Email log entry
     * @return bool
     */
    private function should_stop_email($email) {
        global $wpdb;
        $purchase_table = $wpdb->prefix . 'skillscore_ebook_purchases';

        // Check if there's a recent purchase for this user and ebook with the campaign tracking
        $recent_purchase = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $purchase_table 
            WHERE user_email = %s 
            AND ebook_id = %d 
            AND status = 'completed'
            AND purchase_date > %s",
            $email->user_email,
            $email->ebook_id,
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // If they already have a completed purchase for this ebook, stop sending
        return $recent_purchase > 1; // More than 1 means they purchased again
    }

    /**
     * Clear scheduled cron event on deactivation
     */
    public function clear_scheduled_event() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::CRON_HOOK);
            error_log('Campaign Scheduler: Cron event cleared');
        }
    }

    /**
     * Manual trigger for testing (can be called via admin AJAX)
     */
    public function manual_trigger() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $this->process_pending_emails();
        
        wp_send_json_success(array('message' => 'Campaign emails processed'));
    }

    /**
     * Get next scheduled run time
     * 
     * @return string|false
     */
    public function get_next_run() {
        $timestamp = wp_next_scheduled(self::CRON_HOOK);
        
        if ($timestamp) {
            return date('Y-m-d H:i:s', $timestamp);
        }
        
        return false;
    }

    /**
     * Get cron status for admin display
     * 
     * @return array
     */
    public function get_cron_status() {
        $next_run = $this->get_next_run();
        
        return array(
            'is_scheduled' => $next_run !== false,
            'next_run' => $next_run,
            'interval' => 'Every 15 Minutes'
        );
    }
}

// Add custom cron intervals
add_filter('cron_schedules', array('SkillScore_Campaign_Scheduler', 'add_cron_intervals'));