# Performance Optimization Changelog

## Phase 2: Critical CSS Implementation (2025-09-06)

### Added
- **Critical CSS Loader** (`class-vidieu-critical-css.php`)
  - Route-based CSS detection and loading
  - Automatic minification
  - Safe inline injection in `<head>`
  
- **Critical CSS Files** (8 templates)
  - `home.css` - Homepage above-the-fold styles
  - `archive-product.css` - Shop/category pages
  - `single-product.css` - Product detail pages
  - `single-post.css` - Blog post pages
  - `page-contact.css` - Contact page
  - `cart.css` - Shopping cart
  - `checkout.css` - Checkout process
  - `my-account.css` - Customer account pages

- **Helper Tools**
  - `extract-helper.php` - Guidelines for extracting critical CSS

### Implementation Details
1. **Non-invasive approach**
   - No modifications to existing stylesheets
   - No changes to stylesheet loading order
   - No preload/async modifications
   - Pure additive optimization

2. **Smart routing**
   - Automatic template detection
   - Fallback to no critical CSS if route unknown
   - Support for custom post types

3. **Performance considerations**
   - Basic minification included
   - File size limit: 12KB per template
   - Early injection (priority 2 in wp_head)

### Configuration
Enable with feature flag:

```php
// In wp-config.php
define('VIDIEU_PERF_CRITICAL_CSS', true);
```

### Expected Impact
- LCP improvement: 300-500ms
- FCP improvement: 200-400ms
- No CLS impact (stable layout)
- Minimal overhead: 2-3KB inline

### Files Modified
- `wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php`

### Files Added
- `inc/perf/class-vidieu-critical-css.php`
- `perf/critical-css/home.css`
- `perf/critical-css/archive-product.css`
- `perf/critical-css/single-product.css`
- `perf/critical-css/single-post.css`
- `perf/critical-css/page-contact.css`
- `perf/critical-css/cart.css`
- `perf/critical-css/checkout.css`
- `perf/critical-css/my-account.css`
- `perf/critical-css/extract-helper.php`

### Testing Checklist
- [ ] Critical CSS loads on correct routes
- [ ] No visual regressions
- [ ] No console errors
- [ ] Mobile view correct
- [ ] No layout shift
- [ ] Performance metrics improved

---

## Phase 1: TTFB & Server-side Optimization (2025-09-06)

### Added
- **Performance Logging System** (`class-vidieu-perf-logger.php`)
  - Query logging with route detection
  - Bootstrap hooks tracking
  - Automatic log analysis and reporting
  
- **Route-based Optimizer** (`class-vidieu-route-optimizer.php`)
  - Conditional WooCommerce loading
  - Route-specific optimizations
  - Query and transient caching

- **Feature Flags** in `vd-perf-guardrails.php`
  - `VIDIEU_PERF_LOG_QUERIES` - Enable query logging
  - `VIDIEU_PERF_LOG_BOOTSTRAP` - Enable bootstrap logging
  - `VIDIEU_PERF_ROUTE_CONDITIONALS` - Enable route-based optimizations

- **Profiling Tools**
  - `run-profiling.php` - Automated route testing
  - `analyze-logs.php` - Log analysis and reporting

### Optimizations
1. **Query Reduction**
   - Transient caching for repeated queries
   - Optimized options autoload
   - Reduced meta query overhead

2. **Conditional Loading**
   - WooCommerce scripts/styles only on commerce pages
   - Cart fragments removed from non-commerce routes
   - Emoji support disabled on transactional pages

3. **Route-specific Improvements**
   - Homepage: Cached main query, optimized widgets
   - Shop/Product: Pre-cached product data
   - Cart/Checkout: Streamlined scripts, cached fields
   - Blog: Optimized post queries, disabled unnecessary caches
   - Contact: Minimal asset loading
   - Account: Cached user data

### Configuration
All optimizations are disabled by default. To enable:

```php
// In wp-config.php
define('VIDIEU_PERF_ROUTE_CONDITIONALS', true);

// For debugging/analysis only
define('VIDIEU_PERF_LOG_QUERIES', true);
define('VIDIEU_PERF_LOG_BOOTSTRAP', true);
```

### Expected Impact
- TTFB reduction: 200-500ms
- Query reduction: 20-40%
- Memory usage: 5-10% improvement

### Files Modified
- `wp-content/mu-plugins/vd-perf-guardrails.php`
- `wp-content/plugins/vidieu-home-sections/vidieu-home-sections.php`

### Files Added
- `inc/perf/class-vidieu-perf-logger.php`
- `inc/perf/class-vidieu-route-optimizer.php`
- `perf/server/run-profiling.php`
- `perf/server/analyze-logs.php`
- `perf/server/slow-queries.md`
- `perf/server/analysis-phase1.md`
- `perf/DELTA_REPORT.md`
- `perf/CHANGELOG_perf.md`

### Testing Status
- [ ] Query logging tested
- [ ] Bootstrap logging tested
- [ ] Route optimizations tested
- [ ] Performance metrics validated
- [ ] No visual regressions confirmed

---

## Phase 0: Initial Setup (2025-09-06)

### Added
- Basic feature flag infrastructure
- Baseline performance data collection
- Documentation structure

### Files
- `wp-content/mu-plugins/vd-perf-guardrails.php` (v1.0.0)
- `perf/BASELINE/` directory with HAR and PSI data
- `perf/README.md`