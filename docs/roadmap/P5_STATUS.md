# P5 — Module boundaries và giảm coupling

Ngày 2026-10-29. **Đã triển khai và kiểm chứng acceptance P5 cho các use case P2–P4; dừng để chủ dự án review. Không triển khai P6, không commit/push.**

## 1. Phạm vi và phân tích trước thay đổi

Đã đọc roadmap, current/target architecture, ADRs, P4 contract/status, provider/autoload/test harness; source checkout/cart/admin status, InventoryService/StockStore/StockWriter/ReservationManager/backfill/reconcile/expiry, Pricing, PaymentService/VnPayGateway, models liên quan và các test P0–P4. Không tìm thấy AGENTS.md tại repo/phạm vi tổ tiên đã kiểm tra. Không audit lại mọi CRUD ngoài dependencies của những use case này.

Coupling được xác minh trước P5:

- PlaceOrder nhận model từ Pricing và dùng trực tiếp User/Cart/Order cùng concrete Inventory/Payments services.
- PaymentService đọc/ghi Order và InventoryReservation, ngoài việc sở hữu payment attempts/events.
- Inventory ReservationManager nhận Eloquent Order/OrderTransition; ExpireReservations nằm trong Inventory nhưng ghi Order/History.
- StockWriter đọc User trực tiếp; nhiều thời điểm nghiệp vụ dùng helper now/date, khó thay clock độc lập.
- Compatibility/model event có implicit auth actor; chưa có architecture guards cho module dependency/ownership.

Kế hoạch ngắn đã thực hiện: ghi baseline query trước refactor → public contracts/DTO và ownership → chuyển từng slice vào modules → bind provider, cập nhật HTTP/CLI adapters → giữ và kiểm thử bridges cũ → architecture/concurrency/regression/query budgets → read-only application verification và tài liệu rollback.

Không đổi schema/migration, dependencies, HTTP routes/payload/redirect; không đổi graph/TTL/idempotency hash format của P4. Không sửa BaseCrud/Category hoặc viết lại mọi CRUD chỉ để đổi thư mục.

## 2. Những thay đổi đã thực hiện

Chi tiết class, ownership, dependency rules và adapter registry tại [p5-module-boundaries](../architecture/p5-module-boundaries.md); quyết định tại [ADR-017](../architecture/architecture-decisions.md).

1. Tạo sáu namespace modules Identity/Catalog/Pricing/Inventory/Payments/Ordering, public Contracts và readonly DTO không chứa Eloquent/query builder.
2. Catalog trả SellableSku snapshot; Pricing áp dụng quy tắc VND hiện có qua contract, không import Product/ProductVariant. QuoteService/StockBalance/OrderTransitionPolicy/PaymentDecision kiểm thử được không boot Laravel.
3. Ordering Application điều phối PlaceOrder, ChangeOrderStatus, PaymentWorkflow, ExpireReservations qua OrderStore/Transactions và các module Contracts.
4. Inventory Infrastructure giữ một StockWriter/StockStore protocol duy nhất; nhận ReservationOrder/Line/StockRequirement DTO thay Eloquent Order. Admin actor kiểm tra qua Identity AccountAccess.
5. Payments chỉ import PaymentAttempt và sở hữu payment_events; tự đọc/khóa attempt, xác thực signature/reference/merchant/amount/state ở write boundary. Không coi PaymentCandidate/Attempt DTO do caller tự tạo là chứng cứ tiền.
6. Ordering xử lý PaymentResult để cập nhật Order và yêu cầu Inventory retain hold cùng transaction. Snapshot supplier giữ order → attempt → reservation lock order mà không tạo Payments → Ordering import.
7. Chuyển expiry orchestration sang Ordering. Maintenance bridge đọc Order/Item qua LegacyOrders, Inventory chỉ nhận DTO và ghi metadata của mình.
8. Thêm ActorContext, Clock, Transactions, Vnd trong SharedKernel; bind các port bằng ModuleServiceProvider. InventoryTransaction không tự lấy auth()->id(); actor/null cho system được writer truyền rõ.
9. HTTP checkout/cart/order-status và console expiry/reconcile dùng public Contracts. InventoryService giữ mapping verb/batch admin cũ và gọi contract; các services cũ chỉ là compatibility adapters/inheritance aliases, không có writer/quy tắc song song.
10. Thêm architecture suite vào phpunit.xml, pure domain tests, CLI/adapter equivalence, forged payment candidate và query budgets cụ thể.

