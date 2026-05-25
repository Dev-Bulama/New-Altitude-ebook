<?php
/**
 * Download Handler - CORRECTED VERSION
 * Fixes method name mismatch error
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Download_Handler {

    public function __construct() {
        // Handle download page display
        add_action('template_redirect', array($this, 'handle_download_page'));
        
        // Handle download requests (AJAX)
        add_action('wp_ajax_skillscore_download_ebook', array($this, 'process_download'));
        add_action('wp_ajax_nopriv_skillscore_download_ebook', array($this, 'process_download'));
        
        // Handle payment success redirects
        add_action('init', array($this, 'handle_payment_success'));
        
        // OLD METHOD NAME - For backwards compatibility
        add_action('init', array($this, 'handle_download_request'), 5);
    }

    /**
     * Handle download request - LEGACY METHOD
     * This is called by the old code - keep for compatibility
     */
    public function handle_download_request() {
        // This method exists to prevent errors
        // The actual handling is done in handle_download_page()
        return;
    }

    /**
     * Handle download page display
     */
    public function handle_download_page() {
        if (!isset($_GET['skillscore_download'])) {
            return;
        }

        $token = sanitize_text_field($_GET['skillscore_download']);
        
        // Load download page template
        $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/download-page-template.php';
        
        if (file_exists($template)) {
            include $template;
            exit;
        } else {
            wp_die(__('Download page template not found. Please ensure the template file is uploaded.', 'skillscore-ebook'));
        }
    }

    /**
     * Handle payment success from gateways
     */
    public function handle_payment_success() {
        // Check for payment success parameters
        if (!isset($_GET['skillscore_payment']) || $_GET['skillscore_payment'] !== 'success') {
            return;
        }

        if (!isset($_GET['reference'])) {
            return;
        }

        $reference = sanitize_text_field($_GET['reference']);

        // Get purchase from database
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE transaction_reference = %s",
            $reference
        ));

        if (!$purchase) {
            wp_die(__('Purchase not found. Please contact support with your payment reference.', 'skillscore-ebook'));
        }

        // Update purchase status to completed if not already
        if ($purchase->status !== 'completed') {
            $wpdb->update(
                $table_name,
                array(
                    'status' => 'completed',
                    'purchase_date' => current_time('mysql')
                ),
                array('id' => $purchase->id),
                array('%s', '%s'),
                array('%d')
            );
        }

        // Redirect to download page
        $download_url = add_query_arg(array(
            'skillscore_download' => $purchase->download_token
        ), home_url());

        wp_redirect($download_url);
        exit;
    }

    /**
     * Process file download
     */
    public function process_download() {
        // Get token
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';

        if (empty($token)) {
            wp_die(__('Invalid download token. Please check your email for the correct link.', 'skillscore-ebook'));
        }

        // Get purchase
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE download_token = %s",
            $token
        ));

        if (!$purchase) {
            wp_die(__('Invalid or expired download link. Please contact support.', 'skillscore-ebook'));
        }

        // Check download limit
        $download_limit = get_option('skillscore_ebook_download_limit', 3);
        if ($purchase->download_count >= $download_limit) {
            wp_die(sprintf(__('Download limit (%d) reached. Please contact support if you need additional downloads.', 'skillscore-ebook'), $download_limit));
        }

        // Get ebook file
        $ebook_id = $purchase->ebook_id;
        $file_url = get_post_meta($ebook_id, '_ebook_file_url', true);

        if (empty($file_url)) {
            wp_die(__('Ebook file not found. Please contact support with your order ID.', 'skillscore-ebook'));
        }

        // Update download count
        $wpdb->update(
            $table_name,
            array(
                'download_count' => $purchase->download_count + 1,
                'last_download_date' => current_time('mysql')
            ),
            array('id' => $purchase->id),
            array('%d', '%s'),
            array('%d')
        );

        // Log download
        error_log(sprintf(
            'Ebook download: Purchase #%d, Ebook #%d, User: %s, Count: %d/%d',
            $purchase->id,
            $ebook_id,
            $purchase->user_email,
            $purchase->download_count + 1,
            $download_limit
        ));

        // Get file path
        $file_path = $this->get_file_path_from_url($file_url);

        if ($file_path && file_exists($file_path)) {
            // Send file download
            $this->send_file_download($file_path);
        } else {
            // Redirect to URL if local file not found
            wp_redirect($file_url);
            exit;
        }
    }

    /**
     * Get local file path from URL
     */
    private function get_file_path_from_url($url) {
        $upload_dir = wp_upload_dir();
        $base_url = $upload_dir['baseurl'];
        $base_dir = $upload_dir['basedir'];

        // Check if URL is from uploads directory
        if (strpos($url, $base_url) === 0) {
            $file_path = str_replace($base_url, $base_dir, $url);
            return $file_path;
        }

        return false;
    }

    /**
     * Send file download with proper headers
     */
    private function send_file_download($file_path) {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Get file info
        $filename = basename($file_path);
        $file_size = filesize($file_path);
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // Set content type
        $content_types = array(
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'mobi' => 'application/x-mobipocket-ebook',
            'azw' => 'application/vnd.amazon.ebook',
            'azw3' => 'application/vnd.amazon.ebook'
        );

        $content_type = isset($content_types[$file_extension]) 
            ? $content_types[$file_extension] 
            : 'application/octet-stream';

        // Set headers
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . $file_size);

        // Clean output buffer
        flush();

        // Read file
        $handle = fopen($file_path, 'rb');
        
        if ($handle === false) {
            wp_die(__('Error reading file. Please contact support.', 'skillscore-ebook'));
        }

        // Send file in chunks
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }

        fclose($handle);
        exit;
    }

    /**
     * Generate unique download token
     */
    public static function generate_download_token() {
        return wp_generate_password(32, false, false);
    }

    /**
     * Create purchase record with download token
     */
    public static function create_purchase_record($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        $purchase_data = array(
            'ebook_id' => $data['ebook_id'],
            'user_name' => $data['user_name'],
            'user_email' => $data['user_email'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'gateway' => $data['gateway'],
            'transaction_reference' => $data['transaction_reference'],
            'download_token' => self::generate_download_token(),
            'download_count' => 0,
            'status' => isset($data['status']) ? $data['status'] : 'pending',
            'purchase_date' => current_time('mysql')
        );

        $wpdb->insert($table_name, $purchase_data);

        $purchase_id = $wpdb->insert_id;

        // Send email with download link
        if ($purchase_id) {
            self::send_download_email($purchase_id);
        }

        return $purchase_id;
    }

    /**
     * Send download email to customer
     */
    public static function send_download_email($purchase_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $purchase_id
        ));

        if (!$purchase) {
            return false;
        }

        // Get ebook details
        $ebook = get_post($purchase->ebook_id);
        if (!$ebook) {
            return false;
        }

        $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');
        $download_limit = get_option('skillscore_ebook_download_limit', 3);

        // Generate download URL
        $download_url = add_query_arg(array(
            'skillscore_download' => $purchase->download_token
        ), home_url());

        // Email subject
        $subject = sprintf(
            __('Your Ebook Purchase: %s', 'skillscore-ebook'),
            $ebook->post_title
        );

        // Email body
        $message = sprintf(__('Hi %s,', 'skillscore-ebook'), $purchase->user_name) . "\n\n";
        $message .= __('Thank you for your purchase!', 'skillscore-ebook') . "\n\n";
        $message .= __('Purchase Details:', 'skillscore-ebook') . "\n";
        $message .= sprintf(__('Ebook: %s', 'skillscore-ebook'), $ebook->post_title) . "\n";
        $message .= sprintf(__('Amount Paid: %s', 'skillscore-ebook'), $currency_symbol . number_format($purchase->amount, 2)) . "\n";
        $message .= sprintf(__('Order ID: #%s', 'skillscore-ebook'), $purchase->id) . "\n\n";
        $message .= __('DOWNLOAD YOUR EBOOK:', 'skillscore-ebook') . "\n";
        $message .= $download_url . "\n\n";
        $message .= __('Important Information:', 'skillscore-ebook') . "\n";
        $message .= sprintf(__('• You can download this ebook up to %d times', 'skillscore-ebook'), $download_limit) . "\n";
        $message .= __('• Save this email or bookmark the download link for future access', 'skillscore-ebook') . "\n";
        $message .= __('• The download link is unique to your purchase', 'skillscore-ebook') . "\n\n";
        $message .= __('If you have any issues accessing your download, please contact our support team.', 'skillscore-ebook') . "\n\n";
        $message .= __('Thank you for your business!', 'skillscore-ebook') . "\n";
        $message .= get_bloginfo('name');

        // Send email
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $sent = wp_mail($purchase->user_email, $subject, $message, $headers);

        if ($sent) {
            error_log('Download email sent to: ' . $purchase->user_email . ' for purchase #' . $purchase_id);
        } else {
            error_log('Failed to send download email to: ' . $purchase->user_email . ' for purchase #' . $purchase_id);
        }

        return $sent;
    }

    /**
     * Get purchase by token
     */
    public static function get_purchase_by_token($token) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE download_token = %s",
            $token
        ));
    }

    /**
     * Update purchase status
     */
    public static function update_purchase_status($purchase_id, $status) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        return $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $purchase_id),
            array('%s'),
            array('%d')
        );
    }
}