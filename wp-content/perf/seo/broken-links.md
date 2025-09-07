# Broken Links Report
**Generated**: 2025-09-07

## Summary

Based on PSI analysis, there are significant issues with non-crawlable links across the site:

| Page Type | Non-Crawlable Links Count |
|-----------|---------------------------|
| Home | 102 links |
| Product | 109 links |
| Post | 54 links |

## Known Issues

These non-crawlable links are primarily:

### 1. JavaScript-Dependent Links
- Quick view buttons without href
- AJAX "Add to Cart" buttons
- Wishlist toggle links
- Modal/popup triggers

### 2. Icon-Only Links Missing Accessibility
- Cart icon
- Wishlist icon
- Search icon
- Account icon

### 3. Links with Empty or Missing href
- `<a>` tags without href attribute
- `<a href="">` empty href
- `<a href="#">` placeholder hrefs used for JS events

## Affected Elements (Common Patterns)

```html
<!-- Quick View Button -->
<a class="quick-view" data-product="123">Quick View</a>

<!-- Add to Cart AJAX -->
<a class="add_to_cart_button ajax_add_to_cart" data-product_id="456">Add to Cart</a>

<!-- Wishlist -->
<a class="wishlist-icon" data-id="789"><i class="icon-heart"></i></a>

<!-- Search Toggle -->
<a class="search-toggle"><i class="icon-search"></i></a>
```

## Recommendations

### 1. Add Proper href Attributes
```html
<!-- Better: Progressive Enhancement -->
<a href="/product/quick-view/123" class="quick-view" data-product="123">Quick View</a>
<a href="/cart/add/456" class="add_to_cart_button ajax_add_to_cart" data-product_id="456">Add to Cart</a>
```

### 2. Add ARIA Labels to Icon Links
```html
<!-- Better: Accessible Icons -->
<a href="/wishlist" class="wishlist-icon" aria-label="Add to Wishlist"><i class="icon-heart"></i></a>
<a href="/search" class="search-toggle" aria-label="Search"><i class="icon-search"></i></a>
```

### 3. Use Buttons for Non-Navigation Actions
```html
<!-- Better: Use button for actions -->
<button type="button" class="quick-view" data-product="123">Quick View</button>
```

## Implementation Status

The SEO Bootstrap module includes fixes for:
- ✅ Automatic aria-label addition to icon links
- ✅ JavaScript fallback for links without href
- ✅ Role="button" for action links

These fixes are implemented in:
- `/wp-content/plugins/vidieu-home-sections/inc/seo/class-vidieu-seo-bootstrap.php`
- `/wp-content/themes/elessi-theme-child/functions-seo.php`