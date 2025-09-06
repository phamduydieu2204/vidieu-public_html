# Vidieu.vn Performance Optimization Documentation

## Overview
This directory contains performance optimization data and baseline metrics for vidieu.vn website.

## Phase 0: Performance Guardrails Setup

### MU-Plugin Structure
Location: `wp-content/mu-plugins/vd-perf-guardrails.php`

### Feature Flags
The following feature flags have been implemented to control performance optimizations:

| Flag | Purpose | Default |
|------|---------|---------|
| `VIDIEU_PERF_CRITICAL_CSS` | Controls Critical CSS inline injection for above-the-fold content | `false` |
| `VIDIEU_PERF_DEFER_JS` | Controls JavaScript deferral for non-critical scripts | `false` |
| `VIDIEU_PERF_FONTS` | Controls font optimization and preloading strategies | `false` |
| `VIDIEU_PERF_WC` | Controls WooCommerce-specific performance optimizations | `false` |
| `VIDIEU_PERF_LOG_QUERIES` | Enables query logging for performance analysis (Phase 1) | `false` |
| `VIDIEU_PERF_LOG_BOOTSTRAP` | Enables bootstrap hooks logging (Phase 1) | `false` |
| `VIDIEU_PERF_ROUTE_CONDITIONALS` | Enables route-based conditional loading (Phase 1) | `false` |

### Usage
To enable any optimization, define the constant in `wp-config.php` before the MU-plugin loads:

```php
define('VIDIEU_PERF_CRITICAL_CSS', true);
define('VIDIEU_PERF_DEFER_JS', true);
define('VIDIEU_PERF_FONTS', true);
define('VIDIEU_PERF_WC', true);
```

### Baseline Data
Location: `wp-content/plugins/vidieu-home-sections/perf/BASELINE/`

Contains performance baseline metrics captured for the following routes:
- `/` - Homepage
- `/san-pham/` - Product archive
- `/bai-viet/` - Post archive
- `/contact/` - Contact page

#### Data Files
- **HAR Files**: HTTP Archive format containing network waterfall data
  - `trang-chu.har`
  - `trang-san-pham.har`
  - `trang-bai-viet.har`
  - `trang-contact.har`

- **PageSpeed Insights**: Google PSI scores and metrics
  - Desktop and Mobile versions for each page
  - Format: `psi-[page]-[device].json`

### Implementation Guidelines
1. All performance optimizations must be implemented within the `vidieu-home-sections` plugin
2. Each optimization must check its corresponding feature flag before executing
3. No modifications to WordPress/WooCommerce core or parent theme
4. Child theme modifications allowed only when absolutely necessary

### Rollback Procedure
To disable any optimization causing issues:
1. Set the corresponding flag to `false` in `wp-config.php`
2. Or comment out the flag definition
3. Clear any caches (object cache, page cache)

### Monitoring
When `WP_DEBUG` is enabled, active performance flags will be displayed in the admin area for administrators.

## Phase 1: TTFB & Server-side Optimization (Implemented)

### Overview
Phase 1 focuses on reducing Time To First Byte (TTFB) through query optimization and route-based conditional loading.

### Components
1. **Performance Logging** (`inc/perf/class-vidieu-perf-logger.php`)
   - Tracks MySQL queries by route
   - Monitors bootstrap hooks and timing
   - Generates detailed performance logs

2. **Route Optimizer** (`inc/perf/class-vidieu-route-optimizer.php`)
   - Conditionally loads features based on current route
   - Removes unnecessary WooCommerce features from non-commerce pages
   - Implements query caching and optimization

3. **Analysis Tools**
   - `perf/server/run-profiling.php` - Automated profiling script
   - `perf/server/analyze-logs.php` - Log analysis and report generation

### Usage
```php
// Enable route optimizations (production)
define('VIDIEU_PERF_ROUTE_CONDITIONALS', true);

// Enable logging (development/testing only)
define('VIDIEU_PERF_LOG_QUERIES', true);
define('VIDIEU_PERF_LOG_BOOTSTRAP', true);
```

### Reports
- `perf/server/slow-queries.md` - Query analysis results
- `perf/server/analysis-phase1.md` - Performance bottleneck analysis
- `perf/DELTA_REPORT.md` - Before/after metrics
- `perf/CHANGELOG_perf.md` - Detailed change log

## Future Phases
- Phase 2: Critical CSS implementation
- Phase 3: JavaScript optimization
- Phase 4: Font optimization
- Phase 5: Advanced WooCommerce optimization

Each phase will be implemented behind its corresponding feature flag.