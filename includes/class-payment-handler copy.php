<?php
/**
 * Payment Handler - PRODUCTION READY VERSION
 * Simplified, bulletproof, with full error logging
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
        
        // Webhook handler
        add_action('init', array($this, 'handle_payment_webhook'));
    }

    /**
     * Initiate payment - SIMPLIFIED
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

        // Log incoming request
        error_log('========== PAYMENT INITIATED ==========');
        error_log('Ebook ID: ' . $ebook_id);
        error_log('User: ' . $user_name . ' (' . $user_email . ')');
        error_log('Gateway: ' . $gateway);

        // Validate
        if (!$ebook_id || !$user_name || !$user_email || !$gateway) {
            error_log('❌ Validation failed - missing fields');
            wp_send_json_error(array('message' => 'Please fill all required fields'));
            return;
        }

        if (!is_email($user_email)) {
            error_log('❌ Invalid email: ' . $user_email);
            wp_send_json_error(array('message' => 'Invalid email address'));
            return;
        }

        // Get ebook
        $ebook = get_post($ebook_id);
        if (!$ebook || $ebook->post_type !== 'ebook') {
            error_log('❌ Invalid ebook ID: ' . $ebook_id);
            wp_send_json_error(array('message' => 'Invalid ebook'));
            return;
        }

        // Get price
        $price = get_post_meta($ebook_id, '_ebook_price', true);
        if (empty($price) || $price <= 0) {
            error_log('❌ Invalid price: ' . $price);
            wp_send_json_error(array('message' => 'Invalid ebook price'));
            return;
        }

        // Calculate total
        $currency = get_option('skillscore_ebook_currency', 'USD');
        $total = floatval($price) * intval($quantity);

        error_log('Price: ' . $price . ' x ' . $quantity . ' = ' . $total . ' ' . $currency);

        // Generate reference
        $reference = 'EBOOK_' . time() . '_' . wp_generate_password(8, false, false);
        error_log('Reference: ' . $reference);

        // Create purchase record
        error_log('Creating purchase record...');
        $purchase_id = $this->create_purchase_record(array(
            'ebook_id' => $ebook_id,
            'user_name' => $user_name,
            'user_email' => $user_email,
            'amount' => $total,
            'currency' => $currency,
            'gateway' => $gateway,
            'transaction_reference' => $reference,
            'status' => 'pending'
        ));

        if (!$purchase_id) {
            error_log('❌❌❌ PURCHASE RECORD CREATION FAILED ❌❌❌');
            wp_send_json_error(array('message' => 'Failed to create purchase record. Please contact support.'));
            return;
        }

        error_log('✅ Purchase record created: ID ' . $purchase_id);

        // Process payment
        error_log('Processing payment with ' . $gateway . '...');
        
        switch ($gateway) {
            case 'paystack':
                $result = $this->process_paystack($total, $currency, $user_email, $reference);
                break;
                
            case 'flutterwave':
                $result = $this->process_flutterwave($total, $currency, $user_email, $user_name, $reference);
                break;
                
            default:
                error_log('❌ Invalid gateway: ' . $gateway);
                wp_send_json_error(array('message' => 'Payment gateway not configured'));
                return;
        }

        // Return result
        if ($result['success']) {
            error_log('✅ Payment URL generated: ' . $result['redirect_url']);
            wp_send_json_success(array(
                'redirect_url' => $result['redirect_url'],
                'purchase_id' => $purchase_id
            ));
        } else {
            error_log('❌ Payment gateway error: ' . $result['message']);
            wp_send_json_error(array('message' => $result['message']));
        }
    }

    /**
     * Create purchase record - BULLETPROOF VERSION
     */
    private function create_purchase_record($data) {
        global $wpdb;
        
        error_log('--- create_purchase_record START ---');
        
        // Check if table exists
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        if (!$table_exists) {
            error_log('❌ TABLE DOES NOT EXIST: ' . $table_name);
            error_log('Run emergency-db-setup.php to create table!');
            return false;
        }
        
        error_log('✓ Table exists: ' . $table_name);

        // Generate download token
        $download_token = wp_generate_password(32, false, false);
        error_log('✓ Token generated: ' . substr($download_token, 0, 10) . '...');

        // Prepare data - ENSURE CORRECT ORDER
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
            'purchase_date' => current_time('mysql')
        );

        error_log('✓ Data prepared:');
        error_log(print_r($purchase_data, true));

        // Try to insert
        error_log('Attempting database insert...');
        
        $result = $wpdb->insert(
            $table_name,
            $purchase_data,
            array('%d', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d', '%s', '%s') // Format specifiers
        );

        // Check result
        if ($result === false) {
            error_log('❌❌❌ DATABASE INSERT FAILED ❌❌❌');
            error_log('MySQL Error: ' . $wpdb->last_error);
            error_log('Last Query: ' . $wpdb->last_query);
            return false;
        }

        // Get insert ID
        $purchase_id = $wpdb->insert_id;
        
        if (empty($purchase_id) || $purchase_id == 0) {
            error_log('❌ Insert succeeded but returned ID 0');
            error_log('This usually means AUTO_INCREMENT is broken');
            return false;
        }

        error_log('✅✅✅ PURCHASE CREATED SUCCESSFULLY ✅✅✅');
        error_log('Purchase ID: ' . $purchase_id);
        error_log('--- create_purchase_record END ---');

        return $purchase_id;
    }

    /**
     * Handle payment webhook
     */
    public function handle_payment_webhook() {
        if (!isset($_GET['skillscore_webhook'])) {
            return;
        }

        $gateway = sanitize_text_field($_GET['skillscore_webhook']);

        error_log('========== WEBHOOK RECEIVED ==========');
        error_log('Gateway: ' . $gateway);

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
     * Process Paystack payment
     */
    private function process_paystack($amount, $currency, $email, $reference) {
        $api_key = get_option('skillscore_ebook_paystack_secret_key');
        
        if (empty($api_key)) {
            error_log('❌ Paystack secret key not configured');
            return array('success' => false, 'message' => 'Paystack not configured');
        }

        $callback_url = add_query_arg(array(
            'skillscore_payment' => 'success',
            'reference' => $reference
        ), home_url());

        $url = 'https://api.paystack.co/transaction/initialize';
        
        $fields = array(
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'currency' => $currency,
            'reference' => $reference,
            'callback_url' => $callback_url
        );

        error_log('Paystack API request:');
        error_log(print_r($fields, true));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($fields),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('❌ Paystack API error: ' . $response->get_error_message());
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Paystack response:');
        error_log(print_r($body, true));

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
            error_log('❌ No reference in webhook');
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
            error_log('❌ Paystack verification error: ' . $response->get_error_message());
            wp_die('Verification failed');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] && $body['data']['status'] === 'success') {
            error_log('✅ Payment verified for: ' . $reference);
            $this->complete_purchase($reference);
        } else {
            error_log('❌ Payment verification failed for: ' . $reference);
        }
    }

    /**
     * Process Flutterwave payment
     */
    private function process_flutterwave($amount, $currency, $email, $name, $reference) {
        $api_key = get_option('skillscore_ebook_flutterwave_secret_key');
        
        if (empty($api_key)) {
            error_log('❌ Flutterwave secret key not configured');
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

        error_log('Flutterwave API request:');
        error_log(print_r($fields, true));

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($fields),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('❌ Flutterwave API error: ' . $response->get_error_message());
            return array('success' => false, 'message' => $response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        error_log('Flutterwave response:');
        error_log(print_r($body, true));

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
            error_log('❌ No tx_ref in webhook');
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
            error_log('❌ Flutterwave verification error: ' . $response->get_error_message());
            wp_die('Verification failed');
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] === 'success' && $body['data']['status'] === 'successful') {
            error_log('✅ Payment verified for: ' . $reference);
            $this->complete_purchase($reference);
        } else {
            error_log('❌ Payment verification failed for: ' . $reference);
        }
    }

    /**
     * Complete purchase after payment verification
     */
    private function complete_purchase($reference) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';

        error_log('Completing purchase: ' . $reference);

        // Update status
        $updated = $wpdb->update(
            $table_name,
            array('status' => 'completed'),
            array('transaction_reference' => $reference),
            array('%s'),
            array('%s')
        );

        if ($updated) {
            error_log('✅ Purchase status updated to completed');

            // Get purchase details
            $purchase = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE transaction_reference = %s",
                $reference
            ));

            if ($purchase) {
                // Send email
                $this->send_download_email($purchase);
                error_log('✅ Download email sent to: ' . $purchase->user_email);
            }
        } else {
            error_log('❌ Failed to update purchase status');
        }
    }

    /**
     * Send download email
     */
    private function send_download_email($purchase) {
        $ebook = get_post($purchase->ebook_id);
        if (!$ebook) {
            return false;
        }

        $download_url = add_query_arg(array(
            'skillscore_download' => $purchase->download_token
        ), home_url());

        $subject = sprintf('Your Ebook Purchase: %s', $ebook->post_title);

        $message = "Hi {$purchase->user_name},\n\n";
        $message .= "Thank you for your purchase!\n\n";
        $message .= "Ebook: {$ebook->post_title}\n";
        $message .= "Amount: {$purchase->currency} {$purchase->amount}\n\n";
        $message .= "DOWNLOAD YOUR EBOOK:\n";
        $message .= $download_url . "\n\n";
        $message .= "Thank you!\n";
        $message .= get_bloginfo('name');

        wp_mail($purchase->user_email, $subject, $message);
    }
}