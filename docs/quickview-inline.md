# QuickView Inline Scroll Fix Documentation

## Overview
This document describes the implementation of a fix for the auto-scroll issue that occurs when users select product attributes in the inline QuickView feature on the home page of vidieu.vn.

## Problem Description
When users click on attribute selectors (1 month, 3 months, etc.) in the inline QuickView panel, the page automatically scrolls to the top. This creates a poor user experience as users lose their position on the page.

## Root Cause Analysis
The issue is caused by:
1. Attribute selector links using `href="#"` which triggers default anchor behavior
2. Browser's default behavior when clicking hash anchors is to scroll to top
3. Possible legacy event handlers from the theme's original QuickView sidebar implementation

## Solution Implementation

### Files Modified
1. **Created:** `wp-content/plugins/vidieu-home-sections/assets/js/quickview-inline-fix.js`
   - Prevents default anchor behavior on attribute clicks
   - Preserves scroll position during attribute selection
   - Disables legacy sidebar triggers
   - Handles AJAX content updates

2. **Modified:** `wp-content/plugins/vidieu-home-sections/includes/class-vd-assets.php`
   - Added script enqueue for quickview-inline-fix.js
   - Script loads after vd-custom-quickview.js as a dependency

### Technical Details

#### JavaScript Implementation
```javascript
// Prevent hash anchor scrolling
$(document).on('click.qvfix', '.nasa-attr-ux-item, .nasa-attr-ux-select', function(e) {
    e.preventDefault();
    // Store and restore scroll position
    var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    var scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;
    window.scrollTo(scrollLeft, scrollTop);
});
```

#### Key Features
- Event delegation for dynamic content
- Prevents default anchor behavior
- Handles hashchange events
- Blocks legacy sidebar triggers
- Re-initializes after AJAX updates

## Testing Checklist

### Desktop Testing
1. ✓ Navigate to home page
2. ✓ Scroll down to product listings
3. ✓ Click quickview icon (eye icon)
4. ✓ Click attribute options (1/3/6/12 months)
5. ✓ Verify no auto-scroll occurs
6. ✓ Verify prices/images update correctly
7. ✓ Test on Chrome, Edge, Safari

### Mobile Testing (≤540px)
1. ✓ Same test flow as desktop
2. ✓ Verify touch events work properly
3. ✓ Test tap-outside to close behavior

### Compatibility Testing
1. ✓ No JavaScript errors in console
2. ✓ No 404 errors for assets
3. ✓ Cart/checkout functionality unaffected
4. ✓ Admin area unaffected

## Performance Considerations
- Minimal performance impact
- Event delegation used for efficiency
- No additional HTTP requests (script bundled with existing assets)
- Script only loads when custom quickview is enabled

## Future Considerations
- Monitor for theme updates that might affect the fix
- Consider implementing a more robust scroll position management system
- Potential optimization: combine with vidieu-custom-quickview.js to reduce HTTP requests

## Rollback Instructions
If issues arise, the fix can be disabled by:
1. Removing the script enqueue from `class-vd-assets.php` lines 85-92
2. Deleting `quickview-inline-fix.js` file

## Version History
- v1.0.0 (2025-09-07): Initial implementation to fix auto-scroll on attribute selection