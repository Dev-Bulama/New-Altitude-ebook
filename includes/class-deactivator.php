<?php
/**
 * Fired during plugin deactivation.
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Deactivator {

    /**
     * Deactivation logic.
     */
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();

        // Clear any scheduled cron jobs
        wp_clear_scheduled_hook('skillscore_ebook_cleanup_expired_downloads');
    }
}
