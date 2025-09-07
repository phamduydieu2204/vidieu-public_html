# Vidieu Performance Policy Engine Documentation

**Version**: 2.6.0  
**Date**: 2025-09-07  
**Status**: Learning Mode Active

## Overview

The Vidieu Policy Engine is a route-based script/style management system that intelligently optimizes performance while preserving functionality. It replaces the aggressive whitelist approach that was breaking critical features.

## Key Features

### 1. Route-Based Policies
Instead of a one-size-fits-all approach, each route has its own policy:
- **home**: Homepage optimizations
- **product**: Single product pages
- **cart**: Shopping cart (special handling for AJAX)
- **checkout**: Checkout process
- **order-received**: Payment confirmation (VCB critical)
- **shop**: Category/archive pages
- **single**: Blog posts
- **page**: Static pages

### 2. Policy Modes

#### Learning Mode (Current)
```php
define('VIDIEU_PERF_POLICY_MODE', 'learning');
```
- Captures all scripts/styles loaded on each route
- Saves data to `/wp-content/uploads/vidieu-perf/policy-learning-{route}.json`
- Does NOT block anything - safe for discovering dependencies

#### Allowlist Mode
```php
define('VIDIEU_PERF_POLICY_MODE', 'allowlist');
```
- Only allows scripts/styles explicitly listed for each route
- Strict but safe when properly configured

#### Denylist Mode
```php
define('VIDIEU_PERF_POLICY_MODE', 'denylist');
```
- Blocks known problematic patterns
- More flexible, good for removing bloat

#### Disabled Mode
```php
define('VIDIEU_PERF_POLICY_MODE', 'disabled');
```
- Completely disables the policy engine

## Global Required Handles (NEVER Blocked)

These scripts/styles are protected on ALL pages:

### Core WordPress
- jquery, jquery-core, jquery-migrate
- underscore, backbone, wp-util
- wp-i18n, wp-hooks, wp-polyfill

### WooCommerce Core
- woocommerce, wc-cart, wc-checkout
- wc-add-to-cart, wc-cart-fragments
- wc-country-select, wc-address-i18n

### Payment Critical
- **vcb-mh** (all variations)
- **sweetalert2** (all variations)
- Payment gateway scripts

### Theme Core
- elessi-theme-js, elessi-theme-css

## Route-Specific Requirements

### Cart Page
**Critical for functionality:**
- wc-add-to-cart-variation
- magnific-popup (lightbox)
- owl-carousel, slick (galleries)
- lazy load scripts
- zoom functionality

### Order-Received Page
**ABSOLUTELY CRITICAL:**
- vcb-mh (payment polling)
- sweetalert2 (payment notifications)
- Analytics/tracking scripts

## AJAX Re-initialization

The policy engine automatically handles AJAX updates:

```javascript
// Triggered after WooCommerce AJAX
$(document.body).on('updated_wc_div updated_cart_totals', function() {
    // Re-init lightbox
    // Re-init lazy loading
    // Re-init zoom
    // Trigger custom event: vidieu_ajax_reinit
});
```

## Configuration

### Step 1: Enable Learning Mode
Add to `wp-config.php` or use the provided config:
```php
define('VIDIEU_PERF_POLICY_MODE', 'learning');
```

### Step 2: Visit All Routes
Navigate through:
1. Homepage
2. Product page
3. Add to cart → Cart page
4. Proceed to checkout
5. Complete order → Order-received page

### Step 3: Generate Policies
Learning data is automatically saved to:
```
/wp-content/uploads/vidieu-perf/
├── policy-learning-home.json
├── policy-learning-product.json
├── policy-learning-cart.json
├── policy-learning-checkout.json
└── policy-learning-order-received.json
```

### Step 4: Review & Refine
1. Check each JSON file for unnecessary scripts
2. Create consolidated `policies.json`
3. Switch to allowlist or denylist mode

## Denylist Patterns

Built-in patterns to block (with exceptions):

```php
'elementor' => ['except' => ['elementor-preview']],
'revslider' => ['except' => ['home']],
'instagram-feed' => ['defer' => true],
'wp-embed' => ['except' => ['single']],
```

## Troubleshooting

### Images Canceled on Cart
**Cause**: Gallery/lightbox scripts blocked  
**Fix**: Ensure magnific-popup, owl-carousel are in cart's allowed scripts

### Payment Not Working
**Cause**: vcb-mh or sweetalert2 blocked  
**Fix**: These are globally required, check if mode is interfering

### Scripts Loading Multiple Times
**Cause**: Learning mode capturing duplicates  
**Fix**: Normal in learning mode, will be optimized in allowlist mode

## Emergency Controls

### Disable Everything
```php
define('VIDIEU_DISABLE_ALL_OPTIMIZATIONS', true);
```

### Disable Only Policy Engine
```php
define('VIDIEU_PERF_POLICY_MODE', 'disabled');
```

### Debug Mode
```php
define('VIDIEU_PERF_DEBUG', true);
```

## Best Practices

1. **Always test in Learning Mode first**
2. **Never block payment scripts**
3. **Test AJAX functionality** (cart updates, etc.)
4. **Keep global required list minimal**
5. **Document route-specific needs**

## Migration from V2 Ultimate

The old aggressive whitelist approach:
- Cart: Limited to 25 scripts → Broke images
- Order-received: Limited to 20 scripts → Broke payments

New Policy Engine:
- Smart detection of dependencies
- Route-aware optimization
- Preserves functionality

## Future Enhancements

1. **Auto-learning**: Detect critical scripts automatically
2. **Performance scoring**: Rate each script's impact
3. **Conditional loading**: Load scripts only when features are used
4. **CDN optimization**: Smart preconnect/dns-prefetch