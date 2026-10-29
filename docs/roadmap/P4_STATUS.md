# P4 — Inventory protocol, reservation và cutover

Ngày: 2026-10-29. **Đã kiểm chứng acceptance kỹ thuật P4 và cutover local; dừng để chủ dự án review, không triển khai P5.** P0/P1 đã regression; schema và gate P2/P3 đã rollout. Phần nghiệm thu merchant VNPay/Google thật của P2 vẫn **chưa xác minh**, không coi toàn bộ dự án production-ready. Không commit/push.

## 1. Phạm vi và quyết định đã duyệt

Yêu cầu hiện tại cho phép review P0–P3, hoàn thiện phần thiếu khả thi, chạy migrations sau backup/rehearsal và triển khai P4. Đã đọc roadmap, các status/ADR/current/target, schema/migrations, checkout/frontend, InventoryService/model/Admin InventoryController/Blade, OrderService, payment gateway/callback, P0–P3 tests. Không tìm thấy AGENTS.md trong repository/phạm vi tổ tiên đã khảo sát. Không audit lại mọi module ngoài các dependency trên.

Chủ dự án xác nhận:

- cost_price là giá vốn; chỉ giảm tồn vật lý khi shipping/bàn giao vận chuyển.
- Một kho active đủ toàn đơn; nếu không đủ thì từ chối, không chia shipment.
- Online mới giữ hàng 15 phút; COD không tự hết hạn. Confirmed không tự hết hạn.
- Legacy mơ hồ được cách ly, giữ nguyên dữ liệu/số dư; chỉ backfill phần chứng minh được.

Kế hoạch thực hiện đã áp dụng: xác minh baseline → backup/restore/rehearsal P2/P3 → additive schema P4 → thống nhất writers → checkout/reservation/expiry → tests nhiều process/fault injection → rehearsal P4 → migration/maintenance/backfill/reconcile → mở gate và scheduler. Không thêm dependency, không sửa schema dump/migration lịch sử, không xóa dữ liệu thật.

## 2. Review P0–P3 và kết quả trên ứng dụng

| Phần | Kết quả |
| --- | --- |
| P0 | Guard testing/DML account riêng còn hiệu lực; C3 đổi từ incomplete sang assertion kho B; các câu hỏi allocation/TTL/quarantine đã chốt |
| P1 | Fresh/upgrade/restore xanh với 55 migrations; so fingerprint mọi cột gốc, không nhầm nullable metadata mới là đổi dữ liệu |
| P2 | Payment tables đã migrate; bỏ enabled/frontend_return config trùng và enabled guard trùng; regression giá, quyền, signed callback/OAuth fixtures xanh |
| P3 | History schema đã migrate; gate bật; service chuyển trạng thái dùng reservation identity và writer P4; graph/replay/actor/atomicity còn được kiểm thử |
| Ngoài khả năng xác minh local | Merchant-specific public HTTPS IPN, giao dịch sandbox merchant thật và Google browser flow thật chưa được nghiệm thu; VNPay mới vẫn tắt |

Trước lượt này DB có 51 migrations. Rollout P2/P3 lên 53 rồi P4 lên 55; hiện có 48 bảng. Mỗi đợt có full backup mới, restore vào clone disposable và migrate clone trước khi apply đúng manifest. Backup P2/P3: 758239246 bytes; P4: 758243517 bytes. Hash/file metadata được lưu trong [migration verification](../architecture/p4-migration-verification.json); backup đầy đủ và receipt chi tiết nằm private/ignored, không đưa lên Git.

Cutover P4 đã bật maintenance ngắn, chỉ backfill metadata, kiểm tra fingerprint trước/sau rồi khôi phục trạng thái phục vụ ban đầu. Kết quả:

- 52 orders, 56 order items, 184 inventory rows, 259 movements lịch sử được giữ nguyên.
- 18 đơn có allocation chứng minh được; 27 đơn quarantined; 7 đơn terminal không áp lifecycle mới hồi tố.
- 40 đơn vị reserved chưa biết thuộc đơn nào được giữ trong legacy hold; không trả về available.
- Không có discrepancy balance legacy tại thời điểm cutover; reconciliation không có unexplained drift.
- Không tạo đơn thật hoặc gọi thanh toán để thử; protocol movements trên DB ứng dụng tại checkpoint bằng 0. Chuỗi movement mới được kiểm chứng trên dữ liệu synthetic.
- .env local: INVENTORY_PROTOCOL_ENABLED=true, ORDER_TRANSITIONS_ENABLED=true, VNPAY_ENABLED=false. Secrets được giữ nguyên, bản env trước cutover nằm private. Không có config cache tại cutover.

