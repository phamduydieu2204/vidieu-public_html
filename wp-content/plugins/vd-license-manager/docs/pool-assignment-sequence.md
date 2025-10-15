# Pool Assignment Sequence Diagrams

## 🔄 Primary Assignment Flow

```mermaid
sequenceDiagram
    participant O as Order Handler
    participant PS as Pool Assignment Service
    participant PC as Pool Selector
    participant AC as Account Selector
    participant DB as Database
    participant L as Logger

    O->>PS: assign_license(license_id, product_id)
    PS->>PC: get_available_pools(product_id)
    PC->>DB: SELECT pools with capacity
    DB-->>PC: pools[]
    PC-->>PS: available_pools[]

    loop For each pool (by strategy)
        PS->>AC: select_account(pool_id)
        AC->>DB: SELECT least-used account
        DB-->>AC: account
        AC->>DB: UPDATE usage (atomic)
        DB-->>AC: success/failure

        alt Success
            AC-->>PS: account_assigned
            PS->>L: log_assignment_success
            PS-->>O: assignment_complete
        else Failure (race condition)
            AC-->>PS: try_next_account
        end
    end

    alt All pools failed
        PS->>PS: queue_for_manual_assignment
        PS->>L: log_assignment_failure
        PS-->>O: assignment_failed
    end
```

## ⚡ Race Condition Handling

```mermaid
sequenceDiagram
    participant T1 as Thread 1
    participant T2 as Thread 2
    participant DB as Database
    participant AC as Account Selector

    par Concurrent Assignments
        T1->>AC: select_account(pool_1)
        T2->>AC: select_account(pool_1)
    end

    par Account Selection
        AC->>DB: SELECT account (usage=5, capacity=10)
        AC->>DB: SELECT account (usage=5, capacity=10)
    end

    Note over DB: Same account returned to both threads

    par Atomic Updates
        T1->>DB: UPDATE usage=6 WHERE id=X AND usage=5
        T2->>DB: UPDATE usage=6 WHERE id=X AND usage=5
    end

    DB-->>T1: 1 row affected (success)
    DB-->>T2: 0 rows affected (failure)

    T1->>T1: assignment_complete
    T2->>AC: try_next_account
```

## 🔄 Fallback Chain Flow

```mermaid
sequenceDiagram
    participant PS as Pool Service
    participant P1 as Priority Pool 1
    participant P2 as Priority Pool 2
    participant P3 as Priority Pool 3
    participant Q as Assignment Queue
    participant A as Admin Notifier

    PS->>P1: try_assignment()
    P1-->>PS: failed (full)

    PS->>P2: try_assignment()
    P2-->>PS: failed (full)

    PS->>P3: try_assignment()
    P3-->>PS: failed (full)

    PS->>Q: queue_for_manual_assignment()
    PS->>A: notify_admin_critical()

    Note over PS: All pools exhausted
    PS-->>PS: return assignment_failed
```

## 📊 Capacity Calculation Flow

```mermaid
sequenceDiagram
    participant CS as Capacity Service
    participant DB as Database
    participant Cache as Redis Cache
    participant PS as Pool Service

    PS->>CS: check_pool_capacity(pool_id)
    CS->>Cache: get_cached_capacity(pool_id)

    alt Cache Hit
        Cache-->>CS: capacity_data
    else Cache Miss
        CS->>DB: calculate_real_capacity(pool_id)
        Note over DB: SUM(account.capacity) WHERE pool_id=X
        DB-->>CS: total_capacity
        CS->>DB: count_assigned_licenses(pool_id)
        DB-->>CS: assigned_count
        CS->>Cache: cache_capacity(pool_id, data, 5min)
    end

    CS->>CS: available = capacity - assigned
    CS-->>PS: pool_availability
```

## 🚨 Error Handling Flow

```mermaid
sequenceDiagram
    participant AS as Assignment Service
    participant EH as Error Handler
    participant L as Logger
    participant N as Notifier
    participant Q as Queue

    AS->>AS: assignment_attempt()

    alt Recoverable Error
        AS->>EH: handle_expired_account()
        EH->>L: log_warning()
        EH-->>AS: continue_next_account
    else Pool Error
        AS->>EH: handle_empty_pool()
        EH->>L: log_error()
        EH-->>AS: try_next_pool
    else Critical Error
        AS->>EH: handle_no_pools_available()
        EH->>L: log_critical()
        EH->>Q: queue_assignment()
        EH->>N: notify_admin_urgent()
        EH-->>AS: assignment_failed
    end
```

## 🔄 Cache Invalidation Flow

```mermaid
sequenceDiagram
    participant LS as License Service
    participant CS as Capacity Service
    participant Cache as Redis Cache
    participant DB as Database

    LS->>LS: license_assigned_successfully
    LS->>CS: invalidate_capacity_cache(pool_id, account_id)

    par Cache Invalidation
        CS->>Cache: delete(pool_capacity_key)
        CS->>Cache: delete(account_capacity_key)
    end

    Note over CS: Next request will recalculate

    LS->>DB: trigger_capacity_recalc_job()

    Note over DB: Background job updates cached values
```