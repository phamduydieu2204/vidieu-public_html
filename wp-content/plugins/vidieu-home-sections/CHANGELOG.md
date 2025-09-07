# Changelog - Vidieu Home Sections

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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