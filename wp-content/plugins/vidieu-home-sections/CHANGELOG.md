# Changelog - Vidieu Home Sections

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.5] - 2025-09-07

### Added
- Spinner slot management system to prevent text overlap on desktop
- Mobile spinner visibility - now displays loading spinner on mobile
- `manageSpinner()` function to handle spinner placement and visibility
- `hideSpinner()` centralized function for consistent spinner hiding
- Flexbox centering for perfect spinner alignment in slot
- VIDIEU_VCBQR_DEBUG flag support for controlled debug logging

### Fixed
- Removed extra script tag (`vidieu-vcb-qr-compat-js-extra`) from DOM after loading
- Desktop spinner no longer overlaps "Bước 1" instruction text
- Mobile devices now properly show loading spinner while QR loads

### Changed
- Spinner is moved to dedicated slot above instruction text
- Improved CSS layout with proper margins and flexbox alignment (display: flex, align-items: center, justify-content: center)
- Spinner management is now idempotent with `data-spinner-mounted` flag
- Removed all production debug console.log statements
- Debug logging now only active when VIDIEU_VCBQR_DEBUG flag is explicitly enabled

### Security
- Cleaned up all test/debug code from production
- Debug output now requires explicit flag activation

## [1.2.4] - 2025-09-07

### Fixed
- **Critical**: Fixed 404 errors for compat CSS/JS files
  - Corrected plugin URL path calculation using `plugins_url()`
  - Files were incorrectly loading from `/wp-content/plugins/assets/`
  - Now correctly load from plugin's actual directory

### Changed
- Switched back to `wp_localize_script` for better compatibility
- Added support for checking QR in both left and right columns
- Implemented debouncing for MutationObserver to prevent duplicate logs
- Removed redundant `ensureQRVisibility` calls

### Improved
- Made initialization idempotent with `window.vcbQRInitialized` flag
- Enhanced QR detection to check left column, right column, and desktop section
- Optimized observer callbacks with debounce timers

## [1.2.3] - 2025-09-07

### Fixed
- Localized script being displayed as text instead of executing
  - Changed from wp_localize_script to wp_add_inline_script to avoid escaping issues
  - Added data-cfasync="false" to prevent Cloudflare Rocket Loader interference
  - Added no-lazyload class for Autoptimize exclusion

### Added
- Fallback QR generation from page data when plugin doesn't provide QR
  - Extracts BIN, account, amount, and content from DOM
  - Generates VietQR URL dynamically
  - Shows error message with link if QR fails to load
- Script tag attributes to prevent optimization conflicts
- QR error styles and retry mechanism

### Changed
- Enhanced mobile QR display with both clone and fallback strategies
- Improved debug logging to accept both integer and string values
- Use file modification time for cache busting

## [1.2.2] - 2025-09-07

### Added
- QR clone strategy for mobile devices - clones QR from desktop to mobile section
- Typo-friendly selectors supporting both `.anMoblie` and `.anMobile`
- Fallback UI with retry button after 8s timeout
- Auto-hide loading spinner when QR is present

### Changed  
- Improved enqueue order (priority 20) for proper script loading
- Updated localization to use string values ('1' instead of boolean)
- Enhanced mobile detection and responsive handling

### Fixed
- QR display on mobile by cloning from `.anPc` to `.anMoblie` section
- Inline CSS syntax error: "line-height: 1, 2em" → "1.2em"
- Script loading order to ensure localized variables load first
- Debug logging now controlled by VIDIEU_VCBQR_DEBUG constant

## [1.2.0] - 2025-09-07

### Added
- VCB-MH compatibility layer for mobile QR code display
  - New compatibility module: `compat/compat-vcbmh.php`
  - Responsive CSS fixes: `assets/css/vcb-qr-compat.css`
  - JavaScript observer: `assets/js/vcb-qr-compat.js`
  - Ensures QR codes display properly on mobile devices

### Changed
- Updated duplicate request guard to whitelist VCB-MH scripts and styles
  - Added vcb-mh-public, sweetalert2 to allowed resources on order-received page
  - Prevents performance optimization from blocking payment gateway functionality

### Fixed
- QR code visibility on mobile devices (viewport <= 768px)
- CSS media query conflicts hiding payment information
- Parent container overflow issues preventing QR display
- SweetAlert2 popup sizing on mobile devices

### Security
- Removed debug output from production code
- Cleaned up test files and directories

## [1.1.1] - Previous Release

### Changed
- Performance optimizations
- AJAX handling improvements

### Fixed
- Various bug fixes and improvements