Patterns có lý do: Ports and Adapters để tách dependency I/O và module; Application Services để điều phối transaction; immutable DTO để không lộ models; Unit of Work trên cùng MySQL connection; compatibility adapter để chuyển từng use case. Không thêm generic repository, event bus, saga, queue hoặc microservice. Không tạo aggregate Domain song song cho mọi CRUD.

Models vật lý vẫn app/Models; ownership nằm ở module Infrastructure và được kiểm tra bằng tests. Legacy CRUD/Reporting read-only giữ cấu trúc cũ với owner/lộ trình ghi rõ, không được dùng để bypass stock/payment writer. Phạm vi này tuân theo P5 “không rewrite toàn bộ CRUD”; không tuyên bố toàn Backend đã chuyển namespace.

## 3. Acceptance đã kiểm chứng

| Acceptance P5 | Bằng chứng |
| --- | --- |
| Các module có public Contracts | Sáu module, provider bindings, HTTP/CLI và integration thực sự gọi contracts |
| Không vòng dependency source | Architecture tests quét PHP tokens, whitelist module directions; module mới không gọi Services compatibility hoặc Infrastructure của module khác |
| Domain/Application không phụ thuộc Laravel/HTTP/model | Architecture guards + unit tests thuần; runtime I/O nằm trong Infrastructure |
| Không model xuyên module | Infrastructure chỉ import model owner; public DTO readonly/type signatures không trả Eloquent; payment/stock nhận snapshot qua boundary |
| Chỉ owner ghi, một implementation mỗi use case | Stock writer assignment guard và source audit; Payments chỉ ghi attempts/events, Ordering ghi orders/history/cart; bridges delegate |
| CLI/job không cần auth global | Integration gọi checkout/transition khi auth()->guest(), actor tường minh; Clock injection và system expiry tests |
| Adapter cũ/mới cùng effect | Legacy checkout rồi contract replay cùng order/reservation; transition replay cùng history; P0–P4 vẫn gọi bridges trong nhiều tests |
| HTTP và concurrency giữ nguyên | Full MySQL HTTP/storage-failure/multi-process suite xanh; không sửa route/request contract P4 |
| Query/latency không tăng bất hợp lý trong fixture | Enforced budgets và before/after diagnostic bên dưới; tối đa một compatibility read, import/IPN giữ nguyên; không tuyên bố load/SLO chưa đo |
| Không adapter tạm thiếu owner/lộ trình | Registry cụ thể trong boundaries: owner, caller/lý do còn giữ, điều kiện gỡ tại cổng review P6 hoặc disposition legacy; không tự phê duyệt P6 |

Guard không phải full static analyzer cho PHP động. Dynamic SQL/reflection/relations và legacy CRUD ngoài Modules vẫn cần review; Reporting read adapters có ngoại lệ read-only. Các test này không thay thế integration/concurrency.

## 4. Kết quả tests, lint, build

PHP thực tế: F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe.

    $p5Php = 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'
    $env:P1_SCHEMA_TESTS='1'; $env:P2_SCHEMA_TESTS='1'
    $env:P3_SCHEMA_TESTS='1'; $env:P4_SCHEMA_TESTS='1'
    & $p5Php vendor/bin/phpunit

Lượt full cuối: **167 tests /1435 assertions, 1 skipped, không failure/incomplete**, 01:57. Skip là IsolationTest chỉ áp dụng trong database-disabled mode. Concurrency suite không bị skip.

    $env:P0_DB_MODE='disabled'
    & $p5Php vendor/bin/phpunit --testsuite Unit,Feature,Architecture

**61 tests /665 assertions xanh**, kiểm chứng cả nhánh isolation bị skip ở lượt MySQL. Khi muốn chạy MySQL lại trong cùng shell cần Remove-Item Env:P0_DB_MODE.

Các kiểm tra khác:

