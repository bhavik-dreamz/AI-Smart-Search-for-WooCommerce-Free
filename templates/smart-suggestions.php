<?php
if (!defined('ABSPATH')) {
    exit;
}

$title = __('You May Also Like', 'ai-smartsearch');
?>

<div class="ai-smartsearch-suggestions">
    <h2 class="ai-smartsearch-section-title"><?php echo esc_html($title); ?></h2>

    <?php do_action('ai_smartsearch_before_suggestions'); ?>

    <div class="products columns-4">
        <?php
        foreach ($suggestions as $product) {
            $product_obj = wc_get_product($product->ID);
            if (!$product_obj) {
                continue;
            }
            ?>
            <div class="product">
                <a href="<?php echo esc_url(get_permalink($product->ID)); ?>" class="woocommerce-LoopProduct-link">
                    <?php echo $product_obj->get_image('woocommerce_thumbnail'); ?>
                    <h2 class="woocommerce-loop-product__title"><?php echo esc_html($product_obj->get_name()); ?></h2>
                    <?php echo $product_obj->get_price_html(); ?>
                </a>
            </div>
            <?php
        }
        ?>
    </div>

    <?php do_action('ai_smartsearch_after_suggestions'); ?>

    <?php
    if (!function_exists('ai_smartsearch_pro_enabled')) {
        ?>
        <div class="ai-smartsearch-pro-notice">
            <?php _e('Upgrade to Pro for AI-powered personalized product suggestions based on user behavior and preferences.', 'ai-smartsearch'); ?>
        </div>
        <?php
    }
    ?>
</div> 