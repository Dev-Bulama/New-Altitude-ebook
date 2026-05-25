<?php
/**
 * One-Time Ebook Recovery Script
 * Upload to WordPress root (same folder as wp-config.php)
 * Run via: https://yourdomain.com/recover-ebooks.php?key=NArecover2025
 *
 * DELETE THIS FILE after running it.
 */

// Security token — change this if you want a different key
define('RECOVERY_KEY', 'NArecover2025');

if (!isset($_GET['key']) || $_GET['key'] !== RECOVERY_KEY) {
    http_response_code(403);
    die('Access denied. Add ?key=NArecover2025 to the URL.');
}

// Load WordPress
$wp_load = dirname(__FILE__) . '/wp-load.php';
if (!file_exists($wp_load)) {
    die('ERROR: wp-load.php not found. Make sure this file is in your WordPress root directory.');
}
require_once $wp_load;

// Verify WordPress loaded
if (!defined('ABSPATH')) {
    die('ERROR: WordPress failed to load.');
}

// Require admin privileges for safety
if (!current_user_logged_in() || !current_user_can('manage_options')) {
    // Allow running even if not logged in — but show a warning
    $admin_logged_in = false;
} else {
    $admin_logged_in = true;
}

global $wpdb;
$log = array();
$fixes = array();
$errors = array();

$log[] = '=== EBOOK RECOVERY SCRIPT ===';
$log[] = 'Time: ' . current_time('mysql');
$log[] = 'Site URL: ' . get_site_url();
$log[] = 'WP Version: ' . get_bloginfo('version');
$log[] = 'Admin logged in: ' . ($admin_logged_in ? 'YES' : 'NO (some actions may be skipped)');
$log[] = '';

// -------------------------------------------------------
// STEP 1: Check if ebook CPT is registered
// -------------------------------------------------------
$log[] = '--- STEP 1: Check CPT Registration ---';
$post_types = get_post_types(array(), 'names');
if (in_array('ebook', $post_types)) {
    $log[] = '[OK] CPT "ebook" is currently registered.';
} else {
    $log[] = '[WARNING] CPT "ebook" is NOT registered right now.';
    $log[] = '  This could mean the plugin is inactive OR this script runs before the CPT registers.';
    $errors[] = 'CPT "ebook" not registered — verify the SkillScore Ebook Commerce plugin is Active.';
}

// -------------------------------------------------------
// STEP 2: Check plugin status
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 2: Plugin Status ---';
$active_plugins = get_option('active_plugins', array());
$plugin_file = 'skillscore-ebook-commerce/skillscore-ebook-commerce.php';
if (in_array($plugin_file, $active_plugins)) {
    $log[] = '[OK] SkillScore Ebook Commerce plugin is ACTIVE.';
} else {
    $log[] = '[ERROR] Plugin is NOT active! Activating now...';
    $result = activate_plugin($plugin_file);
    if (is_wp_error($result)) {
        $log[] = '[ERROR] Could not activate plugin: ' . $result->get_error_message();
        $errors[] = 'Plugin activation failed: ' . $result->get_error_message();
    } else {
        $log[] = '[FIXED] Plugin activated successfully.';
        $fixes[] = 'Activated SkillScore Ebook Commerce plugin.';
        // Re-load CPT now that plugin is active
        do_action('init');
    }
}

// -------------------------------------------------------
// STEP 3: Find ALL ebook posts (all statuses)
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 3: Database Scan for Ebook Posts ---';

$all_statuses = array('publish', 'draft', 'pending', 'private', 'trash', 'auto-draft', 'inherit', 'future');
$status_counts = array();

foreach ($all_statuses as $status) {
    $count = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
            'ebook',
            $status
        )
    );
    $status_counts[$status] = intval($count);
}

$total = array_sum($status_counts);
$log[] = 'Total ebook posts found in database: ' . $total;
foreach ($status_counts as $status => $count) {
    if ($count > 0) {
        $log[] = '  ' . strtoupper($status) . ': ' . $count;
    }
}

if ($total === 0) {
    $log[] = '[WARNING] No ebook posts exist in the database at all.';
    $log[] = '  The posts were never created, or the database was wiped.';
    $log[] = '  Recovery is not possible without a database backup.';
    $log[] = '  You will need to create ebooks manually via the WordPress admin.';
    $errors[] = 'No ebook posts found in database — content must be re-created manually.';
}

// -------------------------------------------------------
// STEP 4: Restore trashed ebooks
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 4: Restore Trashed Ebooks ---';

