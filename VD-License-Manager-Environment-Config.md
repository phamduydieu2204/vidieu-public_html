# VD License Manager - Environment Configuration

## Overview
Environment-specific configuration for VD License Manager plugin deployment on vidieu.vn production environment.

## WordPress Environment

### Core Configuration
```bash
WP_VERSION=6.8.2
TABLE_PREFIX=bz_
PHP_VERSION=7.4.27
```

### Required PHP Extensions (Verified)
```bash
✅ mysqli       - Database connectivity
✅ curl         - External API calls
✅ mbstring     - String handling
⚠️  openssl     - Required for encryption (check availability)
⚠️  json        - JSON processing (check availability)
```

**Action Required**: Verify additional extensions
```bash
php -m | grep -E "(openssl|json)"
```

## Database Configuration

### Connection Settings
```php
// wp-config.php values
DB_NAME=vidieu_db
DB_USER=vidieu
DB_HOST=localhost
DB_CHARSET=utf8mb4
DB_COLLATE=utf8mb4_unicode_ci
```

### Table Prefix
```php
$table_prefix = 'bz_';

// VD License Manager tables will be:
bz_vd_provider_accounts
bz_vd_content_versions
bz_vd_licenses
bz_vd_license_assignments
bz_vd_product_provider_mapping
bz_vd_device_requests
bz_vd_rate_limits
bz_vd_audit_logs
bz_vd_access_logs
bz_vd_field_sharing_config
bz_vd_manual_assignments
```

## Security Configuration

### Encryption Key
```php
// wp-config.php
define('VD_ENCRYPTION_KEY', 'base64:VkQtTGljZW5zZS1NYW5hZ2VyLUtleS0zMi1CeXRlcyE=');

// Decoded key length verification:
// Base64 decode → 32 bytes ✅ (AES-256 compatible)
```

### Security Headers (Recommended)
```php
// Add to wp-config.php or .htaccess
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

## API Configuration

### REST API Settings
```bash
REST_BASE=/wp-json/vd/v1
ENDPOINT_RESOLVE=/license/resolve-info

# Full endpoint URL:
# https://vidieu.vn/wp-json/vd/v1/license/resolve-info
```

### API Routes Structure
```php
// VD License Manager API endpoints
GET    /wp-json/vd/v1/license/resolve-info
POST   /wp-json/vd/v1/license/resolve-info
PUT    /wp-json/vd/v1/license/assign-manual
GET    /wp-json/vd/v1/device/approve/{device_id}
POST   /wp-json/vd/v1/device/reject/{device_id}
GET    /wp-json/vd/v1/account/test-connection/{account_id}
POST   /wp-json/vd/v1/content/update-version
GET    /wp-json/vd/v1/audit/logs
POST   /wp-json/vd/v1/rate-limit/bypass
```

## LMfWC Integration

### License Source Configuration
```php
// Plugin configuration
LICENSE_SOURCE=LMFWC_DB   // Direct database access (recommended for performance)

// Alternative: EXTERNAL_API (if needed for compatibility)
```

### Database Integration
```sql
-- Primary license table
TABLE: bz_lmfwc_licenses

-- Available columns:
id                    - Primary key
order_id             - WooCommerce order reference
product_id           - WooCommerce product ID
user_id              - WordPress user ID
license_key          - Unique license identifier
hash                 - License key hash
expires_at           - Expiration timestamp
valid_for            - Validity period (days)
source               - License source identifier
status               - License status (active/inactive/expired)
times_activated      - Current activation count
times_activated_max  - Maximum allowed activations
created_at           - Creation timestamp
created_by           - Creator user ID
updated_at           - Last update timestamp
updated_by           - Last updater user ID

-- Fields used by VD License Manager:
LMFWC_FIELDS=license_key,product_id,status,expires_at
```

### Related LMfWC Tables
```sql
-- Additional tables for reference
bz_lmfwc_activations    - License activation records
bz_lmfwc_api_keys      - API authentication keys
bz_lmfwc_generators    - License generation rules
bz_lmfwc_licenses_meta - License metadata storage
```

### LMfWC API Integration
```php
// Available LMfWC REST API endpoints
GET    /wp-json/lmfwc/v2/licenses                     - List all licenses
GET    /wp-json/lmfwc/v2/licenses/{license_key}       - Get specific license
POST   /wp-json/lmfwc/v2/licenses                     - Create new license
PUT    /wp-json/lmfwc/v2/licenses/{license_key}       - Update license
DELETE /wp-json/lmfwc/v2/licenses/{license_key}       - Delete license
GET    /wp-json/lmfwc/v2/licenses/activate/{license_key}      - Activate license
GET    /wp-json/lmfwc/v2/licenses/deactivate/{activation_token} - Deactivate
GET    /wp-json/lmfwc/v2/licenses/validate/{license_key}       - Validate license
GET    /wp-json/lmfwc/v2/generators                   - List generators
GET    /wp-json/lmfwc/v2/generators/{id}              - Get generator
POST   /wp-json/lmfwc/v2/generators                   - Create generator
PUT    /wp-json/lmfwc/v2/generators/{id}              - Update generator
DELETE /wp-json/lmfwc/v2/generators/{id}              - Delete generator
```

### API Authentication
```php
// LMfWC REST API credentials
Consumer Key:    ck_208d18a140490def109b29fcc14739765427d8cb
Consumer Secret: cs_36f463fa7f9548f6aff9cf195a3143a064b159ed

