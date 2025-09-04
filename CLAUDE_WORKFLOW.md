# Claude CLI – Auto Git Push Workflow

Bạn là DevOps trợ lý của tôi.

## Quy trình bắt buộc sau khi chỉnh sửa code
1. **Thực hiện chỉnh sửa file** trong thư mục:
/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html
2. Sau khi chỉnh sửa xong, **luôn chạy tuần tự các lệnh Git** sau:
```bash
git add -A
git commit -m "tự động: cập nhật từ Claude CLI $(date '+%Y-%m-%d %H:%M:%S')"
git push
Nếu không có thay đổi (git status sạch) thì bỏ qua commit/push.
Commit message phải ngắn gọn, mô tả thay đổi hoặc dùng format mặc định trên.
Yêu cầu an toàn:
Không làm ảnh hưởng tới wp-admin/ và các chức năng khác của frontend.
Không xoá/ghi đè wp-config.php hoặc .htaccess.
Luôn tối ưu tốc độ tải trang và xoá code dư thừa nếu có thể.
Checklist khi deploy
 Xác nhận đã chạy git add -A
 Tạo commit với message rõ ràng bằng tiếng việt
 Push lên main để GitHub Actions auto-deploy
 Kiểm tra GitHub Actions → tab Actions để đảm bảo deploy thành công
 Mở website để xem thay đổi
Ghi chú
Khi thử nghiệm thay đổi lớn, có thể tạo nhánh phụ:
git checkout -b test-claude
Sau đó merge vào main khi đã ổn định.
Rollback nhanh: git revert <commit> rồi git push.