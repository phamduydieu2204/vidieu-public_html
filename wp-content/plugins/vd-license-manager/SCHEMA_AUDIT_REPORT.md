# Database Schema Audit Report

**Date:** 2025-01-15
**Status:** PHASE 1 - COMPREHENSIVE AUDIT
**Source of Truth:** `/docs/database_schema.md`

## 📊 ACTUAL DATABASE SCHEMA (Production)

### Tables in Production Database

1. **bz_vd_account_fetch_log** (14 columns)
2. **bz_vd_device_fingerprints** (25 columns)
3. **bz_vd_license_access_log** (20 columns)
4. **bz_vd_license_devices** (14 columns)
5. **bz_vd_license_device_limits** (12 columns)
6. **bz_vd_license_keys** (24 columns)
7. **bz_vd_license_rate_limits** (19 columns)
8. **bz_vd_pools** (6 columns)
9. **bz_vd_pool_accounts** (8 columns)
10. **bz_vd_product_pools** (13 columns)
11. **bz_vd_product_share_configs** (8 columns)
12. **bz_vd_provider_accounts** (30 columns)

## 🔍 CRITICAL FINDINGS

### ❌ CRITICAL MISMATCHES FOUND

#### 1. **Provider Accounts Table** - CRITICAL COLUMN NAME ISSUES

**File:** `includes/class-vd-lm-order-handler.php`
**Line:** 44-61 (ACCOUNT_COLUMNS mapping)

```php
const ACCOUNT_COLUMNS = array(
    'password' => 'login_password',  // ❌ MISMATCH
    'cookie' => 'cookie',            // ❌ MISMATCH
);
```

**Issues Found:**
- ❌ **CRITICAL:** Uses `'login_password'` but actual column is `'account_password'` (line 191 in schema)
- ❌ **CRITICAL:** Uses `'cookie'` but actual column is `'cookies'` (line 192 in schema)

**Actual Columns in bz_vd_provider_accounts:**
- ✅ `account_password` (line 191)
- ✅ `cookies` (line 192)
- ✅ `current_usage` (line 197)
- ✅ `capacity` (line 174)

#### 2. **Missing Column References** - POTENTIAL ISSUES

**Columns that exist in schema but may not be used in code:**
- `phone_recovery` (line 193)
- `email_recovery` (line 194)
- `secret_key` (line 195)
- `custom_fields` (line 196)
- `last_credentials_update` (line 198)
- `next_update_due` (line 199)
- `notes` (line 200)

#### 3. **Pool Structure Issues**

**File:** `includes/class-vd-rest-api.php`
**Lines:** 628-630

**Issues Found:**
- Code tries to JOIN `product_pools` directly to `provider_accounts`
- ❌ **CRITICAL:** Missing proper JOIN through `pool_accounts` junction table
- Schema shows `bz_vd_product_pools.pool_id` (line 161) should link to `bz_vd_pools.id`
- Then `bz_vd_pool_accounts` links pools to accounts

## 🔧 REQUIRED FIXES

### Priority 1: CRITICAL COLUMN NAME FIXES

#### Fix 1: Order Handler Column Mapping
```php
// BEFORE (WRONG):
const ACCOUNT_COLUMNS = array(
    'password' => 'login_password',
    'cookie' => 'cookie',
);

// AFTER (CORRECT):
const ACCOUNT_COLUMNS = array(
    'password' => 'account_password',
    'cookie' => 'cookies',
);
```

#### Fix 2: REST API JOIN Logic
```sql
-- BEFORE (BROKEN):
INNER JOIN bz_vd_provider_accounts pa ON pp.account_id = pa.id

-- AFTER (CORRECT):
INNER JOIN bz_vd_pool_accounts pac ON pp.pool_id = pac.pool_id
INNER JOIN bz_vd_provider_accounts pa ON pac.account_id = pa.id
```

### Priority 2: Missing Column Integration

Review if these schema columns should be used:
- `phone_recovery`, `email_recovery` - For account recovery features
- `secret_key` - For additional security
- `custom_fields` - For extensible account data
- `notes` - For admin notes

## 📋 AUDIT CHECKLIST - NEXT STEPS

### Phase 1.2: Column Reference Scan
- [ ] Scan all SQL queries for column names
- [ ] Verify each column exists in actual schema
- [ ] Check JOIN relationships are correct
- [ ] Identify deprecated column references

### Phase 1.3: Table Name Consistency
- [ ] Verify all table names use proper `$wpdb->prefix`
- [ ] Check for hardcoded 'bz_' prefixes
- [ ] Ensure consistent table naming patterns

### Phase 2: File-by-File Fixes
- [ ] Fix `includes/class-vd-lm-order-handler.php` (column mapping)
- [ ] Fix `includes/class-vd-rest-api.php` (JOIN logic)
- [ ] Fix any other files with mismatches
- [ ] Test each fix individually

### Phase 3: Validation
- [ ] Re-scan codebase for remaining issues
- [ ] Run all tests
- [ ] Verify 100% schema compliance

---

## 🎯 IMPACT ASSESSMENT

### HIGH RISK ISSUES
1. **Order processing will fail** due to wrong column names in ACCOUNT_COLUMNS
2. **API license assignment will fail** due to incorrect JOIN logic
3. **Database queries will return no results** due to non-existent columns

### MEDIUM RISK ISSUES
1. Missing integration of schema columns may limit functionality
2. Inconsistent table name usage may cause confusion

### SUCCESS CRITERIA
- ✅ All table names match schema exactly
- ✅ All column names match schema exactly
- ✅ All JOINs use correct foreign key relationships
- ✅ All tests pass after fixes
- ✅ Zero SQL errors in logs

---

**NEXT ACTION:** Proceed to Phase 1.2 - Column Reference Scan