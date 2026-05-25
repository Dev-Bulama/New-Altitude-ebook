<?php
/**
 * Single Ebook Template - WITH SHIPPING ADDRESS COLLECTION
 * Shows shipping fields when "Printed" format selected
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

// Checkout feature flags (admin-controlled)
$enable_bulk_inquiry   = get_option('skillscore_ebook_enable_bulk_inquiry') === '1';
$enable_order_bump     = get_option('skillscore_ebook_enable_order_bump') === '1';
$enable_dynamic_button = get_option('skillscore_ebook_enable_dynamic_button') === '1';
$bump_name             = get_option('skillscore_ebook_order_bump_name', '90-Day No Excuse Journal, Discussion & Reflection Guide');
$bump_price            = floatval(get_option('skillscore_ebook_order_bump_price', 0));
$bump_desc             = get_option('skillscore_ebook_order_bump_desc', 'Take the book deeper with the companion guide designed for personal reflection, reading groups, leadership cohorts, and serious discussion.');
$bulk_min              = intval(get_option('skillscore_ebook_bulk_min_copies', 10));
?>

<div class="skillscore-ebook-single" style="background: #0D0D0D; min-height: 100vh; padding: 40px 20px;">
    
    <div style="max-width: 1200px; margin: 0 auto;">
        
        <!-- Back to Store -->
        <div style="margin-bottom: 30px;">
            <a href="<?php echo esc_url(home_url('/en/books-sample/')); ?>" 
               style="color: #EAB308; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 8px;">
                <span>←</span> Back to Store
            </a>
        </div>

        <!-- Main Content Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-bottom: 60px;">
            
            <!-- LEFT: Book Cover & Info -->
            <div>
                <!-- Book Cover -->
                <div style="background: #1A1A1A; border-radius: 12px; padding: 40px; text-align: center; margin-bottom: 30px;">
                    <?php if (has_post_thumbnail()) : ?>
                        <?php the_post_thumbnail('large', array(
                            'style' => 'max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 10px 40px rgba(234, 179, 8, 0.2);'
                        )); ?>
                    <?php else : ?>
                        <div style="width: 100%; height: 500px; background: #2A2A2A; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                            <span style="color: #666; font-size: 18px;">📚 No Cover Image</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Book Title -->
                <h1 style="color: #FFFFFF; font-size: 32px; font-weight: 700; margin: 0 0 15px 0; line-height: 1.2;">
                    <?php the_title(); ?>
                </h1>

                <!-- Author -->
                <?php if ($author) : ?>
                    <p style="color: #9CA3AF; font-size: 18px; margin: 0 0 25px 0;">
                        by <span style="color: #EAB308;"><?php echo esc_html($author); ?></span>
                    </p>
                <?php endif; ?>

                <!-- Price -->
                <div style="margin-bottom: 30px;">
                    <span style="color: #EAB308; font-size: 42px; font-weight: 700;">
                        <?php echo esc_html($currency_symbol . number_format($price, 2)); ?>
                    </span>
                </div>

                <!-- Audio Preview Section -->
                <?php if ($enable_audio) : ?>
                    <div id="audio-preview-section" style="background: #1A1A1A; border-radius: 12px; padding: 25px; margin-bottom: 30px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                            <span style="font-size: 24px;">🎧</span>
                            <h3 style="color: #FFFFFF; font-size: 18px; margin: 0;">Audio Preview</h3>
                        </div>
                        
                        <p style="color: #9CA3AF; font-size: 14px; margin: 0 0 15px 0;">
                            Listen to a sample before purchasing
                        </p>

                        <div id="audio-preview-player" class="hidden"></div>

                        <button id="load-audio-preview" 
                                data-ebook-id="<?php echo esc_attr($ebook_id); ?>"
                                style="width: 100%; padding: 14px; background: #EAB308; color: #0D0D0D; border: none; border-radius: 8px; font-weight: 600; font-size: 16px; cursor: pointer; transition: all 0.3s;">
                            ▶ Play Audio Preview
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Book Details -->
                <div style="background: #1A1A1A; border-radius: 12px; padding: 25px;">
                    <h3 style="color: #EAB308; font-size: 16px; font-weight: 600; margin: 0 0 20px 0; text-transform: uppercase; letter-spacing: 1px;">
                        Book Details
                    </h3>
                    
                    <div style="display: grid; gap: 12px;">
                        <?php if ($author) : ?>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #2A2A2A;">
                                <span style="color: #9CA3AF;">Author:</span>
                                <span style="color: #FFFFFF; font-weight: 500;"><?php echo esc_html($author); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($publisher) : ?>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #2A2A2A;">
                                <span style="color: #9CA3AF;">Publisher:</span>
                                <span style="color: #FFFFFF; font-weight: 500;"><?php echo esc_html($publisher); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($pages) : ?>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #2A2A2A;">
                                <span style="color: #9CA3AF;">Pages:</span>
                                <span style="color: #FFFFFF; font-weight: 500;"><?php echo esc_html($pages); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($language) : ?>
                            <div style="display: flex; justify-content: space-between; padding-bottom: 12px; border-bottom: 1px solid #2A2A2A;">
                                <span style="color: #9CA3AF;">Language:</span>
                                <span style="color: #FFFFFF; font-weight: 500;"><?php echo esc_html($language); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($isbn) : ?>
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: #9CA3AF;">ISBN:</span>
                                <span style="color: #FFFFFF; font-weight: 500; font-family: monospace;"><?php echo esc_html($isbn); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Purchase Form -->
            <div>
                <!-- Purchase Form Card -->
                <div style="background: #1A1A1A; border-radius: 12px; padding: 40px; position: sticky; top: 40px;">
                    
                    <h2 style="color: #FFFFFF; font-size: 24px; font-weight: 700; margin: 0 0 10px 0;">
                        Purchase This Ebook
                    </h2>
                    
                    <p style="color: #9CA3AF; font-size: 14px; margin: 0 0 30px 0;">
                        Secure checkout • Instant download
                    </p>

                    <!-- Stock Status -->
                    <?php if ($in_stock) : ?>
                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10B981; border-radius: 8px; padding: 12px; margin-bottom: 25px;">
                            <p style="color: #10B981; font-weight: 600; margin: 0; font-size: 14px;">
                                ✓ In Stock - Available Now
                            </p>
                        </div>
                    <?php else : ?>
                        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #EF4444; border-radius: 8px; padding: 12px; margin-bottom: 25px;">
                            <p style="color: #EF4444; font-weight: 600; margin: 0; font-size: 14px;">
                                ✗ Out of Stock
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php if ($in_stock) : ?>

                        <!-- PURCHASE TYPE TOGGLE (admin-controlled) -->
                        <?php if ($enable_bulk_inquiry) : ?>
                        <div style="margin-bottom: 25px;">
                            <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 12px; font-size: 14px;">
                                I am purchasing as:
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <label id="type-individual-label" style="display: flex; align-items: center; gap: 10px; background: #0D0D0D; border: 2px solid #EAB308; border-radius: 8px; padding: 14px; cursor: pointer; transition: border-color 0.3s;">
                                    <input type="radio" name="purchase_type" id="purchase-type-individual" value="individual" checked style="accent-color: #EAB308;">
                                    <div>
                                        <span style="color: #FFFFFF; font-weight: 600; font-size: 14px; display: block;">Individual</span>
                                        <span style="color: #9CA3AF; font-size: 12px;">Personal copy</span>
                                    </div>
                                </label>
                                <label id="type-bulk-label" style="display: flex; align-items: center; gap: 10px; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 14px; cursor: pointer; transition: border-color 0.3s;">
                                    <input type="radio" name="purchase_type" id="purchase-type-bulk" value="bulk" style="accent-color: #EAB308;">
                                    <div>
                                        <span style="color: #FFFFFF; font-weight: 600; font-size: 14px; display: block;">Bulk / Institutional</span>
                                        <span style="color: #9CA3AF; font-size: 12px;"><?php echo intval($bulk_min); ?>+ copies</span>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- INDIVIDUAL PURCHASE SECTION (existing form wrapped) -->
                        <div id="individual-purchase-section">

                        <!-- Purchase Form -->
                        <form id="ebook-purchase-form" method="post">
                            <input type="hidden" name="ebook_id" value="<?php echo esc_attr($ebook_id); ?>">

                            <!-- FORMAT SELECTION -->
                            <?php
                            // Get available formats
                            $has_ebook = get_post_meta($ebook_id, '_ebook_format_ebook', true);
                            $has_audio = get_post_meta($ebook_id, '_ebook_format_audio', true);
                            $has_printed = get_post_meta($ebook_id, '_ebook_format_printed', true);
                            
                            // Get shipping fee for printed
                            $shipping_fee = get_post_meta($ebook_id, '_ebook_shipping_fee', true) ?: 0;
                            
                            // If nothing selected, default to ebook
                            if (!$has_ebook && !$has_audio && !$has_printed) {
                                $has_ebook = '1';
                            }
                            
                            // Count how many formats are available
                            $format_count = 0;
                            if ($has_ebook == '1') $format_count++;
                            if ($has_audio == '1') $format_count++;
                            if ($has_printed == '1') $format_count++;
                            ?>
                            
                            <?php if ($format_count > 1): ?>
                            <!-- Show format selection if multiple formats available -->
                            <div style="margin-bottom: 25px;">
                                <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 12px; font-size: 14px;">
                                    Select Format *
                                </label>
                                
                                <?php if ($has_ebook == '1'): ?>
                                <label class="format-option" data-format="ebook" style="display: block; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 14px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s;">
                                    <input type="radio" name="format" value="ebook" checked style="margin-right: 10px;">
                                    <span style="color: #FFFFFF; font-weight: 500; font-size: 15px;">
                                        📖 Digital Ebook (PDF/EPUB) - Instant Download
                                    </span>
                                </label>
                                <?php endif; ?>
                                
                                <?php if ($has_audio == '1'): ?>
                                <label class="format-option" data-format="audio" style="display: block; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 14px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s;">
                                    <input type="radio" name="format" value="audio" style="margin-right: 10px;">
                                    <span style="color: #FFFFFF; font-weight: 500; font-size: 15px;">
                                        🎧 Audiobook (MP3) - Instant Download
                                    </span>
                                </label>
                                <?php endif; ?>
                                
                                <?php if ($has_printed == '1'): ?>
                                <label class="format-option" data-format="printed" style="display: block; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 14px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s;">
                                    <input type="radio" name="format" value="printed" style="margin-right: 10px;" data-shipping-fee="<?php echo esc_attr($shipping_fee); ?>">
                                    <span style="color: #FFFFFF; font-weight: 500; font-size: 15px;">
                                        📦 Printed Book (Physical Copy) - Shipping Required
                                    </span>
                                    <?php if ($shipping_fee > 0): ?>
                                    <span style="display: block; color: #9CA3AF; font-size: 13px; margin-top: 5px; margin-left: 30px;">
                                        + <?php echo esc_html($currency_symbol . number_format($shipping_fee, 2)); ?> shipping fee
                                    </span>
                                    <?php endif; ?>
                                </label>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <!-- Hidden format field if only one format -->
                            <input type="hidden" name="format" value="<?php 
                                echo $has_audio == '1' ? 'audio' : ($has_printed == '1' ? 'printed' : 'ebook'); 
                            ?>">
                            <?php endif; ?>

                            <!-- SHIPPING ADDRESS FIELDS (Hidden by default, shown when printed selected) -->
                            <div id="shipping-fields" style="display: none; margin-bottom: 25px; padding: 20px; background: #0D0D0D; border: 2px solid #EAB308; border-radius: 8px;">
                                <h3 style="color: #EAB308; font-size: 16px; font-weight: 600; margin: 0 0 15px 0;">
                                    📦 Shipping Address
                                </h3>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        Street Address *
                                    </label>
                                    <input type="text" 
                                           name="shipping_address" 
                                           id="shipping_address"
                                           placeholder="123 Main Street"
                                           style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div>
                                        <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                            City *
                                        </label>
                                        <input type="text" 
                                               name="shipping_city" 
                                               id="shipping_city"
                                               placeholder="City"
                                               style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                    </div>
                                    <div>
                                        <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                            State/Province *
                                        </label>
                                        <input type="text" 
                                               name="shipping_state" 
                                               id="shipping_state"
                                               placeholder="State"
                                               style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                    <div>
                                        <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                            Postal Code *
                                        </label>
                                        <input type="text" 
                                               name="shipping_postal" 
                                               id="shipping_postal"
                                               placeholder="12345"
                                               style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                    </div>
                                    <div>
                                        <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                            Country *
                                        </label>
                                        <input type="text" 
                                               name="shipping_country" 
                                               id="shipping_country"
                                               placeholder="Country"
                                               style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        Phone Number *
                                    </label>
                                    <input type="tel" 
                                           name="shipping_phone" 
                                           id="shipping_phone"
                                           placeholder="+1 234 567 8900"
                                           style="width: 100%; padding: 12px; background: #1A1A1A; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 14px;">
                                </div>
                            </div>

                            <!-- Name Field -->
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                    Full Name *
                                </label>
                                <input type="text" 
                                       name="user_name" 
                                       required 
                                       placeholder="Enter your full name"
                                       style="width: 100%; padding: 14px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px;">
                            </div>

                            <!-- Email Field -->
                            <div style="margin-bottom: 20px;">
                                <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                    Email Address *
                                </label>
                                <input type="email" 
                                       name="user_email" 
                                       required 
                                       placeholder="your@email.com"
                                       style="width: 100%; padding: 14px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px;">
                                <p style="color: #9CA3AF; font-size: 12px; margin: 6px 0 0 0;">
                                    <span id="email-note-digital">Download link will be sent to this email</span>
                                    <span id="email-note-printed" style="display: none;">Order confirmation will be sent to this email</span>
                                </p>
                            </div>

                            <!-- Quantity (Optional) -->
                            <?php if ($enable_quantity_selector) : ?>
                                <div style="margin-bottom: 25px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        Quantity
                                    </label>
                                    <input type="number" 
                                           name="quantity" 
                                           id="quantity-input"
                                           min="1" 
                                           max="<?php echo $unlimited ? '999' : esc_attr($quantity); ?>" 
                                           value="1"
                                           data-price="<?php echo esc_attr($price); ?>"
                                           data-shipping-fee="<?php echo esc_attr($shipping_fee); ?>"
                                           style="width: 100%; padding: 14px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px;">
                                </div>
                            <?php endif; ?>

                            <!-- ORDER BUMP / ADD-ON (admin-controlled) -->
                            <?php if ($enable_order_bump) : ?>
                            <div style="background: #0D0D0D; border: 2px dashed #EAB308; border-radius: 8px; padding: 18px; margin-bottom: 25px;">
                                <div style="display: flex; align-items: flex-start; gap: 12px;">
                                    <div style="flex: 1;">
                                        <p style="color: #EAB308; font-size: 11px; font-weight: 700; margin: 0 0 6px 0; text-transform: uppercase; letter-spacing: 1px;">Add to Your Order</p>
                                        <p style="color: #FFFFFF; font-size: 15px; font-weight: 700; margin: 0 0 6px 0;"><?php echo esc_html($bump_name); ?></p>
                                        <p style="color: #9CA3AF; font-size: 13px; margin: 0 0 8px 0; line-height: 1.5;"><?php echo esc_html($bump_desc); ?></p>
                                        <?php if ($bump_price > 0) : ?>
                                        <p style="color: #EAB308; font-size: 18px; font-weight: 700; margin: 0;">
                                            + <?php echo esc_html($currency_symbol . number_format($bump_price, 2)); ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div style="flex-shrink: 0; padding-top: 2px;">
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" id="order-bump-checkbox" name="add_order_bump" value="1"
                                                   data-bump-price="<?php echo esc_attr($bump_price); ?>"
                                                   style="width: 20px; height: 20px; accent-color: #EAB308; cursor: pointer;">
                                            <span style="color: #EAB308; font-size: 12px; font-weight: 700; white-space: nowrap;">ADD TO ORDER</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Payment Gateway Selection -->
                            <div style="margin-bottom: 25px;">
                                <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 12px; font-size: 14px;">
                                    Payment Method *
                                </label>
                                
                                <?php
                                $gateways = array(
                                    'paystack' => array('name' => 'Paystack', 'icon' => '💳'),
                                    'flutterwave' => array('name' => 'Flutterwave', 'icon' => '💰'),
                                    'stripe' => array('name' => 'Stripe', 'icon' => '💷'),
                                    'paypal' => array('name' => 'PayPal', 'icon' => '🅿️'),
                                );

                                $first_gateway = true;
                                foreach ($gateways as $key => $gateway) :
                                    $enabled = get_option('skillscore_ebook_enable_' . $key);
                                    
                                    if ($enabled == '1' || $enabled === 1 || $enabled === true || $enabled === 'true') :
                                ?>
                                    <label class="payment-method-option" 
                                           style="display: block; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 14px; margin-bottom: 10px; cursor: pointer; transition: all 0.3s;">
                                        <input type="radio" 
                                               name="gateway" 
                                               value="<?php echo esc_attr($key); ?>" 
                                               <?php echo $first_gateway ? 'checked' : ''; ?>
                                               style="margin-right: 10px;">
                                        <span style="color: #FFFFFF; font-weight: 500; font-size: 15px;">
                                            <?php echo esc_html($gateway['icon'] . ' ' . $gateway['name']); ?>
                                        </span>
                                    </label>
                                <?php
                                        $first_gateway = false;
                                    endif;
                                endforeach;
                                ?>
                            </div>

                            <!-- Total Price Display -->
                            <div style="background: #0D0D0D; border: 2px solid #EAB308; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                    <span style="color: #9CA3AF; font-size: 14px;">Subtotal:</span>
                                    <span class="subtotal-display" style="color: #FFFFFF; font-size: 16px; font-weight: 500;">
                                        <?php echo esc_html($currency_symbol . number_format($price, 2)); ?>
                                    </span>
                                </div>
                                <div id="shipping-fee-row" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #2A2A2A;">
                                    <span style="color: #9CA3AF; font-size: 14px;">Shipping:</span>
                                    <span class="shipping-fee-display" style="color: #FFFFFF; font-size: 16px; font-weight: 500;">
                                        <?php echo esc_html($currency_symbol . number_format($shipping_fee, 2)); ?>
                                    </span>
                                </div>
                                <?php if ($enable_order_bump && $bump_price > 0) : ?>
                                <div id="bump-fee-row" style="display: none; justify-content: space-between; align-items: center; margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #2A2A2A;">
                                    <span style="color: #9CA3AF; font-size: 14px;">Add-on:</span>
                                    <span class="bump-fee-display" style="color: #EAB308; font-size: 16px; font-weight: 500;">
                                        + <?php echo esc_html($currency_symbol . number_format($bump_price, 2)); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span style="color: #FFFFFF; font-size: 18px; font-weight: 600;">Total:</span>
                                    <span class="total-price-display" style="color: #EAB308; font-size: 28px; font-weight: 700;">
                                        <?php echo esc_html($currency_symbol . number_format($price, 2)); ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit"
                                    id="purchase-submit-btn"
                                    data-text-ebook="<?php echo $enable_dynamic_button ? 'GET INSTANT ACCESS' : 'Complete Purchase →'; ?>"
                                    data-text-audio="<?php echo $enable_dynamic_button ? 'GET INSTANT ACCESS' : 'Complete Purchase →'; ?>"
                                    data-text-printed="<?php echo $enable_dynamic_button ? 'COMPLETE MY ORDER' : 'Complete Purchase →'; ?>"
                                    style="width: 100%; padding: 18px; background: #EAB308; color: #0D0D0D; border: none; border-radius: 8px; font-weight: 700; font-size: 18px; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px;">
                                <?php echo $enable_dynamic_button ? 'GET INSTANT ACCESS' : 'Complete Purchase →'; ?>
                            </button>

                            <!-- Security Note -->
                            <p style="color: #9CA3AF; font-size: 12px; text-align: center; margin: 15px 0 0 0;">
                                🔒 Secure payment • Your data is protected
                            </p>
                        </form>

                        </div><!-- /#individual-purchase-section -->

                        <!-- BULK INQUIRY FORM (admin-controlled) -->
                        <?php if ($enable_bulk_inquiry) : ?>
                        <div id="bulk-inquiry-section" style="display: none;">

                            <div style="background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
                                <p style="color: #9CA3AF; font-size: 13px; margin: 0; line-height: 1.6;">
                                    Bring this book into your institution, cohort, summit, or community. Submit the form below and our team will follow up with bulk pricing, distribution options, and next steps.
                                </p>
                            </div>

                            <form id="bulk-inquiry-form" method="post">
                                <input type="hidden" name="ebook_id" value="<?php echo esc_attr($ebook_id); ?>">

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Contact Name *</label>
                                    <input type="text" name="contact_name" required placeholder="Your full name"
                                           style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Email Address *</label>
                                    <input type="email" name="contact_email" required placeholder="your@organization.com"
                                           style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        Phone Number <span style="color: #6B7280; font-size: 12px; font-weight: 400;">(optional)</span>
                                    </label>
                                    <input type="tel" name="contact_phone" placeholder="+1 234 567 8900"
                                           style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Organization / Institution Name *</label>
                                    <input type="text" name="org_name" required placeholder="Name of your organization"
                                           style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Organization Type *</label>
                                    <select name="org_type" required
                                            style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                        <option value="" disabled selected style="color: #6B7280;">Select organization type...</option>
                                        <option value="Youth Program / Mentorship">Youth Program / Mentorship</option>
                                        <option value="Leadership Institute">Leadership Institute</option>
                                        <option value="School / University">School / University</option>
                                        <option value="Faith-Based Organization">Faith-Based Organization</option>
                                        <option value="Conference / Event">Conference / Event</option>
                                        <option value="Corporate / Business">Corporate / Business</option>
                                        <option value="Entrepreneurship Community">Entrepreneurship Community</option>
                                        <option value="Civic / NGO">Civic / NGO</option>
                                        <option value="Reading Group / Book Club">Reading Group / Book Club</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>

                                <!-- Bulk Copy Quantity Presets -->
                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Number of Copies Needed *</label>
                                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; margin-bottom: 10px;">
                                        <?php foreach (array(10, 25, 50, 100, 250, 500) as $preset) : ?>
                                        <label class="bulk-preset-btn" style="display: flex; align-items: center; justify-content: center; background: #0D0D0D; border: 2px solid #2A2A2A; border-radius: 8px; padding: 10px; cursor: pointer; transition: all 0.3s; text-align: center;">
                                            <input type="radio" name="copies_preset" value="<?php echo intval($preset); ?>" style="display: none;">
                                            <span style="color: #FFFFFF; font-weight: 600; font-size: 14px;"><?php echo intval($preset); ?>+</span>
                                        </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <input type="number" name="copies_needed" id="copies-needed-input" required
                                           min="<?php echo intval($bulk_min); ?>"
                                           placeholder="Or enter exact number (min <?php echo intval($bulk_min); ?>)"
                                           style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box;">
                                    <p style="color: #6B7280; font-size: 12px; margin: 5px 0 0 0;">Select a preset above or type a specific number. Minimum <?php echo intval($bulk_min); ?> copies.</p>
                                </div>

                                <div style="margin-bottom: 16px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">Purpose / Use Case *</label>
                                    <textarea name="purpose" required rows="4"
                                              placeholder="Describe how you plan to use the books — e.g., youth leadership program, conference distribution, curriculum, sponsored copies..."
                                              style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box; resize: vertical;"></textarea>
                                </div>

                                <div style="margin-bottom: 25px;">
                                    <label style="display: block; color: #FFFFFF; font-weight: 600; margin-bottom: 8px; font-size: 14px;">
                                        Additional Notes <span style="color: #6B7280; font-size: 12px; font-weight: 400;">(optional)</span>
                                    </label>
                                    <textarea name="notes" rows="3"
                                              placeholder="Any other details, timeline, partnership ideas, or questions..."
                                              style="width: 100%; padding: 13px; background: #0D0D0D; border: 1px solid #2A2A2A; border-radius: 8px; color: #FFFFFF; font-size: 15px; box-sizing: border-box; resize: vertical;"></textarea>
                                </div>

                                <button type="submit" id="bulk-submit-btn"
                                        style="width: 100%; padding: 18px; background: #EAB308; color: #0D0D0D; border: none; border-radius: 8px; font-weight: 700; font-size: 18px; cursor: pointer; transition: all 0.3s; text-transform: uppercase; letter-spacing: 1px;">
                                    SUBMIT INQUIRY
                                </button>

                                <p style="color: #9CA3AF; font-size: 12px; text-align: center; margin: 15px 0 0 0;">
                                    Our team will review your inquiry and follow up with pricing and next steps.
                                </p>
                            </form>

                            <!-- Bulk success message (hidden) -->
                            <div id="bulk-inquiry-success" style="display: none; background: rgba(16,185,129,0.1); border: 2px solid #10B981; border-radius: 8px; padding: 24px; text-align: center;">
                                <h3 style="color: #10B981; font-size: 20px; margin: 0 0 12px 0;">Your Inquiry Has Been Received</h3>
                                <p style="color: #E5E7EB; font-size: 15px; margin: 0 0 8px 0;">Thank you for your interest in bringing <em><?php the_title(); ?></em> to your organization or community.</p>
                                <p style="color: #9CA3AF; font-size: 14px; margin: 0;">A member of our team will review your request and follow up with bulk pricing, distribution options, and next steps. Please keep an eye on your inbox.</p>
                            </div>

                        </div><!-- /#bulk-inquiry-section -->
                        <?php endif; ?>

                    <?php endif; ?>
                </div>
            </div>

        </div>

        <!-- Description Section (Full Width) -->
        <?php if (get_the_content()) : ?>
            <div style="background: #1A1A1A; border-radius: 12px; padding: 40px; margin-bottom: 40px;">
                <h2 style="color: #EAB308; font-size: 24px; font-weight: 700; margin: 0 0 20px 0;">
                    About This Book
                </h2>
                <div style="color: #E5E7EB; font-size: 16px; line-height: 1.8;">
                    <?php the_content(); ?>
                </div>
            </div>
        <?php endif; ?>

    </div>

</div>

<style>
    /* Hover effects */
    #load-audio-preview:hover {
        background: #FCD34D !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(234, 179, 8, 0.3);
    }

    button[type="submit"]:hover {
        background: #FCD34D !important;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(234, 179, 8, 0.4);
    }

    .payment-method-option:hover,
    .format-option:hover {
        border-color: #EAB308 !important;
        background: rgba(234, 179, 8, 0.05) !important;
    }

    .payment-method-option input:checked + span,
    .format-option input:checked + span {
        color: #EAB308 !important;
    }

    input:focus, textarea:focus, select:focus {
        outline: none;
        border-color: #EAB308 !important;
    }

    .bulk-preset-btn:hover {
        border-color: #EAB308 !important;
        background: rgba(234, 179, 8, 0.05) !important;
    }

    .bulk-preset-btn.active {
        border-color: #EAB308 !important;
        background: rgba(234, 179, 8, 0.1) !important;
    }

    .bulk-preset-btn.active span {
        color: #EAB308 !important;
    }

    /* Responsive */
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {

    var currencySymbol = '<?php echo esc_js($currency_symbol); ?>';
    var basePrice      = <?php echo floatval($price); ?>;
    var bumpPrice      = <?php echo floatval($bump_price); ?>;

    // ===== (EXISTING) Show/hide shipping fields based on format selection =====
    $('input[name="format"]').on('change', function() {
        var selectedFormat = $(this).val();
        var $shippingFields = $('#shipping-fields');
        var $shippingFeeRow = $('#shipping-fee-row');
        var shippingFee = parseFloat($(this).data('shipping-fee')) || 0;

        if (selectedFormat === 'printed') {
            $shippingFields.slideDown(300);
            $('#shipping_address, #shipping_city, #shipping_state, #shipping_postal, #shipping_country, #shipping_phone').attr('required', true);
            if (shippingFee > 0) {
                $shippingFeeRow.css('display', 'flex');
            }
            $('#email-note-digital').hide();
            $('#email-note-printed').show();
        } else {
            $shippingFields.slideUp(300);
            $('#shipping_address, #shipping_city, #shipping_state, #shipping_postal, #shipping_country, #shipping_phone').attr('required', false);
            $shippingFeeRow.hide();
            $('#email-note-digital').show();
            $('#email-note-printed').hide();
        }

        // NEW: Dynamic button text
        var $btn = $('#purchase-submit-btn');
        if ($btn.length) {
            $btn.text($btn.data('text-' + selectedFormat) || $btn.data('text-ebook'));
        }

        updateTotal();
    });

    // ===== (EXISTING) Update total price when quantity changes =====
    $('#quantity-input').on('input change', function() {
        updateTotal();
    });

    // ===== (EXISTING + EXTENDED) Recalculate total =====
    function updateTotal() {
        var quantity     = parseInt($('#quantity-input').val()) || 1;
        var bprice       = parseFloat($('#quantity-input').data('price')) || basePrice;
        var selectedFmt  = $('input[name="format"]:checked').val();
        var shippingFee  = 0;

        if (selectedFmt === 'printed') {
            shippingFee = parseFloat($('input[name="format"]:checked').data('shipping-fee')) || 0;
        }

        var bumpTotal = ($('#order-bump-checkbox').is(':checked')) ? bumpPrice : 0;
        var subtotal  = quantity * bprice;
        var total     = subtotal + shippingFee + bumpTotal;

        $('.subtotal-display').text(currencySymbol + subtotal.toFixed(2));
        $('.shipping-fee-display').text(currencySymbol + shippingFee.toFixed(2));
        $('.bump-fee-display').text('+ ' + currencySymbol + bumpTotal.toFixed(2));
        $('.total-price-display').text(currencySymbol + total.toFixed(2));
    }

    // Initial calculation
    updateTotal();

    // ===== NEW: Purchase type toggle =====
    $('input[name="purchase_type"]').on('change', function() {
        var type = $(this).val();
        if (type === 'bulk') {
            $('#individual-purchase-section').hide();
            $('#bulk-inquiry-section').show();
            $('#type-bulk-label').css('border-color', '#EAB308');
            $('#type-individual-label').css('border-color', '#2A2A2A');
        } else {
            $('#individual-purchase-section').show();
            $('#bulk-inquiry-section').hide();
            $('#type-individual-label').css('border-color', '#EAB308');
            $('#type-bulk-label').css('border-color', '#2A2A2A');
        }
    });

    // ===== NEW: Order bump checkbox =====
    $('#order-bump-checkbox').on('change', function() {
        if ($(this).is(':checked') && bumpPrice > 0) {
            $('#bump-fee-row').css('display', 'flex');
        } else {
            $('#bump-fee-row').hide();
        }
        updateTotal();
    });

    // ===== NEW: Bulk copy preset buttons =====
    $('.bulk-preset-btn').on('click', function() {
        var val = $(this).find('input[type="radio"]').val();
        $(this).find('input[type="radio"]').prop('checked', true);
        $('.bulk-preset-btn').removeClass('active');
        $(this).addClass('active');
        $('#copies-needed-input').val(val);
    });

    // ===== NEW: Bulk inquiry form AJAX =====
    $('#bulk-inquiry-form').on('submit', function(e) {
        e.preventDefault();
        var $form   = $(this);
        var $btn    = $('#bulk-submit-btn');
        var origTxt = $btn.html();

        if (!$form[0].checkValidity()) { $form[0].reportValidity(); return; }

        $btn.prop('disabled', true).html('Submitting...');

        $.ajax({
            url:     skillscoreEbook.ajaxUrl,
            type:    'POST',
            timeout: 30000,
            data: {
                action:        'skillscore_submit_bulk_inquiry',
                nonce:         skillscoreEbook.nonce,
                ebook_id:      $form.find('input[name="ebook_id"]').val(),
                contact_name:  $form.find('input[name="contact_name"]').val(),
                contact_email: $form.find('input[name="contact_email"]').val(),
                contact_phone: $form.find('input[name="contact_phone"]').val(),
                org_name:      $form.find('input[name="org_name"]').val(),
                org_type:      $form.find('select[name="org_type"]').val(),
                copies_needed: $form.find('input[name="copies_needed"]').val(),
                purpose:       $form.find('textarea[name="purpose"]').val(),
                notes:         $form.find('textarea[name="notes"]').val()
            },
            success: function(response) {
                if (response.success) {
                    $form.hide();
                    $('#bulk-inquiry-success').show();
                } else {
                    var msg = (response.data && response.data.message) ? response.data.message : 'Submission failed. Please try again.';
                    alert(msg);
                    $btn.prop('disabled', false).html(origTxt);
                }
            },
            error: function() {
                alert('Network error. Please try again.');
                $btn.prop('disabled', false).html(origTxt);
            }
        });
    });

});
</script>