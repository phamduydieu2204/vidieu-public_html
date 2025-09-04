# Select Options Opens NASA Quickview Sidebar

## Overview
This feature allows the "Tùy chọn" (Select Options) button in plugin product sections to open the NASA theme's quickview sidebar instead of the custom in-card attributes panel.

## Implementation Details

### File Location
- JavaScript: `assets/js/vd-select-options-open-qv.js`
- Enqueued in: `includes/class-vd-assets.php`

### Selectors Used

#### Section Container
- Primary: `div[id^="vd-products-"]` (dynamically generated IDs like `#vd-products-68aa89bd4097e`)

#### Button Selector
- Primary: `.vd-buy-now-button.vd-buy-now-variable`
- Secondary: `.vd-buy-now-button[data-action="select-options"]`

#### Product ID Sources (in priority order)
1. `.nasa-product-content-variable-warp[data-product_id]` within `li.product`
2. Button's `data-product_id` attribute
3. Product container's `data-product_id` or `data-product-id`
4. `.quick-view[data-prod]` within the same product

### NASA Theme Integration

#### Method 1: Trigger Existing Quickview
- Finds theme's quickview button outside plugin sections: `.quick-view[data-prod="X"]`
- Triggers click event on found button

#### Method 2: Create Temporary Trigger
- Creates temporary quickview element with theme's expected attributes:
  - Classes: `quick-view btn-link quick-view-icon`
  - Attributes: `data-prod` (product ID), `data-href` (product URL)
- Triggers click and removes element after execution

### Event Delegation & Re-initialization

#### Initial Binding
- Uses namespaced event delegation: `click.vdhs`
- Binds to stable container: `div[id^="vd-products-"]`

#### Re-initialization Triggers
1. Plugin events:
   - `vidieu_products_filtered`
   - `vidieu_products_page_loaded`
   - `vidieu_items_loaded`
   - Legacy: `vd_products_filtered`, etc.

2. NASA theme events:
   - `nasa_after_load`
   - `nasa_refresh_shop`

3. MutationObserver:
   - Monitors DOM changes within product containers
   - Debounced re-init after 150ms

### Anti-Double-Binding
- Removes previous handlers before binding: `$containers.off('click.vdhs')`
- Uses event namespace `.vdhs` for clean removal

## Scope & Limitations

### Applied To
- Only within plugin-rendered sections: `.vd-home-section.vd-home-products`
- Only for variable products with "Tùy chọn" button

### Not Affected
- Theme's original quickview buttons (outside plugin sections)
- Admin area functionality
- Other frontend features (add-to-cart, filters, pagination)
- Simple products with direct "Mua ngay" button

## Debug Mode
Enable debug mode to see console logs:
```javascript
window.vd_debug_mode = true;
```

## Compatibility Notes
- Works with NASA/Elessi theme's quickview sidebar mechanism
- Maintains theme's AJAX loading and animation behaviors
- Does not interfere with custom quickview logic from `vidieu-custom-quickview.js`