| Lệnh/phạm vi | Kết quả |
| --- | --- |
| PHPUnit Architecture | 6 tests; kiểm module DAG, models, DTO, framework, SharedKernel, writer; đã có trong full suite |
| PHPUnit P5 ModuleCompatibilityTest + ModulePerformanceTest | 4 cases: CLI/adapter replay, Clock, manufactured/mismatched payment DTO, query budgets; đã có trong full |
| PHPUnit P2/P3/P4 | Success/ownership/validation, verified/late/replayed payments, state graph, fault injection và independent-process concurrency xanh |
| Pint --test trên toàn PHP thay đổi/mới | 85 files pass |
| node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs | 4 tests pass |
| npm run build trong frontend | Vite build thành công, 1990 modules; không đổi frontend source |
| git diff --check | Pass |
| git diff --quiet -- database composer.json composer.lock frontend/package.json frontend/package-lock.json | Không thay schema/migration hoặc dependencies |
| artisan inventory:reconcile với TELESCOPE_ENABLED=false | 184 stock rows, unexplained=[], 40 legacy holds, 0 open discrepancies |
| artisan schedule:list / Windows ScheduledTaskInfo | Expiry mỗi phút, task P4 LastTaskResult=0, missed runs=0 |

Trong quá trình refactor đã bắt và sửa hai lỗi mapping DTO: status mặc định của attempt chưa có trên model vừa create và tên orderId trong URL gateway. Không sửa expectation của security tests để che lỗi. Guard tokenizer cũng được sửa để phân biệt helper now() với Clock->now(), không nới rule framework independence.

Tất cả test có ghi dùng synthetic/disposable MySQL dưới guard P0; không chạy HTTP write tests trên DB ứng dụng, không gọi provider thật. Các opt-in schema tests chỉ migrate sandbox, không phải rollout application migration.

## 5. Baseline hiệu năng

[Performance evidence](../architecture/p5-performance-baseline.json) lưu baseline trước sửa tại commit P4 73d5729 và measurement sau trên cùng fixture/use-case driver.

| Use case (qua entry tương thích) | Queries P4 | Queries P5 | Enforced budget P5 |
| --- | ---: | ---: | ---: |
| COD checkout | 34 | 35 | 35 |
| Replay checkout | 4 | 5 | 5 |
| Confirm | 18 | 19 | 19 |
| Shipping | 34 | 35 | 35 |
| Import | 15 | 15 | 15 |
| Verified IPN | 10 | 10 | 10 |

Read tăng là chuyển DTO về Eloquent cho legacy callers. HTTP callers mới dùng contract/DTO trực tiếp; không đẩy việc hydrate model cũ vào core. Không có N+1 mới theo số module hoặc network trong lock. Query budgets là assertions bắt buộc, không chỉ ghi log.

elapsed_ms là quan sát synthetic local một process/lần, không gồm boot app nhưng có class autoload/warm-up trong use case. Measurement có thể bị ảnh hưởng cache/OS/MySQL load, không đủ suy ra p95/SLO production. Các con số chi tiết trong JSON được giữ minh bạch; benchmark tải/lock contention thuộc P7.

## 6. Database và trạng thái ứng dụng

P5 không có migration, backfill, thao tác sửa số dư, thay .env, cài dependencies, đăng ký lại scheduler hoặc đổi payment flags. Source read-only verification được thực hiện bằng native PDO READ ONLY; [runtime evidence](../architecture/p5-runtime-verification.json):

- Cùng endpoint identity với P4; tất cả fingerprint user/order/items/cart/SKU/stock/movements/payment/history khớp checkpoint P4.
- Vẫn 55 migrations; 52 orders, 56 order items, 184 stock rows, 259 movements lịch sử giữ nguyên.
- 18 reservation legacy active, 27 quarantined; 40 unknown reserved tiếp tục giữ nguyên.
- INVENTORY_PROTOCOL_ENABLED=true, ORDER_TRANSITIONS_ENABLED=true, VNPAY_ENABLED=false.
- Scheduler Windows P4 tiếp tục chạy; chỉ hoạt động khi user đã cấu hình còn đăng nhập và MySQL chạy. Không tuyên bố đã có service độc lập login.

Không chạy lại cutover-p4.php --apply; backfill guard còn giữ và CLI dry-run vẫn sẵn có nhưng không cần ghi metadata thêm.

## 7. Những việc chưa giải quyết

