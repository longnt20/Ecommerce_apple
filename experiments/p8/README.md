# P8 — Catalog Reporting projection

Thử nghiệm trên nhánh `experiment/p8-catalog-reporting`. Giữ Modular Monolith là mặc định; đây là prototype tách process/database, chưa là service production triển khai độc lập.

Chạy từ repository root, dùng PHP/MySQL hiện có:

```powershell
$env:P8_SCHEMA_TESTS='1'
php vendor/bin/phpunit tests/Integration/P8 tests/Feature/P8 tests/Unit/P8
php tests/Support/benchmark-p8.php
php tests/Support/p8-lint.php
```

Các helper tạo hai schema MySQL ngẫu nhiên allowlisted, role relay/consumer giới hạn quyền và spool riêng dưới ignored `tests/.runtime/`. Dữ liệu hoàn toàn tổng hợp. Kết thúc sẽ đóng/drop đúng tài nguyên helper đã tạo; giữ lại [performance](../../docs/architecture/p8-performance.json) và [verification](../../docs/architecture/p8-verification.json). Cần cấu hình admin MySQL local trong `.env` và test isolation P0–P7 đã có; không in credentials hoặc nhập dữ liệu thật.

`worker.php` chạy không boot Laravel, không đọc `.env`, không gọi HTTP/mail/payment. Input JSON trên stdin gồm `action=relay|consume`, `connection` tới đúng schema sandbox, `spool`, `limit` tối đa 1.000; consumer hỗ trợ `offset`, relay hỗ trợ `replay_id`. Helper là caller mẫu cung cấp credentials qua stdin, không qua argv/log. Consumer không được cấp quyền đọc bảng sản phẩm của producer.

Schema versioned/checksummed nằm ở `database/001_producer.sql` và `001_consumer.sql`, cố ý ngoài thư mục migration mặc định Laravel. `Schema::apply` chỉ chấp nhận DB fresh ngẫu nhiên của sandbox; không chạy các file này vào database ứng dụng. MySQL DDL không atomic: nếu khởi tạo bị ngắt giữa DDL, hủy đúng sandbox do helper tạo và chạy lại, không tự sửa database bất kỳ.

`CatalogProjectionEvents` là port trong Catalog. `SaveProduct::save/variant` gọi port sau khi lưu xong aggregate nhưng trước outer commit. Mặc định dùng `NoCatalogProjectionEvents`, không cần P8 tables hoặc worker. Implementation thử nghiệm chỉ hoạt động khi opt-in, APP_ENV testing và DB fresh allowlisted. Không đặt `P8_REPORTING_EXPERIMENT=true` trong `.env` đang dùng.

Phạm vi event hiện chỉ là SaveProduct/save SKU và bootstrap tổng hợp có khóa product. Archive/restore controller trực tiếp, raw SQL, category label và các đường ghi khác **chưa có feed đầy đủ**. Projection không phục vụ API/UI thật, không làm nguồn quyết định giá hoặc tồn checkout. Để dùng rộng hơn phải mở rộng/kiểm chứng coverage, topology, transport, auth và vận hành trong phạm vi được duyệt tiếp.

[Runbook](../../docs/runbooks/P8_EXPERIMENT.md) có protocol, failure/replay/rollback và các giới hạn. [P8_STATUS](../../docs/roadmap/P8_STATUS.md) ghi kết quả, file thay đổi và quyết định kiến trúc.
