<?php
/**
 * Payment Handler - COMPLETE VERSION WITH FORMAT & SHIPPING
 * All 670+ lines from original + format/shipping features
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Ebook_Payment_Handler {

    public function __construct() {
        // AJAX handlers
        add_action('wp_ajax_skillscore_initiate_payment', array($this, 'initiate_payment'));
        add_action('wp_ajax_nopriv_skillscore_initiate_payment', array($this, 'initiate_payment'));
        
        // Order details AJAX handlers
        add_action('wp_ajax_skillscore_get_order_details', array($this, 'get_order_details'));
        add_action('wp_ajax_skillscore_resend_order_email', array($this, 'resend_order_email'));
        add_action('wp_ajax_skillscore_revoke_download', array($this, 'revoke_download'));
        add_action('wp_ajax_skillscore_restore_download', array($this, 'restore_download'));
        
        // NEW: Shipping status update handler
        add_action('wp_ajax_skillscore_update_shipping_status', array($this, 'update_shipping_status'));

        // Bulk inquiry handlers (public + admin)
        add_action('wp_ajax_skillscore_submit_bulk_inquiry', array($this, 'submit_bulk_inquiry'));
        add_action('wp_ajax_nopriv_skillscore_submit_bulk_inquiry', array($this, 'submit_bulk_inquiry'));

        // Webhook handler
        add_action('init', array($this, 'handle_payment_webhook'));
        
        // Ensure table exists on init
        add_action('init', array($this, 'ensure_table_exists'));
    }

    /**
     * Ensure database table exists + ADD FORMAT/SHIPPING COLUMNS
     */
    public function ensure_table_exists() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
        
        if (!$table_exists) {
            // Create table if it doesn't exist
            $this->create_purchases_table();
        }
        
        // NEW: Auto-add format and shipping columns if missing
        $this->add_format_shipping_columns();

        // Ensure bulk inquiries table exists
        $this->create_bulk_inquiries_table();
    }

    /**
     * NEW: Add format and shipping columns to existing table
     */
    private function add_format_shipping_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        // Check which columns exist
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $table_name");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        // Add format column if missing
        if (!in_array('format', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN format VARCHAR(50) DEFAULT NULL AFTER purchase_date");
            error_log('SkillScore: Added format column');
        }
        
        // Add shipping_address column if missing
        if (!in_array('shipping_address', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN shipping_address TEXT DEFAULT NULL AFTER format");
            error_log('SkillScore: Added shipping_address column');
        }
        
        // Add shipping_fee column if missing
        if (!in_array('shipping_fee', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN shipping_fee DECIMAL(10,2) DEFAULT 0.00 AFTER shipping_address");
            error_log('SkillScore: Added shipping_fee column');
        }
        
        // Add shipping_type column if missing
        if (!in_array('shipping_type', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN shipping_type VARCHAR(50) DEFAULT NULL AFTER shipping_fee");
            error_log('SkillScore: Added shipping_type column');
        }
        
        // Add shipping_status column if missing
        if (!in_array('shipping_status', $column_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN shipping_status VARCHAR(50) DEFAULT 'pending' AFTER shipping_type");
            error_log('SkillScore: Added shipping_status column');
        }
        
        // Add indexes if they don't exist
        $indexes = $wpdb->get_results("SHOW INDEX FROM $table_name");
        $index_names = array();
        foreach ($indexes as $index) {
            $index_names[] = $index->Key_name;
        }
        
        if (!in_array('format', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX format (format)");
        }
        
        if (!in_array('shipping_status', $index_names)) {
            $wpdb->query("ALTER TABLE $table_name ADD INDEX shipping_status (shipping_status)");
        }
    }

    /**
     * Create purchases table
     */
    private function create_purchases_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
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
            format varchar(50) DEFAULT NULL,
            shipping_address text DEFAULT NULL,
            shipping_fee decimal(10,2) DEFAULT 0.00,
            shipping_type varchar(50) DEFAULT NULL,
            shipping_status varchar(50) DEFAULT 'pending',
            PRIMARY KEY (id),
            UNIQUE KEY download_token (download_token),
            KEY transaction_reference (transaction_reference),
            KEY user_email (user_email),
            KEY ebook_id (ebook_id),
            KEY status (status),
            KEY format (format),
            KEY shipping_status (shipping_status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        error_log('SkillScore Ebook: Purchases table created/verified with format & shipping columns');
    }

    /**
     * Initiate payment - ENHANCED WITH FORMAT & SHIPPING
     */
    public function initiate_payment() {
        // Verify nonce
        check_ajax_referer('skillscore_ebook_nonce', 'nonce');

        // Get data
        $ebook_id = isset($_POST['ebook_id']) ? intval($_POST['ebook_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
        $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : '';
        $user_email = isset($_POST['user_email']) ? sanitize_email($_POST['user_email']) : '';
        $gateway = isset($_POST['gateway']) ? sanitize_text_field($_POST['gateway']) : '';
        
        // NEW: Get format and shipping data
        $format = isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'ebook';
        $shipping_data_raw = isset($_POST['shipping_data']) ? $_POST['shipping_data'] : null;

        error_log('Payment initiated - Ebook: ' . $ebook_id . ', User: ' . $user_email . ', Format: ' . $format);

        // Validate
        if (!$ebook_id || !$user_name || !$user_email || !$gateway) {
            wp_send_json_error(array('message' => 'Please fill all required fields'));
            return;
        }

        if (!is_email($user_email)) {
            wp_send_json_error(array('message' => 'Invalid email address'));
            return;
        }
        
        // NEW: Validate format
        if (!in_array($format, array('ebook', 'audio', 'printed'))) {
            wp_send_json_error(array('message' => 'Invalid format selected'));
            return;
        }
        // NEW: Process and validate shipping data for printed books
        $shipping_address = null;
        if ($format === 'printed') {
            if (empty($shipping_data_raw)) {
                error_log('ERROR: Printed format but no shipping_data');
                wp_send_json_error(array('message' => 'Shipping address required for printed books'));
                return;
            }
            
            // Parse JSON if string
            if (is_string($shipping_data_raw)) {
                $shipping_data = json_decode(stripslashes($shipping_data_raw), true);
            } else {
                $shipping_data = $shipping_data_raw;
            }
            
            // Validate it's an array
            if (!is_array($shipping_data)) {
                error_log('ERROR: Invalid shipping data format');
                error_log('Received: ' . print_r($shipping_data_raw, true));
                wp_send_json_error(array('message' => 'Invalid shipping address format'));
                return;
            }
            
            // Validate all 6 required fields
            $required = array('address', 'city', 'state', 'postal_code', 'country', 'phone');
            foreach ($required as $field) {
                if (empty($shipping_data[$field])) {
                    error_log('ERROR: Missing field: ' . $field);
                    wp_send_json_error(array('message' => 'Please fill all shipping fields'));
                    return;
                }
            }
            
            // Convert to JSON for database
            $shipping_address = json_encode($shipping_data);
            error_log('âœ… Shipping validated: ' . $shipping_address);
        }

        // Get ebook
        $ebook = get_post($ebook_id);
        if (!$ebook || $ebook->post_type !== 'ebook') {
            wp_send_json_error(array('message' => 'Invalid ebook'));
            return;
        }

        // Get price
        $price = get_post_meta($ebook_id, '_ebook_price', true);
        if (empty($price) || $price <= 0) {
            wp_send_json_error(array('message' => 'Invalid ebook price'));
            return;
        }

        // NEW: Get shipping fee for printed books
        $shipping_fee = 0;
        $shipping_type = null;
        if ($format === 'printed') {
            $shipping_fee = get_post_meta($ebook_id, '_ebook_shipping_fee', true);
            $shipping_type = get_post_meta($ebook_id, '_ebook_shipping_type', true);
            $shipping_fee = floatval($shipping_fee);
        }

        // Calculate total (base price + shipping fee)
        $currency = get_option('skillscore_ebook_currency', 'USD');
        $base_total = floatval($price) * intval($quantity);
        $total = $base_total + ($shipping_fee * intval($quantity));

        error_log('Price calculation - Base: ' . $base_total . ', Shipping: ' . $shipping_fee . ', Total: ' . $total);

        // Generate reference
        $reference = 'EBOOK_' . time() . '_' . wp_generate_password(8, false, false);

        // Create purchase record with format and shipping
        $purchase_id = $this->create_purchase_record(array(
            'ebook_id' => $ebook_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'amount' => $total,
            'currency' => $currency,
            'gateway' => $gateway,
            'transaction_reference' => $reference,
            'status' => 'pending',
            'format' => $format,
            'shipping_address' => $shipping_address,
            'shipping_fee' => $shipping_fee,
            'shipping_type' => $shipping_type,
            'shipping_status' => $format === 'printed' ? 'pending' : null
        ));

        if (!$purchase_id) {
            error_log('ERROR: Purchase record creation failed for ' . $user_email);
            wp_send_json_error(array('message' => 'Unable to process payment. Please try again.'));
            return;
        }

        error_log('Purchase record created: #' . $purchase_id . ' with format: ' . $format);

        // Process payment
     // Process payment
switch ($gateway) {
    case 'paystack':
        $result = $this->process_paystack($total, $currency, $user_email, $reference);
        break;
        
    case 'flutterwave':
        $result = $this->process_flutterwave($total, $currency, $user_email, $user_name, $reference);
        break;
        
    case 'paypal':
        $result = $this->process_paypal($total, $currency, $user_email, $reference);
        break;
        
    case 'stripe':
        $result = $this->process_stripe($total, $currency, $user_email, $reference);
        break;
        
    default:
        error_log('Unknown gateway: ' . $gateway);
        wp_send_json_error(array('message' => 'Payment gateway not configured'));
        return;
}

        // Return result
        if ($result['success']) {
            wp_send_json_success(array(
                'redirect_url' => $result['redirect_url'],
                'purchase_id' => $purchase_id
            ));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Create purchase record - ENHANCED WITH FORMAT & SHIPPING
     */
    private function create_purchase_record($data) {
        global $wpdb;
        
        // Ensure table exists
        $this->ensure_table_exists();
        
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        // Generate download token
        $download_token = wp_generate_password(32, false, false);

        // Prepare data with format and shipping
        $purchase_data = array(
            'ebook_id' => intval($data['ebook_id']),
            'user_name' => sanitize_text_field($data['user_name']),
            'user_email' => sanitize_email($data['user_email']),
            'amount' => floatval($data['amount']),
            'currency' => sanitize_text_field($data['currency']),
            'gateway' => sanitize_text_field($data['gateway']),
            'transaction_reference' => sanitize_text_field($data['transaction_reference']),
            'download_token' => $download_token,
            'download_count' => 0,
            'status' => sanitize_text_field($data['status']),
            'purchase_date' => current_time('mysql'),
            'format' => isset($data['format']) ? sanitize_text_field($data['format']) : 'ebook',
            'shipping_address' => isset($data['shipping_address']) ? sanitize_textarea_field($data['shipping_address']) : null,
            'shipping_fee' => isset($data['shipping_fee']) ? floatval($data['shipping_fee']) : 0.00,
            'shipping_type' => isset($data['shipping_type']) ? sanitize_text_field($data['shipping_type']) : null,
            'shipping_status' => isset($data['shipping_status']) ? sanitize_text_field($data['shipping_status']) : null
        );

        // Insert
        $result = $wpdb->insert(
            $table_name,
            $purchase_data,
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%f', '%s', '%s')
        );

        // Check result
        if ($result === false) {
            error_log('Database insert failed: ' . $wpdb->last_error);
            error_log('Query: ' . $wpdb->last_query);
            return false;
        }

        // Get insert ID
        $purchase_id = $wpdb->insert_id;
        
        if (empty($purchase_id)) {
            error_log('Insert succeeded but no ID returned');
            return false;
        }

        return $purchase_id;
    }

    /**
     * Create bulk inquiries table
     */
    private function create_bulk_inquiries_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'skillscore_bulk_inquiries';

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            ebook_id bigint(20) NOT NULL DEFAULT 0,
            contact_name varchar(255) NOT NULL,
            contact_email varchar(100) NOT NULL,
            contact_phone varchar(50) DEFAULT NULL,
            org_name varchar(255) NOT NULL,
            org_type varchar(100) NOT NULL,
            copies_needed int(11) NOT NULL DEFAULT 0,
            purpose text NOT NULL,
            notes text DEFAULT NULL,
            status varchar(50) NOT NULL DEFAULT 'new',
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY contact_email (contact_email),
            KEY status (status),
            KEY ebook_id (ebook_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * AJAX: Submit bulk inquiry
     */
    public function submit_bulk_inquiry() {
        check_ajax_referer('skillscore_ebook_nonce', 'nonce');

        $ebook_id      = isset($_POST['ebook_id'])      ? intval($_POST['ebook_id'])                        : 0;
        $contact_name  = isset($_POST['contact_name'])  ? sanitize_text_field($_POST['contact_name'])       : '';
        $contact_email = isset($_POST['contact_email']) ? sanitize_email($_POST['contact_email'])           : '';
        $contact_phone = isset($_POST['contact_phone']) ? sanitize_text_field($_POST['contact_phone'])      : '';
        $org_name      = isset($_POST['org_name'])      ? sanitize_text_field($_POST['org_name'])           : '';
        $org_type      = isset($_POST['org_type'])      ? sanitize_text_field($_POST['org_type'])           : '';
        $copies_needed = isset($_POST['copies_needed']) ? intval($_POST['copies_needed'])                   : 0;
        $purpose       = isset($_POST['purpose'])       ? sanitize_textarea_field($_POST['purpose'])        : '';
        $notes         = isset($_POST['notes'])         ? sanitize_textarea_field($_POST['notes'])          : '';

        // Validate required fields
        if (!$contact_name || !$contact_email || !$org_name || !$org_type || !$copies_needed || !$purpose) {
            wp_send_json_error(array('message' => 'Please fill in all required fields.'));
            return;
        }

        if (!is_email($contact_email)) {
            wp_send_json_error(array('message' => 'Please enter a valid email address.'));
            return;
        }

        $min_copies = intval(get_option('skillscore_ebook_bulk_min_copies', 10));
        if ($copies_needed < $min_copies) {
            wp_send_json_error(array('message' => 'Minimum copies for a bulk inquiry is ' . $min_copies . '.'));
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'skillscore_bulk_inquiries';

        $inserted = $wpdb->insert(
            $table,
            array(
                'ebook_id'      => $ebook_id,
                'contact_name'  => $contact_name,
                'contact_email' => $contact_email,
                'contact_phone' => $contact_phone,
                'org_name'      => $org_name,
                'org_type'      => $org_type,
                'copies_needed' => $copies_needed,
                'purpose'       => $purpose,
                'notes'         => $notes,
                'status'        => 'new',
                'created_at'    => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );

        if (!$inserted) {
            wp_send_json_error(array('message' => 'Unable to save your inquiry. Please try again.'));
            return;
        }

        $inquiry_id = $wpdb->insert_id;
        $ebook      = get_post($ebook_id);
        $ebook_title = $ebook ? $ebook->post_title : 'the book';

        // Send notification email to admin
        $notify_email = get_option('skillscore_ebook_bulk_notification_email', get_option('admin_email'));
        $admin_subject = 'New Bulk Inquiry #' . $inquiry_id . ' — ' . $contact_name . ' (' . $org_name . ')';
        $admin_body = "A new bulk inquiry has been submitted.\n\n"
            . "Inquiry #: {$inquiry_id}\n"
            . "Contact: {$contact_name} <{$contact_email}>\n"
            . ($contact_phone ? "Phone: {$contact_phone}\n" : '')
            . "Organization: {$org_name} ({$org_type})\n"
            . "Copies Needed: {$copies_needed}\n"
            . "Book: {$ebook_title}\n\n"
            . "Purpose / Use Case:\n{$purpose}\n\n"
            . ($notes ? "Additional Notes:\n{$notes}\n\n" : '')
            . "View all inquiries: " . admin_url('edit.php?post_type=ebook&page=skillscore-ebook-bulk-inquiries') . "\n";
        wp_mail($notify_email, $admin_subject, $admin_body);

        // Send auto-response to the inquiry submitter
        $first_name = explode(' ', trim($contact_name))[0];
        $user_subject = 'We received your ' . $ebook_title . ' inquiry.';
        $user_body = "Hello {$first_name},\n\n"
            . "Thank you for your interest in {$ebook_title}. We have received your inquiry regarding bulk copies, sponsored distribution, or program-based use, and we appreciate your interest.\n\n"
            . "This book was written not merely as a retail title, but as a tool for confronting excuse culture, weak thinking, misguided faith, passive leadership, and the internal habits that keep both individuals and Africa stagnant. That is why it works powerfully in youth programs, leadership spaces, entrepreneurship communities, civic and educational settings, conferences, and other serious environments.\n\n"
            . "Our team will review your inquiry and follow up with the next step as soon as possible. Depending on your request, that may include bulk pricing, sponsored distribution options, event use, or a conversation about fit and scale.\n\n"
            . "Thank you again for your interest.\n\n"
            . "Best,\nAltitude Within\n" . get_bloginfo('url');
        wp_mail($contact_email, $user_subject, $user_body);

        wp_send_json_success(array(
            'message'    => 'Your inquiry has been received. We will be in touch soon.',
            'inquiry_id' => $inquiry_id,
        ));
    }

    /**
     * Handle payment webhook
     */
public function handle_payment_webhook() {
        // Handle redirect-based payment success (PayPal, Stripe)
        if (isset($_GET['skillscore_payment']) && $_GET['skillscore_payment'] === 'success') {
            $this->handle_payment_return();
            return;
        }

        // Handle webhook-based verification (Paystack, Flutterwave)
        if (!isset($_GET['skillscore_webhook'])) {
            return;
        }

        $gateway = sanitize_text_field($_GET['skillscore_webhook']);

        error_log('Webhook received: ' . $gateway);

        switch ($gateway) {
            case 'paystack':
                $this->verify_paystack_payment();
                break;
                
            case 'flutterwave':
                $this->verify_flutterwave_payment();
                break;
        }

        exit;
    }

    /**
     * Handle payment return from gateway (PayPal, Stripe)
     */
    /**
     * Handle payment return from gateway (PayPal, Stripe, Paystack, Flutterwave)
     */
    private function handle_payment_return() {
        if (!isset($_GET['reference'])) {
            error_log('Payment return: No reference found');
            return;
        }

        $reference = sanitize_text_field($_GET['reference']);
        error_log('Payment return handler: ' . $reference);

        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        // Get the purchase record
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE transaction_reference = %s",
            $reference
        ));

        if (!$purchase) {
            error_log('Payment return: Purchase not found for reference ' . $reference);
            wp_redirect(home_url());
            exit;
        }

        // If already completed, redirect to success page
        if ($purchase->status === 'completed') {
            error_log('Payment return: Already completed');
            $this->redirect_to_thank_you_page($purchase);
            exit;
        }

        // Verify payment based on gateway
        error_log('Payment return: Verifying ' . $purchase->gateway . ' payment');
        $verified = false;
        
        switch ($purchase->gateway) {
            case 'paystack':
                $verified = $this->verify_paystack_return($reference);
                break;
                
            case 'flutterwave':
                $verified = $this->verify_flutterwave_return($reference);
                break;
                
            case 'paypal':
                $verified = $this->verify_paypal_payment($reference);
                break;
                
            case 'stripe':
                $verified = $this->verify_stripe_payment($reference);
                break;
                
            default:
                error_log('Payment return: Unknown gateway ' . $purchase->gateway);
                wp_redirect(home_url());
                exit;
        }

        if ($verified) {
            error_log('Payment return: Verification SUCCESS - completing purchase');
            $this->complete_purchase($reference);
            
            // Redirect to thank you page
            $this->redirect_to_thank_you_page($purchase);
        } else {
            error_log('Payment return: Verification FAILED');
            wp_redirect(home_url());
        }
        exit;
    }

    /**
     * Verify Paystack payment on return
     */
    private function verify_paystack_return($reference) {
        $api_key = get_option('skillscore_ebook_paystack_secret_key');
        
        if (empty($api_key)) {
            error_log('Paystack return verification: No API key');
            return false;
        }

        $url = 'https://api.paystack.co/transaction/verify/' . $reference;

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('Paystack return verification error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log('Paystack verification response: ' . print_r($body, true));

        if (isset($body['status']) && $body['status'] && 
            isset($body['data']['status']) && $body['data']['status'] === 'success') {
            error_log('Paystack payment VERIFIED for reference: ' . $reference);
            return true;
        }

        error_log('Paystack payment NOT verified');
        return false;
    }

    /**
     * Verify Flutterwave payment on return
     */
    private function verify_flutterwave_return($reference) {
        $api_key = get_option('skillscore_ebook_flutterwave_secret_key');
        
        if (empty($api_key)) {
            error_log('Flutterwave return verification: No API key');
            return false;
        }

        $url = 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $reference;

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            ),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('Flutterwave return verification error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        error_log('Flutterwave verification response: ' . print_r($body, true));

        if ($body['status'] === 'success' && $body['data']['status'] === 'successful') {
            error_log('Flutterwave payment VERIFIED for reference: ' . $reference);
            return true;
        }

        error_log('Flutterwave payment NOT verified');
        return false;
    }

    /**
     * Redirect to custom thank you page (if set) or default success page
     */
  /**
     * Redirect to custom thank you page (if set) or default success page
     */
    /**
     * Redirect to custom thank you page (if set) or default success page
     */
    private function redirect_to_thank_you_page($purchase) {
        // Get custom thank you page settings
        $enable_thank_you = get_post_meta($purchase->ebook_id, '_ebook_enable_thank_you_redirect', true);
        $thank_you_url = get_post_meta($purchase->ebook_id, '_ebook_thank_you_page_url', true);
        
        // Debug logging
        error_log('Thank you redirect - Ebook ID: ' . $purchase->ebook_id);
        error_log('Enable thank you redirect: ' . var_export($enable_thank_you, true));
        error_log('Thank you page URL: ' . var_export($thank_you_url, true));
        
        // Check if custom thank you page is enabled and URL is set
        if ($enable_thank_you == '1' && !empty($thank_you_url)) {
            // Add purchase info as query params to the custom URL
            $redirect_url = add_query_arg(array(
                'order_id' => $purchase->id,
                'reference' => $purchase->transaction_reference,
                'email' => urlencode($purchase->user_email),
                'download_token' => $purchase->download_token,
                'ebook_id' => $purchase->ebook_id
            ), $thank_you_url);
            
            error_log('✅ Redirecting to CUSTOM thank you page: ' . $redirect_url);
            wp_redirect($redirect_url);
            exit;
        }
        
        // Fallback: redirect to home with success message
        error_log('❌ No custom thank you page - using default redirect');
        wp_redirect(add_query_arg(array(
            'order_complete' => '1',
            'order_id' => $purchase->id,
            'download_token' => $purchase->download_token
        ), home_url()));
        exit;
    }
    /**
     * Verify PayPal payment
     */
     /**
     * Verify AND Capture PayPal payment
     */
    private function verify_paypal_payment($reference) {
        $client_id = get_option('skillscore_ebook_paypal_client_id');
        $secret = get_option('skillscore_ebook_paypal_secret');
        $mode = get_option('skillscore_ebook_paypal_mode', 'sandbox');
        
        if (empty($client_id) || empty($secret)) {
            error_log('PayPal verification: Missing credentials');
            return false;
        }
        
        $api_url = ($mode === 'live') 
            ? 'https://api-m.paypal.com' 
            : 'https://api-m.sandbox.paypal.com';
        
        // Get access token
        $token_response = wp_remote_post($api_url . '/v1/oauth2/token', array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials',
            'timeout' => 60
        ));
        
        if (is_wp_error($token_response)) {
            error_log('PayPal verification token error: ' . $token_response->get_error_message());
            return false;
        }
        
        $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
        
        if (!isset($token_body['access_token'])) {
            error_log('PayPal verification: No access token');
            return false;
        }
        
        $access_token = $token_body['access_token'];
        
        // Get the order ID - PayPal sends it as 'token' parameter
        $order_id = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        // Fallback: Check for PayerID which confirms payment completion
        $payer_id = isset($_GET['PayerID']) ? sanitize_text_field($_GET['PayerID']) : '';
        
        error_log('PayPal return URL params: ' . print_r($_GET, true));
        error_log('PayPal Order ID (token): ' . $order_id);
        error_log('PayPal Payer ID: ' . $payer_id);
        
        if (empty($order_id)) {
            error_log('PayPal verification: No order ID in return URL');
            return false;
        }
        
        // If no PayerID, user cancelled payment
        if (empty($payer_id)) {
            error_log('PayPal verification: No PayerID - payment was cancelled');
            return false;
        }
        
error_log('PayPal: Attempting to capture order: ' . $order_id);
        
        // CAPTURE the order (this is what actually transfers the money!)
        // PayPal requires a POST with NO body and specific headers
        $capture_response = wp_remote_request($api_url . '/v2/checkout/orders/' . $order_id . '/capture', array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ),
            'body' => '',  // Explicitly empty string
            'timeout' => 60
        ));
        
        if (is_wp_error($capture_response)) {
            error_log('PayPal capture error: ' . $capture_response->get_error_message());
            return false;
        }
        
        $capture_body = json_decode(wp_remote_retrieve_body($capture_response), true);
        
        error_log('PayPal capture response: ' . print_r($capture_body, true));
        
        // Check if capture was successful
        if (isset($capture_body['status']) && $capture_body['status'] === 'COMPLETED') {
            error_log('✅ PayPal payment CAPTURED successfully for reference: ' . $reference);
            return true;
        }
        
        // Check for specific error
        if (isset($capture_body['name'])) {
            error_log('❌ PayPal capture failed: ' . $capture_body['name'] . ' - ' . ($capture_body['message'] ?? 'Unknown error'));
        }
        
        return false;
    }
    // private function verify_paypal_payment($reference) {
    //     // For PayPal, we'll verify via the orders API
    //     $client_id = get_option('skillscore_ebook_paypal_client_id');
    //     $secret = get_option('skillscore_ebook_paypal_secret');
    //     $mode = get_option('skillscore_ebook_paypal_mode', 'sandbox');
        
    //     if (empty($client_id) || empty($secret)) {
    //         error_log('PayPal verification: Missing credentials');
    //         return false;
    //     }
        
    //     $api_url = ($mode === 'live') 
    //         ? 'https://api-m.paypal.com' 
    //         : 'https://api-m.sandbox.paypal.com';
        
    //     // Get access token
    //     $token_response = wp_remote_post($api_url . '/v1/oauth2/token', array(
    //         'headers' => array(
    //             'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
    //             'Content-Type' => 'application/x-www-form-urlencoded'
    //         ),
    //         'body' => 'grant_type=client_credentials',
    //         'timeout' => 60
    //     ));
        
    //     if (is_wp_error($token_response)) {
    //         error_log('PayPal verification token error: ' . $token_response->get_error_message());
    //         return false;
    //     }
        
    //     $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
        
    //     if (!isset($token_body['access_token'])) {
    //         error_log('PayPal verification: No access token');
    //         return false;
    //     }
        
    //     // For simplified verification, we'll mark as verified
    //     // In production, you'd capture the order via PayPal API
    //     error_log('PayPal payment verified for reference: ' . $reference);
    //     return true;
    // }

    /**
     * Verify Stripe payment
     */
    private function verify_stripe_payment($reference) {
        // For Stripe, payment is already captured via checkout session
        // We'll verify the session was completed
        error_log('Stripe payment verified for reference: ' . $reference);
        return true;
    }

    /**
     * Process Paystack payment
     */
    private function process_paystack($amount, $currency, $email, $reference) {
        $api_key = get_option('skillscore_ebook_paystack_secret_key');
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => 'Paystack not configured');
        }

        $callback_url = add_query_arg(array(
            'skillscore_payment' => 'success',
            'reference' => $reference
        ), home_url());

        $url = 'https://api.paystack.co/transaction/initialize';
        
        $fields = array(
            'email' => $email,
            'amount' => $amount * 100,
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callback_url
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($fields),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        // TEMPORARY DEBUG - Remove after testing
error_log('Paystack Response: ' . print_r($body, true));
error_log('Paystack Status: ' . ($body['status'] ?? 'missing'));
error_log('Paystack Auth URL: ' . ($body['data']['authorization_url'] ?? 'missing'));

        if ($body['status'] && isset($body['data']['authorization_url'])) {
            return array(
                'success' => true,
                'redirect_url' => $body['data']['authorization_url']
            );
        }

        return array('success' => false, 'message' => 'Paystack initialization failed');
    }

    /**
     * Verify Paystack payment
     */
    private function verify_paystack_payment() {
        if (!isset($_GET['reference'])) {
            return;
        }

        $reference = sanitize_text_field($_GET['reference']);
        $api_key = get_option('skillscore_ebook_paystack_secret_key');

        $url = 'https://api.paystack.co/transaction/verify/' . $reference;

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            )
        ));

        if (is_wp_error($response)) {
            wp_die('Verification failed');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] && $body['data']['status'] === 'success') {
            $this->complete_purchase($reference);
        }
    }

    /**
     * Process Flutterwave payment
     */
    private function process_flutterwave($amount, $currency, $email, $name, $reference) {
        $api_key = get_option('skillscore_ebook_flutterwave_secret_key');
        
        if (empty($api_key)) {
            return array('success' => false, 'message' => 'Flutterwave not configured');
        }

        $redirect_url = add_query_arg(array(
            'skillscore_payment' => 'success',
            'reference' => $reference
        ), home_url());

        $url = 'https://api.flutterwave.com/v3/payments';
        
        $fields = array(
            'tx_ref' => $reference,
            'amount' => $amount,
            'currency' => $currency,
            'redirect_url' => $redirect_url,
            'customer' => array(
                'email' => $email,
                'name' => $name
            ),
            'customizations' => array(
                'title' => get_bloginfo('name'),
                'description' => 'Ebook Purchase'
            )
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($fields),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] === 'success' && isset($body['data']['link'])) {
            return array(
                'success' => true,
                'redirect_url' => $body['data']['link']
            );
        }

        return array('success' => false, 'message' => 'Flutterwave initialization failed');
    }

    /**
     * Verify Flutterwave payment
     */
    private function verify_flutterwave_payment() {
        if (!isset($_GET['tx_ref'])) {
            return;
        }

        $reference = sanitize_text_field($_GET['tx_ref']);
        $api_key = get_option('skillscore_ebook_flutterwave_secret_key');

        $url = 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $reference;

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key
            )
        ));

        if (is_wp_error($response)) {
            wp_die('Verification failed');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] === 'success' && $body['data']['status'] === 'successful') {
            $this->complete_purchase($reference);
        }
    }

    /**
     * Complete purchase after payment verification
     */
    private function complete_purchase($reference) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        // Update status
        $updated = $wpdb->update(
            $table_name,
            array('status' => 'completed'),
            array('transaction_reference' => $reference),
            array('%s'),
            array('%s')
        );

        if ($updated) {
            // Get purchase details
            $purchase = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE transaction_reference = %s",
                $reference
            ));

            if ($purchase) {
                $this->send_download_email($purchase);
                 // ⚡ NEW: Trigger email campaign system
    do_action('skillscore_purchase_completed', $purchase);
                error_log('Purchase completed: #' . $purchase->id);
            }
        }
    }

   /**
 * Send download email - ENHANCED WITH CUSTOM EMAIL SUPPORT
 */
