# SPRINT 2 - PROVIDER ACCOUNTS LIST PAGE TEST GUIDE

## Overview
This guide provides comprehensive testing steps for the Provider Accounts list page functionality.

## Prerequisites
1. All SPRINT 2 files have been uploaded to the server
2. WordPress admin access available
3. Plugin is activated

## Test Data Setup

### Step 1: Access Test Data Insertion
Go to: `https://vidieu.vn/test-accounts-page.php?run_test=yes`

This will:
- Check if the database table exists
- Insert 3 test accounts if needed
- Verify all classes are loading
- Provide summary of setup status

### Alternative: Manual SQL Insert
If the PHP file doesn't work, run this SQL manually in phpMyAdmin:

```sql
INSERT INTO bz_vd_provider_accounts
(provider, account_login, display_name, capacity, status, created_at, updated_at)
VALUES
('netflix', 'test1@example.com', 'Test Netflix Account', 5, 'active', NOW(), NOW()),
('spotify', 'test2@example.com', 'Test Spotify Account', 3, 'active', NOW(), NOW()),
('youtube', 'test3@example.com', 'Test YouTube Account', 10, 'suspended', NOW(), NOW());
```

## Manual Testing Checklist

### TEST 1: Page Access ✅
**Steps:**
1. Navigate to: WordPress Admin → VD License Manager → Provider Accounts
2. URL should be: `admin.php?page=vd-provider-accounts`

**Expected Results:**
- [ ] Page loads without PHP errors
- [ ] Page title "Provider Accounts" displays
- [ ] "Add New" button visible in header
- [ ] No JavaScript console errors

**If Failed:**
- Check error logs for PHP errors
- Verify all files exist in correct locations
- Check if autoloader is working

### TEST 2: Empty State (if no data)
**Expected Results:**
- [ ] Empty table with message "No items found"
- [ ] Search box visible
- [ ] Filter dropdowns visible (Provider, Status)
- [ ] Pagination not shown

### TEST 3: Table Display with Data
**Expected Results:**
- [ ] 3 test accounts display in table
- [ ] All columns show correct data:
  - [ ] ID column shows account IDs (1, 2, 3)
  - [ ] Account Name shows with clickable edit link
  - [ ] Provider shows with emoji icon (🎬 Netflix, 🎵 Spotify, 📺 YouTube)
  - [ ] Login shows email addresses
  - [ ] Capacity shows "0 / X (0%)" format
  - [ ] Status shows colored indicator (● Active, ● Suspended)
  - [ ] Created shows timestamp
  - [ ] Actions shows "Edit" button

### TEST 4: Search Functionality ✅
**Steps:**
1. Enter "netflix" in search box
2. Click "Search Accounts" or press Enter

**Expected Results:**
- [ ] Only Netflix account displays
- [ ] URL contains "?page=vd-provider-accounts&s=netflix"
- [ ] Search box retains "netflix" value
- [ ] Other accounts hidden

**Test Case 2:**
1. Search for "test"
2. Should show all 3 accounts (all contain "test")

### TEST 5: Filter by Provider ✅
**Steps:**
1. Select "Spotify" from Provider dropdown
2. Click "Filter" button

**Expected Results:**
- [ ] Only Spotify account displays
- [ ] URL contains "&provider=spotify"
- [ ] Filter dropdown retains "Spotify" selection
- [ ] Other accounts hidden

**Test All Providers:**
- [ ] Test "Netflix" filter
- [ ] Test "YouTube" filter
- [ ] Test "All Providers" (shows all)

### TEST 6: Filter by Status ✅
**Steps:**
1. Select "Suspended" from Status dropdown
2. Click "Filter" button

**Expected Results:**
- [ ] Only suspended accounts display (YouTube)
- [ ] URL contains "&status=suspended"
- [ ] Status dropdown retains "Suspended" selection

**Test All Statuses:**
- [ ] Test "Active" filter (shows Netflix, Spotify)
- [ ] Test "All Statuses" (shows all)

### TEST 7: Combined Filters ✅
**Steps:**
1. Set Provider = "Netflix" AND Status = "Active"
2. Click "Filter"

**Expected Results:**
- [ ] Only Netflix account displays
- [ ] URL contains both parameters
- [ ] Both dropdowns retain selections

### TEST 8: Column Sorting ✅
**Test each sortable column:**

**ID Column:**
1. Click "ID" header
2. Should sort ascending (1, 2, 3)
3. Click again - should sort descending (3, 2, 1)
4. URL should show "&orderby=id&order=asc" or "desc"

**Provider Column:**
1. Click "Provider" header
2. Should sort alphabetically
3. URL should show "&orderby=provider"

**Status Column:**
1. Click "Status" header
2. Should group by status
3. URL should show "&orderby=status"

**Created Column:**
1. Click "Created" header
2. Should sort by date (default = newest first)

### TEST 9: Edit Links ✅
**Steps:**
1. Click account name link OR "Edit" button

**Expected Results:**
- [ ] Navigate to edit page
- [ ] URL is "?page=vd-provider-accounts&action=edit&id=X"
- [ ] Should show "Form handler not yet implemented" message (normal for Sprint 2)

**Test for each account:**
- [ ] Netflix account edit link
- [ ] Spotify account edit link
- [ ] YouTube account edit link

### TEST 10: Bulk Actions Interface ✅
**Expected Results:**
- [ ] Bulk Actions dropdown exists above table
- [ ] Contains options: "Delete", "Activate", "Suspend"
- [ ] Checkboxes appear in first column
- [ ] "Apply" button visible

**Test Interactions:**
- [ ] Check one checkbox - bulk actions become available
- [ ] Check multiple checkboxes
- [ ] Select bulk action (won't execute, but UI should work)

### TEST 11: Pagination (if >20 accounts)
**Only if you have >20 accounts:**
- [ ] Pagination controls appear at bottom
- [ ] Page numbers work
- [ ] "Next" and "Previous" buttons work
- [ ] URL shows "&paged=2" etc.

### TEST 12: Responsive Design ✅
**Test on different screen sizes:**
- [ ] Table scrolls horizontally on mobile
- [ ] All buttons remain clickable
- [ ] Text remains readable

## Error Reporting

If any test fails, please provide:

### A. Error Details:
- Which specific test failed
- Error message (if any)
- Screenshot (if helpful)
- Browser console errors

### B. Debug Information:
- Check WordPress debug.log for PHP errors
- Check browser console for JavaScript errors
- Verify all files exist in correct locations:
  - `/admin/pages/class-vd-admin-provider-accounts.php`
  - `/admin/pages/accounts/class-vd-accounts-list-table.php`
  - `/admin/pages/accounts/class-vd-accounts-list-view.php`

### C. Database Check:
```sql
-- Check table exists
SHOW TABLES LIKE 'bz_vd_provider_accounts';

-- Check data exists
SELECT COUNT(*) FROM bz_vd_provider_accounts;

-- Check table structure
DESCRIBE bz_vd_provider_accounts;
```

## Success Criteria

✅ **SPRINT 2 Complete when:**
- All 12 tests pass successfully
- No PHP errors in logs
- Page performs well with test data
- All user interface elements work as expected

## Cleanup After Testing

1. Delete test file: `/test-accounts-page.php`
2. Delete this guide: `/SPRINT-2-TEST-GUIDE.md`
3. Optionally keep test data for continued testing
4. Ready for SPRINT 3: Account Add/Edit Forms

---

**Last Updated:** Sprint 2 Completion
**Next Sprint:** Account Add/Edit Forms (Sprint 3)