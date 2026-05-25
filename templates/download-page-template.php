<?php
/**
 * Download Page Template - COMPLETE VERSION
 * Handles: Ebook, Audiobook, Physical Book
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

// Get format from purchase (defaults to 'ebook' for backward compatibility)
$format = isset($purchase->format) ? $purchase->format : 'ebook';

// Check download limit
$download_limit = get_option('skillscore_ebook_download_limit', 3);
$limit_reached = ($purchase->download_count >= $download_limit);

// Get file based on format
$download_url = '';
$file_type = '';

if ($format === 'audiobook') {
    // Get audiobook file
    $audiobook_file_id = get_post_meta($ebook_id, '_ebook_audiobook_file_id', true);
    $audiobook_file_url = get_post_meta($ebook_id, '_ebook_audiobook_file_url', true);
    
    if ($audiobook_file_id) {
        $download_url = wp_get_attachment_url($audiobook_file_id);
    } elseif ($audiobook_file_url) {
        $download_url = $audiobook_file_url;
    }
    $file_type = 'audiobook';
    
} else {
    // Get ebook file (PDF/EPUB)
    $ebook_file_id = get_post_meta($ebook_id, '_ebook_file_id', true);
    $ebook_file_url = get_post_meta($ebook_id, '_ebook_file_url', true);
    
    if ($ebook_file_id) {
        $download_url = wp_get_attachment_url($ebook_file_id);
    } elseif ($ebook_file_url) {
        $download_url = $ebook_file_url;
    }
    $file_type = 'ebook';
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
    
    if ($format === 'audiobook' && !empty($audiobook_file_id)) {
        $file_path = get_attached_file($audiobook_file_id);
    } elseif ($format === 'audiobook' && !empty($audiobook_file_url)) {
        $upload_dir = wp_upload_dir();
        if (strpos($audiobook_file_url, $upload_dir['baseurl']) !== false) {
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $audiobook_file_url);
        } else {
            wp_redirect($download_url);
            exit;
        }
    } elseif (!empty($ebook_file_id)) {
        $file_path = get_attached_file($ebook_file_id);
    } elseif (!empty($ebook_file_url)) {
        $upload_dir = wp_upload_dir();
        if (strpos($ebook_file_url, $upload_dir['baseurl']) !== false) {
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $ebook_file_url);
        } else {
            wp_redirect($download_url);
            exit;
        }
    }
    
    // If we have a local file path, force download
    if ($file_path && file_exists($file_path)) {
        // Get filename
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

// Physical books don't have downloads - show order info only
$is_physical = ($format === 'printed');

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $is_physical ? 'Order Confirmation' : 'Download Your ' . ucfirst($file_type); ?> - <?php bloginfo('name'); ?></title>
    <style>
        body {
            background: #0f172a;
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
            background: #1e293b;
            border-radius: 16px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            margin: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            text-align: center;
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
        }
        h1 {
            font-size: 28px;
            margin: 0 0 10px 0;
            color: #fff;
        }
        .subtitle {
            color: #94a3b8;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .ebook-info {
            background: #0f172a;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: left;
        }
        .ebook-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 10px 0;
            color: #fff;
        }
        .format-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .format-ebook {
            background: #3b82f6;
            color: white;
        }
        .format-audiobook {
            background: #8b5cf6;
            color: white;
        }
        .format-printed {
            background: #f59e0b;
            color: white;
        }
        .details {
            display: grid;
            gap: 10px;
            margin-top: 15px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #334155;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .label {
            color: #94a3b8;
        }
        .value {
            color: #fff;
            font-weight: 500;
        }
        .download-btn {
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 16px 40px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 10px 0;
            transition: all 0.3s;
        }
        .download-btn.audiobook {
            background: #8b5cf6;
        }
        .download-btn:hover {
            transform: translateY(-2px);
        }
        .download-btn.audiobook:hover {
            background: #7c3aed;
        }
        .download-btn:hover {
            background: #2563eb;
        }
        .download-btn.disabled {
            background: #64748b;
            cursor: not-allowed;
        }
        .download-btn.disabled:hover {
            transform: none;
        }
        .download-count {
            color: #94a3b8;
            font-size: 14px;
            margin: 10px 0 25px 0;
        }
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
        }
        .shipping-info {
            background: #0f172a;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .shipping-info h3 {
            margin: 0 0 15px 0;
            color: #f59e0b;
            font-size: 18px;
        }
        .shipping-info p {
            margin: 5px 0;
            color: #94a3b8;
            line-height: 1.6;
        }
        .copy-section {
            background: #0f172a;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        .copy-input {
            width: 100%;
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 12px;
            color: white;
            margin-bottom: 10px;
            font-family: monospace;
            font-size: 14px;
        }
        .copy-btn {
            background: #334155;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            cursor: pointer;
            width: 100%;
            font-weight: 500;
            transition: background 0.3s;
        }
        .copy-btn:hover {
            background: #475569;
        }
        .back-link {
            color: #94a3b8;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: color 0.3s;
        }
        .back-link:hover {
            color: #3b82f6;
        }
        @media (max-width: 640px) {
            .container {
                padding: 25px 20px;
            }
            h1 {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="success-icon">✅</div>
    
    <h1>Payment Successful!</h1>
    <p class="subtitle">
        <?php if ($is_physical): ?>
            Your order has been received
        <?php else: ?>
            Your <?php echo $file_type; ?> is ready to download
        <?php endif; ?>
    </p>
    
    <div class="ebook-info">
        <span class="format-badge format-<?php echo esc_attr($format); ?>">
            <?php 
            if ($format === 'audiobook') {
                echo '🎧 Audiobook';
            } elseif ($format === 'printed') {
                echo '📦 Physical Book';
            } else {
                echo '📚 Ebook';
            }
            ?>
        </span>
        
        <h2 class="ebook-title"><?php echo esc_html($ebook->post_title); ?></h2>
        
        <div class="details">
            <div class="detail-row">
                <span class="label">Order ID:</span>
                <span class="value">#<?php echo esc_html($purchase->id); ?></span>
            </div>
            <div class="detail-row">
                <span class="label">Format:</span>
                <span class="value"><?php echo esc_html(ucfirst($format)); ?></span>
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
    
    <?php if ($is_physical): ?>
        <!-- PHYSICAL BOOK - Show shipping info -->
        <?php 
        $shipping_data = null;
        if (!empty($purchase->shipping_data)) {
            $shipping_data = json_decode($purchase->shipping_data, true);
        }
        ?>
        
        <div class="info">
            <strong>📦 Your book will be shipped soon!</strong><br>
            We'll send you tracking information via email once it ships.
        </div>
        
        <?php if ($shipping_data): ?>
        <div class="shipping-info">
            <h3>Shipping Address</h3>
            <p><strong><?php echo esc_html($purchase->user_name); ?></strong></p>
            <p><?php echo esc_html($shipping_data['address']); ?></p>
            <p><?php echo esc_html($shipping_data['city']); ?>, <?php echo esc_html($shipping_data['state']); ?> <?php echo esc_html($shipping_data['postal_code']); ?></p>
            <p><?php echo esc_html($shipping_data['country']); ?></p>
            <p>Phone: <?php echo esc_html($shipping_data['phone']); ?></p>
        </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- DIGITAL DOWNLOAD (Ebook or Audiobook) -->
        
        <?php if ($limit_reached): ?>
            <div class="warning">
                <strong>⚠️ Download Limit Reached</strong><br>
                You have used all <?php echo $download_limit; ?> download attempts. Please contact support.
            </div>
        <?php elseif (!$download_url): ?>
            <div class="warning">
                <strong>⚠️ File Not Found</strong><br>
                The <?php echo $file_type; ?> file is not available. Please contact support.
            </div>
        <?php else: ?>
            <a href="<?php echo esc_url(add_query_arg(array('action' => 'download'), $_SERVER['REQUEST_URI'])); ?>" 
               class="download-btn <?php echo $format === 'audiobook' ? 'audiobook' : ''; ?>" 
               id="downloadBtn"
               download>
                <?php 
                if ($format === 'audiobook') {
                    echo '🎧 Download Audiobook Now';
                } else {
                    echo '⬇️ Download Ebook Now';
                }
                ?>
            </a>
            
            <div class="download-count">
                Downloads: <?php echo esc_html($purchase->download_count); ?> of <?php echo esc_html($download_limit); ?>
            </div>
        <?php endif; ?>
        
    <?php endif; ?>
    
    <div class="copy-section">
        <input type="text" 
               class="copy-input" 
               value="<?php echo esc_attr(home_url() . '?skillscore_download=' . $token); ?>" 
               readonly>
        <button class="copy-btn" onclick="copyLink()">
            Copy <?php echo $is_physical ? 'Order' : 'Download'; ?> Link
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
        btn.style.background = '#10b981';
        
        setTimeout(() => {
            btn.textContent = 'Copy <?php echo $is_physical ? "Order" : "Download"; ?> Link';
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
        // Update button text
        this.innerHTML = '⬇️ Downloading...';
        this.classList.add('disabled');
        
        // Reset button after 5 seconds
        setTimeout(() => {
            <?php if ($format === 'audiobook'): ?>
            this.innerHTML = '🎧 Download Audiobook Now';
            <?php else: ?>
            this.innerHTML = '⬇️ Download Ebook Now';
            <?php endif; ?>
            this.classList.remove('disabled');
        }, 5000);
    });
}
</script>

</body>
</html>