Bằng chứng: [cutover + business hashes](../architecture/p4-cutover-report.json), [read-only runtime](../architecture/p4-runtime-baseline.json), [scheduler](../architecture/p4-scheduler-verification.json). Các file baseline/status P0–P3 cũ giữ lịch sử và có ghi chú cập nhật đầu tài liệu.

## 3. Thiết kế và thay đổi chính

[Contract P4](../architecture/p4-inventory-contract.md) mô tả đầy đủ HTTP, locking, lifecycle và vận hành; [ADR-016](../architecture/architecture-decisions.md) ghi lý do lựa chọn.

- PlaceOrder là application service điều phối normalize/quote/allocate/order/items/reserve/payment attempt/cart cùng một transaction. Payload client price không có quyền quyết định tiền.
- ReservationManager giữ allocation identity theo order/item/warehouse/SKU. Confirm xóa deadline, shipping giảm quantity và reserved, cancel/expiry release đúng identity.
- StockStore là narrow persistence boundary; StockWriter là writer balance/movement duy nhất. Khóa warehouse theo ID tăng dần trước inventory để bảo vệ cả tạo row đích. Current reads trên child reservation rows xử lý REPEATABLE READ, không join khóa parent của đơn khác.
- IdempotentOperation dùng unique operation hash, payload hash và kết quả cùng transaction. Root key bao phủ batch admin, line key chống lặp từng effect; retry checkout trả order/snapshot gốc. Không gọi network khi đang giữ lock.
- InventoryService là compatibility adapter; raw model reserve/release/updateQuantity và public commit/release không có lifecycle context bị chặn. Không còn caller nghiệp vụ ghi balance qua implementation cũ trong phạm vi source đã rà soát.
- Transfer ghi hai movement đối dấu cùng operation. Stocktake thấp hơn reserved ghi discrepancy và giữ balance; stock đó bị chặn allocation/export/ship.
- LegacyReservationBackfill là công cụ metadata-only có maintenance guard. Aggregate overallocated cách ly tất cả candidate liên quan thay vì gán tùy tiện cho đơn đầu tiên.
- ReconcileInventory chỉ đọc snapshot: invariant, active allocation + legacy hold, movement before/after và cặp transfer. Không tự sửa chênh lệch.
- ExpireReservations phối hợp order/payment/reservation locks với callback và confirm. Scheduler mỗi phút; duplicate scheduler không thể double release nhờ DB.
- Frontend giữ digest/key trong sessionStorage của tab, chặn double submit và xử lý replay payment_url null. Các form admin có operation_key.
- Checkout yêu cầu UUID idempotency_key (body hoặc header), admin yêu cầu UUID operation_key. Đây là thay đổi contract có chủ đích theo P4, đã cập nhật caller/tests/tài liệu.
- Chặn action=draft/is_draft/save_progress với 422, không ghi stock. Các nút nháp cũ đang gọi endpoint thực thi nhưng chưa có draft lifecycle/storage; lưu nháp thật thuộc P6.

Patterns được dùng để giải quyết vấn đề cụ thể: Application Service, allocation/lifecycle policy, Unit of Work từ transaction Laravel, narrow StockStore, idempotent command, compatibility adapter. Không thêm generic repository, event bus, saga hoặc microservice. P5 mới tổ chức namespace/module contracts.

## 4. Acceptance đã kiểm chứng

