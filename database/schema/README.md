# MySQL schema baseline của P1

`mysql-schema.sql` là schema state theo cơ chế có sẵn của Laravel 11. File được dựng từ metadata đã xác minh ngày 2026-10-28: 40 bảng, 50 dòng migration history; không chứa business rows, credentials, `USE` hoặc `CREATE DATABASE`. AUTO_INCREMENT counters đã bỏ. Đây là schema trước repair P1, không phải dump dữ liệu để restore.

## Database mới

1. Chọn database rỗng và credentials đúng trong môi trường đích. Không dùng database đang có dữ liệu cho quy trình dựng mới.
2. Bảo đảm PHP phù hợp composer.lock và `mysql` client có trong PATH (Laragon hiện có MySQL 8.0.30).
3. Chạy `php artisan migrate`. Laravel nạp baseline khi migration repository chưa có migration đã chạy, sau đó chạy repair P1 và các migration mới hơn.
4. Kiểm tra user.code unique, thumbnail nullable, movement enum có commit; `migrate` lần hai không có migration pending.

Không cần replay hai bộ migration kho cũ. Không dùng `--prune`, không xóa các file migration lịch sử. Không dùng `--pretend` thay cho rehearsal với migration phụ thuộc metadata/data: Laravel có thể không thực thi các SELECT cần cho preflight trong pretend mode.

## Database hiện có

Baseline không được nạp vào DB đã có migration history. Trước upgrade phải backup, kiểm tra collision codes/status, orphan/duplicate warehouse-SKU và metadata. Sau đó chỉ chạy repair mới trong maintenance/release window phù hợp. Dùng `--path=database/migrations/2026_10_28_000001_reconcile_application_schema.php` nếu muốn giới hạn đúng P1.

Repair hỗ trợ schema kho phong phú như snapshot hiện tại, với các drift users.role/code/status, thumbnail/thumnail, movement commit và chiều rộng text đã xác minh. Nếu chỉ còn schema kho v1 (`reserved`, `change`) hoặc có hai cột ảnh mâu thuẫn, duplicate code không rỗng, duplicate balance, repair dừng trước DDL; cần mapping được review riêng.

Các bảng/cột không liên quan không bị xóa. Cột typo cũ chỉ rename khi chưa có thumbnail; nếu cả hai tồn tại và không mâu thuẫn thì merge giá trị thiếu, giữ cột cũ chờ contract migration riêng.

## Vận hành và rollback

- Backup đầy đủ ở storage private, không commit. Tài liệu bàn giao P1 ghi SHA-256 và kết quả restore.
- MySQL DDL implicit commit. Chạy trong cửa sổ kiểm soát writers, không giả định transaction bọc được toàn bộ upgrade.
- Backfill code rỗng là `legacy-user-{id}`; map `block` → `blocked`. IDs, code không rỗng, giá tiền và ledger không thay đổi.
- `down()` cố ý từ chối: không xóa mã đã cấp, không làm thumbnail bắt buộc trở lại, không thu hẹp enum có thể đã được sử dụng. Dùng forward repair được duyệt hoặc restore backup sang DB cách ly để đối soát; không ghi đè backup cũ lên DB đang có giao dịch mới.
- Không tự regenerate baseline mỗi lần setup; `prepare-database-sandbox.php` dùng bản đã review nếu có sẵn. Thay baseline cần PR/review riêng và chạy lại fresh/upgrade tests.

Xem [P1_STATUS](../../docs/roadmap/P1_STATUS.md) cho lệnh và phạm vi đã triển khai.