if ($status_counts['trash'] > 0) {
    $trashed = $wpdb->get_results(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'ebook' AND post_status = 'trash'"
    );
    foreach ($trashed as $post) {
        $result = wp_untrash_post($post->ID);
        if ($result) {
            $log[] = '[RESTORED] "' . $post->post_title . '" (ID: ' . $post->ID . ') — untrashed.';
            $fixes[] = 'Restored from trash: "' . $post->post_title . '" (ID: ' . $post->ID . ')';
        } else {
            $log[] = '[ERROR] Could not untrash post ID ' . $post->ID;
            $errors[] = 'Failed to untrash post ID ' . $post->ID;
        }
    }
} else {
    $log[] = 'No trashed ebooks found.';
}

// -------------------------------------------------------
// STEP 5: Publish draft/pending ebooks
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 5: Publish Draft / Pending Ebooks ---';

$unpublished_statuses = array('draft', 'pending', 'auto-draft');
$unpublished = $wpdb->get_results(
    "SELECT ID, post_title, post_status FROM {$wpdb->posts}
     WHERE post_type = 'ebook'
     AND post_status IN ('draft', 'pending', 'auto-draft')"
);

if (!empty($unpublished)) {
    foreach ($unpublished as $post) {
        $update = wp_update_post(array(
            'ID'          => $post->ID,
            'post_status' => 'publish',
        ));
        if ($update && !is_wp_error($update)) {
            $log[] = '[PUBLISHED] "' . $post->post_title . '" (ID: ' . $post->ID . ') — was ' . $post->post_status;
            $fixes[] = 'Published: "' . $post->post_title . '" (ID: ' . $post->ID . ')';
        } else {
            $log[] = '[ERROR] Could not publish post ID ' . $post->ID;
            $errors[] = 'Failed to publish post ID ' . $post->ID;
        }
    }
} else {
    $log[] = 'No draft/pending ebooks found.';
}

// -------------------------------------------------------
// STEP 6: List all currently published ebooks
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 6: Published Ebooks (after fixes) ---';

$published = $wpdb->get_results(
    "SELECT ID, post_title, post_date, post_status FROM {$wpdb->posts}
     WHERE post_type = 'ebook' AND post_status = 'publish'
     ORDER BY post_date DESC"
);

if (!empty($published)) {
    $log[] = 'Found ' . count($published) . ' published ebook(s):';
    foreach ($published as $post) {
        $price = get_post_meta($post->ID, '_ebook_price', true);
        $log[] = '  [ID:' . $post->ID . '] "' . $post->post_title . '" — Price: ' . ($price ? $price : 'not set') . ' — Date: ' . $post->post_date;
    }
} else {
    $log[] = '[WARNING] Still no published ebooks after attempted fixes.';
}

// -------------------------------------------------------
// STEP 7: Check rewrite rules and flush
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 7: Flush Rewrite Rules ---';

flush_rewrite_rules(true);
$log[] = '[OK] Rewrite rules flushed.';
$fixes[] = 'Flushed WordPress rewrite rules.';

// Also update the option to trigger a flush on next load
update_option('rewrite_rules', '');
$log[] = '[OK] Queued rewrite rules regeneration on next page load.';

// -------------------------------------------------------
// STEP 8: Check permalink structure
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 8: Permalink Structure ---';

$permalink_structure = get_option('permalink_structure');
if (empty($permalink_structure)) {
    $log[] = '[WARNING] Permalink structure is set to "Plain" — CPT archive URLs may not work.';
    $log[] = '  Go to Settings > Permalinks and choose "Post name" or another option, then Save.';
    $errors[] = 'Permalink structure is Plain — archive pages will not work. Change it in Settings > Permalinks.';
} else {
    $log[] = '[OK] Permalink structure: ' . $permalink_structure;
}

// -------------------------------------------------------
// STEP 9: Verify shortcode is registered
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 9: Shortcode Registration ---';

if (shortcode_exists('skillscore_ebooks')) {
    $log[] = '[OK] Shortcode [skillscore_ebooks] is registered.';
} else {
    $log[] = '[WARNING] Shortcode [skillscore_ebooks] is NOT registered.';
    $log[] = '  The plugin may not be loading its shortcode class. Verify the plugin is active.';
    $errors[] = '[skillscore_ebooks] shortcode not registered — plugin may not be fully loaded.';
}

// -------------------------------------------------------
// STEP 10: Check page using shortcode
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 10: Pages Using [skillscore_ebooks] Shortcode ---';

$shortcode_pages = $wpdb->get_results(
    "SELECT ID, post_title, post_status, post_name FROM {$wpdb->posts}
     WHERE post_content LIKE '%skillscore_ebooks%'
     AND post_type IN ('page', 'post')
     AND post_status = 'publish'"
);

