# P6 — Lifecycle và bảo toàn lịch sử

Ngày 2026-10-30. **Hoàn thành triển khai và acceptance P6 theo MVP đã duyệt; migration đã áp dụng sau backup/restore/rehearsal.** Dừng để chủ dự án review. Không triển khai P7/P8, không commit/push. Không đồng nghĩa đã kích hoạt đầy đủ scheduler/worker/provider; các giới hạn vận hành được ghi riêng bên dưới.

## Phê duyệt và khảo sát

Đã đọc roadmap, current/target architecture, ADR, registry P5, code Ordering/Pricing/Payments/Inventory, catalog/account/coupon/warehouse controllers, Requests, Models, Blade/Vue callers, schema và test harness. Không tìm thấy AGENTS.md trong phạm vi repository/ancestor đã khảo sát. Không sửa `.env`, dependencies hoặc migration P0–P4.

Chủ dự án xác nhận trong phiên P6: thu đủ COD có mã đối soát; refund request/đối soát thủ công, không gọi provider; hàng trả kiểm tra đạt mới thành sellable. Coupon một mã/đơn, reserve checkout, redeem online paid/COD confirmed, release pending cancel/expiry. Cho phép additive migration sau sandbox + full backup/restore/rehearsal và giữ legacy mơ hồ.

Phát hiện có dẫn chứng trước sửa:

- ProductController::update dùng ProductVariant::find không scope; các lần save product/spec/variant không chung transaction; xóa gallery trước DB commit; store thumbnail biến thể hai lần.
- UserController::destroy dùng phép gán role; store/update dùng dữ liệu ngoài validated payload; avatar cũ bị xóa trước commit và sai disk; status event phát trước commit.
- Api/UserController::profile query coupon creator thay assignment; AssignCouponJob insert lặp khi retry, gửi trước commit; coupon update xóa cả history assignment; hạn assignment 7/30 ngày không thống nhất.
- InventoryController::updateSettings không persist, view settings thiếu; transfer/stocktake chưa có document header/lines hoặc workflow đóng discrepancy.
- Chưa có COD collection/refund/return ledger. Graph completed không thể chứng minh đã thu tiền. Force-delete có thể mất lịch sử; category cascade là đường xóa vòng qua product.
- P6 tests xác nhận MySQL enum trả `COD` viết hoa; normalize tại OrderStore để policy không so sai với `cod`.

## Thay đổi theo lát cắt

| Lát cắt | Implementation | Tác động/contract |
| --- | --- | --- |
| P6a | Ordering.AfterSales, Payments.ManualPayments, Inventory.ReturnedGoods + ledger/events/receipt | admin money/return endpoints và trang đối soát; tiền/hàng độc lập; replay và cap theo chứng cứ |
| P6b | Pricing.CouponRedemptions/CouponAssignments; lifecycle hooks trong Ordering; jobs/delivery ledger/mail template | optional coupon_code ở checkout, snapshot discount; cap atomic; assignment retry/revision/revoke; profile vouchers scoped |
| P6c | InventoryOperations document persistence; StockWriter.resolve; InventorySettings + view | paired movements giữ nguyên, phiếu gốc có dòng kết quả; fresh count giải discrepancy có audit; settings persist không phá invariant |
| P6d | Catalog.SaveProduct/CatalogMedia, Identity.SaveAccount; archive guards/relations | multi-entity tx, scoped child IDs, cleanup file theo commit; validated accounts; không hard-delete history/cascade category |

Không thêm design pattern theo hình thức: use-case coordinator + ports/adapters giữ dependency DAG; MySQL transaction làm Unit of Work; durable idempotency, immutable snapshots/events và compensating stock movements xử lý retry/recovery thực tế. Media compensation xử lý filesystem ngoài transaction; không gọi refund provider hoặc dùng distributed saga.

Legacy compatibility bridges P0–P5 giữ owner/caller audit trong registry, không tạo implementation song song. Không đổi base CRUD toàn dự án. Coupon import/export, product/SKU uploadTemp và user filter POST chưa có handler trước P6 được bỏ khỏi routes/UI; không xóa chức năng đang chạy. Warehouse show, inventory transaction detail, coupon archived list/restore có handler và contract test.

