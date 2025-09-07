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

Enable debug mode by setting:
```javascript
window.vidieuVCBCompat.debug = true;
```

Debug messages will appear in browser console with prefix `[VCB QR Compat]`.

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

## Version History

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