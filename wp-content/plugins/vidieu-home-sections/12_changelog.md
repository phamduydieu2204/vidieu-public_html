# Changelog - Vidieu Home Sections

## [Unreleased]

### Added
- New feature: "Tùy chọn" button in product sections now opens NASA quickview sidebar
  - Created `assets/js/vd-select-options-open-qv.js` to handle quickview sidebar opening
  - Integrated with NASA/Elessi theme's quickview mechanism
  - Added event delegation for dynamic content support
  - Implemented re-initialization after AJAX updates
  - Added MutationObserver for automatic re-binding
  - Created documentation at `docs/qa/sections-interactions/select-options-opens-nasa-sidebar.md`

### Changed
- Updated `includes/class-vd-assets.php` to enqueue new JavaScript file
- Modified button behavior: "Tùy chọn" now opens theme's quickview sidebar instead of custom panel

### Technical Details
- Uses NASA theme's native quickview trigger mechanism
- Maintains compatibility with existing custom quickview logic
- No modifications to theme core files
- Scoped to plugin sections only