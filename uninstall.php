<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('ai_smartsearch_api_key');
delete_option('ai_smartsearch_enabled');

// Delete search logs table
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_smartsearch_logs");

// Clear any transients we've set
delete_transient('ai_smartsearch_cache');

// Clear any scheduled hooks
wp_clear_scheduled_hook('ai_smartsearch_daily_cleanup'); 