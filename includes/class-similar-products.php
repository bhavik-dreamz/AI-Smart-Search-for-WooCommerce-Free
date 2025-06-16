<?php
namespace AI_SmartSearch;

class Similar_Products {
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
        
        add_action('woocommerce_after_single_product_summary', [$this, 'display_similar_products'], 20);
        add_shortcode('ai_similar_products', [$this, 'similar_products_shortcode']);
        
    }

    /**
     * Get similar products based on meta attributes
     */
    private function get_meta_attribute_matches($product_id) {
        $meta_queries = [];
        $product = wc_get_product($product_id);
        
        if (!$product) {
            return $meta_queries;
        }

        // Get product attributes from meta
        $product_attributes = get_post_meta($product_id, '_product_attributes', true);
        
        if (!empty($product_attributes) && is_array($product_attributes)) {
            foreach ($product_attributes as $attribute_key => $attribute) {
                if (!empty($attribute['value'])) {
                    // Handle both taxonomy and custom attributes
                    if ($attribute['is_taxonomy']) {
                        $terms = wp_get_post_terms($product_id, $attribute_key, ['fields' => 'ids']);
                        if (!empty($terms)) {
                            $meta_queries[] = [
                                'taxonomy' => $attribute_key,
                                'field' => 'term_id',
                                'terms' => $terms,
                                'operator' => 'IN'
                            ];
                        }
                    } else {
                        // Handle custom attributes stored as meta
                        $values = explode('|', $attribute['value']);
                        if (!empty($values)) {
                            $meta_queries[] = [
                                'key' => $attribute_key,
                                'value' => $values,
                                'compare' => 'IN'
                            ];
                        }
                    }
                }
            }
        }

        return $meta_queries;
    }

    /**
     * Get similar products
     */
    public function get_similar_products($product_id, $limit = 4) {
        $product = wc_get_product($product_id);
        if (!$product || !$product->is_type(['simple', 'variable'])) {
            return [];
        }

        $categories = wp_get_post_terms($product_id, 'product_cat', ['fields' => 'ids']);
        $tags = wp_get_post_terms($product_id, 'product_tag', ['fields' => 'ids']);
        $attributes = $product->get_attributes();

        // Build attribute queries
        $attribute_queries = [];
        foreach ($attributes as $attribute) {
            $taxonomy = $attribute->get_name();
            $terms = wp_get_post_terms($product_id, $taxonomy, ['fields' => 'ids']);
            if (!empty($terms) && taxonomy_exists($taxonomy)) {
                $attribute_queries[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $terms,
                    'operator' => 'IN'
                ];
            }
        }

        // Get meta attribute matches
        $meta_attribute_queries = $this->get_meta_attribute_matches($product_id);
        

        // Build the main tax query
        $tax_query = ['relation' => 'OR'];

        // Add category query
        if (!empty($categories)) {
            $tax_query[] = [
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $categories,
                'operator' => 'IN'
            ];
        }

        // Add tag query
        if (!empty($tags)) {
            $tax_query[] = [
                'taxonomy' => 'product_tag',
                'field' => 'term_id',
                'terms' => $tags,
                'operator' => 'IN'
            ];
        }

        // Add attribute queries
        if (!empty($attribute_queries)) {
            $tax_query[] = [
                'relation' => 'AND',
                $attribute_queries
            ];
        }

        // Main query args
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $limit,
            'post__not_in' => [$product_id],
            'ignore_sticky_posts' => true,
            'tax_query' => $tax_query,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_stock_status',
                    'value' => 'instock',
                    'compare' => '='
                ],
                [
                    'key' => '_visibility',
                    'value' => ['catalog', 'visible'],
                    'compare' => 'IN'
                ]
            ],
            'orderby' => [
                'relevance' => 'DESC',
                'date' => 'DESC'
            ]
        ];

        // Add meta attribute queries if any
        if (!empty($meta_attribute_queries)) {
            $args['meta_query'][] = [
                'relation' => 'OR',
                $meta_attribute_queries
            ];
        }

        // Allow modification of query args
        $args = apply_filters('ai_smartsearch_similar_products_args', $args, $product_id);
        

        // Run the main query
        $query = new \WP_Query($args);
        $similar_products = $query->posts;

        // If we don't have enough results, try a fallback query
        if (count($similar_products) < $limit) {
            $remaining = $limit - count($similar_products);
            $exclude_ids = array_merge([$product_id], wp_list_pluck($similar_products, 'ID'));

            // Build fallback tax query
            $fallback_tax_query = ['relation' => 'OR'];

            // Add category query
            if (!empty($categories)) {
                $fallback_tax_query[] = [
                    'taxonomy' => 'product_cat',
                    'field' => 'term_id',
                    'terms' => $categories,
                    'operator' => 'IN'
                ];
            }

            // Add tag query
            if (!empty($tags)) {
                $fallback_tax_query[] = [
                    'taxonomy' => 'product_tag',
                    'field' => 'term_id',
                    'terms' => $tags,
                    'operator' => 'IN'
                ];
            }

            // Fallback query args
            $fallback_args = [
                'post_type' => 'product',
                'posts_per_page' => $remaining,
                'post__not_in' => $exclude_ids,
                'ignore_sticky_posts' => true,
                'tax_query' => $fallback_tax_query,
                'meta_query' => [
                    'relation' => 'AND',
                    [
                        'key' => '_stock_status',
                        'value' => 'instock',
                        'compare' => '='
                    ],
                    [
                        'key' => '_visibility',
                        'value' => ['catalog', 'visible'],
                        'compare' => 'IN'
                    ]
                ],
                'orderby' => [
                    'relevance' => 'DESC',
                    'date' => 'DESC'
                ]
            ];

            // Add meta attribute queries to fallback if any
            if (!empty($meta_attribute_queries)) {
                $fallback_args['meta_query'][] = [
                    'relation' => 'OR',
                    $meta_attribute_queries
                ];
            }

            $fallback_query = new \WP_Query($fallback_args);
            $fallback_products = $fallback_query->posts;

            $similar_products = array_merge($similar_products, $fallback_products);
        }

        return $similar_products;
    }

    /**
     * Get parent and child categories for a given set of category IDs
     */
    private function get_parent_child_categories($category_ids) {
        $all_categories = [];
        
        foreach ($category_ids as $category_id) {
            // Get parent categories
            $ancestors = get_ancestors($category_id, 'product_cat', 'taxonomy');
            $all_categories = array_merge($all_categories, $ancestors);
            
            // Get child categories
            $children = get_terms([
                'taxonomy' => 'product_cat',
                'parent' => $category_id,
                'fields' => 'ids',
                'hide_empty' => true
            ]);
            $all_categories = array_merge($all_categories, $children);
        }
        
        return array_unique($all_categories);
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
        $args = [
            'post_type' => 'product',
            'posts_per_page' => $atts['limit'],
            'post__not_in' => [$product_id]
        ];

        $args = apply_filters('ai_smartsearch_similar_products_args', $args, $product_id);
        
        $query = new \WP_Query($args);
        $similar_products = $query->posts;   

        ob_start();
        include AI_SMARTSEARCH_PLUGIN_DIR . 'templates/similar-products.php';
        wp_reset_postdata();
        return ob_get_clean();
    }

} 