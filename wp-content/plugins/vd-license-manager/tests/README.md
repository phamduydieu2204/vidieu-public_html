# VD License Manager - Testing Guide

## 🧪 Testing Overview

The VD License Manager plugin includes comprehensive testing tools to verify the DAY 7-8 implementation. Use the appropriate test based on your needs:

---

## 🚀 Quick Check (Recommended First Step)

**File:** `quick-check.php`
**Execution Time:** < 5 seconds
**Purpose:** Fast verification of basic functionality

### What It Tests:
- ✅ **Environment:** PHP version, WordPress, database connection
- ✅ **Database Tables:** Core tables existence (5 main tables)
- ✅ **Plugin Classes:** Critical classes loaded properly
- ✅ **REST API:** Endpoint registration verification
- ✅ **Sample Data:** Basic database operations test

### How to Run:

**Method 1: Direct Browser Access**
```
https://vidieu.vn/wp-content/plugins/vd-license-manager/tests/quick-check.php
```

**Method 2: Local Development**
```
http://localhost/wp-content/plugins/vd-license-manager/tests/quick-check.php
```

### Expected Results:
- All checks show ✅ (green checkmarks)
- Execution time < 5 seconds
- Memory usage < 64MB
- No ❌ (red) errors

### If Quick Check Fails:
1. ❌ **Fix all reported issues first**
2. ❌ **Do NOT proceed to full testing**
3. ✅ **Re-run quick-check.php until all pass**

---

## 🔧 Full Comprehensive Test

**File:** `manual-test-day-7-8.php`
**Execution Time:** 30-60 seconds
**Purpose:** Complete implementation verification

### What It Tests:
- 📊 **Database Verification:** All 11 tables, structure, relationships
- 🌐 **REST API Testing:** Complete endpoint testing with real requests
- 📱 **Device Manager:** Device registration, limits, lifecycle
- 🏊 **Pool Assignment:** Pool logic, capacity, priority testing

### Prerequisites:
1. ✅ **Quick check MUST pass first**
2. ✅ **Plugin must be activated**
3. ✅ **Database tables must exist**
4. ✅ **Server must allow 60+ second execution time**

### How to Run:

**Method 1: WordPress Admin (Recommended)**
1. Add to `functions.php`:
```php
function run_vd_tests() {
    if (current_user_can('manage_options') && isset($_GET['run_vd_tests'])) {
        require_once WP_PLUGIN_DIR . '/vd-license-manager/tests/manual-test-day-7-8.php';
        exit;
    }
}
add_action('init', 'run_vd_tests');
```
2. Visit: `https://vidieu.vn/?run_vd_tests=1`

**Method 2: WP-CLI (If Available)**
```bash
cd /path/to/wordpress
wp eval-file wp-content/plugins/vd-license-manager/tests/manual-test-day-7-8.php
```

**Method 3: Direct Browser (May Timeout)**
```
https://vidieu.vn/wp-content/plugins/vd-license-manager/tests/manual-test-day-7-8.php
```

### Warning:
- ⚠️ **May timeout on slow servers**
- ⚠️ **Creates and removes test data**
- ⚠️ **Requires admin privileges**
- ⚠️ **Only run on staging/development sites**

---

## 🔍 Test Workflow (Recommended Process)

### Step 1: Quick Health Check
```bash
# Run quick check first
curl -s "https://vidieu.vn/wp-content/plugins/vd-license-manager/tests/quick-check.php"
```

### Step 2: Fix Issues (If Any)
If quick check shows ❌ errors:
1. Check plugin activation
2. Run database installer
3. Verify file permissions
4. Check PHP/WordPress versions

### Step 3: Full Testing (Only After Step 1 Passes)
```bash
# Run comprehensive test
curl -s "https://vidieu.vn/?run_vd_tests=1"
```

### Step 4: API Testing (Manual)
```bash
# Test REST API endpoint
curl -X POST "https://vidieu.vn/wp-json/vd/v1/license/access" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "device_combined_id": "test_device_001",
    "device_name": "Test Device"
  }'
```

---

## 🛠️ Troubleshooting

### Common Issues:

**1. HTTP 500 Error on Tests**
- **Cause:** Plugin not activated or missing classes
- **Fix:** Run `quick-check.php` to identify specific issues

**2. "Class Not Found" Errors**
- **Cause:** Autoloader or file inclusion issues
- **Fix:** Check plugin activation and file permissions

**3. "Table Not Found" Errors**
- **Cause:** Database tables not created
- **Fix:** Deactivate and reactivate plugin to trigger installer

**4. REST API "404 Not Found"**
- **Cause:** Permalink structure or REST API disabled
- **Fix:** Go to Settings → Permalinks and click "Save"

**5. Database Connection Errors**
- **Cause:** Database credentials or permissions
- **Fix:** Check `wp-config.php` database settings

**6. Memory/Timeout Issues**
- **Cause:** Server resource limitations
- **Fix:** Use quick-check.php instead of full test

### Debug Information:

**Enable WordPress Debug:**
```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

**Check Error Logs:**
- WordPress: `/wp-content/debug.log`
- Server: `/var/log/error_log` or `/var/log/apache2/error.log`

**Check Plugin Status:**
```php
// Check if plugin active
var_dump(is_plugin_active('vd-license-manager/vd-license-manager.php'));

// Check if classes exist
var_dump(class_exists('VD_License_Manager'));
var_dump(class_exists('VD_REST_API'));
```

---

## 📋 Test Results Interpretation

### Quick Check Results:

**✅ All Green:** Ready for production/full testing
**⚠️ Some Orange:** Issues need attention but not critical
**❌ Any Red:** MUST fix before proceeding

### Full Test Results:

**Success Rate > 90%:** Implementation working well
**Success Rate 70-90%:** Some issues need investigation
**Success Rate < 70%:** Major problems, review implementation

---

## 🔒 Security Notes

### Test Safety:
- ✅ **Tests are safe** - they create/clean test data automatically
- ✅ **No production data affected** - uses unique test identifiers
- ✅ **Temporary data only** - all test data is removed after testing

### Access Control:
- 🔐 **Admin privileges required** for full tests
- 🔐 **Quick check is safe** for any user
- 🔐 **Never run on production** without backup

---

## 📊 Performance Benchmarks

### Expected Performance:

**Quick Check:**
- Execution Time: < 5 seconds
- Memory Usage: < 64MB
- Database Queries: < 15

**Full Test:**
- Execution Time: < 60 seconds
- Memory Usage: < 128MB
- Database Queries: < 100

### If Performance Is Poor:
1. Check server resources (CPU, memory)
2. Verify database performance
3. Consider using quick-check.php only
4. Review plugin configuration

---

## 🚀 Next Steps After Testing

### If Tests Pass:
1. ✅ **API Integration:** Connect client applications
2. ✅ **Load Testing:** Test with multiple concurrent users
3. ✅ **Production Deployment:** Ready for live environment

### If Tests Fail:
1. ❌ **Review error messages** carefully
2. ❌ **Check troubleshooting section** above
3. ❌ **Fix issues one by one** and re-test
4. ❌ **Contact support** if problems persist

---

## 📞 Support

**For Issues:**
1. Run `quick-check.php` first to identify problems
2. Check WordPress and PHP error logs
3. Review this README troubleshooting section
4. Provide test results and error messages when asking for help

**Test Files:**
- `quick-check.php` - Fast basic verification (< 200 lines)
- `manual-test-day-7-8.php` - Comprehensive testing (800+ lines)
- `README.md` - This documentation

---

**Remember:** Always run `quick-check.php` first! 🚀