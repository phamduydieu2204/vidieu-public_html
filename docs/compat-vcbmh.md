# VCB-MH Compatibility Layer Documentation

**Version**: 1.0.0  
**Package**: Vidieu_Home_Sections  
**Location**: `/wp-content/plugins/vidieu-home-sections/compat/`

## Overview

The VCB-MH compatibility layer ensures proper display and functionality of VCB-MH payment gateway QR codes on mobile devices without modifying the third-party plugin code.

## Architecture

### PHP Component (`compat-vcbmh.php`)

#### Class: `Vidieu_VCB_MH_Compat`
Singleton class that manages the compatibility layer.

**Key Methods:**
- `init_compat()` - Initializes compatibility features
- `setup_vcb_compat()` - Sets up page-specific compatibility
- `enqueue_compat_assets()` - Loads CSS/JS assets conditionally
- `add_critical_css()` - Injects critical CSS for immediate effect
- `whitelist_vcb_scripts/styles()` - Ensures VCB-MH resources load
- `ensure_vcb_script_loading()` - Modifies script tags for proper loading

**Hooks Used:**
- `init` (priority 5) - Early initialization
- `wp` (priority 20) - Page context detection
- `wp_enqueue_scripts` (priority 25) - Asset loading
- `wp_head` (priority 5) - Critical CSS injection
- `script_loader_tag` - Script attribute modification

### CSS Component (`vcb-qr-compat.css`)

**Mobile Breakpoints:**
- `<= 768px` - Tablet and below
- `<= 540px` - Small mobile devices
- `<= 375px` - iPhone SE size

**Key Fixes:**
1. **Visibility Override**
   ```css
   .qrVietqr { 
       display: block !important;
       visibility: visible !important;
   }
   ```

2. **Responsive Sizing**
   ```css
   .qrVietqr img {
       max-width: 100% !important;
       height: auto !important;
   }
   ```

3. **Container Overflow**
   ```css
   #payment-info {
       overflow: visible !important;
   }
   ```

### JavaScript Component (`vcb-qr-compat.js`)

**Core Functions:**
- `ensureQRVisibility()` - Forces QR elements to be visible
- `initVCBQR()` - Initializes VCB-MH specific fixes
- `handleSweetAlertQR()` - Manages QR in popups
- `handleResponsive()` - Responds to viewport changes
- `monitorWooCommerceEvents()` - Listens for WC updates
- `addImageFallback()` - Provides retry mechanism

**Event Monitoring:**
- DOM MutationObserver for dynamic content
- Window resize/orientation events
- WooCommerce AJAX events
- Image load/error events

## Selectors Used

### QR Element Selectors
```javascript
const qrSelectors = [
    '.qrVietqr',
    '#qrVietqr',
    '.vcb-qr-code',
    '#vcb-qr-code',
    '[class*="vcb"][class*="qr"]',
    '[id*="vcb"][id*="qr"]',
    'img[src*="vietqr.io"]',
    'img[src*="qr_only.jpg"]'
];
```

### Container Selectors
- `#payment-info` - Main payment information container
- `#left-col`, `#right-col` - Column layout containers
- `.swal2-popup` - SweetAlert2 popup container

## Events Listened

### WooCommerce Events
- `updated_wc_div` - Generic WC content update
- `updated_checkout` - Checkout form updates
- `wc_fragments_refreshed` - Cart fragments update

### DOM Events
- `DOMContentLoaded` - Initial setup
- `load` - Final visibility check
- `resize` - Viewport size changes
- `orientationchange` - Device rotation

## Configuration

### Localized Script Data
```javascript
window.vidieuVCBCompat = {
    ajaxUrl: '/wp-admin/admin-ajax.php',
    isOrderReceived: boolean,
    isCheckout: boolean,
    debug: boolean
};
```

## Performance Considerations

1. **Conditional Loading**
   - Assets only load on checkout/order-received pages
   - No impact on other pages

2. **Debounced Events**
   - Resize events debounced to 250ms
   - Prevents excessive recalculation

3. **Selective Observation**
   - MutationObserver only on payment container
   - Reduces DOM observation overhead

## Browser Compatibility

- **Desktop**: All modern browsers
- **Mobile**: iOS 12+, Android 5+
- **Features Used**:
  - MutationObserver (IE11+)
  - CSS Flexbox (IE10+)
  - ES5 JavaScript (IE9+)

## Debugging

Debug logging is controlled by the VIDIEU_VCBQR_DEBUG flag. To enable debug mode:

1. Set in PHP (recommended):
   ```php
   define('VIDIEU_VCBQR_DEBUG', true);
   ```

2. Set via JavaScript:
   ```javascript
   window.VIDIEU_VCBQR_DEBUG = true;
   ```

3. Or via the localized config:
   ```javascript
   window.vidieuVCBCompat.debug = '1';
   ```

Debug messages will appear in browser console with prefix `[VCB QR Compat]`. All debug logs are removed from production code unless the debug flag is explicitly enabled.

## Fallback Mechanisms

1. **Image Reload**
   - Automatic retry on image load failure
   - Manual retry button for users

2. **Multiple Visibility Checks**
   - Initial check on DOM ready
   - Delayed checks at 1s and 3s
   - Event-driven checks

3. **Parent Container Fix**
   - Recursively ensures parent visibility
   - Handles deeply nested QR elements

## Known Limitations

1. Cannot modify VCB-MH plugin directly
2. Relies on specific CSS selectors
3. May need updates if VCB-MH changes structure

## Maintenance Notes

- Monitor VCB-MH plugin updates for structural changes
- Test after WooCommerce major updates
- Verify after theme updates affecting checkout
- Check mobile browser updates for compatibility

