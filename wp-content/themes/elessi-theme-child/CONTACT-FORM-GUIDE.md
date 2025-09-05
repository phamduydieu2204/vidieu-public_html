# Hướng dẫn tạo Contact Form tiếng Việt

## 1. Tạo form mới trong Contact Form 7

1. Vào **WordPress Admin → Contact → Add New**
2. Đặt tên form: "Form Liên Hệ Tiếng Việt"
3. Copy nội dung từ file `contact-form-vietnamese-template.txt`
4. Paste vào các tab tương ứng (Form, Mail, Mail 2, Messages)
5. Click **Save**

## 2. Lấy Shortcode

Sau khi save, bạn sẽ thấy shortcode dạng:
```
[contact-form-7 id="123" title="Form Liên Hệ Tiếng Việt"]
```

## 3. Thêm Form vào trang Contact

### Cách 1: Sử dụng Block Editor (Gutenberg)
1. Edit trang Contact
2. Thêm block "Shortcode"
3. Paste shortcode vào

### Cách 2: Sử dụng Elementor
1. Edit trang với Elementor
2. Kéo widget "Shortcode" hoặc "Contact Form 7"
3. Paste shortcode vào

### Cách 3: Sử dụng Classic Editor
1. Edit trang Contact
2. Paste shortcode trực tiếp vào nội dung

## 4. Tùy chỉnh Email

### Email gửi cho Admin:
- Vào tab **Mail** trong Contact Form 7
- Sửa email trong field **To**: admin@vidieu.vn
- Có thể thêm nhiều email cách nhau bằng dấu phẩy

### Email tự động reply:
- Tick vào **Mail (2)**
- Đã có template sẵn cho email tự động reply

## 5. Style Form

CSS đã được tự động load từ file `contact-form-styles.css` với:
- Responsive design
- Màu chủ đạo #F76B6A (đồng bộ với theme)
- Form 2 cột trên desktop, 1 cột trên mobile

## 6. Thông tin liên hệ bổ sung

Bạn có thể thêm thông tin liên hệ trước/sau form:

```html
<div class="contact-info">
    <h3>Thông tin liên hệ</h3>
    <p><strong>Hotline:</strong> 0988 691 196</p>
    <p><strong>Email:</strong> support@vidieu.vn</p>
    <p><strong>Địa chỉ:</strong> [Địa chỉ của bạn]</p>
    
    <div class="social-links">
        <a href="https://zalo.me/g/hwcfvo585">Zalo</a>
        <a href="https://m.me/vidieuvn.muatoolAmazon">Facebook</a>
        <a href="https://t.me/+ZanU07t-Vgc3OWJl">Telegram</a>
    </div>
</div>

[Shortcode Contact Form 7 ở đây]
```

## 7. Test Form

1. Gửi test từ frontend
2. Kiểm tra email nhận được
3. Kiểm tra email auto-reply
4. Test trên mobile

## 8. Troubleshooting

### Form không gửi được:
- Kiểm tra SMTP settings
- Cài plugin WP Mail SMTP nếu cần

### CSS không load:
- Clear cache
- Kiểm tra Console có lỗi JS không

### Email không nhận được:
- Kiểm tra Spam folder
- Cấu hình SMTP đúng cách