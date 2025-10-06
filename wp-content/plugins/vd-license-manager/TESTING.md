# VD License Manager - Testing Guide

## Pre-Deployment Checklist

### 1. INSTALLATION
- [ ] Plugin activates without errors
- [ ] All database tables created (11 tables)
- [ ] Default options set
- [ ] Requirements check passes (PHP 7.4+, WP 5.8+, WooCommerce, LMFWC)

### 2. ADMIN - ACCOUNTS
- [ ] Can add new provider account
- [ ] Can edit provider account
- [ ] Can delete account (only if no active licenses)
- [ ] List page displays correctly
- [ ] Search/filters work
- [ ] Bulk actions work

### 3. ADMIN - POOLS
- [ ] Can create product pool
- [ ] Can add accounts to pool
- [ ] Can set strategy (sticky/weighted/priority)
- [ ] Usage statistics display correctly
- [ ] Cannot create duplicate pool for same product

### 4. ADMIN - PRODUCT CONFIGS
- [ ] Can configure share fields for product
- [ ] Checkbox selections save correctly
- [ ] Instructions save and display
- [ ] At least 1 field required (validation)

### 5. ADMIN - DEVICES
- [ ] Devices list displays correctly
- [ ] Can approve pending device
- [ ] Can block device
- [ ] Bulk actions work
- [ ] Filters work

### 6. ADMIN - LOGS
- [ ] Access logs display correctly
- [ ] Fetch logs display correctly
- [ ] Filters work (date, license, status)
- [ ] CSV export works
- [ ] IPs >90 days are hashed

### 7. ADMIN - SETTINGS
- [ ] All tabs display correctly
- [ ] Settings save correctly
- [ ] Default values applied
- [ ] Validation works (emails, numbers)

### 8. REST API ENDPOINTS

Test with actual license: H10D-DIJD-14RC-SOLE-6KUV30

#### Device Verify
```bash
curl -X POST "https://vidieu.vn/wp-json/vd/v1/device/verify" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "fp": {"ua":"Mozilla/5.0 (Windows NT 10.0)", "screen":"1920x1080"},
    "ua": "Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36",
    "os": "Windows 10"
  }'
```

Expected: 200 OK with device verification result

#### Account Info
```bash
curl -X POST "https://vidieu.vn/wp-json/vd/v1/account/info" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "device_fp": "abc123..."
  }'
```

Expected: 200 OK with account credentials

#### History
```bash
curl -X POST "https://vidieu.vn/wp-json/vd/v1/history" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "limit": 20,
    "offset": 0
  }'
```

Expected: 200 OK with timeline events

#### Devices
```bash
curl -X POST "https://vidieu.vn/wp-json/vd/v1/devices" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30"
  }'
```

Expected: 200 OK with device list

### 9. PORTAL ACCESS
- [ ] Portal accessible at: `/vd-portal/?license=H10D-DIJD-14RC-SOLE-6KUV30`
- [ ] Device fingerprint generates correctly
- [ ] Login form validates license key
- [ ] Redirects to portal after verification
- [ ] All sections load data correctly
- [ ] Copy buttons work
- [ ] Download buttons work
- [ ] TOTP countdown works
- [ ] Timeline filters work
- [ ] Device cards display correctly

### 10. WOOCOMMERCE INTEGRATION
- [ ] Product meta box appears on product edit
- [ ] Auto-assign setting saves correctly
- [ ] Pool configuration links work
- [ ] Order completion triggers assignment
- [ ] Assignment success/failure logged
- [ ] Admin notices display correctly
- [ ] Email notifications sent (if enabled)

### 11. CRON JOBS
- [ ] Daily cleanup scheduled
- [ ] Hourly check scheduled
- [ ] Capacity check scheduled (4 hours)
- [ ] Manual triggers work from settings
- [ ] Cleanup logs properly
- [ ] Notifications sent for warnings

### 12. EMAIL NOTIFICATIONS
- [ ] Device rotation requests sent to admin
- [ ] Account expiring warnings sent
- [ ] Capacity high alerts sent
- [ ] Welcome emails sent to customers
- [ ] HTML formatting displays correctly
- [ ] All email templates load properly

### 13. SECURITY TESTS
- [ ] SQL injection protection works
- [ ] XSS protection works
- [ ] CSRF tokens validate
- [ ] Rate limiting blocks excessive requests
- [ ] IP hashing works for privacy
- [ ] Unauthorized access blocked

### 14. PERFORMANCE TESTS
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] Large datasets handled properly
- [ ] Memory usage reasonable
- [ ] No fatal errors under load

## Common Issues & Troubleshooting

### Database Tables Not Created
1. Check file permissions
2. Verify MySQL user privileges
3. Check WordPress debug log
4. Manually run activator

### REST API 404 Errors
1. Flush permalink rules
2. Check .htaccess file
3. Verify mod_rewrite enabled
4. Test with default permalinks

### Portal Not Loading
1. Check license key format
2. Verify device fingerprint
3. Check browser console errors
4. Verify CSS/JS files loaded

### Assignment Failures
1. Check pool configuration
2. Verify product settings
3. Check account capacity
4. Review assignment logs

### Email Not Sending
1. Test wp_mail() function
2. Check SMTP configuration
3. Verify admin email setting
4. Check email logs

## Performance Optimization

### Database Indexing
```sql
ALTER TABLE wp_vd_license_access_log ADD INDEX idx_license_time (license_id, access_time);
ALTER TABLE wp_vd_cookie_assignments ADD INDEX idx_provider_status (provider_account_id, status);
```

### Caching Recommendations
- Enable object caching
- Use transients for expensive queries
- Cache device fingerprints
- Implement rate limit caching

### Monitoring
- Monitor database table sizes
- Track API response times
- Monitor error logs
- Check memory usage

## Development Notes

### Debug Mode
Enable debugging by adding to wp-config.php:
```php
define('VD_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Test Data
Use these test accounts for development:
- License: H10D-DIJD-14RC-SOLE-6KUV30
- Product ID: 123
- Device FP: test-device-fingerprint

### Required Plugins
1. WooCommerce
2. License Manager for WooCommerce (LMFWC)
3. VD License Manager (this plugin)

### Browser Testing
Test in:
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers

## Final Checklist
- [ ] All admin pages load without errors
- [ ] All REST endpoints return valid JSON
- [ ] Portal interface fully functional
- [ ] Email system working
- [ ] Cron jobs scheduled properly
- [ ] Security measures in place
- [ ] Performance acceptable
- [ ] Documentation complete
- [ ] Code follows WordPress standards
- [ ] Ready for production deployment