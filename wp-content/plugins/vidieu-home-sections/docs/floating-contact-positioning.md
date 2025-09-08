# Floating Contact Icons Positioning

**Version:** 1.7.1  
**Last Updated:** 2025-09-08  
**Context:** Modified contact icons positioning to left-bottom

## Overview

The floating contact icons have been repositioned from right-bottom to left-bottom to accommodate the Re:plain chat widget.

## Changes Made

### 1. Source Code Modification

**File:** `/wp-content/themes/elessi-theme-child/floating-contact-widget.php`

**Lines Modified:** 143-153 (CSS positioning)

```css
/* Previous position (right-bottom) */
.vd-floating-contact-widget {
    position: fixed;
    bottom: 10px;
    right: 25px;  /* OLD */
    z-index: 9999;
    align-items: flex-end; /* OLD */
}

/* New position (left-bottom) */
.vd-floating-contact-widget {
    position: fixed;
    bottom: 10px;
    left: 25px;   /* NEW */
    z-index: 9999;
    align-items: flex-start; /* NEW */
}
```

### 2. Slide Content Direction

**Lines Modified:** 258-287 (Slide tooltips)

- Changed slide content to appear on the right of buttons (was left)
- Updated arrow direction from pointing left to pointing right
- Modified transform animations to slide from left

### 3. Entry Animation

**Lines Modified:** 347-359

- Changed animation from `slideInRight` to `slideInLeft`
- Buttons now slide in from the left side on page load

### 4. CSS Override Removal

**File:** `/wp-content/plugins/vidieu-home-sections/assets/css/replain-chat.css`

- Removed CSS overrides that forced contact icons to left position
- Now relies on source code positioning instead of !important overrides

## Current Layout

### Desktop (≥769px)
```
[Contact Icons]                              [Re:plain Widget]
(left: 25px)                                (right: 20px)
```

### Mobile (<768px)
- Contact icons: Hidden (users use bottom bar)
- Re:plain: Hidden by default, opens fullscreen when triggered

## Benefits of Source Code Modification

1. **Cleaner CSS** - No need for !important overrides
2. **Better Maintainability** - Changes are in the proper location
3. **No Conflicts** - Direct source modification prevents CSS specificity battles
4. **Consistent Behavior** - All positioning logic in one place

## Rollback Instructions

To revert to right-side positioning:

1. Edit `/wp-content/themes/elessi-theme-child/floating-contact-widget.php`
2. Change line 147: `left: 25px;` → `right: 25px;`
3. Change line 152: `align-items: flex-start;` → `align-items: flex-end;`
4. Update slide content position (lines 260, 279)
5. Update animation name (line 347)

## Testing Checklist

- [x] Contact icons appear at left-bottom (25px margin)
- [x] Re:plain widget appears at right-bottom (20px margin)
- [x] Slide tooltips appear on the right of buttons
- [x] Entry animation slides from left
- [x] No overlap between elements
- [x] Mobile view hides contact icons properly

## Related Files

- `/wp-content/themes/elessi-theme-child/floating-contact-widget.php` - Main source file
- `/wp-content/plugins/vidieu-home-sections/assets/css/replain-chat.css` - Re:plain positioning
- `/wp-content/plugins/vidieu-home-sections/docs/replain.md` - Re:plain documentation