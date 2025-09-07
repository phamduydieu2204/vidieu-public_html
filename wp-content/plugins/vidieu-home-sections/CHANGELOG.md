# Changelog - Vidieu Home Sections

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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