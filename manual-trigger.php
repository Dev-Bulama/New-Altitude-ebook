<?php
/**
 * MANUAL CAMPAIGN TRIGGER - Run this once to fix existing purchases
 * 
 * This tool will:
 * 1. Find your recent purchase (ID #113)
 * 2. Manually register it for any active campaigns
 * 3. Allow the campaign emails to be sent
 * 
 * HOW TO USE:
 * 1. Upload this file to: wp-content/plugins/skillscore-ebook-commerce/
 * 2. Visit: https://yoursite.com/wp-content/plugins/skillscore-ebook-commerce/manual-trigger.php
 * 3. Follow the on-screen instructions
 * 4. DELETE this file after use
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Unauthorized access');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Manual Campaign Trigger</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f0f0f0;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #667eea;
            margin-top: 0;
        }
        .status {
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin: 10px 5px 10px 0;
        }
        button:hover {
            background: #5568d3;
        }
        button.danger {
            background: #dc3545;
        }
        button.danger:hover {
            background: #c82333;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .step {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-left: 4px solid #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Manual Campaign Trigger Tool</h1>

<?php

if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'trigger_purchase') {
        $purchase_id = intval($_POST['purchase_id']);
        
        echo '<div class="status info">Processing purchase #' . $purchase_id . '...</div>';
        
        // Get purchase details
        global $wpdb;
        $table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
        $purchase = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $purchase_id
        ));
        
        if (!$purchase) {
            echo '<div class="status error">❌ Purchase #' . $purchase_id . ' not found!</div>';
        } else {
            // Check if campaign classes exist
            if (!class_exists('SkillScore_Email_Campaign')) {
                echo '<div class="status error">❌ Campaign class not loaded! Make sure you followed the integration steps.</div>';
            } else {
                // Trigger the campaign
                $campaign_manager = new SkillScore_Email_Campaign();
                
                // Get campaigns for this ebook
                $campaigns = $campaign_manager->get_ebook_campaigns($purchase->ebook_id);
                
                if (empty($campaigns)) {
                    echo '<div class="status warning">⚠️ No active campaigns found for ebook ID: ' . $purchase->ebook_id . '</div>';
                } else {
                    echo '<div class="status success">✅ Found ' . count($campaigns) . ' campaign(s) for this ebook</div>';
                    
                    // Register user for each campaign
                    foreach ($campaigns as $campaign) {
                        // Check if already registered
                        $log_table = $wpdb->prefix . 'ebook_campaign_log';
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM $log_table WHERE campaign_id = %d AND user_email = %s",
                            $campaign->id,
                            $purchase->user_email
                        ));
                        
                        if ($exists) {
                            echo '<div class="status warning">⚠️ Campaign #' . $campaign->id . ' - Already registered</div>';
                        } else {
                            // Insert into log
                            $wpdb->insert(
                                $log_table,
                                array(
                                    'campaign_id' => $campaign->id,
                                    'user_email' => $purchase->user_email,
                                    'user_name' => $purchase->user_name,
                                    'purchase_id' => $purchase->id,
                                    'status' => 'pending'
                                ),
                                array('%d', '%s', '%s', '%d', '%s')
                            );
                            
                            echo '<div class="status success">✅ Campaign #' . $campaign->id . ' - Registered successfully!</div>';
                        }
                    }
                    
                    echo '<div class="status success"><strong>✅ Done!</strong> User registered for campaigns. Emails will send at scheduled times.</div>';
                }
            }
        }
        
    } elseif ($action === 'trigger_cron') {
        echo '<div class="status info">Manually triggering WP-Cron...</div>';
        
        if (class_exists('SkillScore_Campaign_Scheduler')) {
            $scheduler = new SkillScore_Campaign_Scheduler();
            $scheduler->process_pending_emails();
            echo '<div class="status success">✅ WP-Cron triggered! Check debug.log for results.</div>';
        } else {
            echo '<div class="status error">❌ Scheduler class not loaded!</div>';
        }
    }
}

// Display current status
echo '<h2>📊 Current Status</h2>';

global $wpdb;

// Check if tables exist
$campaigns_table = $wpdb->prefix . 'ebook_email_campaigns';
$log_table = $wpdb->prefix . 'ebook_campaign_log';

$campaigns_exists = $wpdb->get_var("SHOW TABLES LIKE '$campaigns_table'") == $campaigns_table;
$log_exists = $wpdb->get_var("SHOW TABLES LIKE '$log_table'") == $log_table;

if (!$campaigns_exists || !$log_exists) {
    echo '<div class="status error">❌ Campaign tables do not exist! Visit any ebook edit page first.</div>';
} else {
    echo '<div class="status success">✅ Campaign tables exist</div>';
    
    // Get recent purchases
    $purchases_table = $wpdb->prefix . 'skillscore_ebook_purchases';
    $recent_purchases = $wpdb->get_results(
        "SELECT * FROM $purchases_table 
        WHERE status = 'completed' 
        ORDER BY purchase_date DESC 
        LIMIT 10"
    );
    
    echo '<h3>Recent Purchases</h3>';
    if (empty($recent_purchases)) {
        echo '<p>No purchases found.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>User Email</th><th>Ebook ID</th><th>Date</th><th>Action</th></tr>';
        foreach ($recent_purchases as $purchase) {
            echo '<tr>';
            echo '<td>#' . $purchase->id . '</td>';
            echo '<td>' . esc_html($purchase->user_email) . '</td>';
            echo '<td>' . $purchase->ebook_id . '</td>';
            echo '<td>' . $purchase->purchase_date . '</td>';
            echo '<td>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="trigger_purchase">
                    <input type="hidden" name="purchase_id" value="' . $purchase->id . '">
                    <button type="submit">Trigger Campaign</button>
                </form>
            </td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Get campaigns
    $campaigns = $wpdb->get_results("SELECT * FROM $campaigns_table ORDER BY id DESC LIMIT 10");
    
    echo '<h3>Active Campaigns</h3>';
    if (empty($campaigns)) {
        echo '<p>No campaigns found.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>Ebook ID</th><th>Subject</th><th>Scheduled</th><th>Active</th></tr>';
        foreach ($campaigns as $campaign) {
            echo '<tr>';
            echo '<td>#' . $campaign->id . '</td>';
            echo '<td>' . $campaign->ebook_id . '</td>';
            echo '<td>' . esc_html($campaign->email_subject) . '</td>';
            echo '<td>' . $campaign->scheduled_datetime . '</td>';
            echo '<td>' . ($campaign->is_active ? '✅ Yes' : '❌ No') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    // Get campaign log
    $log_entries = $wpdb->get_results("SELECT * FROM $log_table ORDER BY id DESC LIMIT 20");
    
    echo '<h3>Campaign Log</h3>';
    if (empty($log_entries)) {
        echo '<p>No log entries found.</p>';
    } else {
        echo '<table>';
        echo '<tr><th>ID</th><th>Campaign ID</th><th>User Email</th><th>Status</th><th>Sent At</th></tr>';
        foreach ($log_entries as $entry) {
            echo '<tr>';
            echo '<td>#' . $entry->id . '</td>';
            echo '<td>Campaign #' . $entry->campaign_id . '</td>';
            echo '<td>' . esc_html($entry->user_email) . '</td>';
            echo '<td><strong>' . $entry->status . '</strong></td>';
            echo '<td>' . ($entry->sent_at ?? 'Not sent yet') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
}
?>

<h2>🚀 Actions</h2>

<div class="step">
    <h3>Step 1: Trigger Campaigns for Existing Purchases</h3>
    <p>Use the "Trigger Campaign" button next to any purchase above to manually register it for campaigns.</p>
</div>

<div class="step">
    <h3>Step 2: Force Send Pending Emails</h3>
    <p>This will immediately process all pending campaign emails (regardless of scheduled time):</p>
    <form method="post">
        <input type="hidden" name="action" value="trigger_cron">
        <button type="submit" class="danger">⚡ Force Send All Pending Emails Now</button>
    </form>
    <p><small><strong>Warning:</strong> This will send emails to ALL pending recipients immediately!</small></p>
</div>

<div class="step">
    <h3>Step 3: Add Auto-Trigger Code</h3>
    <p>To automatically trigger campaigns for future purchases, add this code to <code>class-payment-handler.php</code>:</p>
    <p><strong>File:</strong> <code>wp-content/plugins/skillscore-ebook-commerce/includes/class-payment-handler.php</code></p>
    <p><strong>Location:</strong> In the <code>complete_purchase()</code> function, after line 967</p>
    <p><strong>Add this line:</strong></p>
    <pre style="background:#f4f4f4; padding:15px; border-radius:5px; overflow-x:auto;">
if ($purchase) {
    $this->send_download_email($purchase);
    
    <strong style="color:#d63384;">// Trigger email campaign system
    do_action('skillscore_purchase_completed', $purchase);</strong>
    
    error_log('Purchase completed: #' . $purchase->id);
}
</pre>
</div>

<hr style="margin: 30px 0;">

<p><strong>⚠️ IMPORTANT:</strong> Delete this file after use for security!</p>
<form method="post" onsubmit="return confirm('Are you sure you want to delete this tool?');">
    <button type="button" onclick="alert('Please delete this file manually via FTP or File Manager:\n\nwp-content/plugins/skillscore-ebook-commerce/manual-trigger.php')" class="danger">
        🗑️ Delete This Tool
    </button>
</form>

    </div>
</body>
</html>