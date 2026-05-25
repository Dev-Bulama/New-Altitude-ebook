<?php
/**
 * Campaign Meta Box Class
 * Provides admin UI for managing email campaigns
 * 
 * @package SkillScore_Ebook
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Campaign_Metabox {

    /**
     * Campaign manager instance
     */
    private $campaign_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->campaign_manager = new SkillScore_Email_Campaign();

        // Add meta box - HIGH PRIORITY to ensure it shows
        add_action('add_meta_boxes_ebook', array($this, 'add_campaign_meta_box'), 10);
        
        // Save campaign data
        add_action('save_post_ebook', array($this, 'save_campaigns'), 20, 2);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX handlers
        add_action('wp_ajax_skillscore_delete_campaign', array($this, 'ajax_delete_campaign'));
        add_action('wp_ajax_skillscore_toggle_campaign', array($this, 'ajax_toggle_campaign'));
        add_action('wp_ajax_skillscore_get_campaign_stats', array($this, 'ajax_get_campaign_stats'));
        
        // Debug logging
        error_log('SkillScore Campaign Metabox: Class initialized');
    }

    /**
     * Add campaign meta box to ebook edit screen
     */
    public function add_campaign_meta_box() {
        error_log('SkillScore Campaign: Adding meta box to ebook editor');
        
        add_meta_box(
            'ebook_email_campaigns',
            '🚀 AUTOMATED EMAIL CAMPAIGNS - Send Follow-Up Emails After Purchase',
            array($this, 'render_campaign_meta_box'),
            'ebook',
            'normal',
            'high'
        );
        
        error_log('SkillScore Campaign: Meta box registered successfully');
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post;

        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        if (!$post || $post->post_type !== 'ebook') {
            return;
        }
        
        // Enqueue custom CSS for campaigns
        wp_add_inline_style('wp-admin', $this->get_inline_css());
        
        // Enqueue custom JS for campaigns
        wp_add_inline_script('jquery', $this->get_inline_js());
    }

    /**
     * Render campaign meta box
     */
    public function render_campaign_meta_box($post) {
        wp_nonce_field('skillscore_campaign_save', 'skillscore_campaign_nonce');

        // Get existing campaigns for this ebook
        $campaigns = $this->campaign_manager->get_ebook_campaigns($post->ID);
        
        ?>
        <div class="skillscore-campaigns-wrapper" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 3px; border-radius: 8px; margin: -12px -12px 0 -12px;">
            <div style="background: white; padding: 20px; border-radius: 6px;">
                <div class="campaigns-header" style="background: #f0f9ff; border: 2px solid #3b82f6; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h3 style="margin: 0 0 10px 0; color: #1e40af; font-size: 18px;">
                        🎯 Create Automated Follow-Up Email Campaigns
                    </h3>
                    <p class="description" style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
                        <strong>What this does:</strong> Automatically send scheduled emails to customers after they purchase this ebook. 
                        Perfect for reminders, bonus content, upsells, or engagement campaigns. Emails stop automatically if the customer 
                        purchases again through your campaign link.
                    </p>
                    <p class="description" style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
                        <strong>Example uses:</strong>
                        <br>• Day 7: "Are you enjoying the book? Here's a bonus chapter..."
                        <br>• Day 14: "Ready for the sequel? Get 20% off..."
                        <br>• Day 30: "Don't forget to download your exclusive resources..."
                    </p>
                    <button type="button" class="button button-primary button-large" id="add-new-campaign" style="font-size: 16px; padding: 8px 20px;">
                        ➕ Add Your First Campaign Email
                    </button>
                </div>

                <div id="campaigns-container">
                    <?php if (!empty($campaigns)) : ?>
                        <?php foreach ($campaigns as $index => $campaign) : 
                            $stats = $this->campaign_manager->get_campaign_stats($campaign->id);
                            ?>
                            <div class="campaign-item" data-campaign-id="<?php echo esc_attr($campaign->id); ?>">
                                <?php $this->render_campaign_form($campaign, $index, $stats); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div class="no-campaigns-message" style="text-align: center; padding: 60px 20px; background: #f9fafb; border: 2px dashed #d1d5db; border-radius: 8px;">
                            <p style="font-size: 48px; margin: 0 0 10px 0;">📧</p>
                            <p style="font-size: 18px; margin: 0 0 10px 0; color: #374151; font-weight: 600;">
                                No email campaigns yet
                            </p>
                            <p style="color: #6b7280; margin: 0;">
                                Click "Add Your First Campaign Email" above to get started with automated follow-ups
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Template for new campaigns -->
                <script type="text/template" id="campaign-template">
                    <div class="campaign-item" data-campaign-id="new">
                        <?php $this->render_campaign_form(null, '{{INDEX}}'); ?>
                    </div>
                </script>
            </div>
        </div>
        <?php
    }

    /**
     * Render individual campaign form
     */
    private function render_campaign_form($campaign, $index, $stats = null) {
        $is_new = $campaign === null;
        $campaign_id = $is_new ? 0 : $campaign->id;
        $subject = $is_new ? '' : esc_attr($campaign->email_subject);
        $body = $is_new ? '' : esc_textarea($campaign->email_body);
        $delay_value = $is_new ? 7 : intval($campaign->delay_value);
        $delay_unit = $is_new ? 'days' : $campaign->delay_unit;
        $is_active = $is_new ? 1 : $campaign->is_active;
        
        ?>
        <div class="campaign-header">
            <h4>
                <?php if ($is_new) : ?>
                    New Campaign Email
                <?php else : ?>
                    Campaign #<?php echo $campaign_id; ?>
                    <?php if ($stats) : ?>
                        <span class="campaign-stats-badge">
                            📊 Sent: <?php echo $stats['sent']; ?> | 
                            ⏳ Pending: <?php echo $stats['pending']; ?> | 
                            🛑 Stopped: <?php echo $stats['stopped']; ?>
                        </span>
                    <?php endif; ?>
                <?php endif; ?>
            </h4>
            <div class="campaign-actions">
                <?php if (!$is_new) : ?>
                    <label class="campaign-toggle">
                        <input type="checkbox" 
                               class="campaign-active-toggle" 
                               data-campaign-id="<?php echo $campaign_id; ?>"
                               <?php checked($is_active, 1); ?>>
                        <span><?php echo $is_active ? '✅ Active' : '⏸️ Paused'; ?></span>
                    </label>
                    <button type="button" 
                            class="button delete-campaign" 
                            data-campaign-id="<?php echo $campaign_id; ?>">
                        🗑️ Delete
                    </button>
                <?php endif; ?>
                <button type="button" class="campaign-collapse">
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
            </div>
        </div>

        <div class="campaign-body">
            <input type="hidden" 
                   name="campaigns[<?php echo $index; ?>][id]" 
                   value="<?php echo $campaign_id; ?>">

            <table class="form-table">
                <tr>
                    <th><label>⏰ Send After Purchase</label></th>
                    <td>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" 
                                   name="campaigns[<?php echo $index; ?>][delay_value]" 
                                   value="<?php echo $delay_value; ?>"
                                   class="small-text"
                                   min="0"
                                   required>
                            <select name="campaigns[<?php echo $index; ?>][delay_unit]">
                                <option value="minutes" <?php selected($delay_unit, 'minutes'); ?>>Minutes</option>
                                <option value="hours" <?php selected($delay_unit, 'hours'); ?>>Hours</option>
                                <option value="days" <?php selected($delay_unit, 'days'); ?>>Days</option>
                                <option value="weeks" <?php selected($delay_unit, 'weeks'); ?>>Weeks</option>
                            </select>
                            <span>after customer purchases</span>
                        </div>
                        <p class="description">
                            <strong>Example:</strong> "7 days" = Email sends 7 days after each customer's purchase date.<br>
                            Each customer gets their email at THEIR scheduled time, not a global time.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label>📬 Email Subject</label></th>
                    <td>
                        <input type="text" 
                               name="campaigns[<?php echo $index; ?>][email_subject]" 
                               value="<?php echo $subject; ?>"
                               class="large-text"
                               placeholder="Hi {customer_name}, how are you enjoying the book?"
                               required>
                    </td>
                </tr>
                <tr>
                    <th><label>✉️ Email Body</label></th>
                    <td>
                        <textarea name="campaigns[<?php echo $index; ?>][email_body]" 
                                  class="large-text campaign-body-editor"
                                  rows="8"
                                  required><?php echo $body; ?></textarea>
                        <p class="description">
                            <strong>Available Placeholders:</strong><br>
                            <code>{customer_name}</code> - Customer's name<br>
                            <code>{ebook_title}</code> - Ebook title<br>
                            <code>{download_link}</code> - Download link<br>
                            <code>{order_date}</code> - Purchase date
                        </p>
                    </td>
                </tr>
            </table>

            <?php if (!$is_new && $stats) : ?>
                <div class="campaign-stats-detailed">
                    <h5>📊 Campaign Performance</h5>
                    <div class="stats-grid">
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats['total']; ?></span>
                            <span class="stat-label">Total Recipients</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats['pending']; ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats['sent']; ?></span>
                            <span class="stat-label">Sent</span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-number"><?php echo $stats['stopped']; ?></span>
                            <span class="stat-label">Stopped</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save campaigns when ebook is saved
     */
    public function save_campaigns($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['skillscore_campaign_nonce']) || 
            !wp_verify_nonce($_POST['skillscore_campaign_nonce'], 'skillscore_campaign_save')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save campaigns
        if (isset($_POST['campaigns']) && is_array($_POST['campaigns'])) {
            foreach ($_POST['campaigns'] as $campaign_data) {
                // Add ebook ID to campaign data
                $campaign_data['ebook_id'] = $post_id;
                
                // Save campaign (delay_value and delay_unit are already in the right format)
                $this->campaign_manager->save_campaign($campaign_data);
            }
        }
    }

    /**
     * AJAX: Delete campaign
     */
    public function ajax_delete_campaign() {
        check_ajax_referer('wp_rest', '_wpnonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

        if (!$campaign_id) {
            wp_send_json_error(array('message' => 'Invalid campaign ID'));
            return;
        }

        $deleted = $this->campaign_manager->delete_campaign($campaign_id);

        if ($deleted) {
            wp_send_json_success(array('message' => 'Campaign deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete campaign'));
        }
    }

    /**
     * AJAX: Toggle campaign active status
     */
    public function ajax_toggle_campaign() {
        check_ajax_referer('wp_rest', '_wpnonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;
        $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 0;

        if (!$campaign_id) {
            wp_send_json_error(array('message' => 'Invalid campaign ID'));
            return;
        }

        $updated = $this->campaign_manager->toggle_campaign($campaign_id, $is_active);

        if ($updated) {
            wp_send_json_success(array(
                'message' => 'Campaign ' . ($is_active ? 'activated' : 'paused'),
                'is_active' => $is_active
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update campaign'));
        }
    }

    /**
     * AJAX: Get campaign statistics
     */
    public function ajax_get_campaign_stats() {
        check_ajax_referer('wp_rest', '_wpnonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        $campaign_id = isset($_POST['campaign_id']) ? intval($_POST['campaign_id']) : 0;

        if (!$campaign_id) {
            wp_send_json_error(array('message' => 'Invalid campaign ID'));
            return;
        }

        $stats = $this->campaign_manager->get_campaign_stats($campaign_id);

        wp_send_json_success($stats);
    }

    /**
     * Get inline CSS for campaigns
     */
    private function get_inline_css() {
        return '
        .skillscore-campaigns-wrapper {
            background: #fff;
            padding: 20px;
        }
        
        .campaigns-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .campaigns-header .description {
            flex: 1;
            margin: 0 20px 0 0;
        }
        
        .campaign-item {
            background: #f9fafb;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .campaign-item.collapsed .campaign-body {
            display: none;
        }
        
        .campaign-item.collapsed .campaign-collapse .dashicons {
            transform: rotate(180deg);
        }
        
        .campaign-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .campaign-header h4 {
            margin: 0;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .campaign-stats-badge {
            background: rgba(255,255,255,0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: normal;
        }
        
        .campaign-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .campaign-toggle input {
            margin-right: 5px;
        }
        
        .campaign-toggle span {
            font-size: 14px;
            font-weight: 600;
        }
        
        .campaign-collapse {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .campaign-collapse:hover {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        
        .campaign-body {
            padding: 20px;
        }
        
        .campaign-body-editor {
            font-family: monospace;
            background: #fff;
        }
        
        .campaign-stats-detailed {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-label {
            display: block;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .no-campaigns-message {
            text-align: center;
            padding: 40px;
            color: #6b7280;
            font-style: italic;
        }
        ';
    }

    /**
     * Get inline JavaScript for campaigns
     */
    private function get_inline_js() {
        return "
        jQuery(document).ready(function($) {
            var campaignIndex = $('.campaign-item').length;
            
            // Add new campaign
            $('#add-new-campaign').on('click', function() {
                var template = $('#campaign-template').html();
                template = template.replace(/\{\{INDEX\}\}/g, campaignIndex);
                $('#campaigns-container').append(template);
                $('.no-campaigns-message').remove();
                campaignIndex++;
            });
            
            // Toggle campaign collapse
            $(document).on('click', '.campaign-collapse', function() {
                $(this).closest('.campaign-item').toggleClass('collapsed');
            });
            
            // Delete campaign
            $(document).on('click', '.delete-campaign', function() {
                if (!confirm('Are you sure you want to delete this campaign? This cannot be undone.')) {
                    return;
                }
                
                var campaignId = $(this).data('campaign-id');
                var \$item = $(this).closest('.campaign-item');
                
                if (campaignId && campaignId !== 'new') {
                    $.post(ajaxurl, {
                        action: 'skillscore_delete_campaign',
                        campaign_id: campaignId,
                        _wpnonce: '" . wp_create_nonce('wp_rest') . "'
                    }, function(response) {
                        if (response.success) {
                            \$item.fadeOut(300, function() {
                                $(this).remove();
                                if ($('.campaign-item').length === 0) {
                                    $('#campaigns-container').html('<p class=\"no-campaigns-message\">No email campaigns yet.</p>');
                                }
                            });
                        } else {
                            alert(response.data.message);
                        }
                    });
                } else {
                    \$item.remove();
                }
            });
            
            // Toggle active status
            $(document).on('change', '.campaign-active-toggle', function() {
                var campaignId = $(this).data('campaign-id');
                var isActive = $(this).is(':checked') ? 1 : 0;
                var \$label = $(this).next('span');
                
                $.post(ajaxurl, {
                    action: 'skillscore_toggle_campaign',
                    campaign_id: campaignId,
                    is_active: isActive,
                    _wpnonce: '" . wp_create_nonce('wp_rest') . "'
                }, function(response) {
                    if (response.success) {
                        \$label.text(isActive ? '✅ Active' : '⏸️ Paused');
                    } else {
                        alert(response.data.message);
                    }
                });
            });
        });
        ";
    }
}