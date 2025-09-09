# Checkout Header/Footer Fix Documentation

## Tình trạng ban đầu
- **Vấn đề**: Khi kích hoạt child theme, trang checkout không hiển thị header/footer đầy đủ của theme Elessi
- **Nguyên nhân**: Child theme không xác định NASA_CHECKOUT_LAYOUT, khiến theme mặc định sử dụng layout 'modern' với header/footer tối giản

## Phân tích nguyên nhân

### 1. Cấu trúc checkout của Elessi theme
- File: `/wp-content/themes/elessi-theme/page-checkout.php`
- Theme có 2 layout checkout:
  - **'default'**: Sử dụng `get_header()` và `get_footer()` chuẩn
  - **'modern'**: Sử dụng `get_header('modern')` và `get_footer('modern')` tối giản

### 2. Logic xác định layout
```php
// Trong nasa-options.php
if (!defined('NASA_CHECKOUT_LAYOUT')) {
    $checkout_layout = isset($nasa_opt['checkout_layout']) && $nasa_opt['checkout_layout'] ?
        $nasa_opt['checkout_layout'] : 'default';
    define('NASA_CHECKOUT_LAYOUT', $checkout_layout);
}
```

### 3. Vấn đề với child theme
- Child theme không có page-checkout.php riêng
- NASA_CHECKOUT_LAYOUT không được define sớm trong child theme
- Theme options có thể bị thay đổi khi load child theme

## Giải pháp triển khai

### File đã sửa: `/wp-content/themes/elessi-theme-child/functions.php`

#### Thay đổi 1: Force checkout layout về 'default'
```php
/**
 * Force checkout layout to 'default' to ensure header/footer display
 * This prevents the theme from using 'modern' layout which has minimal header/footer
 */
add_action('init', 'elessi_child_force_default_checkout_layout', 1);
function elessi_child_force_default_checkout_layout() {
    if (!defined('NASA_CHECKOUT_LAYOUT')) {
        define('NASA_CHECKOUT_LAYOUT', 'default');
    }
}
```

#### Thay đổi 2: Filter theme options
```php
/**
 * Filter theme options to ensure checkout_layout is 'default'
 */
add_filter('nasa_theme_options', 'elessi_child_checkout_options', 999);
function elessi_child_checkout_options($options) {
    $options['checkout_layout'] = 'default';
    return $options;
}
```

#### Code giữ nguyên: CSS cho WooCommerce Checkout Block
```php
// Giữ nguyên các functions:
// - elessi_child_checkout_modifications()
// - elessi_child_checkout_block_styles()
```

## Kết quả đạt được

1. **Header/Footer hiển thị đúng**: Trang checkout sử dụng header/footer đầy đủ như theme gốc
2. **Không ảnh hưởng tính năng khác**: Các trang khác và admin vẫn hoạt động bình thường
3. **Tương thích WooCommerce Block**: Checkout block vẫn hiển thị và hoạt động tốt
4. **Hiệu suất**: Không thêm query database, chỉ sử dụng hooks PHP

## Kiểm thử

1. ✅ Kích hoạt child theme → Truy cập /checkout/ → Header/footer hiển thị đầy đủ
2. ✅ Checkout process hoạt động bình thường
3. ✅ Order received page hiển thị đúng
4. ✅ Các trang khác không bị ảnh hưởng
5. ✅ Admin area hoạt động bình thường

## Lưu ý bảo trì

- Nếu muốn sử dụng modern checkout layout, comment out 2 functions:
  - `elessi_child_force_default_checkout_layout`
  - `elessi_child_checkout_options`
- Có thể điều chỉnh layout qua filter `nasa_theme_options` nếu cần