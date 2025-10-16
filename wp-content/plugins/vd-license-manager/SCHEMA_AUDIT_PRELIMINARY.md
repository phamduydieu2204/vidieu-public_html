# Database Schema Audit - Preliminary Report

**Date:** 2025-01-15
**Status:** WAITING FOR database_schema.md
**Phase:** 1.1 - Table References Scan

## Overview

Scanning codebase for table and column references to prepare for synchronization with actual database schema.

## Tables Found in Codebase

Based on initial scan of `$wpdb->prefix . 'vd_*'` and `{$wpdb->prefix}vd_*` patterns:

### Core Tables
1. **vd_provider_accounts** - Provider account credentials
2. **vd_pools** - Pool definitions
3. **vd_product_pools** - Product to pool mappings
4. **vd_pool_accounts** - Pool to account mappings
5. **vd_license_keys** - License key storage
6. **vd_product_share_configs** - Product response configurations
7. **vd_device_fingerprints** - Device fingerprint storage
8. **vd_license_devices** - License to device mappings
9. **vd_license_device_limits** - Device limits per license
10. **vd_account_fetch_log** - Account fetch history
11. **vd_license_access_log** - License access attempts
12. **vd_license_rate_limits** - Rate limiting tracking

### Additional Tables Found
13. **vd_datacenter_ip_ranges** - VPS detection IP ranges
14. **vd_device_access_log** - (Possibly deprecated)

## Files with Table References

### Priority 1: Core Logic Files
- `includes/class-vd-lm-database.php` (Schema definitions)
- `includes/class-vd-rest-api.php` (API endpoints)
- `includes/class-vd-lm-order-handler.php` (Order processing)
- `includes/services/class-vd-capacity-manager.php` (Capacity management)
- `includes/services/class-vd-pool-capacity-calculator.php` (Pool calculations)
- `includes/services/class-vd-edge-case-handler.php` (Edge cases)
- `includes/services/class-vd-database-optimizer.php` (DB optimization)

### Priority 2: Admin Pages
- `admin/class-vd-lm-pools-page.php`
- `admin/class-vd-lm-accounts-page.php`
- `admin/class-vd-license-sync-admin.php`
- `admin/partials/accounts-list.php`
- `admin/partials/pools-list.php`

### Priority 3: Migration and Test Files
- `includes/migrations/migrate-account-columns-v2.php`
- Various test files (`test-*.php`)
- Debug files (`debug-*.php`)

## Common Patterns Found

### Table Name Usage
```php
// Good patterns (using $wpdb->prefix)
$table_name = $wpdb->prefix . 'vd_license_keys';
$pools_table = $wpdb->prefix . 'vd_pools';

// In SQL strings
FROM {$wpdb->prefix}vd_provider_accounts a
JOIN {$wpdb->prefix}vd_pool_accounts pa
```

### Potential Issues to Validate
1. **Column name consistency** - Need to verify against actual schema
2. **Table relationships** - Ensure JOINs use correct foreign keys
3. **Missing indexes** - Compare with schema requirements
4. **Deprecated tables** - Check if `vd_device_access_log` should be removed

## Next Steps

**CRITICAL:** Need `database_schema.md` to proceed with:

1. **Phase 1.2:** Column References Scan
   - Extract all column names used in SELECT, WHERE, ORDER BY
   - Map columns to their respective tables
   - Check existence against actual schema

2. **Phase 1.3:** Hardcoded Table Names Scan
   - Find any hardcoded 'bz_vd_*' references
   - Verify proper use of $wpdb->prefix

3. **Phase 2:** Incremental Fixes
   - Fix mismatches file by file
   - Test each fix independently

4. **Phase 3:** Final Verification
   - Re-scan for remaining issues
   - Run comprehensive tests

## Temporary Halt

**STATUS: WAITING FOR database_schema.md**

Cannot proceed with detailed validation and fixes without the actual database schema as reference. Please provide the schema file to continue with the comprehensive audit and synchronization process.

## Files Scanned So Far
- **Total files scanned:** ~100+
- **Files with table references:** ~50
- **Table references found:** ~200+
- **Unique table names:** 14

---

**Note:** This is a preliminary report. The actual audit and fixes will begin once `database_schema.md` is provided as the single source of truth for schema validation.