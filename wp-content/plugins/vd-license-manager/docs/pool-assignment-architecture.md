# Pool Assignment Architecture Design

## 📋 Overview

This document outlines the architecture design for the VD License Manager pool assignment system refactoring.

## 🏗️ Architecture Components

### 1. Strategy Pattern Implementation

```
VD_Pool_Assignment_Strategy (Interface)
├── VD_Priority_Assignment_Strategy
├── VD_Balanced_Assignment_Strategy
└── VD_Weighted_Assignment_Strategy (Future)
```

### 2. Core Services

```
VD_Pool_Assignment_Service (Main orchestrator)
├── VD_Pool_Selector
├── VD_Account_Selector
├── VD_Capacity_Calculator
└── VD_Assignment_Logger
```

### 3. Data Flow

```
Order Completed
    ↓
Pool Assignment Service
    ↓
Select Strategy (Priority/Balanced)
    ↓
Get Available Pools (with capacity check)
    ↓
For Each Pool (by priority/load):
    ↓
Select Account (least-used, active, not-expired)
    ↓
Atomic Assignment (optimistic locking)
    ↓
Success? → Update capacity, log, notify
    ↓
Failed? → Try next account/pool
    ↓
All Failed? → Queue + notify admin
```

## 🎯 Assignment Strategies

### Priority Strategy
1. Get pools for product ordered by `priority ASC`
2. For each pool, check capacity
3. Select first pool with available capacity
4. Within pool, select least-used account

### Balanced Strategy
1. Get pools for product
2. Calculate current load for each pool
3. Select pool with lowest load
4. If tie, use priority as tiebreaker
5. Within pool, select least-used account

## 🔒 Concurrency Handling

### Optimistic Locking
```sql
UPDATE bz_vd_provider_accounts
SET current_usage = current_usage + 1,
    updated_at = NOW()
WHERE id = ?
  AND current_usage < capacity
  AND status = 'active'
  AND (expires_at IS NULL OR expires_at > NOW())
  AND updated_at = ?  -- Version check
```

### Retry Logic
- Max 3 retries per account
- 100ms exponential backoff
- Move to next account/pool on failure
- Total timeout: 5 seconds

## 💾 Caching Strategy

### Pool Availability Cache
- **Key**: `vd_pool_availability_{product_id}`
- **TTL**: 5 minutes
- **Data**: Array of available pools with capacity
- **Invalidation**: On license assignment/unassignment

### Account Capacity Cache
- **Key**: `vd_account_capacity_{account_id}`
- **TTL**: 1 minute
- **Data**: Current usage and capacity
- **Invalidation**: On account update

## 🚨 Error Handling

### Error Levels
1. **Recoverable**: Skip to next account (expired account)
2. **Pool-level**: Try next pool (empty pool)
3. **Critical**: Fail assignment + queue (no pools available)

### Fallback Chain
```
Priority Pool 1 → Account Selection → Atomic Update
    ↓ Failed
Priority Pool 2 → Account Selection → Atomic Update
    ↓ Failed
...
    ↓ All Failed
Queue for Manual Assignment + Notify Admin
```

## 📊 Monitoring & Alerts

### Metrics to Track
- Assignment success rate by strategy
- Average assignment time
- Pool utilization rates
- Account capacity distribution
- Failed assignment reasons

### Alerts
- Pool capacity > 90% (Warning)
- Pool capacity = 100% (Critical)
- Assignment failure rate > 10% (Warning)
- No available pools (Critical)

## 🔄 Migration Strategy

### Phase 1: Schema Consistency
- Fix column naming inconsistencies
- Update all code references
- Add missing indexes

### Phase 2: Strategy Implementation
- Implement strategy pattern
- Add assignment service layer
- Maintain backward compatibility

### Phase 3: Optimization
- Add caching layer
- Implement monitoring
- Performance tuning

### Phase 4: Cleanup
- Remove deprecated code
- Final testing
- Documentation

## 🎪 Testing Strategy

### Unit Tests
- Strategy implementations
- Account selection logic
- Capacity calculations
- Error handling

### Integration Tests
- Full assignment flow
- Concurrent assignments
- Fallback mechanisms
- Cache invalidation

### Performance Tests
- Assignment time < 100ms
- Concurrent load testing
- Database query optimization
- Memory usage monitoring

## 📈 Success Metrics

- ✅ Assignment success rate > 99%
- ✅ Average assignment time < 100ms
- ✅ Zero race conditions in concurrent tests
- ✅ 100% edge case coverage
- ✅ Database consistency maintained