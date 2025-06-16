<?php
namespace AI_SmartSearch;

class AI_Search_Lite {
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->load_dependencies();
        $this->load_classes();
      
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Search functionality
        add_action('pre_get_posts', [$this, 'modify_search_query']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('wp_ajax_ai_search', [$this, 'ajax_search']);
        add_action('wp_ajax_nopriv_ai_search', [$this, 'ajax_search']);

         
        

        // Admin
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once AI_SMARTSEARCH_PLUGIN_DIR . 'includes/class-similar-products.php';
        require_once AI_SMARTSEARCH_PLUGIN_DIR . 'includes/class-smart-suggestions.php';
        require_once AI_SMARTSEARCH_PLUGIN_DIR . 'includes/class-hooks.php';
    }

    public function load_classes()
    {   
        $this->Similar_Products = new Similar_Products();
        $this->Smart_Suggestions = new Smart_Suggestions();
    }

    /**
     * Modify search query
     */
    public function modify_search_query($query) {
        if (!is_admin() && $query->is_main_query() && $query->is_search()) {
            // Only modify product searches
            if (isset($_GET['post_type']) && $_GET['post_type'] === 'product') {
                $search_query = $query->get('s');
                
                // Log the search
                $this->log_search($search_query);

                // Allow modification of query
                $query = apply_filters('ai_smartsearch_modify_query', $query);
            }
        }
        return $query;
    }

    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        wp_enqueue_style(
            'ai-smartsearch-style',
            AI_SMARTSEARCH_PLUGIN_URL . 'assets/css/style.css',
            [],
            AI_SMARTSEARCH_VERSION
        );

        wp_enqueue_script(
            'ai-smartsearch-js',
            AI_SMARTSEARCH_PLUGIN_URL . 'assets/js/ai-search.js',
            ['jquery'],
            AI_SMARTSEARCH_VERSION,
            true
        );

        wp_localize_script('ai-smartsearch-js', 'aiSmartSearch', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai-smartsearch-nonce')
        ]);
    }

    /**
     * AJAX search handler
     */
    public function ajax_search() {
        check_ajax_referer('ai-smartsearch-nonce', 'nonce');

        $search_term = sanitize_text_field($_POST['search_term']);
        
        // Log the search
        $this->log_search($search_term);

        $args = [
            'post_type' => 'product',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => 5
        ];

        $query = new \WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product = wc_get_product(get_the_ID());
                
                $results[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'price' => $product->get_price_html(),
                    'image' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail'),
                    'url' => get_permalink()
                ];
            }
        }

        wp_reset_postdata();
        wp_send_json_success($results);
    }

  
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('AI Smart Search', 'ai-smartsearch'),
            __('AI Smart Search', 'ai-smartsearch'),
            'manage_options',
            'ai-smartsearch',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('ai_smartsearch_settings', 'ai_smartsearch_api_key');
        register_setting('ai_smartsearch_settings', 'ai_smartsearch_enabled');
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        include AI_SMARTSEARCH_PLUGIN_DIR . 'admin/settings-page.php';
    }

    /**
     * Log search query
     */
    private function log_search($keyword) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_smartsearch_logs';
        
        // Use WordPress's prepare method for better security
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}ai_smartsearch_logs WHERE keyword = %s",
            $keyword
        ));

        if ($existing) {
            $wpdb->update(
                $table_name,
                [
                    'count' => $existing->count + 1,
                    'last_searched' => current_time('mysql')
                ],
                ['id' => $existing->id],
                ['%d', '%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'keyword' => $keyword,
                    'count' => 1,
                    'last_searched' => current_time('mysql')
                ],
                ['%s', '%d', '%s']
            );
        }
    }
} 