## DOM-Specific Implementation

### Clone Strategy
The compatibility layer implements a QR clone strategy to handle the specific DOM structure where:
- QR exists in `.anPc` (desktop section) which is hidden on mobile
- `.anMoblie` (mobile section with typo) lacks QR content

**Process**:
1. Detect mobile viewport (≤768px)
2. Find QR in `.anPc img.qrVietqr[src*="api.vietqr.io"]`
3. Clone QR to new `#vcb-qr-mobile` slot within `.anMoblie`
4. Hide loading spinner when QR is present
5. Provide fallback UI after 8s timeout

### Typo-Friendly Selectors
Handles both `.anMoblie` (actual) and `.anMobile` (expected) class names to ensure compatibility regardless of typo fixes.

## Optimization Exclusions

### Autoptimize
The compatibility scripts include `class="no-lazyload"` to prevent Autoptimize from processing them.

### Cloudflare Rocket Loader
Scripts include `data-cfasync="false"` to prevent Rocket Loader from deferring execution.

### WordPress Script Optimization
- Uses `wp_add_inline_script` instead of `wp_localize_script` to avoid escaping issues
- Removes any `defer` or `async` attributes
- Ensures scripts load in footer with proper ordering

## Troubleshooting

### Localized Script Displayed as Text
**Issue**: The `vidieuVCBCompat` variable appears as visible text on the page.

**Cause**: Script optimization or security plugins escaping the localized data.

**Solution**: The compatibility layer now uses `wp_add_inline_script` with JSON encoding to ensure proper execution.

### QR Not Appearing on Mobile
**Issue**: QR code exists but isn't visible on mobile devices.

**Solutions**:
1. **Clone Strategy**: Automatically clones QR from `.anPc` to `.anMoblie` section
2. **Fallback Generation**: Creates QR from page data if none exists
3. **Force Visibility**: CSS overrides ensure QR displays even if parent is hidden

## Plugin Path Resolution

The compatibility files must be loaded with correct paths. The compat file is located at:
```
/wp-content/plugins/vidieu-home-sections/compat/compat-vcbmh.php
```

To correctly calculate asset URLs from this location:
```php
$plugin_dir = dirname(dirname(__FILE__));
$plugin_url = plugins_url('', $plugin_dir . '/vidieu-home-sections.php');
$css_url = $plugin_url . '/assets/css/vcb-qr-compat.css';
$js_url = $plugin_url . '/assets/js/vcb-qr-compat.js';
```

## Left/Right Column Layout

VCB-MH plugin renders QR codes in both left and right columns. The compatibility layer now checks all possible locations:
1. Desktop section (`.anPc`)
2. Right column (`#right-col`)
3. Left column (`#left-col`)

This ensures QR detection works regardless of which column is visible on mobile.

## Spinner Management

The compatibility layer includes a spinner management system to properly display loading states:

1. **Spinner Slot**: Creates a dedicated container (`#vcb-qr-spinner-slot`) above instruction text
2. **Desktop Fix**: Prevents spinner from overlapping "Bước 1" text
3. **Mobile Support**: Ensures spinner is visible on mobile devices while QR loads
4. **Automatic Hiding**: Spinner disappears when QR image loads successfully

### DOM Cleanup

The compatibility layer implements several cleanup mechanisms:

1. **Script Tag Removal**: Automatically removes the extra script tag generated by WordPress:
   ```javascript
   $('#vidieu-vcb-qr-compat-js-extra').remove();
   ```

2. **Debug Code Cleanup**: All console.log statements have been removed from production code. Debug output only occurs when the VIDIEU_VCBQR_DEBUG flag is explicitly enabled.

3. **Idempotent Initialization**: Prevents duplicate initialization and observers with the `window.vcbQRInitialized` flag.

4. **Event Handler Optimization**: All event handlers are properly scoped and debounced to prevent performance issues.

## Version History

- **1.0.5** (2025-09-07) - Production cleanup and optimization
  - Centered spinner properly in slot using flexbox
  - Removed all debug console.log statements from production code
  - Debug logging now only active when VIDIEU_VCBQR_DEBUG flag is enabled
  - Verified no duplicate event listeners or observers
  - Confirmed proper DOM cleanup of extra script tags

- **1.0.4** (2025-09-07) - Spinner management and DOM cleanup
  - Added spinner slot system to fix desktop text overlap
  - Implemented mobile spinner visibility
  - Automatically removes extra script tag from DOM
  - Centralized spinner hiding with `hideSpinner()` function

- **1.0.3** (2025-09-07) - Path fixes and optimization
  - Fixed 404 errors for CSS/JS files with correct `plugins_url()` usage
  - Implemented debouncing for MutationObserver to prevent duplicate logs
  - Added idempotent initialization with `window.vcbQRInitialized` flag
  - Enhanced QR detection for left/right column layouts
  - Switched back to `wp_localize_script` for better compatibility

- **1.0.2** (2025-09-07) - Localization fix and fallback QR
  - Fixed localized script being displayed as text
  - Added fallback QR generation from page data
  - Added Autoptimize/Cloudflare exclusions
  - Enhanced error handling with retry links
  - Use file modification time for cache busting

- **1.0.1** (2025-09-07) - Mobile clone strategy
  - Implemented QR clone from desktop to mobile section
  - Added typo-friendly selectors
  - Fixed enqueue order and localization
  - Added fallback message after timeout
  - Fixed inline CSS syntax errors
  
- **1.0.0** (2025-09-07) - Initial release
  - Mobile QR visibility fixes
  - Performance optimization compatibility
  - SweetAlert2 integration
  - Responsive improvements