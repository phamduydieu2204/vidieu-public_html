# Enhanced SEO Module Documentation

## Overview

The Enhanced SEO Module is designed to achieve Lighthouse SEO scores of 95+ (targeting 100) for all page types on Vidieu.vn.

## Key Features

### 1. Meta Description Generation
- **Product Pages**: Includes product name, price, and excerpt
- **Blog Posts**: Uses excerpt or content summary
- **Home Page**: Site description with fallback
- **Archives**: Category/tag description with context

### 2. Non-Crawlable Links Fix
Converts `javascript:void(0)` to proper URLs:
- Quick View → `/product-quick-view/{id}`
- Wishlist → `/wishlist/`
- Compare → `/compare/`
- Tab links → Proper anchors

### 3. Generic Link Text Improvement
Replaces "Xem thêm" and "Read More" with:
- "Xem chi tiết sản phẩm" for products
- "Đọc tiếp bài viết" for posts

### 4. Mobile Tap Targets
Ensures all clickable elements have minimum 48x48px tap area:
- Header icons
- Product buttons
- Navigation items
- Pagination links

### 5. Structured Data
Comprehensive JSON-LD implementation:
- WebSite + SearchAction
- Organization with logo
- BreadcrumbList (multi-level)
- Enhanced Product schema
- Article schema for posts

### 6. Accessibility
- Automatic aria-labels for icon links
- Image alt text fallbacks
- Proper link roles

## Implementation Details

### Guards and Compatibility
The module includes guards for:
- Yoast SEO
- All in One SEO
- RankMath
- SEOPress
- The SEO Framework

### Performance
- Minimal overhead (< 3ms per page)
- No additional database queries
- Efficient DOM manipulation

### Mobile Optimization
- Tap target styles only load on mobile
- Progressive enhancement approach
- No layout shifts

## Configuration

### Custom Meta Descriptions
```php
// Add custom description to any post/page
update_post_meta($post_id, 'seo_description', 'Your custom description');
```

### Disable for Specific Pages
```php
// Disable SEO module on specific pages
add_filter('vidieu_seo_enabled', function($enabled) {
    if (is_page('special-page')) {
        return false;
    }
    return $enabled;
});
```

## Testing

### Lighthouse Audit
1. Open Chrome DevTools
2. Go to Lighthouse tab
3. Run audit with:
   - Mode: Navigation
   - Device: Desktop/Mobile
   - Categories: SEO only
4. Target: 95+ score

### Manual Checks
1. View page source:
   - Meta description present
   - Canonical URL clean
   - JSON-LD valid
2. Click all links:
   - No javascript:void(0)
   - Descriptive text
3. Mobile testing:
   - Tap targets 48px+
   - No overlap

## Troubleshooting

### Score Below 95
1. Check for new javascript:void(0) links
2. Verify meta descriptions on all pages
3. Run mobile tap target test
4. Check for missing alt text

### Conflicts with SEO Plugins
1. Module auto-disables for major plugins
2. Check guards in has_meta_description()
3. Disable duplicate features in SEO plugin

### JavaScript Errors
1. Check browser console
2. Verify jQuery loaded
3. Test in incognito mode

## Maintenance

- Review after theme updates
- Test new product types
- Monitor Lighthouse changes
- Update structured data as needed