// Usage in requests:
Authorization: Basic base64(consumer_key:consumer_secret)
```

## Product Configuration

### Product → Share Type Mapping
```php
// WooCommerce Product ID to content sharing type mapping
PRODUCT_8210 = COOKIE        // Helium10 - Browser cookies
PRODUCT_1357 = USERPASS      // Midjourney - Username/password
PRODUCT_6456 = USERPASS_2FA  // Freepik - Username/password with 2FA

// Database mapping table: bz_vd_product_provider_mapping
// Links WooCommerce products to VD provider accounts
```

### Share Type Definitions
```php
// Content sharing types
COOKIE       - Browser cookies (JSON format)
USERPASS     - Username and password credentials
USERPASS_2FA - Username, password, and 2FA setup
```

## Test Data Configuration

### Test License Information
```php
// Test license for development/debugging
TEST_LICENSE=H10D-DIJD-14RC-SOLE-6KUV30
TEST_LICENSE_PRODUCT=8210    // Maps to COOKIE share type
TEST_LICENSE_STATUS=active
TEST_SHARE_TYPE=COOKIE
```

### Test API Calls
```bash
# Test license resolution
curl -X POST "https://vidieu.vn/wp-json/vd/v1/license/resolve-info" \
  -H "Content-Type: application/json" \
  -d '{
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "device_fp": "test-device-fingerprint-hash"
  }'

# Expected response for test license
{
  "success": true,
  "data": {
    "license_key": "H10D-DIJD-14RC-SOLE-6KUV30",
    "status": "active",
    "product_id": 8210,
    "share_type": "COOKIE",
    "provider_account": {
      "account_name": "helium10-main-01",
      "content": {
        // Encrypted cookie data
      }
    }
  }
}
```

## Performance Considerations

### PHP Version Compatibility
```php
// Current: PHP 7.4.27
// VD License Manager minimum: PHP 8.0+
// ⚠️ UPGRADE REQUIRED for optimal performance

// Compatibility considerations for PHP 7.4:
- Remove PHP 8+ specific syntax (match expressions, named arguments)
- Use traditional null coalescing instead of nullsafe operator
- Ensure backward compatibility for all features
```

### Database Optimization
```sql
-- Performance tuning for vidieu_db
-- Index optimization for frequent queries

-- License lookup performance
CREATE INDEX idx_license_key_status ON bz_lmfwc_licenses(license_key, status);
CREATE INDEX idx_product_status ON bz_lmfwc_licenses(product_id, status);

-- VD table indexes (will be created by plugin)
-- All indexes defined in VD-License-Manager-Final-Database-ERD.md
```

## Monitoring & Logging

### Log Locations
```bash
# WordPress debug logs
/wp-content/debug.log

# VD License Manager logs (will be created)
/wp-content/uploads/vd-license-manager/logs/
├── access.log      - API access logs
├── audit.log       - Security audit trail
├── error.log       - Error messages
└── debug.log       - Debug information (dev only)
```

### Monitoring Metrics
```php
// Key metrics to monitor
- License resolution response time
- Database query performance
- API rate limiting status
- Provider account health
- Device approval rates
- Encryption/decryption performance
```

## Deployment Checklist

### Pre-deployment Requirements
```bash
□ Verify PHP extensions (openssl, json)
□ Create VD database tables
□ Configure encryption key in wp-config.php
□ Set up API authentication
□ Test LMfWC integration
□ Configure product mappings
□ Set appropriate file permissions
□ Enable error logging
```

### Security Verification
```bash
□ Encryption key properly secured
□ Database user has minimal required permissions
□ API endpoints require proper authentication
□ Rate limiting configured
□ Audit logging enabled
□ No sensitive data in error logs
```

### Performance Verification
```bash
□ Database indexes created
□ Query optimization verified
□ Caching strategy implemented
□ API response times < 2 seconds
□ Memory usage within limits
□ No memory leaks during extended operation
```

## Environment-Specific Modifications

### Database Schema Updates
```sql
-- Update table creation scripts to use 'bz_' prefix
-- All CREATE TABLE statements in database ERD need prefix update:

-- Example update:
-- FROM: CREATE TABLE wp_vd_provider_accounts
-- TO:   CREATE TABLE bz_vd_provider_accounts
```

### API Endpoint Configuration
```php
// Update REST API namespace in plugin
// FROM: /wp-json/vd/v1/
// TO:   /wp-json/vd/v1/ (already matches)

// Ensure endpoint matches expectation:
// /wp-json/vd/v1/license/resolve-info ✅
```

### Integration Points
```php
// LMfWC database queries must use correct table name:
// FROM: wp_lmfwc_licenses
// TO:   bz_lmfwc_licenses

// Product ID validation against WooCommerce:
// Products 8210, 1357, 6456 must exist in WooCommerce
```

## Next Steps for Implementation

### Immediate Actions
1. **Verify PHP extensions**: Check openssl and json availability
2. **Update database schemas**: Apply bz_ prefix to all VD tables
3. **Create environment config file**: wp-content/plugins/vd-license-manager/config/environment.php
4. **Test LMfWC connectivity**: Verify API credentials work
5. **Validate product mappings**: Ensure products 8210, 1357, 6456 exist

### Implementation Priority
1. **Core plugin structure** with environment-specific configuration
2. **Database manager** with bz_ prefix support
3. **LMfWC integration** using direct database access
4. **API endpoints** with proper authentication
5. **Testing** with provided test license data