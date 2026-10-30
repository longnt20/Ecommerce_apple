# Apple Commerce — Backend portfolio

Website bán điện thoại và quản lý đa kho, phát triển từ Laravel truyền thống sang Modular Monolith. Trọng tâm portfolio là bảo toàn tiền/tồn kho, transaction, idempotency, concurrency và khả năng kiểm chứng khi nâng cấp hệ thống có dữ liệu lịch sử.

Stack hiện tại: Laravel 11, PHP 8.3, MySQL 8/InnoDB, Sanctum, Blade/session cho admin; Vue 3, Pinia, Axios và Vite cho storefront. Giữ dependencies hiện có trong lockfiles.

## Xem nhanh

- [Case study kiến trúc và số liệu](docs/portfolio/CASE_STUDY.md).
- [Kịch bản demo 8–10 phút](docs/portfolio/DEMO.md).
- [Review P0–P8 và các mục còn mở](docs/review/P0_P8_REVIEW.md).
- [Kiến trúc hiện tại](docs/architecture/current-architecture.md), [module boundaries](docs/architecture/p5-module-boundaries.md), [ADRs](docs/architecture/architecture-decisions.md).
- [Roadmap và bằng chứng từng giai đoạn](docs/roadmap/UPGRADE_PLAN.md).
- [Cách frontend/backend giao tiếp](SEPARATION_GUIDE.md).

## Nghiệp vụ đã triển khai

SKU có giá bán riêng; giá vốn không dùng làm giá giảm. Checkout chốt snapshot giá phía server và giữ tồn tại **một kho active đủ toàn đơn**. Đơn online có reservation 15 phút; COD/confirmed không tự hết hạn. Trừ tồn vật lý khi chuyển sang shipping. Hủy/retry/expiry đi qua transaction và khóa hàng; idempotency key ngăn ghi hiệu ứng hai lần.

Các module Identity, Catalog, Pricing, Inventory, Payments, Ordering giao tiếp qua Contracts. Module tests kiểm tra dependency DAG, ownership và đường ghi tồn. COD thu tiền bằng đối soát thủ công; refund ghi yêu cầu/đối soát, không gọi provider. Hàng trả chỉ thành available khi được phân loại sellable. Coupon có hold/consume/release và snapshot. Chuyển kho, kiểm kê và archive giữ chứng từ/lịch sử.

P7 bổ sung query budgets, streaming CSV, cache invalidation sau commit và recovery rehearsal. P8 là thử nghiệm Reporting outbox/inbox/projection trên process và logical database riêng; **mặc định tắt**, chưa phải microservice production.

## Chạy demo cách ly

Yêu cầu PHP 8.3 với PDO MySQL, MySQL 8.0.30 local, Node/npm; đã có `vendor/` và `frontend/node_modules/`. Trên bản clone mới, cài từ lockfiles bằng `composer install --no-scripts` và `npm ci --prefix frontend`. Tạo `.env` riêng từ `.env.example` nếu chưa tồn tại, cấu hình kết nối MySQL local tới một database rỗng dành riêng cho demo; tài khoản cần quyền tạo/xóa sandbox. Không lấy `.env` hoặc SQL thật đưa vào portfolio.

```powershell
php scripts/portfolio-demo.php verify --frontend
php scripts/portfolio-demo.php serve --frontend --seconds=900
```

Script đọc thông số kết nối local trong `.env`, tạo schema **mới có tên ngẫu nhiên**, seed dữ liệu tổng hợp và mở hai cổng loopback được in ra. Không migrate hoặc seed database nguồn. Mail dùng array; payment/OAuth bị chặn trong router demo. Hết thời gian script dừng process và xóa runtime/schema của nó. Đọc [DEMO.md](docs/portfolio/DEMO.md) trước khi chạy, đặc biệt cách recovery khi terminal bị đóng cưỡng bức.

## Kiểm chứng

```powershell
$env:P1_SCHEMA_TESTS='1'; $env:P2_SCHEMA_TESTS='1'; $env:P3_SCHEMA_TESTS='1'
$env:P4_SCHEMA_TESTS='1'; $env:P6_SCHEMA_TESTS='1'; $env:P7_SCHEMA_TESTS='1'; $env:P8_SCHEMA_TESTS='1'
php vendor/bin/phpunit
php tests/Support/p7-lint.php
php tests/Support/p8-lint.php
node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs
npm run build --prefix frontend
```

Full integration suite cần `.env.testing`/user DML cách ly được provision theo [P0](docs/roadmap/P0_STATUS.md), [P6 runbook](docs/runbooks/P6_OPERATIONS.md). Không đổi DB test sang database ứng dụng. CI tự provision dữ liệu tổng hợp; xem [.github/workflows/quality.yml](.github/workflows/quality.yml). Bằng chứng local và CI đã chạy nằm trong [docs/review](docs/review/P0_P8_REVIEW.md); số liệu benchmark không phải SLO production.

## Giới hạn công khai

Chưa nghiệm thu VNPay merchant/IPN public, Google OAuth thật, SMTP thật, production scheduler hoặc tải HTTP đồng thời. Scheduler Windows đang Disabled theo quyết định chủ dự án. Một phần dữ liệu legacy vẫn cách ly vì thiếu bằng chứng; không đoán để sửa tiền/tồn. Demo đã kiểm tra HTTP và Vite proxy, chưa xác minh trực quan bằng browser automation. Đây là portfolio có kiểm chứng kỹ thuật, chưa tuyên bố sẵn sàng vận hành production.
