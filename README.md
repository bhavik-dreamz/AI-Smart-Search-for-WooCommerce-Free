# AI Smart Search for WooCommerce

A powerful and intelligent product search and recommendation system for WooCommerce that enhances the shopping experience with smart product suggestions and similar product matching.

## Features

### Smart Product Search
- Enhanced search functionality with intelligent matching
- Title-based similarity matching
- Category and tag-based recommendations
- Price range consideration
- Brand matching
- Product attribute matching
- Image gallery similarity

### Similar Products Engine
- Advanced product similarity algorithm
- Multiple matching criteria:
  - Product titles
  - Categories and tags
  - Price ranges
  - Product attributes
  - Image galleries
  - Brand information
- Configurable similarity weights
- Performance optimized queries
- WooCommerce product lookup table support

### Integration Options
- Shortcode support: `[ai_similar_products]`
- WooCommerce hooks integration
- AJAX endpoints for dynamic loading
- REST API support

## Installation

1. Upload the `ai-smartsearch` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure the plugin settings under WooCommerce > AI Smart Search

## Usage

### Display Similar Products
Use the shortcode to display similar products anywhere on your site:
```php
[ai_similar_products limit="4"]
```

### Programmatic Usage
```php
// Get similar products for a specific product
$similar_products = $ai_smartsearch->get_similar_products($product_id, $limit = 4);

// Get smart suggestions
$suggestions = $ai_smartsearch->get_smart_suggestions($search_term);
```

### Hooks and Filters
```php
// Modify similar products query
add_filter('ai_smartsearch_modify_query', function($query) {
    // Modify query as needed
    return $query;
});

// Add custom business rules
add_filter('similar_products_business_rules', function($pass, $candidate_product, $base_product) {
    // Add custom rules
    return $pass;
}, 10, 3);
```

## Configuration

### Similarity Weights
The plugin uses configurable weights for different matching criteria:
- Category: 0.4
- Tags: 0.2
- Attributes: 0.25
- Price Range: 0.1
- Brand: 0.15
- Semantic: 0.3

### Performance Settings
- Caching enabled by default (1 hour expiry)
- WooCommerce product lookup table support
- Optimized SQL queries
- Configurable result limits

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher

## Pro Version Features

The Pro version includes additional features:
- Personalized product recommendations
- User behavior tracking
- Advanced analytics
- Custom recommendation rules
- A/B testing support
- Priority support

## Support

For support, feature requests, or bug reports, please:
1. Check the [documentation](https://your-docs-url.com)
2. Submit an issue on [GitHub](https://github.com/your-repo)
3. Contact support at support@your-domain.com

## Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- Developed by [Your Company Name]
- Built with ❤️ for the WooCommerce community 