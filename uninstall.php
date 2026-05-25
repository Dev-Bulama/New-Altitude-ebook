<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package SkillScore_Ebook
 */

// If uninstall not called from WordPress, exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete custom post type posts
$ebooks = get_posts(array(
    'post_type' => 'ebook',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($ebooks as $ebook) {
    wp_delete_post($ebook->ID, true);
}

// Delete custom taxonomies
$terms = get_terms(array(
    'taxonomy' => 'ebook_category',
    'hide_empty' => false,
));

foreach ($terms as $term) {
    wp_delete_term($term->term_id, 'ebook_category');
}

// Delete custom database tables
$orders_table = $wpdb->prefix . 'skillscore_orders';
$downloads_table = $wpdb->prefix . 'skillscore_downloads';

$wpdb->query("DROP TABLE IF EXISTS $orders_table");
$wpdb->query("DROP TABLE IF EXISTS $downloads_table");

// Delete plugin options
$options = array(
    'skillscore_ebook_currency',
    'skillscore_ebook_currency_symbol',
    'skillscore_ebook_enable_quantity_selector',
    'skillscore_ebook_download_limit',
    'skillscore_ebook_download_expiry_days',
    'skillscore_ebook_enable_paystack',
    'skillscore_ebook_paystack_public_key',
    'skillscore_ebook_paystack_secret_key',
    'skillscore_ebook_enable_flutterwave',
    'skillscore_ebook_flutterwave_public_key',
    'skillscore_ebook_flutterwave_secret_key',
    'skillscore_ebook_enable_stripe',
    'skillscore_ebook_stripe_publishable_key',
    'skillscore_ebook_stripe_secret_key',
    'skillscore_ebook_enable_paypal',
    'skillscore_ebook_paypal_client_id',
    'skillscore_ebook_paypal_secret',
    'skillscore_ebook_paypal_mode',
    'skillscore_ebook_enable_audio_preview',
    'skillscore_ebook_use_global_voice',
    'skillscore_ebook_global_voice_url',
    'skillscore_ebook_tts_engine',
    'skillscore_ebook_piper_path',
    'skillscore_ebook_piper_model',
    'skillscore_ebook_coqui_api_url',
    'skillscore_ebook_ffmpeg_path',
);

foreach ($options as $option) {
    delete_option($option);
}

// Delete uploaded files
$upload_dir = wp_upload_dir();
$ebook_dir = $upload_dir['basedir'] . '/skillscore-ebooks';
$audio_dir = $upload_dir['basedir'] . '/skillscore-audio';

// Recursively delete directories
if (is_dir($ebook_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($ebook_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }

    rmdir($ebook_dir);
}

if (is_dir($audio_dir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($audio_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
        $todo($fileinfo->getRealPath());
    }

    rmdir($audio_dir);
}

// Clear any cached data
wp_cache_flush();
