<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php settings_errors(); ?>

    <div class="ai-smartsearch-admin-content">
        <div class="ai-smartsearch-admin-main">
            <form method="post" action="options.php">
                <?php
                settings_fields('ai_smartsearch_settings');
                do_settings_sections('ai_smartsearch_settings');
                ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="ai_smartsearch_api_key"><?php _e('API Key', 'ai-smartsearch'); ?></label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="ai_smartsearch_api_key" 
                                   name="ai_smartsearch_api_key" 
                                   value="<?php echo esc_attr(get_option('ai_smartsearch_api_key')); ?>" 
                                   class="regular-text">
                            <p class="description">
                                <?php _e('Enter your API key for AI Smart Search. This is required for the Pro version.', 'ai-smartsearch'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="ai_smartsearch_enabled"><?php _e('Enable AI Search', 'ai-smartsearch'); ?></label>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" 
                                       id="ai_smartsearch_enabled" 
                                       name="ai_smartsearch_enabled" 
                                       value="1" 
                                       <?php checked(get_option('ai_smartsearch_enabled'), 1); ?>>
                                <?php _e('Enable AI-powered search functionality', 'ai-smartsearch'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>

        <div class="ai-smartsearch-admin-sidebar">
            <div class="ai-smartsearch-pro-features">
                <h2><?php _e('Pro Features', 'ai-smartsearch'); ?></h2>
                <ul>
                    <li><?php _e('Advanced AI-powered search with semantic understanding', 'ai-smartsearch'); ?></li>
                    <li><?php _e('Personalized product recommendations', 'ai-smartsearch'); ?></li>
                    <li><?php _e('Search analytics and insights', 'ai-smartsearch'); ?></li>
                    <li><?php _e('Custom search ranking rules', 'ai-smartsearch'); ?></li>
                    <li><?php _e('Priority support', 'ai-smartsearch'); ?></li>
                </ul>
                <a href="#" class="button button-primary"><?php _e('Upgrade to Pro', 'ai-smartsearch'); ?></a>
            </div>

            <div class="ai-smartsearch-stats">
                <h2><?php _e('Search Statistics', 'ai-smartsearch'); ?></h2>
                <?php
                global $wpdb;
                $table_name = $wpdb->prefix . 'ai_smartsearch_logs';
                $top_searches = $wpdb->get_results(
                    $wpdb->prepare(
                        "SELECT keyword, count 
                        FROM {$wpdb->prefix}ai_smartsearch_logs 
                        ORDER BY count DESC 
                        LIMIT %d",
                        5
                    )
                );

                if ($top_searches) {
                    echo '<ul>';
                    foreach ($top_searches as $search) {
                        echo '<li>' . esc_html($search->keyword) . ' - ' . esc_html($search->count) . ' searches</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p>' . __('No search data available yet.', 'ai-smartsearch') . '</p>';
                }
                ?>
            </div>
        </div>
    </div>
</div> 