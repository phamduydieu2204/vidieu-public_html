# Buy Now No-Scroll Enhancement

## Overview
Giải quyết vấn đề trang tự động scroll lên top khi người dùng click nút "Mua ngay" trong thẻ sản phẩm.

## Vấn đề gốc
- Khi người dùng cuộn xuống và click "Mua ngay", trang tự động scroll lên top
- Gây trải nghiệm người dùng không tốt, đặc biệt trên trang có nhiều sản phẩm
- Nguyên nhân có thể từ:
  - Theme/WooCommerce scroll đến notices sau khi add-to-cart
  - Nút có href="#" gây navigation
  - JavaScript handlers của theme gọi scrollTo(0,0)

## Giải pháp

### 1. Scripts được thêm
- `buy-now-no-scroll.js`: Script chính xử lý prevent scroll và enhance UI
- `buy-now-scroll-debug.js`: Script debug để trace scroll events (chỉ dùng khi cần)

### 2. Cách hoạt động
- Intercept các scroll methods: `window.scrollTo`, `Element.scrollIntoView`
- Chỉ block scroll khi action là "buy-now"
- Lưu và restore vị trí scroll nếu bị thay đổi
- Thêm visual feedback cho nút (loading, success states)

### 3. Feature flags
```php
// Enable/disable no-scroll fix (default: true)
define('VD_FIX_BUY_NOW_NOSCROLL', true);

// Enable/disable debug mode (default: false)
define('VD_DEBUG_SCROLL', false);
```

### 4. Visual feedback
- **Loading state**: Spinner + "Đang xử lý..."
- **Success state**: Checkmark + "Đã thêm"
- **Error state**: Shake animation + tooltip cho biến thể chưa chọn

## Testing

### Debug mode
1. Enable debug trong wp-config.php:
```php
define('VD_DEBUG_SCROLL', true);
```

2. Mở browser console và click "Mua ngay"
3. Xem log events trong console
4. Click nút "Debug" góc phải màn hình để xem panel

### Test cases
1. **Simple products**: Click "Mua ngay" → Không scroll, hiện loading → success
2. **Variable products**: 
   - Chưa chọn thuộc tính → Shake + tooltip
   - Đã chọn thuộc tính → Xử lý như simple
3. **Scroll positions**: Test ở top, middle, bottom của trang
4. **Cart update**: Kiểm tra mini-cart vẫn update đúng

## Troubleshooting

### Vẫn bị scroll
1. Check console có error không
2. Enable debug mode xem nguồn scroll từ đâu
3. Kiểm tra có plugin khác conflict không

### Button không có feedback
1. Check CSS có load không
2. Xem có JavaScript error không
3. Kiểm tra class names có đúng không

### Tắt tính năng
Thêm vào wp-config.php:
```php
define('VD_FIX_BUY_NOW_NOSCROLL', false);
```

## Code Structure

### JavaScript
- Modular pattern với namespace VDBuyNow
- Event delegation cho performance
- Debounce/throttle cho scroll monitoring
- Clean up handlers sau 3 giây

### CSS
- Scoped với prefix .vd- để tránh conflict
- Animations dùng CSS3
- Responsive và accessible

## Browser Support
- Chrome/Edge: Full support
- Firefox: Full support
- Safari: Full support
- IE11: Basic support (no animations)

## Performance
- Minimal overhead: < 5ms per click
- No impact khi không click Buy Now
- Auto cleanup sau mỗi action

## Future Improvements
- [ ] Có thể config scroll behavior (smooth/instant)
- [ ] Custom animation durations
- [ ] Hook points cho developers
- [ ] Integration với các page builders khác