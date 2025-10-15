# Migration Plan - Pool Account Logic Refactoring

## 📋 Pre-Migration Checklist

### ✅ Environment Preparation
- [ ] Full database backup created
- [ ] Staging environment matches production
- [ ] All migration scripts tested on staging
- [ ] Rollback procedures documented and tested
- [ ] Monitoring dashboards configured
- [ ] Emergency contact list prepared

### ✅ Code Preparation
- [ ] All new code reviewed and approved
- [ ] Unit tests passing (100% coverage)
- [ ] Integration tests passing
- [ ] Performance benchmarks established
- [ ] Feature flags implemented for rollback

### ✅ Database Preparation
- [ ] Current schema documented
- [ ] Table sizes and indexes analyzed
- [ ] Foreign key dependencies mapped
- [ ] Lock time estimates calculated
- [ ] Maintenance window scheduled

## 🔄 Migration Steps

### Phase 1: Schema Consistency (30 minutes)

#### Step 1.1: Database Backup (5 minutes)
```bash
# Create full backup
mysqldump -u vidieu -p vidieu_db > backup_pre_migration_$(date +%Y%m%d_%H%M%S).sql

# Verify backup
mysql -u vidieu -p vidieu_db < backup_pre_migration_*.sql --dry-run
```

#### Step 1.2: Schema Analysis (5 minutes)
```sql
-- Check current column structure
DESCRIBE bz_vd_provider_accounts;
DESCRIBE bz_vd_pool_accounts;
DESCRIBE bz_vd_pools;
DESCRIBE bz_vd_license_keys;

-- Count existing data
SELECT COUNT(*) FROM bz_vd_provider_accounts;
SELECT COUNT(*) FROM bz_vd_license_keys;
```

#### Step 1.3: Add Missing Columns (10 minutes)
```sql
-- Add columns if they don't exist
ALTER TABLE bz_vd_provider_accounts
ADD COLUMN IF NOT EXISTS account_password LONGTEXT NULL
COMMENT 'Login password (encrypted)' AFTER display_name;

ALTER TABLE bz_vd_provider_accounts
ADD COLUMN IF NOT EXISTS current_usage INT NOT NULL DEFAULT 0
COMMENT 'Current number of active licenses' AFTER capacity;

-- Update existing data
UPDATE bz_vd_provider_accounts
SET current_usage = (
    SELECT COUNT(*)
    FROM bz_vd_license_keys
    WHERE account_id = bz_vd_provider_accounts.id
    AND status = 'active'
) WHERE current_usage = 0;
```

#### Step 1.4: Update Code References (10 minutes)
```php
// Deploy updated order handler with correct column mapping
// Feature flag: use_new_schema = true
update_option('vd_use_new_schema', true);
```

### Phase 2: Strategy Implementation (45 minutes)

#### Step 2.1: Deploy Strategy Classes (15 minutes)
```bash
# Upload new strategy files
rsync -av includes/services/pool-assignment/ production:/path/to/wp-content/plugins/vd-license-manager/includes/services/

# Verify files uploaded
ls -la includes/services/pool-assignment/
```

#### Step 2.2: Update REST API (15 minutes)
```php
// Deploy updated REST API with proper JOIN logic
// Feature flag: use_new_assignment_logic = true
update_option('vd_use_new_assignment_logic', true);
```

#### Step 2.3: Test Assignment Flow (15 minutes)
```bash
# Run integration tests
php tests/integration/pool-assignment-test.php

# Create test order
curl -X POST /wp-json/wc/v3/orders -d @test-order.json

# Verify assignment in logs
tail -f wp-content/debug.log | grep "VD Pool Assignment"
```

### Phase 3: Capacity Calculation (30 minutes)

#### Step 3.1: Deploy Capacity Service (10 minutes)
```php
// Upload capacity calculation service
update_option('vd_use_capacity_service', true);
```

#### Step 3.2: Initial Capacity Sync (15 minutes)
```php
// Run initial capacity calculation for all pools
$service = new VD_Pool_Capacity_Service();
$service->sync_all_pools();
```

#### Step 3.3: Setup Monitoring (5 minutes)
```php
// Enable capacity monitoring
wp_schedule_event(time(), 'hourly', 'vd_sync_pool_capacities');
```

### Phase 4: Database Optimization (20 minutes)

#### Step 4.1: Add Constraints (10 minutes)
```sql
-- Add foreign key constraints
ALTER TABLE bz_vd_pool_accounts
ADD CONSTRAINT fk_pool_accounts_pool_id
FOREIGN KEY (pool_id) REFERENCES bz_vd_pools(id)
ON DELETE CASCADE;

ALTER TABLE bz_vd_pool_accounts
ADD CONSTRAINT fk_pool_accounts_account_id
FOREIGN KEY (account_id) REFERENCES bz_vd_provider_accounts(id)
ON DELETE CASCADE;
```

#### Step 4.2: Add Indexes (10 minutes)
```sql
-- Add performance indexes
CREATE INDEX idx_pool_accounts_pool_status
ON bz_vd_pool_accounts(pool_id, status);

CREATE INDEX idx_accounts_capacity_usage
ON bz_vd_provider_accounts(current_usage, capacity, status);

CREATE INDEX idx_accounts_expires
ON bz_vd_provider_accounts(expires_at, status);
```

