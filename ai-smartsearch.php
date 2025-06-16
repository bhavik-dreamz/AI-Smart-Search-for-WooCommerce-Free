<?php
/**
 * Plugin Name: AI Smart Search for WooCommerce (Free)
 * Plugin URI: https://github.com/bhavik-dreamz/AI-Smart-Search-for-WooCommerce-Free
 * Description: AI-powered product search and recommendations for WooCommerce
 * Version: 1.0.0
 * Author: Bhavik Patel
 * Author URI: 
 * Text Domain: ai-smartsearch
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * Woo: 12345:342928dfsfhsf2349842374wdf4234sfd
 * WooCommerce: true
 *
 * @package AI_SmartSearch
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Declare incompatibility to WooCommerce HPOS
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true    
        );
    }
});

// Disable HPOS compatibility flag
add_filter('woocommerce_feature_enabled_custom_order_tables', '__return_false');

// Prevent HPOS from being used if the store has enabled it
add_filter('woocommerce_orders_table_usage_is_enabled', '__return_false');

// Define plugin constants
define('AI_SMARTSEARCH_VERSION', '1.0.0');
define('AI_SMARTSEARCH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_SMARTSEARCH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_SMARTSEARCH_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'AI_SmartSearch\\';
    $base_dir = AI_SMARTSEARCH_PLUGIN_DIR . 'includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . 'class-' . str_replace('_', '-', strtolower($relative_class)) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function ai_smartsearch_init() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p><?php _e('AI Smart Search requires WooCommerce to be installed and activated.', 'ai-smartsearch'); ?></p>
            </div>
            <?php
        });
        return;
    }

    // Initialize main plugin class
    new AI_SmartSearch\AI_Search_Lite();
}
add_action('plugins_loaded', 'ai_smartsearch_init');

// Activation hook
register_activation_hook(__FILE__, function() {
    // Create necessary database tables
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'ai_smartsearch_logs';
    
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        keyword varchar(255) NOT NULL,
        count bigint(20) NOT NULL DEFAULT 1,
        last_searched datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY keyword (keyword)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Cleanup if needed
});

// Uninstall hook
register_uninstall_hook(__FILE__, 'ai_smartsearch_uninstall'); 