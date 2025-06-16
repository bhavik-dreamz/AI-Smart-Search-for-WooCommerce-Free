<?php
namespace AI_SmartSearch;

class Hooks {
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Search hooks
        add_filter('posts_search', [$this, 'modify_search_query'], 10, 2);
        add_filter('posts_where', [$this, 'modify_search_where'], 10, 2);
        add_filter('posts_join', [$this, 'modify_search_join'], 10, 2);
        add_filter('posts_groupby', [$this, 'modify_search_groupby'], 10, 2);

        // Template hooks
        add_action('wp_footer', [$this, 'add_search_overlay']);
        add_filter('woocommerce_product_loop_start', [$this, 'modify_product_loop_start']);
        add_filter('woocommerce_product_loop_end', [$this, 'modify_product_loop_end']);

        // Admin hooks
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('admin_notices', [$this, 'admin_notices']);
    }

    /**
     * Modify search query
     */
    public function modify_search_query($search, $wp_query) {
        if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search()) {
            global $wpdb;

            $search_term = $wp_query->get('s');
            if (empty($search_term)) {
                return $search;
            }

            // Basic keyword matching
            $search_term = '%' . $wpdb->esc_like($search_term) . '%';
            
            $search = $wpdb->prepare(
                " AND (
                    {$wpdb->posts}.post_title LIKE %s
                    OR {$wpdb->posts}.post_content LIKE %s
                    OR EXISTS (
                        SELECT 1 FROM {$wpdb->postmeta}
                        WHERE post_id = {$wpdb->posts}.ID
                        AND meta_key IN ('_sku', '_product_attributes')
                        AND meta_value LIKE %s
                    )
                )",
                $search_term,
                $search_term,
                $search_term
            );
        }
        return $search;
    }

    /**
     * Modify search where clause
     */
    public function modify_search_where($where, $wp_query) {
        if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search()) {
            // Add custom where conditions if needed
        }
        return $where;
    }

    /**
     * Modify search join clause
     */
    public function modify_search_join($join, $wp_query) {
        if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search()) {
            // Add custom joins if needed
        }
        return $join;
    }

    /**
     * Modify search groupby clause
     */
    public function modify_search_groupby($groupby, $wp_query) {
        if (!is_admin() && $wp_query->is_main_query() && $wp_query->is_search()) {
            // Add custom groupby if needed
        }
        return $groupby;
    }

    /**
     * Add search overlay
     */
    public function add_search_overlay() {
        if (!is_shop() && !is_product_category()) {
            return;
        }
        ?>
        <div id="ai-smartsearch-overlay" class="ai-smartsearch-overlay" style="display: none;">
            <div class="ai-smartsearch-overlay-content">
                <div class="ai-smartsearch-search-box">
                    <input type="text" id="ai-smartsearch-input" placeholder="<?php esc_attr_e('Search products...', 'ai-smartsearch'); ?>">
                    <div id="ai-smartsearch-results" class="ai-smartsearch-results"></div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Modify product loop start
     */
    public function modify_product_loop_start($html) {
        if (is_search()) {
            $html = '<div class="ai-smartsearch-results-header">' . 
                   sprintf(__('Search results for: %s', 'ai-smartsearch'), get_search_query()) . 
                   '</div>' . $html;
        }
        return $html;
    }

    /**
     * Modify product loop end
     */
    public function modify_product_loop_end($html) {
        if (is_search()) {
            $html .= $this->get_pro_features_notice();
        }
        return $html;
    }

    /**
     * Admin enqueue scripts
     */
    public function admin_enqueue_scripts($hook) {
        if ('woocommerce_page_ai-smartsearch' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'ai-smartsearch-admin',
            AI_SMARTSEARCH_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AI_SMARTSEARCH_VERSION
        );

        wp_enqueue_script(
            'ai-smartsearch-admin',
            AI_SMARTSEARCH_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            AI_SMARTSEARCH_VERSION,
            true
        );
    }

    /**
     * Admin notices
     */
    public function admin_notices() {
        if (!get_option('ai_smartsearch_api_key')) {
            ?>
            <div class="notice notice-warning">
                <p><?php _e('AI Smart Search: Please enter your API key in the settings.', 'ai-smartsearch'); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Get pro features notice
     */
    private function get_pro_features_notice() {
        if (!function_exists('ai_smartsearch_pro_enabled')) {
            return sprintf(
                '<div class="ai-smartsearch-pro-notice">%s</div>',
                __('Upgrade to Pro for advanced AI-powered search and recommendations.', 'ai-smartsearch')
            );
        }
        return '';
    }
} 