| Tiêu chí P4 | Bằng chứng |
| --- | --- |
| on_hand/reserved/available đúng invariant | StockStore/StockWriter và các integration/concurrency assertions; source reconciliation 184 rows không drift |
| Một operation chỉ có một effect | Checkout/batch replay, same-key multi-process, payload conflict và unique operation/line |
| Order mới có allocation đầy đủ | Checkout atomic order/items/reservation; kho B, inactive, whole-warehouse rejection và multi-SKU rollback tests |
| Movement/balance đối chiếu được | ReconcileInventory; transfer conservation/pairing, movement chain/reserved before-after; không áp giả định ledger P4 lên lịch sử cũ |
| Không bán từ kho inactive | Allocation kiểm tra lại dưới khóa, test chọn kho B khi kho A inactive |
| Writers thống nhất protocol | Audit references tới Inventory/balance fields trong app; model writer cũ bị chặn; admin/checkout/transition/expiry đều tích hợp |
| Không network trong lock | Source PlaceOrder/StockWriter/ReservationManager/PaymentGateway: quote/SQL/ký local; test boot ngăn stray Laravel HTTP |
| Concurrency connection độc lập xanh | Worker process/barrier trên disposable MySQL, không SQLite, không outer rollback che tranh chấp |
| Legacy ambiguous đã cách ly | 27 parents quarantined + 40 held; fingerprint lịch sử giữ nguyên |
| Không chênh lệch mới | Source reconcile exit 0; synthetic chains/stocktake/transfer/expiry tests đối soát sau operation |

Không drop schema/column cũ ở P4; điều kiện phê duyệt đóng cửa sổ tương thích vẫn giữ cho đợt thay đổi phá hủy trong tương lai.

## 5. Kiểm thử và lệnh đã chạy

PHP thực tế: F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe. Các lệnh dưới dùng biến PowerShell:

    $p4Php = 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'
    $env:P1_SCHEMA_TESTS='1'; $env:P2_SCHEMA_TESTS='1'
    $env:P3_SCHEMA_TESTS='1'; $env:P4_SCHEMA_TESTS='1'
    & $p4Php vendor/bin/phpunit

Kết quả lượt cuối: **149 tests, 853 assertions, 1 skipped, không failure/incomplete** (02:51). Skip là IsolationTest chỉ áp dụng khi database disabled, không phải skip concurrency.

    $env:P0_DB_MODE='disabled'
    & $p4Php vendor/bin/phpunit --testsuite Unit,Feature

**47 tests /111 assertions xanh**, bao gồm nhánh isolation đã skip ở lượt MySQL. Khi chạy lại MySQL trong cùng shell phải Remove-Item Env:P0_DB_MODE trước.

Các lần kiểm tra khác:

| Lệnh/phạm vi | Kết quả |
| --- | --- |
| PHPUnit filter VnPay/PaymentSecurityTest sau bỏ guard trùng cuối lượt | 17 tests /125 assertions xanh |
| Pint --test trên 44 PHP files thay đổi/mới và VnPayGateway riêng | Pass toàn bộ 45 PHP files; không lint lại vendor/module không liên quan |
| node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs | 4 tests xanh |
| npm run build trong frontend | Vite 5.4.21 build thành công, 1990 modules |
| git diff --check | Exit 0 |
| rollout-schema.php p2-p3 --rehearse / --apply | Backup/restore/rehearsal/apply thành công, 53 migrations |
| rollout-schema.php p4 --rehearse / --apply | Backup/restore/rehearsal/apply thành công, 55 migrations |
| cutover-p4.php --dry-run / --apply | Phân loại, metadata-only backfill, hash preservation và gate switch thành công |
| artisan inventory:reconcile | Exit 0; unexplained=[], 40 legacy hold, 0 discrepancy |
| artisan inventory:expire-reservations --limit=100 | Expired 0, blocked 0; không áp TTL hồi tố lên legacy |
| artisan schedule:list + ScheduledTaskInfo | Every-minute expiry; Windows task last result 0, missed runs 0 |

Các trường hợp đáng chú ý: hai buyer tranh SKU cuối; cùng checkout key đồng thời; đảo SKU order; import vs checkout; export/transfer vs reserve; hai transfer vào row đích chưa có; tổng chuyển kho không đổi; stocktake dưới reserved; rollback item/movement đích; process bị kill giữa transaction; expiry hai lần; expiry vs callback/confirm cả trước và sau deadline; paid không release; callback muộn chỉ reconciliation; replay sau đổi giá; legacy overallocated không chọn tùy tiện; model writer cũ bị chặn và phát hiện drift ngoài protocol.

