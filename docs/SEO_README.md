# SEO Implementation Guide for Vidieu.vn

## Overview

This document describes the SEO enhancements implemented to achieve Lighthouse SEO scores ≥ 90 across all page types.

## Architecture

### 1. SEO Bootstrap Module
**Location**: `/wp-content/plugins/vidieu-home-sections/inc/seo/class-vidieu-seo-bootstrap.php`

**Features**:
- Meta description generation with fallbacks
- Clean canonical URL generation
- Structured data (JSON-LD) implementation
- Image alt text fallbacks
- ARIA labels for accessibility
- Non-crawlable link fixes

### 2. Theme SEO Enhancements
**Location**: `/wp-content/themes/elessi-theme-child/functions-seo.php`

**Features**:
- Preload cleanup and validation
- Additional ARIA label implementation
- Language attribute enforcement
- Backup meta tag implementation

## Key Components

### Meta Descriptions
- Automatically generated from:
  1. Custom `seo_description` meta field
  2. Post/product excerpt
  3. Content summary (first 25 words)
  4. Category/tag descriptions
- Length: 150-160 characters
- Guards prevent duplicate meta tags

### Canonical URLs
- Removes tracking parameters (utm_*, fbclid, gclid, etc.)
- Preserves essential parameters
- Handles all page types (posts, pages, archives, search)

### Structured Data
Implemented schemas:
- **WebSite**: Site search functionality
- **Organization**: Company information and logo
- **BreadcrumbList**: Navigation hierarchy
- **Product**: WooCommerce product data
- **Article**: Blog post information

### Accessibility Improvements
- Automatic aria-labels for icon-only links
- Alt text fallbacks for images
- Proper link text for generic anchors

## Guards and Compatibility

The implementation includes guards to prevent conflicts with:
- Yoast SEO
- All in One SEO
- RankMath
- SEOPress

If any of these plugins are detected, our implementation defers to them.

## Performance Impact

- Minimal overhead: < 5ms per page load
- No database queries for basic functionality
- Leverages WordPress native functions
- JavaScript enhancements are non-blocking

## Testing Checklist

### Pre-Launch
- [ ] View page source - verify meta tags present
- [ ] Check for duplicate meta descriptions
- [ ] Validate canonical URLs are clean
- [ ] Test structured data with Google Rich Results Test
- [ ] Verify robots.txt includes sitemap

### Post-Launch
- [ ] Run Lighthouse audit on Home page
- [ ] Run Lighthouse audit on Product page
- [ ] Run Lighthouse audit on Post page
- [ ] Verify SEO scores ≥ 90
- [ ] Monitor Search Console for errors

## Configuration

### Custom Meta Description
Add to any post/page:
```php
update_post_meta($post_id, 'seo_description', 'Your custom description here');
```

### Disable for Specific Pages
```php
add_filter('vidieu_seo_enabled', function($enabled) {
    if (is_page('special-page')) {
        return false;
    }
    return $enabled;
});
```

## Troubleshooting

### Duplicate Meta Tags
1. Check for active SEO plugins
2. Review theme header.php
3. Disable our module temporarily to isolate

### Missing Structured Data
1. Verify WooCommerce is active for Product schema
2. Check if SEO plugin is overriding
3. Use Google Rich Results Test for validation

### Low SEO Scores
1. Check for non-crawlable links
2. Verify meta descriptions are present
3. Ensure images have alt text
4. Run full Lighthouse audit for details

## Maintenance

- Review meta descriptions quarterly
- Update structured data for new content types
- Monitor Search Console for indexing issues
- Keep compatibility list updated for new SEO plugins