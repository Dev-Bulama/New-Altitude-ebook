<?php
/**
 * Audiobook Download Page Template
 * Specifically for audiobook purchases
 *
 * @package SkillScore_Ebook
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get download token from URL
$token = '';
if (isset($_GET['skillscore_download'])) {
    $token = sanitize_text_field($_GET['skillscore_download']);
}

if (empty($token)) {
    wp_die('Invalid download link. Please check your email for the correct link.');
}

// Verify token and get purchase data
global $wpdb;
$table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
$purchase = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE download_token = %s",
    $token
));

if (!$purchase) {
    wp_die('Download link expired or invalid. Please contact support.');
}

// Get ebook details
$ebook_id = $purchase->ebook_id;
$ebook = get_post($ebook_id);
$price = get_post_meta($ebook_id, '_ebook_price', true);
$currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');

// Check download limit
$download_limit = get_option('skillscore_ebook_download_limit', 3);
$limit_reached = ($purchase->download_count >= $download_limit);

// Get audiobook file - FIXED to use correct meta key
$audiobook_file_id = get_post_meta($ebook_id, '_ebook_audio_preview_id', true);
$audiobook_file_url = get_post_meta($ebook_id, '_ebook_audio_preview_url', true);

$download_url = '';
if ($audiobook_file_id) {
    $download_url = wp_get_attachment_url($audiobook_file_id);
} elseif ($audiobook_file_url) {
    $download_url = $audiobook_file_url;
}

// Handle direct file download
if (isset($_GET['action']) && $_GET['action'] === 'download' && !$limit_reached && $download_url) {
    // Update download count first
    $new_count = $purchase->download_count + 1;
    $wpdb->update(
        $table_name,
        array(
            'download_count' => $new_count,
            'last_download_date' => current_time('mysql')
        ),
        array('download_token' => $token),
        array('%d', '%s'),
        array('%s')
    );
    
    // Get the file path from URL
    $file_path = '';
    
    if ($audiobook_file_id) {
        $file_path = get_attached_file($audiobook_file_id);
    } elseif ($audiobook_file_url) {
        // Check if it's a local file in uploads directory
        $upload_dir = wp_upload_dir();
        if (strpos($audiobook_file_url, $upload_dir['baseurl']) !== false) {
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $audiobook_file_url);
        } else {
            // It's an external URL, use redirect
            wp_redirect($download_url);
            exit;
        }
    }
    
    // If we have a local file path, force download
    if ($file_path && file_exists($file_path)) {
        // Get audiobook title for filename
        $ebook_title = sanitize_file_name($ebook->post_title);
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        $filename = $ebook_title . '.' . $file_extension;
        
        // Set download headers
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        
        // Clear any output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Read file
        readfile($file_path);
        exit;
    } else {
        // Fallback to redirect if file not found
        wp_redirect($download_url);
        exit;
    }
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Download Your Audiobook - <?php bloginfo('name'); ?></title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .container {
            background: rgba(30, 30, 60, 0.95);
            border-radius: 20px;
            padding: 40px;
            max-width: 550px;
            width: 90%;
            margin: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            text-align: center;
            border: 2px solid rgba(139, 92, 246, 0.3);
        }
        .success-icon {
            font-size: 72px;
            margin-bottom: 20px;
            display: block;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            font-size: 32px;
            margin: 0 0 10px 0;
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .subtitle {
            color: #c4b5fd;
            margin-bottom: 30px;
            font-size: 18px;
        }
        .audiobook-badge {
            display: inline-block;
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(139, 92, 246, 0.4);
        }
        .ebook-info {
            background: rgba(15, 15, 30, 0.8);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            text-align: left;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .ebook-title {
            font-size: 22px;
            font-weight: 700;
            margin: 0 0 15px 0;
            color: #fff;
        }
        .details {
            display: grid;
            gap: 12px;
            margin-top: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(139, 92, 246, 0.2);
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            color: #c4b5fd;
        }
        .value {
            color: #fff;
            font-weight: 600;
        }
        .download-btn {
            background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 18px 45px;
            font-size: 20px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(139, 92, 246, 0.4);
        }
        .download-btn:hover {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(139, 92, 246, 0.6);
        }
        .download-btn.disabled {
            background: #64748b;
            cursor: not-allowed;
            box-shadow: none;
        }
        .download-btn.disabled:hover {
            transform: none;
        }
        .download-count {
            color: #c4b5fd;
            font-size: 15px;
            margin: 15px 0 25px 0;
            font-weight: 500;
        }
        .warning {
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 18px;
            margin: 20px 0;
            color: #fecaca;
        }
        .copy-section {
            background: rgba(15, 15, 30, 0.8);
            border-radius: 16px;
            padding: 25px;
            margin: 25px 0;
            border: 1px solid rgba(139, 92, 246, 0.2);
        }
        .copy-input {
            width: 100%;
            background: rgba(30, 30, 60, 0.8);
            border: 2px solid rgba(139, 92, 246, 0.3);
            border-radius: 10px;
            padding: 14px;
            color: white;
            margin-bottom: 12px;
            font-family: monospace;
            font-size: 13px;
        }
        .copy-btn {
            background: rgba(139, 92, 246, 0.3);
            color: white;
            border: 2px solid #8b5cf6;
            border-radius: 10px;
            padding: 14px 24px;
            cursor: pointer;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s;
        }
        .copy-btn:hover {
            background: rgba(139, 92, 246, 0.5);
        }
        .back-link {
            color: #c4b5fd;
            text-decoration: none;
            display: inline-block;
            margin-top: 25px;
            transition: color 0.3s;
            font-weight: 500;
        }
        .back-link:hover {
            color: #8b5cf6;
        }
        @media (max-width: 640px) {
            .container {
                padding: 30px 20px;
            }
            h1 {
                font-size: 26px;
            }
            .download-btn {
                font-size: 18px;
                padding: 16px 35px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="success-icon">🎧</div>
    
    <h1>Payment Successful!</h1>
    <p class="subtitle">Your audiobook is ready to download</p>
    
    <span class="audiobook-badge">🎵 AUDIOBOOK</span>
    
    <div class="ebook-info">
        <h2 class="ebook-title"><?php echo esc_html($ebook->post_title); ?></h2>
        
        <div class="details">
            <div class="detail-row">
                <span class="label">Order ID:</span>
                <span class="value">#<?php echo esc_html($purchase->id); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Format:</span>
                <span class="value">Audiobook</span>
            </div>
            <div class="detail-row">
                <span class="label">Purchase Date:</span>
                <span class="value"><?php echo date('M j, Y', strtotime($purchase->purchase_date)); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Amount Paid:</span>
                <span class="value"><?php echo esc_html($currency_symbol . number_format($price, 2)); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Your Email:</span>
                <span class="value"><?php echo esc_html($purchase->user_email); ?></span>
            </div>
        </div>
    </div>
    
    <?php if ($limit_reached) : ?>
        <div class="warning">
            <strong>⚠️ Download Limit Reached</strong><br>
            You have used all <?php echo $download_limit; ?> download attempts. Please contact support.
        </div>
    <?php elseif (!$download_url) : ?>
        <div class="warning">
            <strong>⚠️ Audiobook File Not Found</strong><br>
            The audiobook file is not available. Please contact support.
        </div>
    <?php else : ?>
        <a href="<?php echo esc_url(add_query_arg(array('action' => 'download'), $_SERVER['REQUEST_URI'])); ?>" 
           class="download-btn" 
           id="downloadBtn"
           download>
            🎧 Download Audiobook Now
        </a>
        
        <div class="download-count">
            Downloads: <?php echo esc_html($purchase->download_count); ?> of <?php echo esc_html($download_limit); ?>
        </div>
    <?php endif; ?>
    
    <div class="copy-section">
        <input type="text" 
               class="copy-input" 
               value="<?php echo esc_attr(home_url() . '?skillscore_download=' . $token); ?>" 
               readonly>
        <button class="copy-btn" onclick="copyLink()">
            📋 Copy Download Link
        </button>
    </div>
    
    <a href="<?php echo esc_url(home_url()); ?>" class="back-link">
        ← Back to Home
    </a>
</div>

<script>
function copyLink() {
    const input = document.querySelector('.copy-input');
    input.select();
    input.setSelectionRange(0, 99999);
    
    try {
        document.execCommand('copy');
        const btn = document.querySelector('.copy-btn');
        btn.textContent = '✓ Copied!';
        btn.style.background = 'rgba(16, 185, 129, 0.5)';
        
        setTimeout(() => {
            btn.textContent = '📋 Copy Download Link';
            btn.style.background = '';
        }, 2000);
    } catch (err) {
        alert('Failed to copy. Please copy manually.');
    }
}

// Handle download button click
const downloadBtn = document.getElementById('downloadBtn');
if (downloadBtn) {
    downloadBtn.addEventListener('click', function(e) {
        this.innerHTML = '⏳ Downloading...';
        this.classList.add('disabled');
        
        setTimeout(() => {
            this.innerHTML = '🎧 Download Audiobook Now';
            this.classList.remove('disabled');
        }, 5000);
    });
}
</script>

</body>
</html>