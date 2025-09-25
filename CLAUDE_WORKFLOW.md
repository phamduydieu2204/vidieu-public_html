Claude CLI – Auto Git Push Workflow (Chỉ dùng main)
Bạn là DevOps trợ lý của tôi.
Tuyệt đối không tạo nhánh mới (feat/*, fix/*, test/*, master…), mọi thay đổi chỉ commit/push vào main.
0) Thiết lập một lần (nếu máy mới)
cd "/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html"
# Khởi tạo và trỏ tới repo từ xa
git init
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/<USER>/<REPO>.git
# Luôn làm việc trên nhánh main
git fetch origin
git checkout -B main origin/main
# Thiết lập upstream
git branch -u origin/main
# Tắt fast-fail do line endings (tuỳ chọn)
git config core.autocrlf false
Nếu thư mục đã là repo: chỉ cần git fetch origin && git checkout -B main origin/main.
1) Thư mục làm việc
/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html
2) Quy trình chuẩn (mỗi lần chỉnh sửa)
Kéo mới nhất về main (tránh lệch code):
git fetch origin
git checkout -B main origin/main
git reset --hard origin/main
git clean -fd        # xoá file thừa không có trong repo (cẩn trọng)
Chỉnh sửa code theo yêu cầu.
Commit & Push lên main:
git add -A
git status           # kiểm tra file sẽ commit
# Nếu có thay đổi:
git commit -m "tự động: cập nhật từ Claude CLI $(date '+%Y-%m-%d %H:%M:%S')"
# Đảm bảo push đúng nhánh main
git push origin main
Không có thay đổi (working tree sạch) → bỏ qua commit/push.
3) Yêu cầu an toàn
Không được tạo branch mới hay PR.
Không đụng vào wp-config.php, .htaccess, và các file cấu hình server (trừ khi được yêu cầu rõ).
Không xoá/thay đổi thư mục hệ thống của WordPress nếu không liên quan.
Không làm ảnh hưởng wp-admin/ và các chức năng frontend hiện hành.
Ưu tiên performance: xoá code dư, nén/tối ưu nếu hợp lý.
4) Checklist trước khi đẩy code (deploy)
 Đã git fetch và đồng bộ main (mục 2.1).
 git add -A và git status sạch các file rác.
 Commit message tiếng Việt, rõ ràng (được phép dùng mẫu auto).
 git push origin main thành công.
 Kiểm tra GitHub Actions → Actions tab: workflow build/deploy success.
 Mở website kiểm tra tính năng liên quan.
5) Quy ước commit (tiếng Việt)
Cú pháp: Tên nhiệm vụ – hành động
Ví dụ: Buy Now Simple – Tạo script kiểm tra handler registration
Cho phép dùng mẫu mặc định:
tự động: cập nhật từ Claude CLI YYYY-MM-DD HH:MM:SS
6) Không tạo branch – chính sách bắt buộc
Tuyệt đối không chạy các lệnh:
git checkout -b <tên-branch>
git push --set-upstream origin <tên-branch>
Nếu lỡ tạo nhánh, quay về main:
git checkout main
git branch -D <tên-branch-lỡ-tạo> 2>/dev/null || true
7) Đồng bộ sạch public_html với repo (khi đang lẫn file cũ)
Dùng khi local có nhiều file cũ/không theo repo, cần kéo đúng y repo:
cd "/mnt/f/Obsidian/Dữ Liệu Của Tôi/Quản Lý Tài Chính/Quản Lý Kinh Doanh bán Tool/Vidieu.vn/public_html"
git init
git remote remove origin 2>/dev/null || true
git remote add origin https://github.com/<USER>/<REPO>.git
git fetch origin
git checkout -B main origin/main
git reset --hard origin/main
git clean -fd
8) Rollback nhanh (khi deploy lỗi)
# Xem lịch sử để lấy <commit_id> ổn định
git log --oneline -n 10
# Quay về commit ổn định và đẩy lên main
git reset --hard <commit_id>
git push -f origin main   # chỉ dùng nếu thật cần thiết và đã được phép
Nếu repo đã bật Branch protection không cho force-push, hãy revert thay vì reset:
git revert <commit_id>
git push origin main
9) Tư duy xử lý vấn đề
Luôn tìm gốc rễ (root cause) → sửa tại nguồn thay vì “vá” tạm.
Viết log/debug đủ để lần sau xác định nhanh điểm hỏng.
Bất kỳ thay đổi DB (dbDelta) → thêm log vào debug.log khi cài/upgrade.
10) Ghi chú CI/CD
Workflow GitHub Actions chỉ deploy khi push vào main:
on:
  push:
    branches: [ "main" ]
Nếu dùng FTP Deploy, để an toàn:
dangerous-clean-slate: false
Secrets (server, user, pass, path) phải được cấu hình trong Settings → Secrets and variables.
