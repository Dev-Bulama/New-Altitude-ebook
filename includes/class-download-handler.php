<?php
/**
 * Download Handler - COMPLETE WITH FORMAT-BASED REDIRECTS
 * Routes to different pages based on purchase format
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
        
        // Handle payment success redirects - WITH FORMAT DETECTION
        add_action('init', array($this, 'handle_payment_success'));
        
        // Handle order success page for printed books
        add_action('template_redirect', array($this, 'handle_order_success_page'));
        
        // OLD METHOD NAME - For backwards compatibility
        add_action('init', array($this, 'handle_download_request'), 5);
    }

    /**
     * Handle download request - LEGACY METHOD
     */
    public function handle_download_request() {
        // This method exists to prevent errors
        // The actual handling is done in handle_download_page()
        return;
    }

    /**
     * Handle payment success from gateways - WITH FORMAT-BASED ROUTING
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

        // CHECK FOR CUSTOM THANK YOU PAGE REDIRECT
        $enable_custom_redirect = get_post_meta($purchase->ebook_id, '_ebook_enable_thank_you_redirect', true);
        $custom_thank_you_url = get_post_meta($purchase->ebook_id, '_ebook_thank_you_page_url', true);

        if ($enable_custom_redirect == '1' && !empty($custom_thank_you_url)) {
            // CUSTOM THANK YOU PAGE: Redirect to custom URL
            // Add reference and token as query parameters for the custom page to use
            $redirect_url = add_query_arg(array(
                'reference' => $reference,
                'download_token' => $purchase->download_token,
                'ebook_id' => $purchase->ebook_id
            ), $custom_thank_you_url);
            
            wp_redirect($redirect_url);
            exit;
        }

        // DEFAULT BEHAVIOR: ROUTE BASED ON FORMAT
        $format = $purchase->format ?: 'ebook';
        
        if ($format === 'printed') {
            // PRINTED BOOK: Redirect to order success page (NO download)
            wp_redirect(add_query_arg(array(
                'skillscore_order_success' => '1',
                'reference' => $reference
            ), home_url()));
            exit;
            
        } else {
            // DIGITAL/AUDIO: Redirect to download page
            $download_url = add_query_arg(array(
                'skillscore_download' => $purchase->download_token
            ), home_url());

            wp_redirect($download_url);
            exit;
        }
    }

    /**
     * Handle order success page display (for printed books)
     */
    public function handle_order_success_page() {
        if (!isset($_GET['skillscore_order_success'])) {
            return;
        }

        // Load order success template
        $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/order-success-printed.php';
        
        if (file_exists($template)) {
            include $template;
            exit;
        } else {
            wp_die(__('Order success page template not found.', 'skillscore-ebook'));
        }
    }

    /**
     * Handle download page display - FIXED TO ROUTE TO AUDIOBOOK TEMPLATE
     */
    public function handle_download_page() {
        if (!isset($_GET['skillscore_download'])) {
            return;
        }

        $token = sanitize_text_field($_GET['skillscore_download']);
        
        // Get purchase to check format
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE download_token = %s",
            $token
        ));
        
        // If printed book, redirect to order success instead
        if ($purchase && $purchase->format === 'printed') {
            wp_redirect(add_query_arg(array(
                'skillscore_order_success' => '1',
                'reference' => $purchase->transaction_reference
            ), home_url()));
            exit;
        }
        
        // SELECT TEMPLATE BASED ON FORMAT - FIXED!
        $template_file = 'download-page-template.php'; // Default for ebook
        
        if ($purchase && isset($purchase->format)) {
            // Check for BOTH 'audiobook' and 'audio'
            if ($purchase->format === 'audiobook' || $purchase->format === 'audio') {
                $template_file = 'audiobook-download-template.php';
            }
        }
        
        $template = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/' . $template_file;
        
        if (file_exists($template)) {
            include $template;
            exit;
        } else {
            // Fallback to default template if audiobook template not found
            $fallback = SKILLSCORE_EBOOK_PLUGIN_DIR . 'templates/download-page-template.php';
            if (file_exists($fallback)) {
                include $fallback;
                exit;
            }
            wp_die(__('Download page template not found: ' . $template_file, 'skillscore-ebook'));
        }
    }

    /**
     * Process file download - WITH FORMAT-SPECIFIC FILE SELECTION - FIXED!
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
        
        // Block download for printed books
        if ($purchase->format === 'printed') {
            wp_die(__('This is a printed book order. No digital download is available. Please check your email for shipping updates.', 'skillscore-ebook'));
        }

        // Check download limit
        $download_limit = get_option('skillscore_ebook_download_limit', 3);
        if ($purchase->download_count >= $download_limit) {
            wp_die(sprintf(__('Download limit (%d) reached. Please contact support if you need additional downloads.', 'skillscore-ebook'), $download_limit));
        }

        // Get ebook ID
        $ebook_id = $purchase->ebook_id;
        
        // SELECT FILE BASED ON FORMAT - FIXED TO CHECK BOTH!
        if ($purchase->format === 'audiobook' || $purchase->format === 'audio') {
            // Get audiobook file - FIXED meta key
            $file_url = get_post_meta($ebook_id, '_ebook_audio_preview_url', true);
            if (empty($file_url)) {
                wp_die(__('Audiobook file not found. Please contact support with your order ID.', 'skillscore-ebook'));
            }
        } else {
            // Get ebook file (digital)
            $file_url = get_post_meta($ebook_id, '_ebook_file_url', true);
            if (empty($file_url)) {
                wp_die(__('Ebook file not found. Please contact support with your order ID.', 'skillscore-ebook'));
            }
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

        // Log download with format
        error_log(sprintf(
            'Download: Purchase #%d, Format: %s, Ebook #%d, User: %s, Count: %d/%d',
            $purchase->id,
            $purchase->format,
            $ebook_id,
            $purchase->user_email,
            $purchase->download_count + 1,
            $download_limit
        ));

        // Get file path
        $file_path = $this->get_file_path_from_url($file_url);

        if ($file_path && file_exists($file_path)) {
            // Send file download
            $this->send_file_download($file_path, $purchase->format);
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
     * Send file download with proper headers - FORMAT-AWARE
     */
    private function send_file_download($file_path, $format = 'ebook') {
        // Clear any previous output
        if (ob_get_level()) {
            ob_end_clean();
        }

        // Get file info
        $filename = basename($file_path);
        $file_size = filesize($file_path);
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));

        // Set content type based on format and extension
        $content_types = array(
            'pdf' => 'application/pdf',
            'epub' => 'application/epub+zip',
            'mobi' => 'application/x-mobipocket-ebook',
            'azw' => 'application/vnd.amazon.ebook',
            'azw3' => 'application/vnd.amazon.ebook',
            'mp3' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'm4b' => 'audio/mp4',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg'
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
            'purchase_date' => current_time('mysql'),
            'format' => isset($data['format']) ? $data['format'] : 'ebook',
            'shipping_address' => isset($data['shipping_address']) ? $data['shipping_address'] : null,
            'shipping_fee' => isset($data['shipping_fee']) ? $data['shipping_fee'] : 0,
            'shipping_type' => isset($data['shipping_type']) ? $data['shipping_type'] : null,
            'shipping_status' => isset($data['shipping_status']) ? $data['shipping_status'] : null
        );

        $wpdb->insert($table_name, $purchase_data);

        $purchase_id = $wpdb->insert_id;

        // Send email with appropriate message based on format
        if ($purchase_id) {
            self::send_purchase_email($purchase_id);
        }

        return $purchase_id;
    }

    /**
     * Send purchase email - FORMAT-AWARE
     */
     /**
 * Send purchase email - FORMAT-AWARE WITH CUSTOM EMAIL SUPPORT
 */
 
 /**
 * Send purchase email - WITH CUSTOM EMAIL SUPPORT
 */
