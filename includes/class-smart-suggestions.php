<?php
namespace AI_SmartSearch;

class Smart_Suggestions {
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', [$this, 'init']);
    }

    /**
     * Initialize
     */
    public function init() {
        add_action('woocommerce_after_shop_loop', [$this, 'display_smart_suggestions'], 20);
        add_shortcode('ai_smart_suggestions', [$this, 'smart_suggestions_shortcode']);
    }

    /**
     * Get smart suggestions
     */
    public function get_smart_suggestions($limit = 4) {
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_stock_status',
                    'value' => 'instock'
                ]
            ]
        ];

        // Allow modification of query args
        $args = apply_filters('ai_smartsearch_suggestions_query', $args);

        $query = new \WP_Query($args);
        return $query->posts;
    }

    /**
     * Display smart suggestions
     */
    public function display_smart_suggestions() {
        if (!is_shop() && !is_product_category()) {
            return;
        }

        $suggestions = $this->get_smart_suggestions();

        if (empty($suggestions)) {
            return;
        }

        // Action before displaying results
        do_action('ai_smartsearch_before_results');

        include AI_SMARTSEARCH_PLUGIN_DIR . 'templates/smart-suggestions.php';

        // Action after displaying results
        do_action('ai_smartsearch_after_results');
    }

    /**
     * Get pro features notice
     */
    public function get_pro_features_notice() {
        if (!function_exists('ai_smartsearch_pro_enabled')) {
            return sprintf(
                '<div class="ai-smartsearch-pro-notice">%s</div>',
                __('Upgrade to Pro for personalized AI suggestions and advanced features.', 'ai-smartsearch')
            );
        }
        return '';
    }


     /**
     * Smart suggestions shortcode
     */
    public function smart_suggestions_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 4
        ], $atts);

        $args = [
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'meta_key' => 'total_sales',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ];

        $args = apply_filters('ai_smartsearch_suggestions_query', $args);

        $query = new \WP_Query($args);
        $suggestions = $query->posts;

        ob_start();
        include AI_SMARTSEARCH_PLUGIN_DIR . 'templates/smart-suggestions.php';
        wp_reset_postdata();
        return ob_get_clean();
    }
} 