<?php
/**
 * Template: Single Ebook View
 *
 * @package SkillScore_Ebook
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check for payment success
$payment_success = isset($_GET['payment_success']) && $_GET['payment_success'] === '1';
$download_token = isset($_GET['download_token']) ? sanitize_text_field($_GET['download_token']) : '';

// Get additional meta
$terms = get_the_terms($ebook_id, 'ebook_category');
$category = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
?>

<div class="skillscore-ebook-single fade-in">

    <?php if ($payment_success && $download_token): ?>
        <!-- Success Message -->
        <div class="success-message">
            <div class="success-message-header">
                <svg fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <h3 class="success-message-title"><?php _e('PAYMENT SUCCESSFUL!', 'skillscore-ebook'); ?></h3>
            </div>
            <p><?php _e('Your payment has been processed successfully. You can now download your ebook.', 'skillscore-ebook'); ?></p>
            <?php
            $download_handler = new SkillScore_Ebook_Download_Handler();
            $download_link = $download_handler->get_download_link($download_token);
            ?>
            <a href="<?php echo esc_url($download_link); ?>" class="btn-primary" style="display: inline-flex; align-items: center;">
                <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
                <?php _e('Download Now', 'skillscore-ebook'); ?>
            </a>
        </div>
    <?php endif; ?>

    <div class="ebook-single-grid">

        <!-- Left Column: Cover Image -->
        <div>
            <div class="single-cover-sticky">
                <?php if (has_post_thumbnail($ebook_id)): ?>
                    <?php echo get_the_post_thumbnail($ebook_id, 'large', array('class' => 'single-cover-image glow-box')); ?>
                <?php else: ?>
                    <div class="single-cover-placeholder glow-box">
                        <svg style="width: 96px; height: 96px; color: var(--rich-black);" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <!-- Price Display -->
                <div style="background: var(--neon-yellow); color: var(--rich-black); text-align: center; padding: 1.5rem; margin: 1.5rem 0; clip-path: polygon(12px 0, 100% 0, 100% calc(100% - 12px), calc(100% - 12px) 100%, 0 100%, 0 12px);">
                    <div style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.25rem; text-transform: uppercase; letter-spacing: 0.05em;">Price</div>
                    <div style="font-size: 2.5rem; font-weight: 800; line-height: 1;"><?php echo esc_html($currency_symbol . number_format($price, 2)); ?></div>
                </div>

                <!-- Purchase Buttons -->
                <?php if ($in_stock): ?>
                    <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                        <button onclick="document.getElementById('ebook-purchase-form').scrollIntoView({behavior: 'smooth'})"
                                class="btn-primary" style="flex: 1;">
                            <?php _e('BUY NOW', 'skillscore-ebook'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div style="background: #ef4444; color: white; text-align: center; padding: 1rem; border-radius: 8px; font-weight: 700; margin-bottom: 1.5rem;">
                        <?php _e('OUT OF STOCK', 'skillscore-ebook'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Details -->
        <div>
            <!-- Title & Author -->
            <div style="margin-bottom: 2rem;">
                <h1 class="single-title glow-text">
                    <?php echo esc_html(strtoupper(get_the_title($ebook_id))); ?>
                </h1>
                <?php if (!empty($author)): ?>
                    <p class="single-author">
                        <?php _e('by', 'skillscore-ebook'); ?> <?php echo esc_html($author); ?>
                    </p>
                <?php endif; ?>
                <?php if ($category): ?>
                    <p class="single-category"><?php echo esc_html($category); ?></p>
                <?php endif; ?>
            </div>

            <!-- Meta Information -->
            <div class="single-meta">
                <div class="ebook-rating">
                    <div class="stars">
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star">★</span>
                        <span class="star empty">★</span>
                    </div>
                    <span style="font-size: 1.125rem; font-weight: 600;">4.0</span>
                </div>
                <?php if (!empty($pages)): ?>
                    <span class="single-meta-divider">|</span>
                    <span class="single-meta-item"><?php echo esc_html($pages); ?> <?php _e('pages', 'skillscore-ebook'); ?></span>
                <?php endif; ?>
                <span class="single-meta-divider">|</span>
                <span class="single-meta-item"><?php _e('Published', 'skillscore-ebook'); ?> <?php echo get_the_date('Y', $ebook_id); ?></span>
            </div>

            <!-- Description -->
            <div style="border-top: 2px solid var(--light-gray); border-bottom: 2px solid var(--light-gray); padding: 1.5rem 0; margin: 2rem 0;">
                <h3 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 1rem;"><?php _e('ABOUT THIS BOOK', 'skillscore-ebook'); ?></h3>
                <div style="color: #d1d5db; line-height: 1.75;">
                    <?php
                    $content = get_post_field('post_content', $ebook_id);
                    if ($content) {
                        echo wpautop(wp_trim_words($content, 50));
                    } else {
                        echo wpautop(get_the_excerpt($ebook_id));
                    }
                    ?>
                </div>
            </div>

            <!-- Audio Preview -->
            <?php if ($enable_audio): ?>
                <div class="audio-preview-section">
                    <div class="audio-preview-header">
                        <h3 class="audio-preview-title">
                            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072m2.828-9.9a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            </svg>
                            <span><?php _e('AUDIO PREVIEW', 'skillscore-ebook'); ?></span>
                        </h3>
                        <span class="audio-preview-chapter"><?php _e('Sample Audio', 'skillscore-ebook'); ?></span>
                    </div>

                    <div class="audio-player">
                        <div class="audio-controls">
                            <button id="load-audio-preview" data-ebook-id="<?php echo esc_attr($ebook_id); ?>" class="audio-play-btn">
                                <div class="audio-play-icon">
                                    <svg fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M8 5v14l11-7z"></path>
                                    </svg>
                                </div>
                                <div class="audio-info-text">
                                    <div class="audio-title"><?php _e('Sample Audio', 'skillscore-ebook'); ?></div>
                                    <div class="audio-duration">3:45</div>
                                </div>
                            </button>

                            <div class="audio-time-display">
                                <span id="currentTime">0:00</span>
                                <span style="color: #6b7280;">/</span>
                                <span id="duration">3:45</span>
                            </div>
                        </div>

                        <div class="audio-progress" id="audio-progress-bar">
                            <div class="audio-progress-bar" id="progressBar"></div>
                        </div>

                        <div id="audio-preview-player" class="hidden">
                            <audio controls style="width: 100%; margin-top: 1rem;">
                                <source id="audio-preview-source" type="audio/mpeg">
                            </audio>
                        </div>

                        <div class="audio-footer">
                            <span>🎧 <?php _e('Listen to a sample of the audiobook version', 'skillscore-ebook'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Text Preview -->
            <?php if ($enable_preview): ?>
                <div class="text-preview-section">
                    <h3 class="text-preview-title">
                        <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <span><?php _e('TEXT PREVIEW', 'skillscore-ebook'); ?></span>
                    </h3>

                    <div class="preview-text">
                        <h4><?php _e('Preview', 'skillscore-ebook'); ?></h4>
                        <?php
                        $preview_content = get_the_excerpt($ebook_id);
                        if (!$preview_content) {
                            $preview_content = get_post_field('post_content', $ebook_id);
                        }
                        echo wpautop(wp_trim_words($preview_content, 150));
                        ?>
                        <p style="font-size: 0.875rem; color: #6b7280; font-style: italic; margin-top: 1.5rem;">
                            <?php _e('Purchase to read the complete ebook...', 'skillscore-ebook'); ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Key Features -->
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">📚</div>
                    <div class="feature-title"><?php _e('Instant Access', 'skillscore-ebook'); ?></div>
                    <div class="feature-description"><?php _e('Download and start reading immediately', 'skillscore-ebook'); ?></div>
                </div>

                <div class="feature-box">
                    <div class="feature-icon">📱</div>
                    <div class="feature-title"><?php _e('Multi-Device', 'skillscore-ebook'); ?></div>
                    <div class="feature-description"><?php _e('Read on phone, tablet, or computer', 'skillscore-ebook'); ?></div>
                </div>

                <?php if ($enable_audio): ?>
                <div class="feature-box">
                    <div class="feature-icon">🎧</div>
                    <div class="feature-title"><?php _e('Audio Included', 'skillscore-ebook'); ?></div>
                    <div class="feature-description"><?php _e('Audiobook version included free', 'skillscore-ebook'); ?></div>
                </div>
                <?php endif; ?>

                <div class="feature-box">
                    <div class="feature-icon">💾</div>
                    <div class="feature-title"><?php _e('Secure Download', 'skillscore-ebook'); ?></div>
                    <div class="feature-description"><?php _e('Yours forever, secure delivery', 'skillscore-ebook'); ?></div>
                </div>
            </div>

            <!-- Purchase Form -->
            <?php if ($in_stock): ?>
                <div style="background: var(--dark-gray); padding: 1.5rem; clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px); border: 2px solid var(--light-gray); margin-top: 2rem;">
                    <h3 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 1rem; color: var(--neon-yellow);">
                        <?php _e('PURCHASE THIS EBOOK', 'skillscore-ebook'); ?>
                    </h3>
                    <form id="ebook-purchase-form">
                        <input type="hidden" name="ebook_id" value="<?php echo esc_attr($ebook_id); ?>">

                        <!-- Quantity Selector -->
                        <?php if ($enable_quantity_selector): ?>
                            <div style="margin-bottom: 1rem;">
                                <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                    <?php _e('Quantity', 'skillscore-ebook'); ?>
                                </label>
                                <input type="number" name="quantity" value="1" min="1"
                                       <?php if (!$unlimited): ?>max="<?php echo esc_attr($quantity); ?>"<?php endif; ?>
                                       style="width: 100%; padding: 12px 16px; background: var(--rich-black); border: 2px solid var(--light-gray); color: var(--white); border-radius: 8px; font-size: 1rem;">
                            </div>
                        <?php else: ?>
                            <input type="hidden" name="quantity" value="1">
                        <?php endif; ?>

                        <!-- Customer Information -->
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                <?php _e('Your Name', 'skillscore-ebook'); ?>
                            </label>
                            <input type="text" name="user_name" required
                                   style="width: 100%; padding: 12px 16px; background: var(--rich-black); border: 2px solid var(--light-gray); color: var(--white); border-radius: 8px; font-size: 1rem;">
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                <?php _e('Your Email', 'skillscore-ebook'); ?>
                            </label>
                            <input type="email" name="user_email" required
                                   style="width: 100%; padding: 12px 16px; background: var(--rich-black); border: 2px solid var(--light-gray); color: var(--white); border-radius: 8px; font-size: 1rem;">
                        </div>

                        <!-- Payment Gateway Selection -->
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; font-weight: 600; font-size: 0.875rem; margin-bottom: 0.75rem;">
                                <?php _e('Select Payment Method', 'skillscore-ebook'); ?>
                            </label>
                            <div class="payment-methods">
                                <?php if (get_option('skillscore_ebook_enable_paystack')): ?>
                                    <label class="payment-method-option">
                                        <input type="radio" name="gateway" value="paystack" required>
                                        <span style="font-weight: 600;">Paystack</span>
                                    </label>
                                <?php endif; ?>

                                <?php if (get_option('skillscore_ebook_enable_flutterwave')): ?>
                                    <label class="payment-method-option">
                                        <input type="radio" name="gateway" value="flutterwave" required>
                                        <span style="font-weight: 600;">Flutterwave</span>
                                    </label>
                                <?php endif; ?>

                                <?php if (get_option('skillscore_ebook_enable_stripe')): ?>
                                    <label class="payment-method-option">
                                        <input type="radio" name="gateway" value="stripe" required>
                                        <span style="font-weight: 600;">Stripe</span>
                                    </label>
                                <?php endif; ?>

                                <?php if (get_option('skillscore_ebook_enable_paypal')): ?>
                                    <label class="payment-method-option">
                                        <input type="radio" name="gateway" value="paypal" required>
                                        <span style="font-weight: 600;">PayPal</span>
                                    </label>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Purchase Button -->
                        <button type="submit" class="btn-primary" style="width: 100%; display: flex; align-items: center; justify-center;">
                            <svg style="width: 20px; height: 20px; margin-right: 8px;" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/>
                            </svg>
                            <?php _e('PURCHASE NOW', 'skillscore-ebook'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Ebook Details -->
            <?php if (!empty($publisher) || !empty($isbn) || !empty($pages) || !empty($language)): ?>
                <div style="background: #000000; border: 2px solid var(--neon-yellow); padding: 1.5rem; margin-top: 2rem; clip-path: polygon(10px 0, 100% 0, 100% calc(100% - 10px), calc(100% - 10px) 100%, 0 100%, 0 10px);">
                    <h3 style="font-weight: 700; font-size: 1.25rem; margin-bottom: 1rem;"><?php _e('DETAILS', 'skillscore-ebook'); ?></h3>
                    <dl style="display: grid; gap: 0.75rem;">
                        <?php if (!empty($publisher)): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <dt style="color: #9ca3af;"><?php _e('Publisher:', 'skillscore-ebook'); ?></dt>
                                <dd style="font-weight: 600;"><?php echo esc_html($publisher); ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($isbn)): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <dt style="color: #9ca3af;"><?php _e('ISBN:', 'skillscore-ebook'); ?></dt>
                                <dd style="font-family: monospace;"><?php echo esc_html($isbn); ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($pages)): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <dt style="color: #9ca3af;"><?php _e('Pages:', 'skillscore-ebook'); ?></dt>
                                <dd style="font-weight: 600;"><?php echo esc_html($pages); ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($language)): ?>
                            <div style="display: flex; justify-content: space-between;">
                                <dt style="color: #9ca3af;"><?php _e('Language:', 'skillscore-ebook'); ?></dt>
                                <dd style="font-weight: 600;"><?php echo esc_html($language); ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
