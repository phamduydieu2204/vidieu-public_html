# Re:plain Live Chat Integration

**Version:** 1.7.0  
**Last Updated:** 2025-09-08  
**Context:** Re:plain live chat widget integration for Vidieu.vn

## Overview

This module integrates Re:plain live chat widget into the website with customized behavior for desktop and mobile devices.

## Features

### Desktop
- Widget positioned at **left-bottom** corner (20px margin)
- Does not conflict with existing contact buttons on the right
- Loads immediately on page load

### Mobile  
- Default widget bubble is **hidden**
- Added "Chat trực tiếp (Re:plain)" item to mobile chat popup
- Lazy loading - script loads only when user clicks the chat item
- No page reload required

## Configuration

### Constants

```php
// Enable/disable Re:plain globally
define('VIDIEU_REPLAIN_ENABLE', true); // Default: true

// Hide widget bubble on mobile devices
define('VIDIEU_REPLAIN_HIDE_ON_MOBILE', true); // Default: true

// Desktop position (currently only left-bottom supported)
define('VIDIEU_REPLAIN_DESKTOP_POSITION', 'left-bottom'); // Default: 'left-bottom'
```

### Filters

```php
// Enable/disable Re:plain
add_filter('vidieu_replain_enable', '__return_false'); // To disable

// Hide on mobile
add_filter('vidieu_replain_hide_on_mobile', '__return_false'); // To show on mobile

// Desktop position
add_filter('vidieu_replain_desktop_position', function() {
    return 'left-bottom'; // Only option currently
});
```

## JavaScript API

### Global Helper Object

```javascript
window.VidieuReplain = {
    // Open Re:plain chat
    open: function() { ... },
    
    // Check if loaded
    isLoaded: boolean,
    
    // Check if loading
    isLoading: boolean
}
```

### Usage Examples

```javascript
// Open Re:plain chat programmatically
window.VidieuReplain.open();

// Check if Re:plain is loaded
if (window.VidieuReplain.isLoaded) {
    // Do something
}
```

## Implementation Details

### File Structure
```
vidieu-home-sections/
├── inc/
│   └── replain-chat.php         # Main PHP class
├── assets/
│   ├── css/
│   │   └── replain-chat.css    # Styling for widget position
│   └── js/
│       └── replain-mobile.js   # Mobile popup integration
```

### PHP Class: VD_Replain_Chat
- Handles script injection via `wp_footer`
- Manages configuration via constants/filters
- Enqueues necessary CSS/JS files
- Prevents loading in admin area

### CSS Positioning
- Desktop: Uses `!important` only for positioning to override Re:plain defaults
- Mobile: Hides widget with `display: none` until explicitly opened
- Z-index management to prevent conflicts

### Mobile Integration
- Dynamically injects Re:plain item into existing chat popup
- Uses MutationObserver to handle dynamic popup loading
- Closes popup before opening Re:plain for better UX

## Lazy Loading Strategy

### Desktop
- Script loads immediately on page load
- No lazy loading to ensure instant availability

### Mobile
- Script only loads when user clicks "Chat trực tiếp (Re:plain)"
- Reduces initial page load on mobile devices
- Improves performance scores

## Known Issues & Workarounds

### Re:plain API Variations
The widget tries multiple methods to open Re:plain:
1. `window.Replain.open()`
2. `window.Replain.show()`
3. `window.replain.open()`
4. `window.replain.show()`
5. Fallback: Find and click widget button

### Mobile Popup Detection
The integration looks for multiple selectors to find the chat popup:
- `#nasa-support-chat`
- `.nasa-support-chat-popup`
- `.nasa-support-chat-wrap`

### Widget Visibility on Mobile
When opened on mobile, the widget gets class `replain-opened` to force visibility.

## Testing Checklist

- [ ] Desktop: Widget appears at left-bottom corner
- [ ] Desktop: No overlap with right-side contact buttons
- [ ] Mobile: Widget bubble is hidden by default
- [ ] Mobile: "Chat trực tiếp (Re:plain)" appears in popup
- [ ] Mobile: Clicking item opens Re:plain without page reload
- [ ] Mobile: Popup closes when Re:plain opens
- [ ] Admin: No Re:plain script in wp-admin
- [ ] Performance: Lazy load works on mobile

## Troubleshooting

### Widget not appearing on desktop
1. Check if `VIDIEU_REPLAIN_ENABLE` is `true`
2. Verify Re:plain ID is correct: `d4320c9c-f3b8-4e8f-bf80-aaf648924d5c`
3. Check browser console for errors

### Mobile item not showing in popup
1. Ensure `replain-mobile.js` is loaded
2. Check if popup structure matches expected selectors
3. Look for JavaScript errors in console

### Widget not opening on mobile
1. Verify `window.VidieuReplain.open()` is defined
2. Check if Re:plain script loaded successfully
3. Ensure popup is closing before Re:plain opens

## Future Enhancements

1. Support for multiple desktop positions (right-bottom, etc.)
2. Customizable widget appearance via settings
3. Admin panel configuration instead of constants
4. Analytics integration for chat events
5. Offline message handling