<?php
/**
 * Campaign Admin Page Class
 * Provides admin page to view all campaigns and recipients
 * 
 * @package SkillScore_Ebook
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SkillScore_Campaign_Admin_Page {

    /**
     * Campaign manager instance
     */
    private $campaign_manager;

    /**
     * Constructor
     */
    public function __construct() {
        $this->campaign_manager = new SkillScore_Email_Campaign();

        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu page
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=ebook',
            __('Email Campaigns', 'skillscore-ebook'),
            __('📧 Email Campaigns', 'skillscore-ebook'),
            'manage_options',
            'skillscore-ebook-campaigns',
            array($this, 'render_campaigns_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if ('ebook_page_skillscore-ebook-campaigns' !== $hook) {
            return;
        }

        wp_enqueue_style('skillscore-campaigns-admin', false);
        wp_add_inline_style('skillscore-campaigns-admin', $this->get_inline_css());
    }

    /**
     * Render campaigns page
     */
    public function render_campaigns_page() {
        // Get all campaigns with stats
        $campaigns = $this->campaign_manager->get_all_campaigns_with_stats();

        // Get filter
        $view = isset($_GET['view']) ? sanitize_text_field($_GET['view']) : 'all';
        $campaign_id = isset($_GET['campaign_id']) ? intval($_GET['campaign_id']) : 0;

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                📧 Email Campaigns
            </h1>
            <a href="<?php echo admin_url('post-new.php?post_type=ebook'); ?>" class="page-title-action">
                Add New Campaign
            </a>
            <hr class="wp-header-end">

            <?php if ($campaign_id > 0) : 
                $this->render_campaign_details($campaign_id);
            else : 
                $this->render_campaigns_overview($campaigns, $view);
            endif; ?>
        </div>
        <?php
    }

    /**
     * Render campaigns overview
     */
    private function render_campaigns_overview($campaigns, $view) {
        ?>
        <div class="campaigns-overview">
            <div class="campaigns-stats-summary">
                <?php
                $total_campaigns = count($campaigns);
                $total_recipients = 0;
                $total_sent = 0;
                $total_pending = 0;

                foreach ($campaigns as $campaign) {
                    $total_recipients += $campaign->stats['total'];
                    $total_sent += $campaign->stats['sent'];
                    $total_pending += $campaign->stats['pending'];
                }
                ?>
                
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_campaigns; ?></div>
                        <div class="stat-label">Total Campaigns</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_recipients; ?></div>
                        <div class="stat-label">Total Recipients</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_sent; ?></div>
                        <div class="stat-label">Emails Sent</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $total_pending; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>

            <?php if (empty($campaigns)) : ?>
                <div class="no-campaigns-notice">
                    <div class="notice notice-info inline">
                        <p>
                            <strong>No email campaigns yet.</strong><br>
                            Create your first campaign by editing an ebook and scrolling to the 
                            "🚀 Automated Email Campaigns" section.
                        </p>
                    </div>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped campaigns-table">
                    <thead>
                        <tr>
                            <th class="column-id">ID</th>
                            <th class="column-ebook">Ebook</th>
                            <th class="column-subject">Email Subject</th>
                            <th class="column-delay">Send After</th>
                            <th class="column-recipients">Recipients</th>
                            <th class="column-sent">Sent</th>
                            <th class="column-pending">Pending</th>
                            <th class="column-status">Status</th>
                            <th class="column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($campaigns as $campaign) : ?>
                            <tr>
                                <td class="column-id">
                                    <strong>#<?php echo $campaign->id; ?></strong>
                                </td>
                                <td class="column-ebook">
                                    <a href="<?php echo get_edit_post_link($campaign->ebook_id); ?>">
                                        <?php echo esc_html($campaign->ebook_title); ?>
                                    </a>
                                    <br>
                                    <small class="description">ID: <?php echo $campaign->ebook_id; ?></small>
                                </td>
                                <td class="column-subject">
                                    <?php echo esc_html($campaign->email_subject); ?>
                                </td>
                                <td class="column-delay">
                                    <strong><?php echo $campaign->delay_value . ' ' . $campaign->delay_unit; ?></strong>
                                </td>
                                <td class="column-recipients">
                                    <a href="<?php echo admin_url('edit.php?post_type=ebook&page=skillscore-ebook-campaigns&campaign_id=' . $campaign->id); ?>">
                                        <?php echo $campaign->stats['total']; ?> recipients
                                    </a>
                                </td>
                                <td class="column-sent">
                                    <span class="badge badge-success"><?php echo $campaign->stats['sent']; ?></span>
                                </td>
                                <td class="column-pending">
                                    <span class="badge badge-warning"><?php echo $campaign->stats['pending']; ?></span>
                                </td>
                                <td class="column-status">
                                    <?php if ($campaign->is_active) : ?>
                                        <span class="badge badge-active">✅ Active</span>
                                    <?php else : ?>
                                        <span class="badge badge-inactive">⏸️ Paused</span>
                                    <?php endif; ?>
                                </td>
                                <td class="column-actions">
                                    <a href="<?php echo get_edit_post_link($campaign->ebook_id); ?>" class="button button-small">
                                        Edit
                                    </a>
                                    <a href="<?php echo admin_url('edit.php?post_type=ebook&page=skillscore-ebook-campaigns&campaign_id=' . $campaign->id); ?>" class="button button-small">
                                        View Recipients
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render campaign details with recipients
     */
    private function render_campaign_details($campaign_id) {
        $campaign = $this->campaign_manager->get_campaign($campaign_id);
        
        if (!$campaign) {
            echo '<div class="notice notice-error"><p>Campaign not found.</p></div>';
            return;
        }

        $ebook_title = get_the_title($campaign->ebook_id);
        $recipients = $this->campaign_manager->get_campaign_recipients($campaign_id);
        $stats = $this->campaign_manager->get_campaign_stats($campaign_id);

        ?>
        <div class="campaign-details">
            <p class="back-link">
                <a href="<?php echo admin_url('edit.php?post_type=ebook&page=skillscore-ebook-campaigns'); ?>">
                    ← Back to All Campaigns
                </a>
            </p>

            <div class="campaign-header-section">
                <h2>Campaign #<?php echo $campaign->id; ?>: <?php echo esc_html($campaign->email_subject); ?></h2>
                <p class="campaign-meta">
                    <strong>Ebook:</strong> 
                    <a href="<?php echo get_edit_post_link($campaign->ebook_id); ?>">
                        <?php echo esc_html($ebook_title); ?>
                    </a> |
                    <strong>Send After:</strong> <?php echo $campaign->delay_value . ' ' . $campaign->delay_unit; ?> after purchase |
                    <strong>Status:</strong> 
                    <?php if ($campaign->is_active) : ?>
                        <span class="badge badge-active">✅ Active</span>
                    <?php else : ?>
                        <span class="badge badge-inactive">⏸️ Paused</span>
                    <?php endif; ?>
                </p>
            </div>

            <div class="campaigns-stats-summary">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Recipients</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['sent']; ?></div>
                        <div class="stat-label">Sent</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🛑</div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['stopped']; ?></div>
                        <div class="stat-label">Stopped</div>
                    </div>
                </div>
            </div>

            <h3>Recipients</h3>
            <?php if (empty($recipients)) : ?>
                <p>No recipients yet. Recipients are added automatically when customers purchase this ebook.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped recipients-table">
                    <thead>
                        <tr>
                            <th>Customer Name</th>
                            <th>Email</th>
                            <th>Purchase Date</th>
                            <th>Scheduled Send</th>
                            <th>Status</th>
                            <th>Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recipients as $recipient) : ?>
                            <tr>
                                <td><?php echo esc_html($recipient->user_name); ?></td>
                                <td><?php echo esc_html($recipient->user_email); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($recipient->purchase_date)); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($recipient->scheduled_send_at)); ?></td>
                                <td>
                                    <?php
                                    switch ($recipient->status) {
                                        case 'pending':
                                            echo '<span class="badge badge-warning">⏳ Pending</span>';
                                            break;
                                        case 'sent':
                                            echo '<span class="badge badge-success">✅ Sent</span>';
                                            break;
                                        case 'stopped':
                                            echo '<span class="badge badge-error">🛑 Stopped</span>';
                                            if ($recipient->stopped_reason) {
                                                echo '<br><small>' . esc_html($recipient->stopped_reason) . '</small>';
                                            }
                                            break;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($recipient->sent_at) {
                                        echo date('M j, Y g:i A', strtotime($recipient->sent_at));
                                    } else {
                                        echo '—';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get inline CSS
     */
    private function get_inline_css() {
        return '
        .campaigns-stats-summary {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .stat-icon {
            font-size: 32px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 14px;
            opacity: 0.9;
            margin-top: 5px;
        }
        
        .campaigns-table {
            margin-top: 20px;
        }
        
        .campaigns-table .column-id {
            width: 60px;
        }
        
        .campaigns-table .column-delay {
            width: 120px;
        }
        
        .campaigns-table .column-recipients,
        .campaigns-table .column-sent,
        .campaigns-table .column-pending {
            width: 100px;
            text-align: center;
        }
        
        .campaigns-table .column-status {
            width: 100px;
        }
        
        .campaigns-table .column-actions {
            width: 200px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .no-campaigns-notice {
            margin: 40px 0;
        }
        
        .campaign-details {
            margin-top: 20px;
        }
        
        .back-link {
            margin-bottom: 20px;
        }
        
        .campaign-header-section {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .campaign-header-section h2 {
            margin-top: 0;
        }
        
        .campaign-meta {
            color: #6b7280;
            font-size: 14px;
        }
        
        .recipients-table {
            margin-top: 20px;
        }
        ';
    }
}