Lượt full đầu có hai failure P1 vì so cả metadata columns mới; đã sửa test giữ projection cột gốc và chạy lại toàn bộ xanh. Không nới kiểm tra số dư/tiền hoặc bỏ test để pass.

## 6. Scheduler và giới hạn còn lại

Đã đăng ký Windows Task Scheduler **AppleProject-LaravelScheduler**, gọi scripts/run-scheduler.ps1 mỗi phút với PHP hiện có, cửa sổ hidden và chống chạy chồng. Script đặt TELESCOPE_ENABLED=false chỉ cho process scheduler. Task đã được start và xác minh LastTaskResult=0; [receipt](../architecture/p4-scheduler-verification.json).

Task dùng Interactive logon: chạy khi user Windows đã cấu hình còn đăng nhập và MySQL đang hoạt động. Khi triển khai máy chủ khác cần scheduler/service tương ứng và kiểm tra lại; không suy ra đã có worker Linux hoặc service chạy khi logout.

Còn lại ngoài phạm vi/ngoài khả năng xác minh:

1. Public HTTPS merchant IPN/merchant transaction và Google flow thật. Không tự bật online từ mock tests. Không thay provider credentials.
2. Giải quyết nghiệp vụ từng đơn quarantine/40 legacy holds cần chứng từ và người duyệt; không có auto-unquarantine/auto-release hoặc auto repair balances.
3. Refund/return/COD collection, draft/transfer header/in-transit, lưu settings và catalog/history hard-delete thuộc P6; không tự mở graph.
4. Module namespace/ports P5, performance benchmark/SLO và operational hardening P7 chưa triển khai. Warehouse lock thô có thể contention; reconciliation scan chưa benchmark quy mô lớn.
5. Không thao tác browser end-to-end thực tế. Frontend đã build và kiểm thử helper; backend HTTP/integration chạy trên sandbox. Chưa publish frontend lên host ngoài repository.
6. Artifact frontend/dist được tạo từ build vẫn nằm untracked. Cơ chế thực thi tự động từ chối cleanup Remove-Item với lý do “blocked by policy”; giữ nguyên artifact, không tìm cách vượt cơ chế này.

## 7. Kiểm tra thủ công và rollback

Kiểm tra không sửa dữ liệu thật:

    $env:TELESCOPE_ENABLED='false'
    & $p4Php tests/Support/inspect-application-database.php --application-read-only
    & $p4Php artisan inventory:reconcile
    & $p4Php artisan schedule:list
    Get-ScheduledTaskInfo -TaskName 'AppleProject-LaravelScheduler'

Kỳ vọng ở checkpoint: 55 migrations/48 bảng, 52 orders, 184 inventory, 259 movements; reconcile không unexplained; task last result 0. Sau giao dịch hợp lệ mới, counts sẽ thay đổi; dùng movement reconciliation thay so tuyệt đối checkpoint.

Trên instance thử nghiệm/clone: đặt COD từ kho B; gửi lại cùng key và xác nhận chỉ một order; confirm không giảm quantity; shipping giảm quantity/reserved cùng lúc; cancel pending release đúng kho; kiểm kê dưới reserved chỉ tạo discrepancy; kho inactive không phân bổ. Online dùng fixtures/signature synthetic; không tạo giao dịch tiền thật để xác minh kỹ thuật.

Rollback:

1. Đặt INVENTORY_PROTOCOL_ENABLED=false và ORDER_TRANSITIONS_ENABLED=false; nếu có config cache ở deployment khác phải clear/rebuild cache phù hợp. Giữ VNPAY_ENABLED=false. Dừng writers, không chuyển về code default kho 1.
2. Disable-ScheduledTask -TaskName 'AppleProject-LaravelScheduler' nếu cần dừng expiry; disable giữ task để có thể bật lại. Những reservation đang active vẫn phải được bảo toàn.
3. Không migrate down, không xóa operations/reservations/history/legacy holds, không restore backup đè lên DB nhận giao dịch mới. Các migration mới cố ý forward-only.
4. Giữ frontend operation keys/caller contracts hoặc deploy compatibility adapter an toàn đọc schema mới. Lưu patch hiện tại để review; chỉ revert từng phần sau khi đánh giá tương thích, không git reset/clean cả repo.
5. Dùng full backup private để restore một database cách ly nếu cần điều tra; mọi sửa số dư phải có bằng chứng và movement bù được duyệt. Không tự sửa 27 đơn/40 holds.
6. Sau roll forward, chạy reconciliation/regression trước khi mở gates và scheduler lại.