## Acceptance và bằng chứng

| Tiêu chí | Bằng chứng | Trạng thái |
| --- | --- | --- |
| Quy tắc tiền/hàng được chốt, matrix riêng | Phê duyệt phiên này; p6-lifecycle-contract.md | Đã kiểm chứng |
| Thu COD, partial/full refund, duplicate/replay, cap | LifecycleTest + LifecycleConcurrencyTest, hai tiến trình MySQL độc lập | Đã kiểm chứng trên dữ liệu tổng hợp |
| Refund không tăng kho, damaged không sellable, return không tự refund | LifecycleTest, receipt/movement rollback fault | Đã kiểm chứng trên dữ liệu tổng hợp |
| Coupon last-use concurrent, retry partial batches, snapshot, release/redeem | CouponTest + LifecycleConcurrencyTest; current locking reads, SQL trigger faults | Đã kiểm chứng trên dữ liệu tổng hợp |
| Transfer/stocktake history, discrepancy resolution, settings | InventoryDocumentsTest, giữ concurrency/atomicity P4 | Đã kiểm chứng trên dữ liệu tổng hợp |
| Archive history, foreign child/gallery rejection, DB/upload failure | CatalogSafetyTest, IdentitySafetyTest, after-commit media test; render lịch sử order/inventory/coupon sau archive | Đã kiểm chứng trên dữ liệu tổng hợp |
| Endpoints có handlers/quyền/validation/view | Feature/P6 RouteContractTest, HTTP/Blade integration tests | Đã kiểm chứng trên dữ liệu tổng hợp |
| Native fresh/upgrade/reentry/restore, history-safe down | P1 schema tests + P6 migration test | Đã kiểm chứng trên sandbox |
| Backup/rehearsal/app rollout giữ dữ liệu cũ | private p6-rollout receipt + read-only runtime artifact | Đã áp dụng; projection dữ liệu cũ của 20 bảng giữ nguyên |
| Runbook có owner/điều kiện đóng/rollback | docs/runbooks/P6_OPERATIONS.md | Đã ghi rõ vai trò Admin/Operator |

## Validation

Đường dẫn PHP: `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe`. Lệnh dưới đây rút gọn `php`.

- `php tests/Support/prepare-p6-sandbox.php --sandbox-only` — chỉ schema tổng hợp.
- `P1_SCHEMA_TESTS=1 P2_SCHEMA_TESTS=1 P3_SCHEMA_TESTS=1 P4_SCHEMA_TESTS=1 P6_SCHEMA_TESTS=1 php vendor/bin/phpunit` — **196 tests, 2.119 assertions, 1 skip**; pass toàn bộ tests thực thi. Skip là nhánh IsolationTest dành cho mode DB-disabled; tất cả tests schema/concurrency opt-in đã chạy thật trên MySQL sandbox, không bị bỏ qua.
- `P0_DB_MODE=disabled php vendor/bin/phpunit --testsuite Unit,Feature,Architecture` — 62 tests, 1.133 assertions, pass; các domain/contracts không cần DB.
- `php vendor/bin/pint --test <toàn bộ PHP thay đổi/mới>` — 74 file, pass. `php -l tests/Support/verify-p6-runtime.php` pass.
- `node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs` — 4 tests, pass; thêm kiểm coupon thay đổi fingerprint/key.
- `npm run build` trong frontend — pass, 1.990 modules. `frontend/dist/` vẫn untracked, không đưa vào source change.
- `git diff --check` — pass. Không chạy test có ghi vào DB ứng dụng, không gửi payment/SMTP thật.

Lỗi regression đã xác định và sửa trong scope: P1 restore count cần 56 migrations; fixture P2 atomicity cần các bảng P6 và prerequisites khi chạy code hiện tại; query budget confirm/IPN thêm đúng một lookup coupon. Budget checkout/replay/shipping/import không nới. Không vô hiệu assertion để bỏ lỗi concurrency hoặc tiền/tồn.

### Rollout thực tế

