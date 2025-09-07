# VCB-QR Compatibility Cleanup Log
**Date**: 2025-09-07
**Purpose**: Document all code removals during cleanup process

## JavaScript Cleanup (vcb-qr-compat.js)

### Unused Functions/Variables Removed:
1. **isElementVisible()** function (lines 36-48)
   - Defined but never called anywhere
   - Was likely used during development for visibility checks

2. **window.vcbQRLoaded** variable (lines 284-286)
   - Set to false but never actually used
   - Was probably meant for tracking QR load state

### Code Sections Kept:
- All QR detection and cloning logic (actively used)
- Spinner management functions (required for loading state)
- SweetAlert2 integration (handles popup QRs)
- WooCommerce event monitoring (ensures compatibility)
- Fallback QR generation (provides backup option)
- Debug logging function (controlled by flag)

## CSS Cleanup (vcb-qr-compat.css)

### CSS Rules Removed:
1. **:has() selector rule** (line 355)
   - `.vcb-qr-mobile-slot:has(img) ~ .acb-gw-is-mb .momo-loading`
   - Removed for better browser compatibility (:has is not supported in all browsers)
   - Spinner hiding is handled by JavaScript instead

### CSS Rules Kept:
- All other styles are actively used
- Spinner sizing rules are required to fix 554×554px issue
- Mobile responsive styles are essential
- `.bank-info-item` kept as it may be used by VCB-MH plugin
- `.qrVietqr.loading::after` kept as it may be triggered by VCB-MH

## PHP Cleanup (compat-vcbmh.php)
- No test code or debug output found
- All functions are properly hooked and used
- No var_dump, echo, or print_r statements
- All code is production-ready

## Summary
The code is very clean with minimal unused code. Total removals:
- 1 unused utility function (isElementVisible)
- 1 unused window variable (window.vcbQRLoaded)
- 1 CSS rule with limited browser support (:has selector)

All other code is actively used and required for functionality. The cleanup ensures:
- Better browser compatibility
- Smaller file sizes
- No test/debug code in production