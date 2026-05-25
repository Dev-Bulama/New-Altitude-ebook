<?php
/**
 * Secure Download Handler
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Download_Handler {

    /**
     * Create download token.
     */
    public function create_download_token($order_id, $ebook_id) {
        global $wpdb;
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';

        // Generate unique token
        $token = wp_hash($order_id . $ebook_id . time() . wp_rand());

        // Get settings
        $download_limit = intval(get_option('skillscore_ebook_download_limit', 5));
        $expiry_days = intval(get_option('skillscore_ebook_download_expiry_days', 30));

        $expires_at = null;
        if ($expiry_days > 0) {
            $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));
        }

        // Insert download record
        $wpdb->insert(
            $downloads_table,
            array(
                'order_id' => $order_id,
                'ebook_id' => $ebook_id,
                'download_token' => $token,
                'download_count' => 0,
                'download_limit' => $download_limit,
                'expires_at' => $expires_at,
                'created_at' => current_time('mysql'),
                'is_revoked' => 0,
            ),
            array('%d', '%d', '%s', '%d', '%d', '%s', '%s', '%d')
        );

        return $token;
    }

    /**
     * Handle download request.
     */
    public function handle_download_request() {
        if (!isset($_GET['skillscore_download'])) {
            return;
        }

        $token = sanitize_text_field($_GET['skillscore_download']);

        if (empty($token)) {
            wp_die(__('Invalid download token.', 'skillscore-ebook'), 403);
        }

        global $wpdb;
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';

        // Get download record
        $download = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $downloads_table WHERE download_token = %s",
            $token
        ));

        if (!$download) {
            wp_die(__('Download not found.', 'skillscore-ebook'), 404);
        }

        // Check if revoked
        if ($download->is_revoked) {
            wp_die(__('This download has been revoked.', 'skillscore-ebook'), 403);
        }

        // Check expiry
        if ($download->expires_at && strtotime($download->expires_at) < time()) {
            wp_die(__('This download link has expired.', 'skillscore-ebook'), 403);
        }

        // Check download limit
        if ($download->download_limit > 0 && $download->download_count >= $download->download_limit) {
            wp_die(__('Download limit exceeded.', 'skillscore-ebook'), 403);
        }

        // Get ebook file
        $file_path = get_post_meta($download->ebook_id, '_ebook_file_path', true);
        $file_name = get_post_meta($download->ebook_id, '_ebook_file_name', true);

        if (!$file_path) {
            wp_die(__('Ebook file not found.', 'skillscore-ebook'), 404);
        }

        $upload_dir = wp_upload_dir();
        $full_path = $upload_dir['basedir'] . '/skillscore-ebooks/' . $file_path;

        if (!file_exists($full_path)) {
            wp_die(__('Ebook file does not exist.', 'skillscore-ebook'), 404);
        }

        // Update download count
        $wpdb->update(
            $downloads_table,
            array(
                'download_count' => $download->download_count + 1,
                'last_downloaded_at' => current_time('mysql'),
                'ip_address' => $this->get_user_ip(),
                'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
            ),
            array('id' => $download->id),
            array('%d', '%s', '%s', '%s'),
            array('%d')
        );

        // Serve file
        $this->serve_file($full_path, $file_name);
    }

    /**
     * Serve file for download.
     */
    private function serve_file($file_path, $file_name) {
        // Clean output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Set headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Pragma: public');

        // Read and output file
        readfile($file_path);
        exit;
    }

    /**
     * Get user IP address.
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return sanitize_text_field($ip);
    }

    /**
     * Revoke download access.
     */
    public function revoke_download($download_id) {
        global $wpdb;
        $downloads_table = $wpdb->prefix . 'skillscore_downloads';

        return $wpdb->update(
            $downloads_table,
            array('is_revoked' => 1),
            array('id' => $download_id),
            array('%d'),
            array('%d')
        );
    }

    /**
     * Get download link.
     */
    public function get_download_link($token) {
        return add_query_arg('skillscore_download', $token, home_url());
    }
}