if (!empty($shortcode_pages)) {
    foreach ($shortcode_pages as $page) {
        $url = get_permalink($page->ID);
        $log[] = '  [ID:' . $page->ID . '] "' . $page->post_title . '" — ' . $url;
    }
} else {
    $log[] = '[WARNING] No published page found containing [skillscore_ebooks].';
    $log[] = '  Make sure you have added [skillscore_ebooks] to your ebooks listing page.';
}

// -------------------------------------------------------
// STEP 11: Check CPT archive URL
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 11: CPT Archive URL ---';

$ebook_archive_url = get_post_type_archive_link('ebook');
if ($ebook_archive_url) {
    $log[] = '[OK] CPT archive URL: ' . $ebook_archive_url;
} else {
    $log[] = '[WARNING] Could not determine CPT archive URL (CPT may not be registered yet).';
}

// -------------------------------------------------------
// STEP 12: Database table check
// -------------------------------------------------------
$log[] = '';
$log[] = '--- STEP 12: Plugin Database Tables ---';

$tables = array(
    $wpdb->prefix . 'skillscore_ebook_purchases',
    $wpdb->prefix . 'skillscore_downloads',
    $wpdb->prefix . 'skillscore_bulk_inquiries',
);

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if ($exists) {
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        $log[] = '[OK] Table exists: ' . $table . ' (' . $count . ' rows)';
    } else {
        $log[] = '[MISSING] Table not found: ' . $table;
    }
}

// -------------------------------------------------------
// SUMMARY
// -------------------------------------------------------
$log[] = '';
$log[] = '=== SUMMARY ===';

if (!empty($fixes)) {
    $log[] = 'FIXES APPLIED (' . count($fixes) . '):';
    foreach ($fixes as $fix) {
        $log[] = '  + ' . $fix;
    }
} else {
    $log[] = 'No fixes were needed (or no fixable issues found).';
}

if (!empty($errors)) {
    $log[] = '';
    $log[] = 'REMAINING ISSUES (' . count($errors) . '):';
    foreach ($errors as $err) {
        $log[] = '  !! ' . $err;
    }
}

$log[] = '';
$log[] = 'NEXT STEPS:';
$log[] = '  1. Visit your ebook listing page: ' . get_site_url() . '/en/book/';
$log[] = '  2. If still empty, go to Settings > Permalinks and click Save Changes.';
$log[] = '  3. Verify plugin is active at: ' . admin_url('plugins.php');
$log[] = '  4. Check ebooks at: ' . admin_url('edit.php?post_type=ebook');
$log[] = '  5. DELETE THIS FILE (recover-ebooks.php) from your server immediately after use.';

// -------------------------------------------------------
// OUTPUT
// -------------------------------------------------------
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Ebook Recovery — New Altitude</title>
<style>
  body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 30px; }
  h1 { color: #38bdf8; }
  pre { background: #1e293b; padding: 20px; border-radius: 8px; line-height: 1.7; overflow-x: auto; white-space: pre-wrap; word-break: break-word; }
  .ok { color: #4ade80; }
  .warn { color: #facc15; }
  .err { color: #f87171; }
  .fix { color: #818cf8; }
  .info { color: #e2e8f0; }
  .section { color: #38bdf8; font-weight: bold; }
  .delete-warning { background: #7f1d1d; border: 2px solid #ef4444; padding: 16px; margin-top: 24px; border-radius: 8px; color: #fca5a5; font-size: 1.1em; }
</style>
</head>
<body>
<h1>Ebook Recovery Script — New Altitude</h1>
<pre><?php
foreach ($log as $line) {
    if (strpos($line, '[OK]') !== false) {
        echo '<span class="ok">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[WARNING]') !== false || strpos($line, 'WARNING') !== false) {
        echo '<span class="warn">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[ERROR]') !== false || strpos($line, 'REMAINING') !== false || strpos($line, '!!') !== false) {
        echo '<span class="err">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '[FIXED]') !== false || strpos($line, '[PUBLISHED]') !== false || strpos($line, '[RESTORED]') !== false || strpos($line, 'FIXES APPLIED') !== false || strpos($line, '  +') !== false) {
        echo '<span class="fix">' . htmlspecialchars($line) . '</span>' . "\n";
    } elseif (strpos($line, '---') !== false || strpos($line, '===') !== false) {
        echo '<span class="section">' . htmlspecialchars($line) . '</span>' . "\n";
    } else {
        echo '<span class="info">' . htmlspecialchars($line) . '</span>' . "\n";
    }
}
?></pre>
<div class="delete-warning">
  &#9888; SECURITY: Delete <strong>recover-ebooks.php</strong> from your server immediately after use via cPanel File Manager.
</div>
</body>
</html>
