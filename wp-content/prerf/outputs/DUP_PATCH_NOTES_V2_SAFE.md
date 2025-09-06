# PATCH NOTES V2 SAFE - DEPLOYMENT GUIDE
*Updated: 2025-09-06*
*Status: Ready for safe deployment*

## CURRENT SITUATION

### What happened:
1. Original MU-plugin caused "critical error" on site
2. Had to rename to `.off` to restore site
3. V2 optimizations not taking effect
4. All targets missed (404s, reCAPTCHA, Cart/Checkout)

### Current state:
- MU-plugin: `fix-404-resources.off` (disabled)
- V2 class: Stepped version ready but not effective
- 404 errors: 3 per page
- reCAPTCHA: 16-28 loads
- Cart/Checkout: 255/242 requests

## SAFE DEPLOYMENT STEPS

### Step 1: Enable Safe MU-Plugin (5 min)

```bash
cd /wp-content/mu-plugins/

# Option A: Use new safe version
mv fix-404-resources.off fix-404-resources.off.backup
# Then use fix-404-resources-safe.php

# Option B: Test first
php -l fix-404-resources-safe.php
# If "No syntax errors detected", then enable:
cp fix-404-resources-safe.php fix-404-resources.php
```

**Test immediately:**
- Visit homepage
- Check if site loads normally
- Open Network tab, filter "404" - should see redirects

### Step 2: Verify V2 Stepped Class (10 min)

1. **Check current features:**
   ```php
   // In class-vidieu-dup-requests-guard-v2-stepped.php
   const ENABLE_BASIC_OPTIMIZATION = true;      // Start here
   const ENABLE_NUCLEAR_RECAPTCHA = false;      // Enable later
   const ENABLE_CART_CHECKOUT_WHITELIST = false; // Enable last
   ```

2. **Test basic optimization first:**
   - Clear cache
   - Load homepage
   - Check footer HTML source for optimization report
   - Should see "V2 Stepped initialization started"

3. **Enable features one by one:**
   - Set `ENABLE_NUCLEAR_RECAPTCHA = true`
   - Test contact form
   - If OK, set `ENABLE_CART_CHECKOUT_WHITELIST = true`
   - Test cart/checkout

### Step 3: Monitor & Verify (15 min)

Run test script:
```
/wp-content/prerf/test-v2-stepped.php
```

Manual checks per route:

1. **Home page:**
   ```javascript
   // Console:
   document.querySelectorAll('script[src*="recaptcha"]').length
   // Should be 1
   ```

2. **Cart page:**
   ```javascript
   // Console:
   performance.getEntriesByType('resource').length
   // Should be < 150
   ```

3. **Checkout page:**
   - Add item to cart
   - Go to checkout
   - Check payment options work
   - Network tab < 180 requests

### Step 4: Debug if Issues

**If site breaks again:**
```bash
# Quick disable:
mv fix-404-resources.php fix-404-resources.disabled

# Or add to wp-config.php:
define('VIDIEU_DISABLE_DUP_OPTIMIZATION', true);
```

**If reCAPTCHA still multiple:**
1. Check which plugins in admin
2. Deactivate one by one:
   - WP Captcha
   - Contact Form 7
   - Any security plugins

**If Cart/Checkout not optimized:**
- Check if WooCommerce updated recently
- Look for new payment plugins
- Check theme compatibility

## FILES REFERENCE

### MU-Plugins Directory
```
/wp-content/mu-plugins/
├── fix-404-resources.off          (disabled original)
├── fix-404-resources-safe.php     (new safe version)
└── fix-404-resources.php          (will be active file)
```

### Plugin Performance Classes
```
/wp-content/plugins/vidieu-home-sections/inc/perf/
├── class-vidieu-dup-requests-guard-v2-stepped.php   (ACTIVE - safe)
├── class-vidieu-dup-requests-guard-v2-enhanced.php  (aggressive)
├── class-vidieu-dup-requests-guard-v2.php           (medium)
└── class-vidieu-dup-requests-guard.php              (v1 fallback)
```

### Test & Reports
```
/wp-content/prerf/
├── test-v2-stepped.php              (use this)
├── outputs/
│   ├── DUP_REPORT_V2_FINAL.md      (current analysis)
│   ├── DUP_BEFORE_AFTER_V2_FINAL.json
│   └── DUP_PATCH_NOTES_V2_SAFE.md  (this file)
```

## EXPECTED RESULTS AFTER DEPLOYMENT

| Check | Before | After | How to Verify |
|-------|--------|-------|---------------|
| 404 errors | 3 | 0 | Network tab filter: status-code:404 |
| reCAPTCHA | 16-28 | 1 | Search "recaptcha" in Network |
| Cart requests | 255 | <150 | Network tab on /cart/ |
| Checkout | 242 | <180 | Network tab on /checkout/ |
| Site works | Yes | Yes | Test all functions |

## TROUBLESHOOTING

### Site shows error after enabling MU-plugin:
- SSH/FTP: rename file immediately
- Check error_log for details
- Try alternative .htaccess method instead

### reCAPTCHA not reducing:
- Plugin conflict likely
- Check browser console for [Vidieu] logs
- May need to contact plugin authors

### Cart/Checkout still high:
- New WooCommerce blocks issue
- Try Classic Cart/Checkout pages
- Consider separate optimization plugin

## SUCCESS CRITERIA

✅ No critical errors
✅ 404s eliminated  
✅ reCAPTCHA = 1
✅ Cart < 150 requests
✅ Checkout < 180 requests
✅ All functionality intact

## SUPPORT

- Debug logs: View page source, search "Vidieu V2"
- Console logs: Filter by "[Vidieu]"
- Kill switch: Always ready in wp-config.php