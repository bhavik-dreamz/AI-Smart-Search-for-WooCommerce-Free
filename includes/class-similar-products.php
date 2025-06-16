<?php
namespace AI_SmartSearch;

class Similar_Products {
    private $engine;

    /**
     * Constructor
     */
    public function __construct() {
        $this->engine = new \SimilarProductsEngine();
        add_action('init', [$this, 'init']);
        
        // Add WooCommerce integration
        add_action('woocommerce_output_related_products_args', [$this, 'modify_related_products']);
        
        // Add AJAX endpoints
        add_action('wp_ajax_get_similar_products', [$this, 'ajax_get_similar_products']);
        add_action('wp_ajax_nopriv_get_similar_products', [$this, 'ajax_get_similar_products']);
    }

    /**
     * Initialize
     */
    public function init() {
        add_action('woocommerce_after_single_product_summary', [$this, 'display_similar_products'], 20);
        add_shortcode('ai_similar_products', [$this, 'similar_products_shortcode']);
    }

    /**
     * Modify WooCommerce related products to use our engine
     */
    public function modify_related_products($args) {
        global $product;
        
        if (!$product) return $args;
        
        $similar_products = $this->engine->get_similar_products(
            $product->get_id(), 
            $args['posts_per_page'] ?? 4
        );
        
        if (!empty($similar_products)) {
            $product_ids = array_column($similar_products, 'product_id');
            $args['post__in'] = $product_ids;
            $args['orderby'] = 'post__in'; // Maintain our custom order
        }
        
        return $args;
    }

    /**
     * Get similar products
     */
    public function get_similar_products($product_id, $limit = 4) {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type(['simple', 'variable'])) {
            return [];
        }

        // Use the new engine to get similar products
        $similar_products = $this->engine->get_similar_products($product_id, $limit);

        // Convert the results to post objects
        $posts = [];
        foreach ($similar_products as $product_data) {
            $post = get_post($product_data['product_id']);
            if ($post) {
                $posts[] = $post;
            }
        }

        return $posts;
    }

    /**
     * Display similar products
     */
    public function display_similar_products() {
        if (!is_product()) {
            return;
        }

        $product_id = get_the_ID();
        $similar_products = $this->get_similar_products($product_id);
        
        

        if (empty($similar_products)) {
            return;
        }

        // Action before displaying results
        do_action('ai_smartsearch_before_results');

        include AI_SMARTSEARCH_PLUGIN_DIR . 'templates/similar-products.php';

        // Action after displaying results
        do_action('ai_smartsearch_after_results');
    }

    /**
     * Similar products shortcode
     */
    public function similar_products_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 4
        ], $atts);

        if (!is_product()) {
            return '';
        }

        $product_id = get_the_ID();
        $similar_products = $this->get_similar_products($product_id, $atts['limit']);

        ob_start();
        include AI_SMARTSEARCH_PLUGIN_DIR . 'templates/similar-products.php';
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * AJAX handler for getting similar products
     */
    public function ajax_get_similar_products() {
        check_ajax_referer('ai-smartsearch-nonce', 'nonce');
        
        $product_id = intval($_POST['product_id']);
        $limit = intval($_POST['limit']) ?: 4;
        
        if (!$product_id) {
            wp_send_json_error('Invalid product ID');
        }
        
        $similar_products = $this->engine->get_similar_products($product_id, $limit);
        
        $response = [];
        foreach ($similar_products as $similar) {
            $product = wc_get_product($similar['product_id']);
            if ($product) {
                $response[] = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html(),
                    'permalink' => $product->get_permalink(),
                    'image' => $product->get_image('woocommerce_thumbnail'),
                    'score' => round($similar['score'], 2)
                ];
            }
        }
        
        wp_send_json_success($response);
    }
} 