# Changelog - Vidieu Home Sections

All notable changes to this plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.5] - 2025-09-08

### Added
- Desktop positioning with 60px bottom offset to avoid bottom navigation
- Clean CSS-only positioning for desktop (right: 20px, bottom: 60px)

### Changed
- Desktop: Re:plain bubble now positioned above bottom navigation
- Mobile: Bubble remains hidden, access only through popup chat

### Improved
- Simple CSS positioning without complex JavaScript
- Better compatibility with bottom navigation bars
- Consistent behavior across different themes

## [1.7.4] - 2025-09-08

### Removed
- ALL custom positioning code for Re:plain widget
- CSS overrides for bottom position
- JavaScript position fix file (`replain-position-fix.js`)
- PHP functions for bottom offset configuration
- Constants `VIDIEU_REPLAIN_DESKTOP_POSITION` and `VIDIEU_REPLAIN_BOTTOM_OFFSET`
- Filters `vidieu_replain_desktop_position` and `vidieu_replain_bottom_offset`

### Changed
- Re:plain now uses its default positioning system
- Simplified CSS to only handle mobile hiding behavior
- Documentation updated to reflect removal of positioning features

### Improved
- Cleaner codebase without positioning overrides
- Better compatibility with Re:plain updates
- Reduced JavaScript execution overhead

## [1.7.3] - 2025-09-08

### Fixed
- Re:plain chat panel now slides up from viewport bottom instead of floating
- Chat app container forced to `bottom: 0` when opened

### Added
- New JavaScript file `replain-position-fix.js` to handle chat panel positioning
- CSS overrides for `#app.app.-right.-bottom` to stick to viewport bottom
- MutationObserver to detect and fix dynamically added chat panels

### Improved
- Better user experience with chat sliding from bottom edge
- Consistent behavior across different screen sizes
- No visual "jump" when opening chat

## [1.7.2] - 2025-09-08

### Changed
- Re:plain widget positioning now uses fixed bottom offset (default: 100px)
- Removed dynamic bottom positioning to avoid reCAPTCHA conflicts
- Bottom offset is configurable via `VIDIEU_REPLAIN_BOTTOM_OFFSET` constant

### Added
- New constant `VIDIEU_REPLAIN_BOTTOM_OFFSET` for configuring widget distance from bottom
- Filter `vidieu_replain_bottom_offset` to dynamically adjust bottom position

### Improved
- Cleaner CSS without unnecessary positioning code
- Better compatibility with reCAPTCHA and other bottom-fixed elements
- Simplified positioning logic using PHP inline styles

## [1.7.1] - 2025-09-08

### Changed
- Swapped positions: Re:plain widget now at right-bottom, contact icons at left-bottom
- Updated CSS positioning rules to match new layout requirements

## [1.7.0] - 2025-09-08

### Added
- Re:plain live chat integration with custom positioning and behavior
- Desktop: Widget positioned at right-bottom corner (20px margin)
- Desktop: Contact icons moved to left-bottom corner (20px margin)
- Mobile: Hidden bubble with "Chat trực tiếp (Re:plain)" item in popup
- Lazy loading on mobile - script loads only when user initiates chat
- Global JavaScript API: window.VidieuReplain.open() for programmatic access
- Configuration via constants: VIDIEU_REPLAIN_ENABLE, VIDIEU_REPLAIN_HIDE_ON_MOBILE
- Comprehensive documentation in docs/replain.md
- Body class 'replain-loaded' when script loads for CSS targeting

### Changed  
- Contact icons position from default to fixed left-bottom on desktop
- Max-width constraint (50%) for both Re:plain and contact icons to prevent overlap
- Z-index management: Re:plain (99998), Contact icons (99997)

## [1.6.2] - 2025-09-07

### Fixed
- Improved Buy Now button handling on single product pages to prevent conflicts with NASA theme
- Fixed inconsistent redirect behavior by intercepting NASA theme's form submission
- Enhanced event handling with capture phase listeners and form submit interception
- Added cleanup of NASA theme states to prevent interference

### Changed
- Removed timeout delay on redirect for immediate navigation
- Added form submit handler to catch NASA theme's Buy Now mechanism
- Improved debug logging for better troubleshooting

## [1.5.0] - 2025-09-07

