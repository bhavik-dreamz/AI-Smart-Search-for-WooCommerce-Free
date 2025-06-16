<?php
if (!defined('ABSPATH')) {
    exit;
}

$title = __('Similar Products', 'ai-smartsearch');
?>

<div class="ai-smartsearch-similar-products">
    <h2 class="ai-smartsearch-section-title"><?php echo esc_html($title); ?></h2>

    <?php do_action('ai_smartsearch_before_similar_products'); ?>

    <ul class="products columns-4">
        <?php
        foreach ($similar_products as $product) {
            $post_object = get_post($product->ID);
            setup_postdata($GLOBALS['post'] =& $post_object);
            wc_get_template_part('content', 'product');
        }
        ?>
    </ul>

    <?php do_action('ai_smartsearch_after_similar_products'); ?>

    <?php
    if (!function_exists('ai_smartsearch_pro_enabled')) {
        ?>
        <div class="ai-smartsearch-pro-notice">
            <?php _e('Upgrade to Pro for personalized product recommendations based on user behavior and preferences.', 'ai-smartsearch'); ?>
        </div>
        <?php
    }
    ?>
</div>
<?php wp_reset_postdata(); ?> 