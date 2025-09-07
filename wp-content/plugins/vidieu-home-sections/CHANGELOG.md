# Vidieu Home Sections - Changelog

## [2.6.0] - 2025-09-07

### Added
- New Policy Engine for intelligent route-based script/style management
- Learning mode to safely discover script dependencies
- Route-specific policies (home, cart, checkout, order-received, etc.)
- AJAX re-initialization for cart/checkout dynamic updates
- Global protection for payment scripts (vcb-mh, sweetalert2)
- Configuration file for performance settings

### Fixed
- Cart page images being canceled due to aggressive script blocking
- Order-received page breaking due to payment script blocking
- Gallery/lightbox functionality on cart page
- Payment confirmation polling for VCB gateway
- AJAX cart updates not re-initializing UI components

### Changed
- Replaced aggressive whitelist with intelligent policy system
- Temporarily disabled cart/checkout whitelist (ENABLE_CART_CHECKOUT_WHITELIST = false)
- Temporarily disabled order-received optimization (ENABLE_ORDER_RECEIVED_OPT = false)
- Default mode set to 'learning' for safe deployment

### Removed
- Debug console.log statements from production code
- Experimental code that was causing issues

## [2.5.0] - 2025-09-07

### Added
- ReCAPTCHA Manager for centralized loading
- Duplicate request guard V2 Ultimate

### Fixed
- ReCAPTCHA triple loading issue (saved 1.6MB per page)
- Various script duplications

## Configuration Options

### Policy Modes
```php
// In wp-config.php or vidieu-perf-config.php
define('VIDIEU_PERF_POLICY_MODE', 'learning'); // Options: learning, allowlist, denylist, disabled
```

### Emergency Controls
```php
// Disable all optimizations
define('VIDIEU_DISABLE_ALL_OPTIMIZATIONS', true);

// Enable debug logging
define('VIDIEU_PERF_DEBUG', true);
```

## Migration Guide

If upgrading from v2.5.0:

1. The aggressive whitelist is temporarily disabled
2. Policy Engine is in learning mode by default
3. Visit all major routes to generate learning data
4. Review `/wp-content/uploads/vidieu-perf/` for collected data
5. Switch to allowlist or denylist mode after review