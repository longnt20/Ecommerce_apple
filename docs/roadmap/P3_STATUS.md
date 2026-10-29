# P3 — Transition đơn atomic và có thể retry

**Review/rollout mới nhất 2026-10-29:** order_transitions đã migrate DB ứng dụng sau rehearsal. Chủ dự án duyệt cách ly legacy; P4 đã backfill allocation identity cho 18 đơn và cách ly 27 đơn, giữ toàn bộ balances/lịch sử. ORDER_TRANSITIONS_ENABLED=true, chuyển trạng thái dùng ReservationManager và StockWriter thay guard suy luận mỗi lần. Các graph/operation replay P3 vẫn được regression/concurrency test; xem [P4_STATUS](P4_STATUS.md), [cutover](../architecture/p4-cutover-report.json). Những câu chưa rollout/chưa triển khai P4 bên dưới ghi lại mốc P3 trước yêu cầu mới.

Ngày: 2026-10-29. **Đã triển khai code và tests; rollout ứng dụng/duyệt legacy còn chờ review. Chưa đóng toàn bộ P3 trên môi trường thật. Không triển khai P4.**

## Phân tích và kế hoạch thực hiện

Đã đọc roadmap, current/target architecture, ADR-005/006/007, P0/P2 status, source OrderService, InventoryService, Order/Inventory/InventoryTransaction, Admin OrderController/Blade, Payments P2, migrations/schema và test isolation. Không tìm thấy AGENTS.md trong repository hoặc các thư mục tổ tiên đã kiểm tra.

OrderService cũ dùng trạng thái model load trước transaction, không có policy tại service, confirm trừ tồn và các stock methods có thể âm reserved/chạy lặp. Controller tự có bảng transition riêng, không có operation/history. Chỉ tìm thấy một caller nội bộ changeStatus và hai stock methods: Admin OrderController → OrderService → InventoryService. Không tìm thấy notification mail riêng cho order để chuyển sang afterCommit; không sửa mail coupon/user.

Đã thực hiện từng phần: policy + conservative evidence guard → history/operation migration → lock/re-read service → stock context/invariants → controller/UI adapter → MySQL integration/concurrency/fault injection → dry-run read-only và tài liệu.

## Thay đổi và patterns

- Giữ graph pending→confirmed/cancelled, confirmed→shipping, shipping→completed; policy duy nhất cho admin và service.
- Confirm giữ reserved; shipping là admin xác nhận bàn giao và trừ quantity/reserved; cancel pending release một lần; timestamp/history ghi cùng transaction.
- Order lock trước mọi kiểm tra trạng thái; inventory khóa theo variant tăng dần, phối hợp thứ tự order trước payment attempt của P2.
- History là operation identity bền vững: same key/payload replay không lặp effect, payload khác conflict. Actor active admin tường minh, reason/request ID/timestamp được lưu.
- Thiếu proof kho/SKU/reservation hoặc stock invariant sai thì chặn và đối soát; không chọn default kho, không sửa allocation P4.
- Stock context chống gọi lại trực tiếp; P3 movement dùng ULID/actor tường minh, giữ tương thích generator cũ.
- Application Service, Transition Policy, idempotent command và transaction/row locks. Không state class cho mỗi status, generic repository, dependency hoặc module rewrite.

Contract, lý do thiết kế, rollout và rollback đầy đủ: [P3 order transitions](../architecture/p3-order-transitions.md).

## Danh sách file

Application/UI/config:

- `app/Exceptions/OrderTransitionConflict.php`
- `app/Services/Ordering/{OrderTransitionPolicy,OrderReservationEvidence,OrderAllocationGuard}.php`
- `app/Services/{OrderService,InventoryService}.php`
- `app/Models/{Order,OrderTransition,InventoryTransaction}.php`
- `app/Http/Controllers/Admin/OrderController.php`
- `resources/views/admin/orders/show.blade.php`
- `config/ordering.php`, `.env.example`
- `database/migrations/2026_10_29_000002_create_order_transitions.php`

Tests/support:

- `tests/Unit/OrderTransitionPolicyTest.php`
- `tests/Integration/P3/{OrderTransitionTest,OrderAtomicityTest}.php`
- `tests/Support/{P3Fixture,prepare-p3-sandbox,order-transition-worker,audit-p3-legacy-orders}.php`
- `tests/Integration/P1/SchemaUpgradeTest.php`: native fresh/upgrade/restore hiện có 53 migrations; baseline/migration cũ không sửa.

Docs: file này, contract P3, JSON audit legacy, status link roadmap và append tiến độ/ADR hiện tại/mục tiêu. Không đổi `.env`, dependencies, API customer; không commit/push trong lượt triển khai này.

## Database và legacy

Migration mới chỉ tạo order_transitions và FKs/indexes; không thay cột orders/inventory, không backfill và không áp dụng DB ứng dụng. Sandbox synthetic có 53 migrations. Các concurrency/fault triggers chỉ tạo trong schema disposable allowlist, drop cùng schema ở cuối test; không tạo trigger trên dữ liệu thật.