- Merchant-specific public IPN/transaction và Google browser flow thật vẫn chưa xác minh; giữ VNPay mới tắt. P5 không tự gọi thanh toán/OAuth thật.
- Disposition 27 đơn quarantine/40 holds cần chứng từ/người duyệt; không tự sửa lịch sử hay auto-unquarantine.
- Refund/return/COD collection/coupon/draft/stocktake workflows/catalog archive/settings thuộc P6. Không mở graph mới.
- Legacy CRUD chưa di chuyển hết, read-only joins Reporting chưa thành projection. Registry nêu owner/điều kiện gỡ; P6/P7/P8 cần phê duyệt riêng.
- Chưa có load benchmark hoặc browser E2E trên môi trường thật. Module boundary hiện vẫn dùng local transaction; không thể thay ledger/stock adapter bằng HTTP remote mà không thiết kế lại consistency.
- frontend/dist vốn untracked từ P4 được build lại để kiểm tra; giữ artifact tại local, không đưa secrets/backups vào Git.

## 8. Kiểm tra thủ công

Kiểm tra không ghi dữ liệu thật:

    $env:TELESCOPE_ENABLED='false'
    & $p5Php artisan inventory:reconcile
    & $p5Php artisan schedule:list
    & $p5Php tests/Support/inspect-application-database.php --application-read-only
    Get-ScheduledTaskInfo -TaskName 'AppleProject-LaravelScheduler'

Kỳ vọng checkpoint: 55 migrations, reconciliation không unexplained, task last result 0. Sau giao dịch mới hợp lệ, dùng reconciliation thay so count tuyệt đối.

Trên instance thử nghiệm/clone: checkout cùng key hai lần chỉ có một order; thử kho A inactive để đặt từ B; confirm không giảm quantity, shipping giảm đúng warehouse; pending cancel/online expiry chỉ release một lần; callback muộn không giữ lại stock. JSON/redirect và lỗi idempotency P4 phải giữ nguyên. Domain tests chạy không boot Laravel; integration CLI dùng ActorContext để xác minh không cần session.

## 9. Rollback

P4 commit an toàn trước P5: **73d57293969160fc01668a48d91704c639f259b7**. Không tự rollback dữ liệu hoặc chạy migration down.

1. Nếu có incident, dừng writers bằng hai gate P4 và dừng scheduler khi cần; giữ nguyên reservations/history/holds/paid records. Không triển khai lại code trước P4.
2. Lưu toàn bộ patch tracked và các file mới trước thao tác khôi phục. Deploy đồng bộ source P4 cùng bootstrap/providers, HTTP/CLI adapters và services; không chỉ xóa ModuleServiceProvider vì bridges mới cần các bindings đó.
3. Không restore backup đè DB đang nhận giao dịch mới, không git reset/clean toàn repository. Schema P4 không đổi nên không cần down migration hoặc xóa module-owned data.
4. Với deployment có cache/opcache/worker dài hạn, clear/rebuild cache và restart worker theo môi trường sau khi đổi source; không giả định cache đã được kiểm chứng ở host khác.
5. Re-run regression/reconciliation, xác minh provider bindings/routes rồi mới mở lại gate/scheduler. Nếu chỉ port binding có lỗi và đã xác định adapter tương thích P4 an toàn thì revert binding/source có kiểm soát, không dual-write.

## 10. File thay đổi

