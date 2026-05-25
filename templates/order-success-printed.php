<?php
/**
 * Order Success Page - FOR PRINTED BOOKS
 * Shows order confirmation WITHOUT download link
 *
 * @package SkillScore_Ebook
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get order reference from URL
$reference = '';
if (isset($_GET['reference'])) {
    $reference = sanitize_text_field($_GET['reference']);
}

if (empty($reference)) {
    wp_die('Invalid order reference. Please check your email for order details.');
}

// Get purchase from database
global $wpdb;
$table_name = $wpdb->prefix . 'skillscore_ebook_purchases';
$purchase = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_name WHERE transaction_reference = %s",
    $reference
));

if (!$purchase) {
    wp_die('Order not found. Please contact support with your payment reference.');
}

// Get ebook details
$ebook_id = $purchase->ebook_id;
$ebook = get_post($ebook_id);
$currency_symbol = get_option('skillscore_ebook_currency_symbol', '$');

// Decode shipping address JSON
$shipping = null;
if (!empty($purchase->shipping_address)) {
    $shipping = json_decode($purchase->shipping_address, true);
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed - <?php bloginfo('name'); ?></title>
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
            max-width: 600px;
            width: 90%;
            margin: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        .success-icon {
            font-size: 64px;
            margin-bottom: 20px;
            display: block;
            text-align: center;
        }
        h1 {
            font-size: 28px;
            margin: 0 0 10px 0;
            color: #fff;
            text-align: center;
        }
        .subtitle {
            color: #94a3b8;
            margin-bottom: 30px;
            font-size: 16px;
            text-align: center;
        }
        .order-info {
            background: #0f172a;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .order-title {
            font-size: 20px;
            font-weight: 600;
            margin: 0 0 15px 0;
            color: #fff;
            border-bottom: 2px solid #334155;
            padding-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #334155;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .label {
            color: #94a3b8;
            font-weight: 500;
        }
        .value {
            color: #fff;
            font-weight: 600;
            text-align: right;
        }
        .shipping-box {
            background: #10b981;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
        }
        .shipping-box h2 {
            margin: 0 0 15px 0;
            color: white;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .address-line {
            color: white;
            margin: 5px 0;
            font-size: 15px;
            line-height: 1.6;
        }
        .notice {
            background: #fef3c7;
            border: 2px solid #fbbf24;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #92400e;
        }
        .notice strong {
            display: block;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .back-link {
            color: #3b82f6;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: color 0.3s;
            text-align: center;
            width: 100%;
        }
        .back-link:hover {
            color: #60a5fa;
        }
        .format-badge {
            display: inline-block;
            background: #fbbf24;
            color: #92400e;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            margin-left: 10px;
        }
        @media (max-width: 640px) {
            .container {
                padding: 25px 20px;
            }
            h1 {
                font-size: 24px;
            }
            .info-row {
                flex-direction: column;
                gap: 5px;
            }
            .value {
                text-align: left;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="success-icon">✅</div>
    
    <h1>Order Confirmed!</h1>
    <p class="subtitle">Thank you for your purchase</p>
    
    <!-- Order Information -->
    <div class="order-info">
        <h2 class="order-title">
            📦 Order Details
            <span class="format-badge">PRINTED BOOK</span>
        </h2>
        
        <div class="info-row">
            <span class="label">Order ID:</span>
            <span class="value">#<?php echo esc_html($purchase->id); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Book:</span>
            <span class="value"><?php echo esc_html($ebook->post_title); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Order Date:</span>
            <span class="value"><?php echo date('M j, Y g:i a', strtotime($purchase->purchase_date)); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Amount Paid:</span>
            <span class="value"><?php echo esc_html($currency_symbol . number_format($purchase->amount, 2)); ?></span>
        </div>
        <?php if ($purchase->shipping_fee > 0): ?>
        <div class="info-row">
            <span class="label">Shipping Fee:</span>
            <span class="value"><?php echo esc_html($currency_symbol . number_format($purchase->shipping_fee, 2)); ?></span>
        </div>
        <?php endif; ?>
        <div class="info-row">
            <span class="label">Customer:</span>
            <span class="value"><?php echo esc_html($purchase->user_name); ?></span>
        </div>
        <div class="info-row">
            <span class="label">Email:</span>
            <span class="value"><?php echo esc_html($purchase->user_email); ?></span>
        </div>
    </div>
    
    <!-- Shipping Address -->
    <?php if ($shipping): ?>
    <div class="shipping-box">
        <h2>📍 Shipping To:</h2>
        <div class="address-line"><strong><?php echo esc_html($purchase->user_name); ?></strong></div>
        <div class="address-line"><?php echo esc_html($shipping['address']); ?></div>
        <div class="address-line">
            <?php echo esc_html($shipping['city']); ?>, 
            <?php echo esc_html($shipping['state']); ?> 
            <?php echo esc_html($shipping['postal_code']); ?>
        </div>
        <div class="address-line"><?php echo esc_html($shipping['country']); ?></div>
        <div class="address-line">📞 <?php echo esc_html($shipping['phone']); ?></div>
    </div>
    <?php endif; ?>
    
    <!-- Processing Notice -->
    <div class="notice">
        <strong>📦 Order Processing</strong>
        Your order is being prepared for shipment. You will receive a tracking number via email once your book ships.
        <br><br>
        <strong>Estimated Delivery:</strong> 5-10 business days
    </div>
    
    <!-- Email Confirmation -->
    <div style="background: #0f172a; border-radius: 8px; padding: 20px; text-align: center; margin-top: 20px;">
        <p style="margin: 0; color: #94a3b8; font-size: 14px;">
            📧 A confirmation email has been sent to<br>
            <strong style="color: #fff; font-size: 16px;"><?php echo esc_html($purchase->user_email); ?></strong>
        </p>
    </div>
    
    <a href="<?php echo esc_url(home_url()); ?>" class="back-link">
        ← Back to Home
    </a>
</div>

</body>
</html>