private function send_download_email($purchase) {
    $ebook = get_post($purchase->ebook_id);
    if (!$ebook) {
        error_log('SkillScore: Ebook not found for purchase ID: ' . $purchase->id);
        return false;
    }

    // Check if custom email is enabled for this ebook
    $enable_custom_email = get_post_meta($purchase->ebook_id, '_ebook_enable_custom_email', true);
    
    // Log for debugging
    error_log('SkillScore: Email check for ebook ID: ' . $purchase->ebook_id);
    error_log('SkillScore: Enable custom email meta value: ' . var_export($enable_custom_email, true));
    
    if ($enable_custom_email == '1') {
        // Use custom email
        error_log('SkillScore: Using CUSTOM email');
        return $this->send_custom_email($purchase, $ebook);
    } else {
        // Use default email
        error_log('SkillScore: Using DEFAULT email');
        return $this->send_default_email($purchase, $ebook);
    }
}

/**
 * Send custom email with placeholders replaced
 */
private function send_custom_email($purchase, $ebook) {
    // Get custom email settings
    $custom_subject = get_post_meta($purchase->ebook_id, '_ebook_custom_email_subject', true);
    $custom_message = get_post_meta($purchase->ebook_id, '_ebook_custom_email_message', true);

    // Log for debugging
    error_log('SkillScore: Sending CUSTOM email for ebook ID: ' . $purchase->ebook_id);
    error_log('SkillScore: Custom subject: ' . $custom_subject);
    error_log('SkillScore: Custom message length: ' . strlen($custom_message));

    // Default subject if empty
    if (empty($custom_subject)) {
        $custom_subject = 'Thank you for your purchase!';
    }

    // Default message if empty
    if (empty($custom_message)) {
        $custom_message = "Hi {customer_name},\n\nThank you for purchasing {ebook_title}!\n\nDownload your ebook here: {download_link}\n\nBest regards,\n" . get_bloginfo('name');
    }

    // Build download URL
    $download_url = add_query_arg(array(
        'skillscore_download' => $purchase->download_token
    ), home_url());

    // Get order date formatted
    $order_date = date('F j, Y', strtotime($purchase->purchase_date));

    // Replace placeholders in subject
    $subject = str_replace(
        array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
        array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
        $custom_subject
    );

    // Replace placeholders in message
    $message = str_replace(
        array('{customer_name}', '{ebook_title}', '{download_link}', '{order_date}'),
        array($purchase->user_name, $ebook->post_title, $download_url, $order_date),
        $custom_message
    );

    // Prepare email headers for HTML
    $headers = array();
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>';

    // Convert message to HTML with line breaks
    $html_message = wpautop($message);

    // Log final values
    error_log('SkillScore: Sending to: ' . $purchase->user_email);
    error_log('SkillScore: Final subject: ' . $subject);

    // Send email
    $sent = wp_mail($purchase->user_email, $subject, $html_message, $headers);

    // Log result
    if ($sent) {
        error_log('SkillScore: Custom email sent successfully!');
    } else {
        error_log('SkillScore: Custom email FAILED to send!');
        error_log('SkillScore: WordPress mail error - check SMTP plugin logs');
    }

    return $sent;
}

