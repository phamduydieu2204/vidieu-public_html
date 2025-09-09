# Changelog

All notable changes to Vidieu Home Sections plugin will be documented in this file.

## [Unreleased]

### Analysis
- 2025-09-09: Buy Now Simple Product Standard Analysis
  - Mapped complete UI states and transitions for simple products
  - Identified duplicate event handlers issue (2+ handlers on same selector)
  - Found 5 hardcoded delays (100ms to 3000ms) 
  - Discovered missing accessibility attributes (aria-busy, aria-label)
  - Created patch plan for standardization

### Optimization
- 2025-09-09: Buy Now Simple Product Implementation
  - Created standardized handler (buynow-simple.js) with namespace .vdBuyNow
  - Removed all hardcoded delays for simple products
  - Added full ARIA support (aria-busy, aria-label, aria-live)
  - Implemented proper state machine (idle → loading → success/error)
  - Added debounce 300ms + processing flag to prevent double-clicks
  - Skip fragment updates when redirecting
  - Added toast notifications for errors
  - Batch DOM updates with requestAnimationFrame
  - Proper timeout cleanup to prevent memory leaks
  - Excluded simple products from legacy handlers

## [1.2.6] - 2025-09-09

### Added
- Buy Now No-Scroll fix to prevent auto-scroll on button click
- Resource loading documentation for known third-party issues
- Contact page with full Vietnamese translation

### Fixed
- Mobile display issues with NASA theme detection
- Auto-scroll behavior when clicking Buy Now
- Contact form validation and auto-fill

### Changed
- Cleaned up console.log statements in production code
- Simplified performance optimization file loading

### Removed
- Deprecated products-grid.php template
- Unused performance optimization files
- Temporary fix attempts for third-party issues

## [1.2.5] - Previous releases
- Initial Buy Now button implementation
- WooCommerce integration 
- AJAX handlers for product actions
- NASA theme compatibility