- `php tests/Support/rollout-schema.php p6 --rehearse` — full backup **758.275.809 bytes**, restore disposable clone và migration rehearsal pass. Backup/receipt giữ riêng tư trong đường dẫn ignored; không đưa SQL thật vào Git.
- `php tests/Support/rollout-schema.php p6 --apply` — pass, application migration count **56**; chỉ migration `2026_10_30_000001_create_commerce_lifecycle.php` được áp dụng.
- `php tests/Support/verify-p6-runtime.php --application-read-only` — pass; [runtime artifact](../architecture/p6-runtime-verification.json). Projection dữ liệu cũ của 20 bảng giữ nguyên: 52 orders, 56 order_items, 184 inventory rows, 259 movements lịch sử. Cả tám bảng P6 mới đều 0 rows; không tạo chứng cứ tiền/hàng/coupon cho lịch sử.
- `TELESCOPE_ENABLED=false php artisan inventory:reconcile` — unexplained=[], open_discrepancies=0, legacy_hold_quantity=40, protocol_movements=0. Giữ nguyên 45 legacy reservations/18 allocation lines; không backfill lại P4.
- `TELESCOPE_ENABLED=false php artisan schedule:list` — expiry mỗi phút, notification recovery mỗi 5 phút đã đăng ký. Windows task hiện Disabled nên không khẳng định tự động chạy.
- Gates đọc từ `.env`: inventory protocol/chuyển trạng thái bật, VNPay tắt. Không sửa `.env` hoặc dependencies. Không chạy scenario ghi dữ liệu thật để nghiệm thu.

## Những giới hạn còn lại

- Không bật VNPay; merchant/public IPN/Google/SMTP thật vẫn chưa xác minh như P5. Refund là đối soát thủ công, không phải tự hoàn qua provider. Không phát sinh giao dịch thật để test.
- Runtime có **3 đơn legacy paid chưa có chứng cứ attempt paid/COD collected phù hợp trong ledger**, không kết luận tiền chưa từng được thu. Dữ liệu giữ nguyên; new refund guard từ chối cho tới khi có quy trình đối soát được duyệt. Không có duplicate assignment pair trong snapshot runtime; dữ liệu mơ hồ mới vẫn bị chặn như tests.
- Quan sát Windows task AppleProject-LaravelScheduler đang Disabled, 28 missed runs, LastTaskResult=0 của lượt chạy cũ. Chưa xác minh lý do tắt, không tự bật lại tác vụ đã tắt. Registration `schedule:list` đã kiểm chứng, tự động chạy scheduler/worker chưa được xác nhận. Operator cần kiểm tra/bật lại trước khi dựa vào tự động expiry/notification; manual recovery theo runbook.
- Không free-order, promotion stacking, split warehouse/in-transit, hủy confirmed/shipping hoặc tự đưa hàng damaged về sellable. Không suy diễn lại đơn/counter/assignment legacy thiếu chứng cứ.
- Email có recovery delivery ledger và at-least-once; queued không chứng minh đã giao, crash có thể gửi trùng thông báo. Side-effect email status/role cũ là after-commit best effort; không tuyên bố guaranteed delivery cho mọi notification trong repo.
- Old FK cascade không bị rewrite; guard chặn mọi HTTP hard-delete liên quan đã tìm thấy, kể cả category. Raw SQL/maintenance ngoài entrypoints cần review riêng. Không tuyên bố mọi API/CRUD ngoài P6 đã được audit/làm lại.
- Chưa có benchmark tải/SLO, CI/queue deployment mới hoặc thử nghiệm Microservices; đó là P7/P8. Source scan architecture có giới hạn đã ghi ở P5.

## Kiểm tra thủ công, recovery, rollback

Xem [runbook P6](../runbooks/P6_OPERATIONS.md). Trước review chỉ dùng dữ liệu thử để thu/hoàn/nhận trả. Đã có P6 business writes thì giữ schema/ledger và lifecycle, dừng ghi mới rồi forward-fix/đối soát; không quay về P5 thuần, không restore đè giao dịch mới, không xóa movement/event. Chưa có writes thì có thể hoàn đúng diff P6 sau lưu patch review, giữ schema additive.

Danh sách file được ghi tại [P6_CHANGED_FILES](P6_CHANGED_FILES.md). Contract/schema/decisions: [P6 contract](../architecture/p6-lifecycle-contract.md), [ADR-018](../architecture/architecture-decisions.md).