public static function send_purchase_email($purchase_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

    $purchase = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $purchase_id
    ));

    if (!$purchase) {
        error_log('Purchase not found: ' . $purchase_id);
        return false;
    }

    $ebook = get_post($purchase->ebook_id);
    if (!$ebook) {
        error_log('Ebook not found: ' . $purchase->ebook_id);
        return false;
    }

    // CHECK FOR CUSTOM EMAIL
    $enable_custom_email = get_post_meta($purchase->ebook_id, '_ebook_enable_custom_email', true);
    
    error_log('=== SkillScore Email Debug ===');
    error_log('Purchase ID: ' . $purchase_id);
    error_log('Ebook ID: ' . $purchase->ebook_id);
    error_log('Custom Email Enabled: ' . $enable_custom_email);
    
    if ($enable_custom_email == '1') {
        // CUSTOM EMAIL
        $custom_subject = get_post_meta($purchase->ebook_id, '_ebook_custom_email_subject', true);
        $custom_message = get_post_meta($purchase->ebook_id, '_ebook_custom_email_message', true);
        
        error_log('Using CUSTOM email');
        error_log('Subject: ' . $custom_subject);
        
        if (empty($custom_subject)) {
            $custom_subject = 'Thank you for your purchase!';
        }
        
        if (empty($custom_message)) {
            $custom_message = "Hi {customer_name},\n\nThank you for purchasing {ebook_title}!\n\nDownload: {download_link}\n\n" . get_bloginfo('name');
        }
        
        $download_url = add_query_arg(array('skillscore_download' => $purchase->download_token), home_url());
        $order_date = date('F j, Y', strtotime($purchase->purchase_date));
        
        $subject = str_replace(
            array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
            array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
            $custom_subject
        );
        
        $message = str_replace(
            array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
            array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
            $custom_message
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        
        $sent = wp_mail($purchase->user_email, $subject, wpautop($message), $headers);
        
        error_log('Custom email ' . ($sent ? 'SENT' : 'FAILED') . ' to: ' . $purchase->user_email);
        
        return $sent;
    }
    
    // DEFAULT EMAIL
    error_log('Using DEFAULT email');
    
    $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');
    $format = $purchase->format ?: 'ebook';

    $subject = sprintf(__('Your Order Confirmation: %s', 'skillscore-ebook'), $ebook->post_title);
    $message = sprintf(__('Hi %s,', 'skillscore-ebook'), $purchase->user_name) . "\n\n";
    $message .= __('Thank you for your purchase!', 'skillscore-ebook') . "\n\n";
    $message .= sprintf(__('Book: %s', 'skillscore-ebook'), $ebook->post_title) . "\n";
    $message .= sprintf(__('Format: %s', 'skillscore-ebook'), ucfirst($format)) . "\n";
    $message .= sprintf(__('Amount: %s', 'skillscore-ebook'), $currency_symbol . number_format($purchase->amount, 2)) . "\n\n";

    if ($format === 'printed') {
        $message .= __('Your order is being prepared for shipment.', 'skillscore-ebook') . "\n\n";
    } else {
        $download_url = add_query_arg(array('skillscore_download' => $purchase->download_token), home_url());
        $message .= __('DOWNLOAD:', 'skillscore-ebook') . "\n" . $download_url . "\n\n";
    }

    $message .= get_bloginfo('name');
    
    $sent = wp_mail($purchase->user_email, $subject, $message);
    
    error_log('Default email ' . ($sent ? 'SENT' : 'FAILED') . ' to: ' . $purchase->user_email);
    
    return $sent;
}
 
 
// public static function send_purchase_email($purchase_id) {
//     global $wpdb;
//     $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

