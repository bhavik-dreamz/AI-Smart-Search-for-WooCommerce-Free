<?php
/**
 * REST API endpoint for similar products
 */
class SimilarProductsAPI {
    
    private $engine;
    
    public function __construct() {
        $this->engine = new SimilarProductsEngine();
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        register_rest_route('wc/v3', '/products/(?P<id>\d+)/similar', [
            'methods' => 'GET',
            'callback' => [$this, 'get_similar_products'],
            'permission_callback' => [$this, 'get_items_permissions_check'],
            'args' => [
                'id' => [
                    'description' => 'Product ID',
                    'type' => 'integer',
                    'required' => true,
                ],
                'limit' => [
                    'description' => 'Number of similar products to return',
                    'type' => 'integer',
                    'default' => 4,
                    'minimum' => 1,
                    'maximum' => 20,
                ],
                'include_scores' => [
                    'description' => 'Include similarity scores in response',
                    'type' => 'boolean',
                    'default' => false,
                ],
            ],
        ]);
    }
    
    public function get_similar_products($request) {
        $product_id = (int) $request['id'];
        $limit = (int) $request['limit'];
        $include_scores = (bool) $request['include_scores'];
        
        try {
            $similar_products = $this->engine->get_similar_products($product_id, $limit);
            
            $response_data = [];
            foreach ($similar_products as $similar_product) {
                $product = wc_get_product($similar_product['product_id']);
                if (!$product) continue;
                
                $product_data = [
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'slug' => $product->get_slug(),
                    'permalink' => $product->get_permalink(),
                    'price' => $product->get_price(),
                    'regular_price' => $product->get_regular_price(),
                    'sale_price' => $product->get_sale_price(),
                    'on_sale' => $product->is_on_sale(),
                    'images' => $this->get_product_images($product),
                    'categories' => $this->get_product_categories($product),
                    'tags' => $this->get_product_tags($product),
                    'average_rating' => $product->get_average_rating(),
                    'rating_count' => $product->get_rating_count(),
                    'stock_status' => $product->get_stock_status(),
                ];
                
                if ($include_scores) {
                    $product_data['similarity_scores'] = [
                        'overall' => round($similar_product['score'], 3),
                        'category' => round($similar_product['category_score'], 3),
                        'tags' => round($similar_product['tag_score'], 3),
                        'attributes' => round($similar_product['attribute_score'], 3),
                        'price' => round($similar_product['price_score'], 3),
                        'brand' => round($similar_product['brand_score'], 3),
                        'semantic' => round($similar_product['semantic_score'], 3),
                    ];
                }
                
                $response_data[] = $product_data;
            }
            
            return rest_ensure_response($response_data);
            
        } catch (Exception $e) {
            return new WP_Error(
                'similar_products_error',
                'Failed to get similar products: ' . $e->getMessage(),
                ['status' => 500]
            );
        }
    }
    
    public function get_items_permissions_check($request) {
        return true; // Public endpoint, adjust as needed
    }
    
    private function get_product_images($product) {
        $images = [];
        $image_ids = array_merge([$product->get_image_id()], $product->get_gallery_image_ids());
        
        foreach ($image_ids as $image_id) {
            if ($image_id) {
                $images[] = [
                    'id' => $image_id,
                    'src' => wp_get_attachment_image_url($image_id, 'woocommerce_thumbnail'),
                    'src_full' => wp_get_attachment_image_url($image_id, 'full'),
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', true),
                ];
            }
        }
        
        return $images;
    }
    
    private function get_product_categories($product) {
        $categories = [];
        $terms = wp_get_post_terms($product->get_id(), 'product_cat');
        
        foreach ($terms as $term) {
            $categories[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }
        
        return $categories;
    }
    
    private function get_product_tags($product) {
        $tags = [];
        $terms = wp_get_post_terms($product->get_id(), 'product_tag');
        
        foreach ($terms as $term) {
            $tags[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
            ];
        }
        
        return $tags;
    }
}

// Initialize the API
new SimilarProductsAPI();