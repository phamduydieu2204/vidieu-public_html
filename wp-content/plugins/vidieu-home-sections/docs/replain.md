# Re:plain Live Chat Integration

**Version:** 1.7.4  
**Last Updated:** 2025-09-08  
**Context:** Re:plain live chat widget integration for Vidieu.vn

## Overview

This module integrates Re:plain live chat widget into the website with customized behavior for desktop and mobile devices.

## Features

### Desktop
- Widget uses **Re:plain default positioning**
- No custom position overrides
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
```

### Filters

```php
// Enable/disable Re:plain
add_filter('vidieu_replain_enable', '__return_false'); // To disable

// Hide on mobile
add_filter('vidieu_replain_hide_on_mobile', '__return_false'); // To show on mobile
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

### CSS Behavior
- Desktop: Uses Re:plain default positioning
- Mobile: Hides widget with `display: none` until explicitly opened
- Body class `replain-loaded` added when Re:plain script loads

### Position Changes (v1.7.4)
- **Removed ALL custom positioning code**:
  - No CSS position overrides
  - No JavaScript repositioning
  - No MutationObserver for position changes
  - No bottom offset configuration
- **Result**: Re:plain widget displays in its default position as configured by Re:plain system

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

## Important Note

**As of v1.7.4**: This plugin no longer modifies Re:plain widget positioning. All custom positioning code has been removed to ensure Re:plain works according to its default behavior.

## Testing Checklist

- [ ] Desktop: Re:plain widget appears in its default position
- [ ] Desktop: No custom positioning applied by plugin
- [ ] Mobile: Widget bubble is hidden by default
- [ ] Mobile: "Chat trực tiếp (Re:plain)" appears in popup
- [ ] Mobile: Chat opens when clicked from popup
- [ ] Admin: Scripts don't load in wp-admin
- [ ] Performance: No unnecessary JavaScript running

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