## 8. Danh sách file thay đổi

Danh sách dưới gồm application, frontend, schema mới, tests, deployment helpers và docs của lượt này; .env/private backup/receipts ignored được mô tả riêng ở trên. Không có dependency lockfile thay đổi.

    app/Exceptions/InventoryConflict.php
    app/Http/Controllers/Admin/InventoryController.php
    app/Http/Controllers/Api/CheckoutController.php
    app/Http/Requests/CheckoutRequest.php
    app/Models/Inventory.php
    app/Models/InventoryDiscrepancy.php
    app/Models/InventoryLegacyHold.php
    app/Models/InventoryOperation.php
    app/Models/InventoryReservation.php
    app/Models/InventoryReservationItem.php
    app/Models/InventoryTransaction.php
    app/Models/Order.php
    app/Services/Inventory/ExpireReservations.php
    app/Services/Inventory/IdempotentOperation.php
    app/Services/Inventory/LegacyReservationBackfill.php
    app/Services/Inventory/ReconcileInventory.php
    app/Services/Inventory/ReservationManager.php
    app/Services/Inventory/StockStore.php
    app/Services/Inventory/StockWriter.php
    app/Services/InventoryService.php
    app/Services/OrderService.php
    app/Services/Ordering/PlaceOrder.php
    app/Services/Payments/PaymentService.php
    app/Services/Payments/VnPayGateway.php
    bootstrap/app.php
    config/inventory.php
    config/services.php
    config/vnpay.php
    database/migrations/2026_10_29_000003_create_inventory_protocol.php
    database/migrations/2026_10_29_000004_create_inventory_legacy_holds.php
    docs/architecture/architecture-decisions.md
    docs/architecture/current-architecture.md
    docs/architecture/p4-cutover-report.json
    docs/architecture/p4-inventory-contract.md
    docs/architecture/p4-migration-verification.json
    docs/architecture/p4-runtime-baseline.json
    docs/architecture/p4-scheduler-verification.json
    docs/architecture/target-architecture.md
    docs/roadmap/P0_STATUS.md
    docs/roadmap/P1_STATUS.md
    docs/roadmap/P2_STATUS.md
    docs/roadmap/P3_STATUS.md
    docs/roadmap/P4_STATUS.md
    docs/roadmap/UPGRADE_PLAN.md
    frontend/src/components/Cart/CartPage.vue
    frontend/src/components/Cart/CheckoutPage.vue
    frontend/src/components/Product/BuyButton.vue
    frontend/src/services/checkoutCommand.js
    resources/views/admin/inventory/export.blade.php
    resources/views/admin/inventory/import.blade.php
    resources/views/admin/inventory/index.blade.php
    resources/views/admin/inventory/stocktake.blade.php
    resources/views/admin/inventory/transfer.blade.php
    resources/views/admin/orders/show.blade.php
    routes/console.php
    scripts/run-scheduler.ps1
    tests/Frontend/p4-checkout.test.mjs
    tests/Integration/P0/KnownRisksTest.php
    tests/Integration/P1/SchemaUpgradeTest.php
    tests/Integration/P2/CheckoutSecurityTest.php
    tests/Integration/P3/OrderAtomicityTest.php
    tests/Integration/P3/OrderTransitionTest.php
    tests/Integration/P4/InventoryConcurrencyTest.php
    tests/Integration/P4/InventoryProtocolTest.php
    tests/Support/MySqlTestCase.php
    tests/Support/P3Fixture.php
    tests/Support/TestApplication.php
    tests/Support/cutover-p4.php
    tests/Support/inventory-worker.php
    tests/Support/prepare-p4-sandbox.php
    tests/Support/rollout-schema.php

Ngoài Git: .env local đã đổi ba feature flags; .env.testing/test schema được nâng additive; private backups/receipts và Windows scheduled task được tạo. Không commit/push, không triển khai P5.