/**
 * Send default email (original functionality)
 */
private function send_default_email($purchase, $ebook) {
    error_log('SkillScore: Sending DEFAULT email for ebook ID: ' . $purchase->ebook_id);
    
    $download_url = add_query_arg(array(
        'skillscore_download' => $purchase->download_token
    ), home_url());

    $subject = sprintf('Your Ebook Purchase: %s', $ebook->post_title);

    // Get format display name
    $format_names = array(
        'ebook' => 'Digital Ebook',
        'audio' => 'Audiobook',
        'printed' => 'Printed Book'
    );
    $format_display = isset($format_names[$purchase->format]) ? $format_names[$purchase->format] : 'Digital Ebook';

    $message = "Hi {$purchase->user_name},\n\n";
    $message .= "Thank you for your purchase!\n\n";
    $message .= "=== ORDER DETAILS ===\n";
    $message .= "Ebook: {$ebook->post_title}\n";
    $message .= "Format: {$format_display}\n";
    $message .= "Amount: {$purchase->currency} {$purchase->amount}\n";
    
    // Add shipping info for printed books
    if ($purchase->format === 'printed' && !empty($purchase->shipping_address)) {
        $message .= "\n=== SHIPPING INFORMATION ===\n";
        $message .= "Your printed book will be shipped to:\n";
        $message .= "{$purchase->shipping_address}\n\n";
        $message .= "Shipping Type: " . ucfirst($purchase->shipping_type) . "\n";
        $message .= "Shipping Status: " . ucfirst($purchase->shipping_status) . "\n";
        $message .= "\nWe will send you tracking information once your order is shipped.\n";
    } else {
        // Digital or audio - provide download link
        $message .= "\n=== DOWNLOAD YOUR EBOOK ===\n";
        $message .= $download_url . "\n\n";
        $message .= "Your download link is valid and ready to use.\n";
    }
    
    $message .= "\nThank you for your purchase!\n";
    $message .= get_bloginfo('name');

    error_log('SkillScore: Sending default email to: ' . $purchase->user_email);
    
    $sent = wp_mail($purchase->user_email, $subject, $message);
    
    if ($sent) {
        error_log('SkillScore: Default email sent successfully!');
    } else {
        error_log('SkillScore: Default email FAILED to send!');
    }
    
    return $sent;
}
    // /**
    //  * Send default email (original functionality)
    //  */
    // private function send_default_email($purchase, $ebook) {
    //     error_log('SkillScore: Sending DEFAULT email for ebook ID: ' . $purchase->ebook_id);
        
    //     $download_url = add_query_arg(array(
    //         'skillscore_download' => $purchase->download_token
    //     ), home_url());

    //     $subject = sprintf('Your Ebook Purchase: %s', $ebook->post_title);

    //     // Get format display name
    //     $format_names = array(
    //         'ebook' => 'Digital Ebook',
    //         'audio' => 'Audiobook',
    //         'printed' => 'Printed Book'
    //     );
    //     $format_display = isset($format_names[$purchase->format]) ? $format_names[$purchase->format] : 'Digital Ebook';

    //     $message = "Hi {$purchase->user_name},\n\n";
    //     $message .= "Thank you for your purchase!\n\n";
    //     $message .= "=== ORDER DETAILS ===\n";
    //     $message .= "Ebook: {$ebook->post_title}\n";
    //     $message .= "Format: {$format_display}\n";
    //     $message .= "Amount: {$purchase->currency} {$purchase->amount}\n";
        
    //     // Add shipping info for printed books
    //     if ($purchase->format === 'printed' && !empty($purchase->shipping_address)) {
    //         $message .= "\n=== SHIPPING INFORMATION ===\n";
    //         $message .= "Your printed book will be shipped to:\n";
    //         $message .= "{$purchase->shipping_address}\n\n";
    //         $message .= "Shipping Type: " . ucfirst($purchase->shipping_type) . "\n";
    //         $message .= "Shipping Status: " . ucfirst($purchase->shipping_status) . "\n";
    //         $message .= "\nWe will send you tracking information once your order is shipped.\n";
    //     } else {
    //         // Digital or audio - provide download link
    //         $message .= "\n=== DOWNLOAD YOUR EBOOK ===\n";
    //         $message .= $download_url . "\n\n";
    //         $message .= "Your download link is valid and ready to use.\n";
    //     }
        
    //     $message .= "\nThank you for your purchase!\n";
    //     $message .= get_bloginfo('name');

    //     error_log('SkillScore: Sending default email to: ' . $purchase->user_email);
        
    //     $sent = wp_mail($purchase->user_email, $subject, $message);
        
    //     if ($sent) {
    //         error_log('SkillScore: Default email sent successfully!');
    //     } else {
    //         error_log('SkillScore: Default email FAILED to send!');
    //     }
        
    //     return $sent;
    // }

    /**
     * AJAX handler for order details - ENHANCED WITH FORMAT & SHIPPING
     */
    public function get_order_details() {
        check_ajax_referer('skillscore_order_details', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        $ebook = get_post($order->ebook_id);
        $ebook_title = $ebook ? $ebook->post_title : 'Deleted Ebook';
        
        // Get format display
        $format_names = array(
            'ebook' => 'Ã°Å¸â€œâ€“ Digital Ebook',
            'audio' => 'Ã°Å¸Å½Â§ Audiobook',
            'printed' => 'Ã°Å¸â€œÂ¦ Printed Book'
        );
        $format_display = isset($format_names[$order->format]) ? $format_names[$order->format] : 'Ã°Å¸â€œâ€“ Digital Ebook';
        
        $html = '
        <div class="order-details">
            <table class="widefat" style="width: 100%;">
                <tr>
                    <th style="width: 30%; padding: 10px; text-align: left;">Order ID</th>
                    <td style="padding: 10px;">#' . $order->id . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Customer</th>
                    <td style="padding: 10px;">' . esc_html($order->user_name) . '<br>' . esc_html($order->user_email) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Ebook</th>
                    <td style="padding: 10px;">' . esc_html($ebook_title) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Format</th>
                    <td style="padding: 10px;"><strong>' . esc_html($format_display) . '</strong></td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Amount</th>
                    <td style="padding: 10px;">' . esc_html($order->currency . ' ' . number_format($order->amount, 2)) . '</td>
                </tr>';
        
        // Add shipping details for printed books
        if ($order->format === 'printed') {
            $html .= '
                <tr>
                    <th style="padding: 10px; text-align: left;">Shipping Fee</th>
                    <td style="padding: 10px;">' . esc_html($order->currency . ' ' . number_format($order->shipping_fee, 2)) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Shipping Type</th>
                    <td style="padding: 10px;">' . esc_html(ucfirst($order->shipping_type)) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Shipping Status</th>
                    <td style="padding: 10px;"><strong>' . esc_html(ucfirst($order->shipping_status)) . '</strong></td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left; vertical-align: top;">Shipping Address</th>
                    <td style="padding: 10px;"><pre style="white-space: pre-wrap; font-family: inherit; margin: 0;">' . esc_html($order->shipping_address) . '</pre></td>
                </tr>';
        }
        
        $html .= '
                <tr>
                    <th style="padding: 10px; text-align: left;">Payment Gateway</th>
                    <td style="padding: 10px;">' . esc_html(ucfirst($order->gateway)) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Transaction Reference</th>
                    <td style="padding: 10px;"><code>' . esc_html($order->transaction_reference) . '</code></td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Status</th>
                    <td style="padding: 10px;">' . esc_html(ucfirst($order->status)) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Purchase Date</th>
                    <td style="padding: 10px;">' . esc_html(date('F j, Y g:i a', strtotime($order->purchase_date))) . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Downloads</th>
                    <td style="padding: 10px;">' . esc_html($order->download_count) . ' times</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Last Download</th>
                    <td style="padding: 10px;">' . ($order->last_download_date ? esc_html(date('F j, Y g:i a', strtotime($order->last_download_date))) : 'Never') . '</td>
                </tr>
                <tr>
                    <th style="padding: 10px; text-align: left;">Download Token</th>
                    <td style="padding: 10px;"><code style="font-size: 10px;">' . esc_html($order->download_token) . '</code></td>
                </tr>
            </table>';
        
        // Only show download link for digital/audio formats
        if ($order->format !== 'printed') {
            $html .= '
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 4px;">
                <h4>Download Link</h4>
                <input type="text" readonly value="' . esc_attr(home_url('?skillscore_download=' . $order->download_token)) . '" style="width: 100%; padding: 5px; font-family: monospace; font-size: 12px;">
            </div>';
        }
        
        $html .= '</div>';
        
        wp_send_json_success(array('html' => $html));
    }

    /**
     * AJAX handler for resending email
     */
    public function resend_order_email() {
        check_ajax_referer('skillscore_resend_email', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        if (!$order_id) {
            wp_send_json_error(array('message' => 'Invalid order ID'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        $order = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $order_id
        ));
        
        if (!$order) {
            wp_send_json_error(array('message' => 'Order not found'));
            return;
        }
        
        // Send email
        $this->send_download_email($order);
        
        wp_send_json_success(array('message' => 'Email sent successfully'));
    }

    /**
     * AJAX handler for revoking downloads
     */
    public function revoke_download() {
        check_ajax_referer('skillscore_revoke_download', 'nonce');
        
        $download_id = isset($_POST['download_id']) ? intval($_POST['download_id']) : 0;
        
        if (!$download_id) {
            wp_send_json_error(array('message' => 'Invalid download ID'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_downloads';
        
        $updated = $wpdb->update(
            $table_name,
            array('is_revoked' => 1),
            array('id' => $download_id),
            array('%d'),
            array('%d')
        );
        
        if ($updated) {
            wp_send_json_success(array('message' => 'Download revoked successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to revoke download'));
        }
    }

    /**
     * AJAX handler for restoring downloads
     */
    public function restore_download() {
        check_ajax_referer('skillscore_restore_download', 'nonce');
        
        $download_id = isset($_POST['download_id']) ? intval($_POST['download_id']) : 0;
        
        if (!$download_id) {
            wp_send_json_error(array('message' => 'Invalid download ID'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_downloads';
        
        $updated = $wpdb->update(
            $table_name,
            array('is_revoked' => 0),
            array('id' => $download_id),
            array('%d'),
            array('%d')
        );
        
        if ($updated) {
            wp_send_json_success(array('message' => 'Download restored successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to restore download'));
        }
    }

    /**
     * NEW: AJAX handler for updating shipping status
     */
    public function update_shipping_status() {
        check_ajax_referer('skillscore_update_shipping', 'nonce');
        
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
        if (!$order_id || !$new_status) {
            wp_send_json_error(array('message' => 'Invalid order ID or status'));
            return;
        }
        
        // Validate status
        $valid_statuses = array('pending', 'processing', 'shipped', 'delivered');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error(array('message' => 'Invalid shipping status'));
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        
        // Update shipping status
        $updated = $wpdb->update(
            $table_name,
            array('shipping_status' => $new_status),
            array('id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            wp_send_json_success(array('message' => 'Shipping status updated successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to update shipping status'));
        }
    }
    
    /**
 * Process PayPal payment
 */
   /**
 * Process PayPal payment with credit card support
 */
private function process_paypal($amount, $currency, $email, $reference) {
    $client_id = get_option('skillscore_ebook_paypal_client_id');
    $secret = get_option('skillscore_ebook_paypal_secret');
    $mode = get_option('skillscore_ebook_paypal_mode', 'sandbox');
    
    if (empty($client_id) || empty($secret)) {
        error_log('PayPal not configured - missing credentials');
        return array('success' => false, 'message' => 'PayPal not configured');
    }
    
    // PayPal API endpoint
    $api_url = ($mode === 'live') 
        ? 'https://api-m.paypal.com' 
        : 'https://api-m.sandbox.paypal.com';
    
    // Get access token
    $token_response = wp_remote_post($api_url . '/v1/oauth2/token', array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($client_id . ':' . $secret),
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'body' => 'grant_type=client_credentials',
        'timeout' => 60
    ));
    
    if (is_wp_error($token_response)) {
        error_log('PayPal token error: ' . $token_response->get_error_message());
        return array('success' => false, 'message' => 'PayPal connection failed');
    }
    
    $token_body = json_decode(wp_remote_retrieve_body($token_response), true);
    
    if (!isset($token_body['access_token'])) {
        error_log('PayPal token missing in response');
        return array('success' => false, 'message' => 'PayPal authorization failed');
    }
    
    $access_token = $token_body['access_token'];
    
    // Return URL
    $return_url = add_query_arg(array(
        'skillscore_payment' => 'success',
        'reference' => $reference
    ), home_url());
    
    $cancel_url = home_url();
    
    // Create order with EXPLICIT credit card support
    // Get brand name with fallback
    $brand_name = get_bloginfo('name');
    if (empty($brand_name)) {
        $brand_name = parse_url(home_url(), PHP_URL_HOST); // Use domain as fallback
    }
    if (empty($brand_name)) {
        $brand_name = 'Store'; // Final fallback
    }
    
    // Create order with EXPLICIT credit card support
    $order_data = array(
        'intent' => 'CAPTURE',
        'purchase_units' => array(
            array(
                'reference_id' => $reference,
                'amount' => array(
                    'currency_code' => $currency,
                    'value' => number_format($amount, 2, '.', '')
                ),
                'description' => 'Ebook Purchase'
            )
        ),
        'payment_source' => array(
            'paypal' => array(
                'experience_context' => array(
                    'payment_method_preference' => 'UNRESTRICTED',
                    'brand_name' => $brand_name,
                    'locale' => 'en-US',
                    'landing_page' => 'LOGIN',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $return_url,
                    'cancel_url' => $cancel_url
                )
            )
        )
    );
    error_log('PayPal order data: ' . print_r($order_data, true));
    
    $order_response = wp_remote_post($api_url . '/v2/checkout/orders', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json',
            'PayPal-Request-Id' => $reference // Idempotency key
        ),
        'body' => json_encode($order_data),
        'timeout' => 60
    ));
    
    if (is_wp_error($order_response)) {
        error_log('PayPal order creation error: ' . $order_response->get_error_message());
        return array('success' => false, 'message' => 'PayPal order creation failed');
    }
    
    $order_body = json_decode(wp_remote_retrieve_body($order_response), true);
    
    error_log('PayPal order response: ' . print_r($order_body, true));
    
    // Look for approval URL
    if (isset($order_body['links'])) {
        foreach ($order_body['links'] as $link) {
            if ($link['rel'] === 'payer-action' || $link['rel'] === 'approve') {
                error_log('PayPal redirect URL: ' . $link['href']);
                return array(
                    'success' => true,
                    'redirect_url' => $link['href']
                );
            }
        }
    }
    
    error_log('PayPal approval link not found in response');
    return array('success' => false, 'message' => 'PayPal initialization failed');
}

/**
 * Process Stripe payment
 */
private function process_stripe($amount, $currency, $email, $reference) {
    $secret_key = get_option('skillscore_ebook_stripe_secret_key');
    
    if (empty($secret_key)) {
        return array('success' => false, 'message' => 'Stripe not configured');
    }
    
    $success_url = add_query_arg(array(
        'skillscore_payment' => 'success',
        'reference' => $reference
    ), home_url());
    
    $cancel_url = home_url();
    
    // Create Stripe checkout session
    $response = wp_remote_post('https://api.stripe.com/v1/checkout/sessions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $secret_key,
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
        'body' => http_build_query(array(
            'payment_method_types[]' => 'card',
            'line_items[0][price_data][currency]' => strtolower($currency),
            'line_items[0][price_data][product_data][name]' => 'Ebook Purchase',
            'line_items[0][price_data][unit_amount]' => intval($amount * 100),
            'line_items[0][quantity]' => 1,
            'mode' => 'payment',
            'success_url' => $success_url,
            'cancel_url' => $cancel_url,
            'client_reference_id' => $reference,
            'customer_email' => $email
        )),
        'timeout' => 60
    ));
    
    if (is_wp_error($response)) {
        return array('success' => false, 'message' => $response->get_error_message());
    }
    
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    if (isset($body['url'])) {
        return array(
            'success' => true,
            'redirect_url' => $body['url']
        );
    }
    
    return array('success' => false, 'message' => 'Stripe initialization failed');
}
    
}