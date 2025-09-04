# Google Fonts Replacement Implementation

## Overview
This document explains how the Elessi theme loads Google Fonts and the implementation to replace them with local fonts.

## How Elessi Theme Loads Fonts

### 1. Font Registration
- **File**: `/wp-content/themes/elessi-theme/cores/nasa-register-fonts.php`
- **Function**: `elessi_register_fonts()`
- **Hook**: `wp_enqueue_scripts` (priority 10)
- **Handle**: `nasa-fonts`

### 2. Font URL Generation
- **Function**: `elessi_google_fonts_url()`
- **Default Font**: Jost (with all weights: 300-900)
- **URL Pattern**: `https://fonts.googleapis.com/css?family=Jost:300,300italic...&subset=latin&display=swap`

### 3. Theme Options
The theme stores font preferences in theme mods:
- `type_font_select`: 'google' or 'custom'
- `type_headings`: Font for headings (default: 'Jost')
- `type_texts`: Font for body text (default: 'Jost')
- `type_nav`: Font for navigation
- `type_banner`: Font for banners
- `type_price`: Font for prices

## Implementation to Block Google Fonts

### 1. Must-Use Plugin
**File**: `/wp-content/mu-plugins/disable-google-fonts.php`
- Overrides `elessi_google_fonts_url()` to return false
- Filters all style URLs to block Google Fonts
- Removes font registration actions early

### 2. Child Theme Functions
**File**: `/wp-content/themes/elessi-theme-child/functions.php`
- Dequeues 'nasa-fonts' style
- Replaces with local fonts or system fonts
- Adds filters to catch any Google Fonts URLs

### 3. CSS Overrides
**File**: `/wp-content/themes/elessi-theme-child/block-google-fonts.css`
- Provides @font-face declarations using system fonts
- Forces all text elements to use system fonts
- Blocks @import statements for Google Fonts

### 4. Inline Styles
- Added critical inline CSS that loads before external stylesheets
- Immediately applies system fonts to prevent FOUT (Flash of Unstyled Text)

## Testing

### 1. Check Network Tab
- Open browser DevTools > Network tab
- Filter by "font" or search for "googleapis"
- Reload page and verify no Google Fonts requests

### 2. Check Page Source
- View page source
- Search for "fonts.googleapis.com"
- Verify no Google Fonts links present

### 3. Verify Font Display
- Text should display using system fonts
- No flash of font change during page load

## Adding Local Fonts

To use actual local Jost fonts instead of system fonts:

1. Download Jost font files (woff2 format recommended)
2. Create directory: `/wp-content/themes/elessi-theme-child/assets/fonts/jost/`
3. Add font files: `jost-regular.woff2`, `jost-bold.woff2`, etc.
4. Create `jost.css` with proper @font-face declarations:

```css
@font-face {
    font-family: 'Jost';
    src: url('jost-regular.woff2') format('woff2');
    font-weight: 400;
    font-style: normal;
    font-display: swap;
}

@font-face {
    font-family: 'Jost';
    src: url('jost-bold.woff2') format('woff2');
    font-weight: 700;
    font-style: normal;
    font-display: swap;
}
```

5. The existing code will automatically detect and use these local fonts

## Troubleshooting

### If Google Fonts Still Load:
1. Clear all caches (browser, WordPress, CDN, Redis)
2. Check if theme has dynamic CSS cache: `/wp-content/uploads/nasa-dynamic/`
3. Deactivate and reactivate theme
4. Check for other plugins loading Google Fonts

### Debug Mode:
The functions.php includes a debug function that logs Google Fonts to error log:
```php
// Check error log for: "Google Font detected: Handle=..."
```

## Notes
- The implementation uses multiple layers to ensure Google Fonts are blocked
- System fonts are used as fallback for better performance
- Vietnamese language support is maintained through system fonts
- Solution is upgrade-safe as all changes are in child theme and mu-plugins