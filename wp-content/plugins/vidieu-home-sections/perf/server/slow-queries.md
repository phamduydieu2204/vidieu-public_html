# Slow Queries Analysis

Generated: [Pending profiling run]

## Instructions

To generate this report:

1. Enable logging flags in wp-config.php:
   ```php
   define('VIDIEU_PERF_LOG_QUERIES', true);
   define('VIDIEU_PERF_LOG_BOOTSTRAP', true);
   ```

2. Run the profiling script:
   ```bash
   cd wp-content/plugins/vidieu-home-sections/perf/server/
   php run-profiling.php
   ```

3. Analyze the collected logs:
   ```bash
   php analyze-logs.php
   ```

4. Disable the logging flags after profiling is complete.

## Report Structure

This report will contain:
- Top 20 slowest queries across all routes
- Common slow query patterns
- Optimization recommendations based on findings

The analysis will help identify:
- Repeated queries that can be cached
- Missing database indexes
- Unnecessary queries on specific routes
- WooCommerce query optimization opportunities