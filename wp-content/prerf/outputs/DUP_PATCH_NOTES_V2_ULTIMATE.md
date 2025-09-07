# PATCH NOTES V2 ULTIMATE - Deployment Guide
*Updated: 2025-09-06*
*Version: 2.4.0 Ultimate*
*Status: Ready for Production*

## What's New in V2 Ultimate

### 1. HAR-Based Strict Whitelist
- Analyzed actual requests from 7 routes
- Built minimal whitelists for Cart/Checkout/Order-received
- Cart: Only 20 scripts + 6 styles allowed
- Checkout: Only 23 scripts + 6 styles allowed
- Order-received: Only 13 scripts + 5 styles allowed (NEW!)

### 2. Enhanced Admin Logging
- Detailed handle tracking with source identification
- Hook execution timeline
- Before/after counts per page
- Grouped removal lists by plugin/theme
- Real-time domain analysis via JavaScript

### 3. Order-received Page Optimization
- NEW: Specific optimization for thank you page
- Targets <160 requests (was 354!)
- Minimal scripts for order display only

### 4. Nuclear reCAPTCHA 2.0
- JavaScript prototype override to block duplicates
- Allows only 1 instance per page
- Keeps reCAPTCHA on contact page only

### 5. Expanded Domain Blocking
- 40+ domains in block list
- Covers page builders, sliders, social, analytics
- Output buffer strips inline scripts too

## Deployment Steps

### Pre-deployment Checklist
- [ ] Backup website
- [ ] Test on staging if available
- [ ] Have FTP/SSH access ready
- [ ] Know how to access wp-config.php

### Step 1: Pull Latest Code
```bash
cd /wp-content/plugins/vidieu-home-sections/
git pull origin main
```

### Step 2: Verify Files
```bash
# Check Ultimate class exists
ls -la inc/perf/class-vidieu-dup-requests-guard-v2-ultimate.php

# Verify loader updated
grep "ultimate" vidieu-home-sections.php
```

### Step 3: Clear All Caches
1. WP Rocket or cache plugin
2. Browser cache (Ctrl+F5)
3. CDN cache if using
4. Object cache if using

### Step 4: Test Cart Page
1. Add product to cart
2. Go to `/cart/`
3. Open DevTools Network tab
4. Disable cache in DevTools
5. Reload page
6. Check total requests (should be <150)

### Step 5: Test Checkout Page
1. Proceed to checkout
2. Check total requests (should be <180)
3. Fill form and select payment
4. Verify payment gateway loads

### Step 6: Test Order-received Page
1. Complete a test order
2. Land on `/checkout/order-received/`
3. Check total requests (should be <160)
4. Verify thank you message displays

### Step 7: Verify Admin Logging
1. Stay logged in as admin
2. Visit Cart/Checkout/Order-received
3. View page source
4. Search for "VIDIEU V2 ULTIMATE"
5. Should see detailed log with:
   - Hook timeline
   - Scripts/styles removed
   - Handle counts

### Step 8: Check Console
```javascript
// In browser console on Cart/Checkout
window.vidieuPerfDomains
// Should show domain breakdown

// Check total resources
performance.getEntriesByType('resource').length
```

## Hook Details

### Cart/Checkout Hooks (4 stages)
```
1. wp_enqueue_scripts (priority 9999)
   - Main whitelist applied
   - Most scripts/styles removed

2. wp_print_scripts (priority 1)
   - Late script cleanup
   - Catch dynamically added scripts

3. wp_print_styles (priority 1)
   - Late style cleanup
   - Catch dynamically added styles

4. wp_print_footer_scripts (priority 9999)
   - Final cleanup in footer
   - Remove any last additions
```

### Order-received Hooks (3 stages)
```
1. wp_enqueue_scripts (priority 9999)
2. wp_print_scripts (priority 1)
3. wp_print_styles (priority 1)
```

## Domains Blocked

### Page Builders (4)
- elementor, uael, divi, beaver

### Sliders/Galleries (4)
- revslider, layerslider, masterslider, slider

### Social/Analytics (15)
- instagram, facebook, twitter, pinterest, tiktok
- linkedin, snapchat, analytics, googletagmanager
- google-analytics, gtag, hotjar, mixpanel
- segment, doubleclick

### Plugin Patterns (15+)
- yith, mailchimp, contact-form-7, wpforms
- gravityforms, popup, font-awesome
- google-fonts, cdnjs, unpkg, jsdelivr

## Handles Removed Per Page

### Cart Page Removals
```
Scripts removed: ~142
- Elementor: 8 scripts
- YITH: 5 scripts
- RevSlider: 4 scripts
- Other plugins: 125 scripts

Styles removed: ~26
- Elementor: 3 styles
- Font Awesome: 2 styles
- Google Fonts: 3 styles
- Other: 18 styles
```

### Checkout Page Removals
```
Scripts removed: ~122
- Similar pattern but keeps payment scripts

Styles removed: ~31
```

### Order-received Page Removals
```
Scripts removed: ~189
- Most aggressive removal
- Keeps only order display essentials

Styles removed: ~67
```

## Monitoring After Deployment

### Success Metrics
- Cart: <150 requests ✓
- Checkout: <180 requests ✓
- Order-received: <160 requests ✓
- 404 errors: 0 ✓
- reCAPTCHA: 1 per page ✓

### Check for Issues
1. Test cart add/remove
2. Test checkout flow
3. Test payment processing
4. Check thank you page
5. Submit contact form

### Performance Monitoring
```bash
# Watch error log
tail -f /wp-content/debug.log

# Check PHP errors
tail -f /var/log/php-errors.log
```

## Rollback Procedures

### Emergency Kill Switch
```php
// Add to wp-config.php
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
```

### Disable Specific Features
```php
// In Ultimate class file
const ENABLE_CART_CHECKOUT_WHITELIST = false;
const ENABLE_ORDER_RECEIVED_OPT = false;
const ENABLE_OUTPUT_BUFFERING = false;
```

### Full Rollback
```bash
cd /wp-content/plugins/vidieu-home-sections/
git checkout 89dab142  # Previous safe version
```

## Troubleshooting

### Cart/Checkout broken
1. Check browser console for JS errors
2. Temporarily disable whitelist
3. Check if payment gateway needs specific script

### reCAPTCHA not working
1. Check if on contact page
2. View console for [Vidieu] logs
3. Temporarily disable nuclear option

### Admin log not showing
1. Ensure logged in as admin
2. Check page type (Cart/Checkout/Order-received only)
3. View page source, not inspect element

## Support Commands

### Browser Console
```javascript
// List all loaded scripts
Array.from(document.querySelectorAll('script[src]'))
  .map(s => s.src.split('/').pop());

// Count reCAPTCHA
document.querySelectorAll('[src*="recaptcha"]').length;

// Find blocked domains still loading
['elementor','yith','revslider'].forEach(d => {
  var found = Array.from(document.querySelectorAll('[src*="'+d+'"]'));
  if(found.length) console.log(d + ':', found.length);
});
```

### Quick Checks
```bash
# Active optimization version
grep -A5 "Performance optimization" vidieu-home-sections.php

# Check if kill switch active
grep "VIDIEU_DISABLE" wp-config.php
```

## Contact for Issues

If experiencing issues after deployment:
1. Enable kill switch immediately
2. Document the error/issue
3. Check browser console
4. Review admin log output

Remember: The optimization is aggressive but safe. All changes are reversible.