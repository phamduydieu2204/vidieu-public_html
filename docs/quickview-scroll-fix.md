# QuickView Scroll Fix Documentation

**Date**: 2025-09-07
**Version**: 1.6.0
**Issue**: Page scrolls to top when selecting product variations in QuickView popup

## Problem Description

When users open a QuickView popup on the homepage (or shop pages) and select a product variation (size, color, etc.), the page automatically scrolls to the top. This creates a poor user experience as users must scroll back down to continue browsing.

## Root Cause Analysis

1. **Anchor Links**: Variation swatches/options use anchor links (`href="#"`) without preventDefault
2. **Focus Changes**: Input focus changes trigger browser scroll behavior
3. **Hash Changes**: Some variation selectors change URL hash, causing scroll
4. **Missing Scroll Lock**: Body remains scrollable when modal is open

## Solution Implementation

### 1. Compatibility Module (`compat-quickview.php`)

- Detects if page has QuickView functionality
- Only loads on pages that need it (homepage, shop, archives)
- Enqueues fix assets with proper dependencies

### 2. JavaScript Fix (`quickview-compat.js`)

**Key Features**:

1. **Prevent Anchor Scrolling**
   - Captures all click events on anchors within QuickView
   - Prevents default behavior for hash links (#, #!, empty hrefs)
   - Handles reset_variations link specially

2. **Maintain Scroll Position**
   - Saves scroll position when QuickView opens
   - Restores position if any action causes scrolling
   - Uses requestAnimationFrame for smooth correction

3. **Body Scroll Lock**
   - Adds `qv-open` class to html/body when modal opens
   - Sets scroll restoration to manual
   - Removes classes when modal closes

4. **Event Monitoring**
   - Listens for NASA theme QuickView events
   - Monitors MagnificPopup events
   - Uses MutationObserver as fallback

5. **WooCommerce Integration**
   - Handles variation found/reset events
   - Maintains position during AJAX updates
   - Works with custom swatch plugins

### 3. CSS Fix (`quickview-compat.css`)

**Scroll Lock Styles**:
```css
html.qv-open, body.qv-open {
    overflow: hidden;
    touch-action: none;
    overscroll-behavior: contain;
}
```

**Mobile Optimizations**:
- Full viewport height for modals
- Better tap targets (44px minimum)
- iOS-specific position fixes

## Debug Mode

Enable debug logging by defining constant in wp-config.php:
```php
define('VIDIEU_QV_DEBUG', true);
```

When enabled, logs:
- Prevented anchor clicks
- Scroll position changes
- QuickView open/close events
- Variation changes

## Browser Compatibility

Tested on:
- Chrome/Edge (Windows, Android)
- Firefox (Windows, Android)
- Safari (macOS, iOS)
- Mobile browsers at 320px-768px widths

## Performance Impact

- Minimal overhead: ~5KB total (JS + CSS)
- Event delegation for efficiency
- No polling or timers (except for position correction)
- Lazy initialization

## Potential Conflicts

Works alongside:
- WooCommerce core
- NASA/Elessi theme
- YITH plugins
- Most swatch/variation plugins

## Testing Checklist

1. **Homepage QuickView**
   - [ ] Open product QuickView
   - [ ] Select color/size variation
   - [ ] Page doesn't scroll to top
   - [ ] Can still browse variations

2. **Mobile Testing**
   - [ ] Body scroll locked when modal open
   - [ ] Modal content still scrollable
   - [ ] Touch events work properly
   - [ ] No rubber-band scrolling on iOS

3. **Reset Variations**
   - [ ] Click "Clear" link
   - [ ] Variations reset properly
   - [ ] No scroll to top

4. **Multiple Opens**
   - [ ] Open/close QuickView multiple times
   - [ ] No duplicate event handlers
   - [ ] Performance remains smooth

## Rollback

To disable the fix:
1. Comment out the include in `vidieu-home-sections.php`
2. Or define: `add_filter('vidieu_has_quickview', '__return_false');`

## Known Limitations

1. Requires jQuery (already loaded by WooCommerce)
2. Specific to NASA/Elessi theme QuickView structure
3. May need adjustment for custom QuickView implementations

## Future Enhancements

1. Add support for more QuickView plugins
2. Implement touch gesture handling
3. Add smooth scroll position restoration
4. Support for gallery navigation within QuickView