### Added
- Font cleanup module to fix 404 errors and remove unused preloads
- System font fallback CSS for consistent UI display
- Multiple layers of protection against Elementor jost.css 404
- Output buffer cleanup for removing unused preload tags

### Fixed
- Elementor jost.css 404 error completely blocked
- Removed unused preloads for main-font.woff2 and style.min.css
- Preload warnings in browser console eliminated

### Changed
- Font display now uses safe system font stack
- Child theme functions updated to remove problematic preloads
- Performance improved by eliminating unnecessary font requests

## [1.4.2] - 2025-09-07

### Changed
- Removed all console.log debug statements from production code
- Simplified performance guard loading to use only v2-ultimate version
- Converted vcb-qr-compat.js log() function to no-op for production
- Cleaned up redundant code and improved code organization

### Security
- All debug/test code removed from production
- Debug functionality now strictly controlled by VIDIEU_VCBQR_DEBUG constant

## [1.4.1] - 2025-09-07

### Fixed
- NASA theme specific javascript:void(0) links that were causing crawlability issues
  - Fixed nasa-sidebar-return-shop buttons
  - Fixed nasa-toggle-widget links
  - Fixed nasa-nav-arrow and slick slider navigation
- Missing meta description on /san-pham/ (shop archive) page
- Canonical URL issues on post archive pages (/bai-viet/)
- Added proper aria-labels for NASA theme UI elements

### Improved
- Extended JavaScript void link detection to catch all patterns
- Better canonical URL generation with proper trailing slashes
- Enhanced meta description fallbacks for shop pages

## [1.4.0] - 2025-09-07

### Added
- Enhanced SEO module targeting 95+ Lighthouse scores
  - Comprehensive meta description generation for all page types
  - Enhanced product meta descriptions with price and details
  - Fixed all non-crawlable links (javascript:void(0))
  - Replaced generic link text with contextual descriptions
  - Mobile tap targets (48x48px minimum)
  - Comprehensive structured data (WebSite, Organization, BreadcrumbList, Product, Article)
  - Automatic aria-labels for icon-only links
  - Image alt text fallbacks
  - Enhanced robots.txt with multiple sitemaps

### Fixed
- P0: Non-crawlable links on all pages (crawlable-anchors audit)
- P0: Missing meta descriptions on product pages
- P0: Generic "Xem thêm" link text on home page
- Tap target sizes on mobile devices
- Missing ARIA labels for accessibility

### Improved
- SEO scores: Target 85→95+ for all page types
- Structured data with complete properties
- Link crawlability for search engines
- Mobile accessibility

## [1.3.0] - 2025-09-07

### Added
- SEO Bootstrap module for Lighthouse SEO optimization
  - Automatic meta description generation with multiple fallbacks
  - Clean canonical URL implementation with parameter filtering
  - Comprehensive structured data (JSON-LD) support
  - WebSite, Organization, BreadcrumbList, Product, and Article schemas
- Image alt text fallback system
- ARIA labels for icon-only links
- Non-crawlable link fixes with JavaScript enhancement
- Sitemap addition to robots.txt
- Guards to prevent conflicts with major SEO plugins

### Fixed
- Missing meta descriptions on all page types
- 265 non-crawlable links across the site
- Generic "Read More" link text issues
- Missing accessibility attributes on icon links
- Problematic preload directives

### Improved
- SEO scores: Home 77→90+, Product 85→90+, Post 83→90+
- Link crawlability for search engines
- Accessibility for screen readers

## [1.2.6] - 2025-09-07

### Fixed
- Spinner being enlarged to 554×554px on desktop due to global img rules
- Force spinner dimensions: desktop 36×36px, mobile 28×28px
- Added max-width: none !important to override global img{max-width:100%} rules

### Added
- `normalizeSpinnerElement()` function to remove width/height attributes from spinner images
- `ensureSpinnerCentered()` function with absolute positioning fallback
- MutationObserver to handle dynamically re-inserted spinners
- Strong CSS selectors to ensure spinner size rules take precedence

### Changed
- Spinner CSS now uses !important on all size properties to prevent overrides
- Added object-fit: contain to maintain spinner aspect ratio
- Enhanced mobile spinner size to 28×28px (was 32×32px)

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