`audit-p3-legacy-orders.php --write-report` chạy trong transaction READ ONLY trên DB ứng dụng, dùng cùng pure evidence evaluator của runtime. [JSON audit](../architecture/p3-legacy-order-audit.json) ghi nhận 52 đơn: 18 phù hợp tại thời điểm đọc, 27 cần đối soát, 7 terminal. Các nguyên nhân: 22 invalid_items, 1 missing_allocation, 4 legacy_stock_state. Không xuất tên/email/address/amount; không tự sửa SKU, trạng thái, kho hoặc movement.

Đường đối soát đề xuất nằm trong contract. **Chưa được chủ dự án duyệt**, nên feature flag mặc định tắt; không coi snapshot 18 đơn là phê duyệt chạy trên DB thật.

## Acceptance

- [x] State/history/stock atomic; lỗi ghi movement hoặc status rollback cả ba, kể cả lỗi SKU thứ hai.
- [x] Confirm/cancel/shipping replay một operation không lặp effect; reserved không âm do invocation lặp trong luồng P3.
- [x] Hai process dùng stale snapshot vẫn chỉ một transition hợp lệ; confirm/cancel race và payment/cancel race có kết quả nhất quán.
- [x] Service trực tiếp kiểm tra transition/actor; history/context đã dùng không thể commit tồn lần nữa.
- [x] Admin và service dùng cùng policy; thiếu proof/default kho không được bypass; legacy đã có danh sách dry-run.
- [x] Không dd, không thêm I/O/mail trong lock, không trừ quantity tại confirm.
- [ ] Chủ dự án review graph/handover, duyệt cách đối soát legacy và rollout migration/feature flag. Không đánh dấu hoàn thành vận hành khi điều kiện này chưa có.

## Lệnh và kết quả kiểm chứng

PHP dùng `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe`; dưới đây rút gọn thành php:

```powershell
php tests/Support/prepare-p3-sandbox.php --sandbox-only
$env:P1_SCHEMA_TESTS='1'; $env:P2_SCHEMA_TESTS='1'; $env:P3_SCHEMA_TESTS='1'
php vendor/bin/phpunit
php tests/Support/audit-p3-legacy-orders.php --write-report
php tests/Support/inspect-p2-databases.php
# Không DB, lượt riêng:
$env:P0_DB_MODE='disabled'
php vendor/bin/phpunit --testsuite Unit,Feature
$changedPhp = @(git diff --name-only -- '*.php')
$newPhp = @(git ls-files --others --exclude-standard -- '*.php')
php vendor/bin/pint --test @changedPhp @newPhp
git diff --check
```

Lượt tổng bật P1/P2/P3 schema flags: **122 tests, 601 assertions, không failure/error; 1 skipped, 1 incomplete**. Skip là test nhánh DB-disabled khi đang bật MySQL; incomplete C3 allocation khác warehouse còn thuộc P4, không được đổi thành pass trong P3. Fresh/upgrade/restore native migrations P1 và payment regression/concurrency P2 vẫn chạy cùng lượt này.

Unit/Feature không DB: **47 tests/111 assertions pass**. P3 integration/concurrency sau bổ sung multi-SKU: **30 tests/163 assertions pass**. Pint `--test`: **21 file PHP pass**; `git diff --check` pass. Blade admin detail được compile/render qua HTTP integration test, có history và operation_key; không thay bundle JS/CSS nên không chạy frontend build hay cài dependencies.

Probe read-only sau suite: DB ứng dụng vẫn **51 migrations**, users 8, orders 52, items 56, inventory 184, movements 259; audit xác minh **0 bảng order_transitions** ở ứng dụng. Counts khớp P1/P2 đã ghi, không phải fingerprint toàn DB. Sandbox **53 migrations**, các bảng users/orders/items/inventory/movements/payment fixtures đều 0 dòng sau rollback. Không chạy DML/DDL trên DB ứng dụng.

## Kiểm tra thủ công và rollback

Trên staging/sandbox đã được duyệt và bật flag:

1. Đơn có reserve proof đúng: confirm giữ quantity/reserved/available; shipping sau bàn giao giảm quantity/reserved, giữ available; completed chỉ đổi status/timestamp.
2. Pending cancel trả reserved sang available một lần, quantity không đổi; retry giữ nguyên history/movement. Cùng operation key nhưng reason/target khác bị conflict.
3. Thử stale confirm/cancel; terminal không mở lại; user thường/blocked admin không được chuyển trạng thái. JSON conflict 409 có code; browser nhận flash error.
4. Đơn lệch kho/thiếu SKU/đã commit tại confirm cũ phải bị chặn; không sửa quantity hoặc warehouse để làm test qua.
5. Tắt ORDER_TRANSITIONS_ENABLED: ẩn nút, transition bị dừng nhưng history vẫn đọc được.

Rollback tắt flag, giữ history/stock/paid records; không migrate down, restore đè dữ liệu thật hoặc phục hồi service cũ trừ kho tại confirm. Chưa commit nên muốn bỏ code cần lưu/review patch rồi hoàn nguyên đúng các file P3, tránh reset/clean cả workspace. P4 allocation/writer protocol và P6 refund/carrier integration vẫn còn ngoài phạm vi. Dừng để review.