//     $purchase = $wpdb->get_row($wpdb->prepare(
//         "SELECT * FROM $table_name WHERE id = %d",
//         $purchase_id
//     ));

//     if (!$purchase) {
//         return false;
//     }

//     $ebook = get_post($purchase->ebook_id);
//     if (!$ebook) {
//         return false;
//     }

//     // ==========================================
//     // CHECK FOR CUSTOM EMAIL
//     // ==========================================
//     $enable_custom_email = get_post_meta($purchase->ebook_id, '_ebook_enable_custom_email', true);
    
//     error_log('SkillScore DOWNLOAD HANDLER: Checking ebook #' . $purchase->ebook_id);
//     error_log('SkillScore DOWNLOAD HANDLER: Custom email = ' . $enable_custom_email);
    
//     if ($enable_custom_email == '1') {
//         // CUSTOM EMAIL
//         $custom_subject = get_post_meta($purchase->ebook_id, '_ebook_custom_email_subject', true);
//         $custom_message = get_post_meta($purchase->ebook_id, '_ebook_custom_email_message', true);
        
//         if (empty($custom_subject)) {
//             $custom_subject = 'Thank you for your purchase!';
//         }
        
//         if (empty($custom_message)) {
//             $custom_message = "Hi {customer_name},\n\nThank you for purchasing {ebook_title}!\n\nDownload: {download_link}\n\n" . get_bloginfo('name');
//         }
        
