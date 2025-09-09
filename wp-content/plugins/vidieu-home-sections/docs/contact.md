# Contact Page Documentation

## Overview
Đã khôi phục trang Liên hệ theo bản chốt trước với các tính năng đầy đủ.

## Implementation Details

### Shortcode
- **Shortcode**: `[vd_contact]`
- **Location**: Có thể sử dụng trên bất kỳ page nào
- **Attributes**:
  - `title`: Tiêu đề trang (mặc định: "Liên hệ với chúng tôi")
  - `show_map`: Hiển thị bản đồ (yes/no, mặc định: yes)
  - `map_height`: Chiều cao bản đồ (mặc định: 400px)
  - `company_name`: Tên công ty (mặc định: "Vidieu.vn")
  - `address`: Địa chỉ (mặc định: "Hà Nội, Việt Nam")

### Files Created
1. **PHP Class**: `/includes/class-vd-contact.php`
   - Xử lý shortcode rendering
   - AJAX form submission
   - Email notifications to admin@vidieu.vn
   - Auto-fill user data

2. **CSS**: `/assets/css/contact.css`
   - Fully scoped với `.vd-contact-page` prefix
   - Không ảnh hưởng các phần khác của website
   - Responsive design

3. **JavaScript**: `/assets/js/contact.js`
   - AJAX form submission
   - LocalStorage support
   - Form validation
   - Chỉ chạy trên pages có contact form

## Features
- ✅ Form liên hệ với các trường: Họ tên, Email, Số điện thoại, Nội dung
- ✅ Hiển thị thông tin liên hệ: Hotline (0988 691 196), Email, Zalo, Messenger
- ✅ Icons giống floating widget
- ✅ Layout 2 cột responsive
- ✅ Google Maps integration
- ✅ Auto-fill cho users đã đăng nhập
- ✅ LocalStorage lưu thông tin (7 ngày)
- ✅ Toàn bộ giao diện tiếng Việt

## Scope Limitation
### CSS Scoping
- Tất cả CSS rules đều có prefix `.vd-contact-page`
- Không override global styles
- Không ảnh hưởng header, footer, forms khác

### JavaScript Scoping
- Chỉ chạy khi có element `#vd-contact-form`
- Event handlers chỉ bind vào contact form
- Không can thiệp vào các forms khác

### Asset Loading
- CSS/JS chỉ load trên pages có shortcode `[vd_contact]`
- Conditional enqueue trong `class-vd-assets.php`

## Usage Example
```
[vd_contact title="Liên hệ với chúng tôi" show_map="yes" map_height="400"]
```

## Email Configuration
- Recipient: `admin@vidieu.vn` (hardcoded)
- From: User's name and email
- Subject: "Liên hệ mới từ [name]"

## Responsive Breakpoints
- Desktop: > 991px (2 columns)
- Tablet: 768px - 991px (1 column)
- Mobile: < 768px (1 column, adjusted spacing)

## Maintenance Notes
- Contact form ID: `vd-contact-form`
- Nonce field: `vd_contact_nonce`
- AJAX action: `vd_contact_submit`
- LocalStorage key: `vd_contact_info`

## Confirmation
✅ Đã khôi phục trang Liên hệ theo bản chốt trước
✅ Xác nhận không có ảnh hưởng tới các phần tử ngoài trang Liên hệ
✅ CSS đã được scoped hoàn toàn với `.vd-contact-page`
✅ JavaScript chỉ chạy trên pages có contact form