## ⏭️ Post-Migration Validation

### ✅ Functional Testing (30 minutes)
```bash
# Test suite execution
php tests/run-all-tests.php

# Manual test scenarios
1. Create new order → verify pool assignment
2. Create 10 concurrent orders → verify no race conditions
3. Fill pool to capacity → verify fallback works
4. Expire account → verify account skipped
5. Check logs → verify no errors
```

### ✅ Performance Testing (15 minutes)
```bash
# Benchmark assignment times
php tests/benchmark-assignment.php

# Expected results:
# - Pool selection: < 50ms
# - Account selection: < 100ms
# - Full assignment: < 200ms
```

### ✅ Data Integrity Check (15 minutes)
```sql
-- Verify capacity calculations
SELECT
    p.id,
    p.name,
    SUM(a.capacity) as calculated_capacity,
    COUNT(l.id) as assigned_licenses
FROM bz_vd_pools p
LEFT JOIN bz_vd_pool_accounts pa ON p.id = pa.pool_id
LEFT JOIN bz_vd_provider_accounts a ON pa.account_id = a.id
LEFT JOIN bz_vd_license_keys l ON p.id = l.pool_id AND l.status = 'active'
GROUP BY p.id;

-- Check for orphaned records
SELECT COUNT(*) FROM bz_vd_license_keys WHERE pool_id NOT IN (SELECT id FROM bz_vd_pools);
SELECT COUNT(*) FROM bz_vd_pool_accounts WHERE pool_id NOT IN (SELECT id FROM bz_vd_pools);
```

## 🔙 Rollback Procedures

### Emergency Rollback (< 5 minutes)
```bash
# 1. Disable new features immediately
update_option('vd_use_new_schema', false);
update_option('vd_use_new_assignment_logic', false);
update_option('vd_use_capacity_service', false);

# 2. Deploy previous code version
git checkout previous-stable-tag
rsync -av . production:/path/to/plugin/

# 3. Verify basic functionality
curl /test-assignment-endpoint
```

### Full Database Rollback (< 15 minutes)
```bash
# Only if data corruption detected
mysql -u vidieu -p vidieu_db < backup_pre_migration_*.sql

# Verify rollback
mysql -u vidieu -p -e "SELECT COUNT(*) FROM bz_vd_provider_accounts;"
```

### Partial Rollback (Schema Only)
```sql
-- Remove new columns if needed
ALTER TABLE bz_vd_provider_accounts DROP COLUMN IF EXISTS account_password;
ALTER TABLE bz_vd_provider_accounts DROP COLUMN IF EXISTS current_usage;

-- Remove constraints
ALTER TABLE bz_vd_pool_accounts DROP CONSTRAINT IF EXISTS fk_pool_accounts_pool_id;
ALTER TABLE bz_vd_pool_accounts DROP CONSTRAINT IF EXISTS fk_pool_accounts_account_id;
```

## 📊 Success Criteria

### ✅ Functional Requirements
- [ ] Pool assignment follows priority rules correctly
- [ ] Balanced assignment distributes load evenly
- [ ] Account capacity checks prevent overselling
- [ ] Edge cases handled gracefully
- [ ] All existing functionality preserved

### ✅ Performance Requirements
- [ ] Assignment time < 200ms (95th percentile)
- [ ] Database query time < 50ms
- [ ] No performance regression vs baseline
- [ ] Memory usage within acceptable limits

### ✅ Reliability Requirements
- [ ] Zero data loss during migration
- [ ] Zero downtime during migration
- [ ] All tests passing (unit + integration)
- [ ] Error rate < 0.1% in first 24 hours
- [ ] Monitoring alerts configured and working

## 🚨 Risk Mitigation

### High Risk: Data Corruption
- **Mitigation**: Full backup + staging testing
- **Detection**: Automated integrity checks
- **Response**: Immediate rollback + data restore

### Medium Risk: Performance Degradation
- **Mitigation**: Performance testing + query optimization
- **Detection**: Real-time monitoring dashboards
- **Response**: Query tuning + caching implementation

### Low Risk: Feature Incompatibility
- **Mitigation**: Feature flags + gradual rollout
- **Detection**: Integration test suite
- **Response**: Feature flag disable + code fix

## 📞 Emergency Contacts

### Migration Team
- **Lead Developer**: [Your Name] - [Phone] - [Email]
- **Database Admin**: [DBA Name] - [Phone] - [Email]
- **DevOps Engineer**: [DevOps Name] - [Phone] - [Email]

### Escalation
- **Technical Lead**: [TL Name] - [Phone] - [Email]
- **Product Owner**: [PO Name] - [Phone] - [Email]

### Vendor Support
- **Hosting Provider**: [Support Number]
- **Database Support**: [Support Number]

---

**Migration Window**: [Date] [Time] - [End Time]
**Estimated Duration**: 2 hours
**Business Impact**: Zero downtime expected
**Success Criteria**: All tests pass + monitoring green