//         $download_url = add_query_arg(array('skillscore_download' => $purchase->download_token), home_url());
//         $order_date = date('F j, Y', strtotime($purchase->purchase_date));
        
//         $subject = str_replace(
//             array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
//             array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
//             $custom_subject
//         );
        
//         $message = str_replace(
//             array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
//             array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
//             $custom_message
//         );
        
//         $headers = array(
//             'Content-Type: text/html; charset=UTF-8',
//             'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
//         );
        
//         $sent = wp_mail($purchase->user_email, $subject, wpautop($message), $headers);
        
//         error_log('SkillScore DOWNLOAD HANDLER: CUSTOM email ' . ($sent ? 'SENT' : 'FAILED'));
        
//         return $sent;
//     }
    
//     // ==========================================
//     // DEFAULT EMAIL (your original code)
//     // ==========================================
//     error_log('SkillScore DOWNLOAD HANDLER: Using DEFAULT email');
    
//     $currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');
//     $format = $purchase->format ?: 'ebook';

//     $subject = sprintf(__('Your Order Confirmation: %s', 'skillscore-ebook'), $ebook->post_title);
//     $message = sprintf(__('Hi %s,', 'skillscore-ebook'), $purchase->user_name) . "\n\n";
//     $message .= __('Thank you for your purchase!', 'skillscore-ebook') . "\n\n";
//     $message .= __('Purchase Details:', 'skillscore-ebook') . "\n";
//     $message .= sprintf(__('Book: %s', 'skillscore-ebook'), $ebook->post_title) . "\n";
//     $message .= sprintf(__('Format: %s', 'skillscore-ebook'), ucfirst($format)) . "\n";
//     $message .= sprintf(__('Amount Paid: %s', 'skillscore-ebook'), $currency_symbol . number_format($purchase->amount, 2)) . "\n";
//     $message .= sprintf(__('Order ID: #%s', 'skillscore-ebook'), $purchase->id) . "\n\n";

//     if ($format === 'printed') {
//         $message .= __('SHIPPING INFORMATION:', 'skillscore-ebook') . "\n";
//         $message .= __('Your order is being prepared for shipment.', 'skillscore-ebook') . "\n\n";
        
//         if (!empty($purchase->shipping_address)) {
//             $shipping = json_decode($purchase->shipping_address, true);
//             if ($shipping) {
//                 $message .= __('Shipping To:', 'skillscore-ebook') . "\n";
//                 $message .= $shipping['address'] . "\n";
//                 $message .= $shipping['city'] . ', ' . $shipping['state'] . ' ' . $shipping['postal_code'] . "\n";
//                 $message .= $shipping['country'] . "\n\n";
//             }
//         }
//     } else {
//         $download_url = add_query_arg(array('skillscore_download' => $purchase->download_token), home_url());
//         $message .= __('DOWNLOAD YOUR EBOOK:', 'skillscore-ebook') . "\n";
//         $message .= $download_url . "\n\n";
//     }

//     $message .= __('Thank you!', 'skillscore-ebook') . "\n" . get_bloginfo('name');
//     $headers = array('Content-Type: text/plain; charset=UTF-8');
//     $sent = wp_mail($purchase->user_email, $subject, $message, $headers);
    
//     error_log('SkillScore DOWNLOAD HANDLER: DEFAULT email ' . ($sent ? 'SENT' : 'FAILED'));
    
//     return $sent;
// }

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