Danh sách source/tests/docs của P5, không bao gồm private runtime artifacts hoặc frontend/dist:

    app/Http/Controllers/Admin/OrderController.php
    app/Http/Controllers/Api/CartController.php
    app/Http/Controllers/Api/CheckoutController.php
    app/Models/InventoryTransaction.php
    app/Modules/Catalog/Contracts/SellableSku.php
    app/Modules/Catalog/Contracts/SellableSkuReader.php
    app/Modules/Catalog/Contracts/SkuUnavailable.php
    app/Modules/Catalog/Infrastructure/EloquentSellableSkuReader.php
    app/Modules/Identity/Contracts/AccountAccess.php
    app/Modules/Identity/Infrastructure/EloquentAccountAccess.php
    app/Modules/Inventory/Contracts/Hold.php
    app/Modules/Inventory/Contracts/InventoryMaintenance.php
    app/Modules/Inventory/Contracts/InventoryOperations.php
    app/Modules/Inventory/Contracts/InventoryReservations.php
    app/Modules/Inventory/Contracts/ReservationLine.php
    app/Modules/Inventory/Contracts/ReservationOrder.php
    app/Modules/Inventory/Contracts/StockRequirement.php
    app/Modules/Inventory/Domain/LegacyAllocationEvidence.php
    app/Modules/Inventory/Domain/StockBalance.php
    app/Modules/Inventory/Infrastructure/IdempotentOperation.php
    app/Modules/Inventory/Infrastructure/InventoryMaintenance.php
    app/Modules/Inventory/Infrastructure/InventoryOperations.php
    app/Modules/Inventory/Infrastructure/LegacyReservationBackfill.php
    app/Modules/Inventory/Infrastructure/ReconcileInventory.php
    app/Modules/Inventory/Infrastructure/ReservationManager.php
    app/Modules/Inventory/Infrastructure/StockStore.php
    app/Modules/Inventory/Infrastructure/StockWriter.php
    app/Modules/ModuleServiceProvider.php
    app/Modules/Ordering/Application/ChangeOrderStatus.php
    app/Modules/Ordering/Application/ExpireReservations.php
    app/Modules/Ordering/Application/PaymentWorkflow.php
    app/Modules/Ordering/Application/PlaceOrder.php
    app/Modules/Ordering/Application/Ports/OrderStore.php
    app/Modules/Ordering/Contracts/Checkout.php
    app/Modules/Ordering/Contracts/LegacyOrders.php
    app/Modules/Ordering/Contracts/OrderSnapshot.php
    app/Modules/Ordering/Contracts/OrderTransitions.php
    app/Modules/Ordering/Contracts/PaymentWorkflow.php
    app/Modules/Ordering/Contracts/ReservationExpiry.php
    app/Modules/Ordering/Contracts/TransitionReceipt.php
    app/Modules/Ordering/Domain/OrderTransitionPolicy.php
    app/Modules/Ordering/Infrastructure/EloquentOrderStore.php
    app/Modules/Payments/Contracts/Attempt.php
    app/Modules/Payments/Contracts/PaymentCandidate.php
    app/Modules/Payments/Contracts/PaymentGateway.php
    app/Modules/Payments/Contracts/PaymentLedger.php
    app/Modules/Payments/Contracts/PaymentOrder.php
    app/Modules/Payments/Contracts/PaymentResult.php
    app/Modules/Payments/Domain/PaymentDecision.php
    app/Modules/Payments/Infrastructure/EloquentPaymentLedger.php
    app/Modules/Payments/Infrastructure/VnPayGateway.php
    app/Modules/Pricing/Application/QuoteService.php
    app/Modules/Pricing/Contracts/Pricing.php
    app/Modules/Pricing/Contracts/Quote.php
    app/Modules/Pricing/Contracts/QuoteLine.php
    app/Modules/Pricing/Domain/InvalidQuote.php
    app/Services/Inventory/ExpireReservations.php
    app/Services/Inventory/IdempotentOperation.php
    app/Services/Inventory/LegacyReservationBackfill.php
    app/Services/Inventory/ReconcileInventory.php
    app/Services/Inventory/ReservationManager.php
    app/Services/Inventory/StockStore.php
    app/Services/Inventory/StockWriter.php
    app/Services/InventoryService.php
    app/Services/OrderService.php
    app/Services/Ordering/OrderReservationEvidence.php
    app/Services/Ordering/OrderTransitionPolicy.php
    app/Services/Ordering/PlaceOrder.php
    app/Services/Payments/PaymentService.php
    app/Services/Payments/VnPayGateway.php
    app/Services/Pricing/QuoteService.php
    app/Services/Pricing/Vnd.php
    app/SharedKernel/ActorContext.php
    app/SharedKernel/Clock.php
    app/SharedKernel/Infrastructure/LaravelClock.php
    app/SharedKernel/Infrastructure/LaravelTransactions.php
    app/SharedKernel/Transactions.php
    app/SharedKernel/Vnd.php
    bootstrap/app.php
    bootstrap/providers.php
    docs/architecture/architecture-decisions.md
    docs/architecture/current-architecture.md
    docs/architecture/p5-module-boundaries.md
    docs/architecture/p5-performance-baseline.json
    docs/architecture/p5-runtime-verification.json
    docs/architecture/target-architecture.md
    docs/roadmap/P4_STATUS.md
    docs/roadmap/P5_STATUS.md
    docs/roadmap/UPGRADE_PLAN.md
    phpunit.xml
    routes/console.php
    tests/Architecture/ModuleBoundaryTest.php
    tests/Integration/P5/ModuleCompatibilityTest.php
    tests/Integration/P5/ModulePerformanceTest.php
    tests/Unit/Modules/DomainRulesTest.php

Không commit/push và không chuyển sang P6 trong lượt này.
