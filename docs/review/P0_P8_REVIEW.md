# Review tổng thể P0–P8 và chuẩn bị portfolio

Ngày review: 2026-10-31, Asia/Saigon. Nhánh review bắt đầu từ P8 `1a3ba5e8378d22e6298debe96ce9ddc445c62510`. Không deploy, commit/push, migration nguồn, thay dependencies hoặc bật scheduler. Không tìm thấy AGENTS.md trong repository và các thư mục tổ tiên đã khảo sát.

## Kết luận và phạm vi

P0–P8 đã có implementation và bằng chứng local theo phạm vi từng phase; P8 là phase tùy chọn cuối roadmap hiện tại, không có P9 đã được phê duyệt. Điều đó **không đồng nghĩa nghiệm thu production**. Tiền/tồn vẫn chạy trong Modular Monolith. Provider thực, dữ liệu lịch sử mơ hồ và vận hành production còn các điều kiện bên ngoài cần xử lý riêng.

Đã đọc roadmap, tài liệu kiến trúc/ADR, status và verification P0–P8; kiểm tra test bootstrap/schema helpers, module boundaries, luồng Pricing/Ordering/Inventory/Payments, P6 lifecycle, P7 reporting/cache/recovery và P8 transport/delivery. Đã kiểm tra routes API/admin, login/checkout/admin order, frontend API/Vite/router, CI và các script demo mới. Full suite chạy lại trên test/disposable DB; actual HTTP scenario bổ sung trên sandbox mới. **Không tuyên bố đọc lại từng dòng mọi CRUD, frontend component, vendor, SQL lịch sử, binary/upload hoặc toàn bộ Git history.** Đây là review thực thi roadmap, không phải penetration test, dependency advisory scan hay audit tài chính thực tế.

Bằng chứng mới: [local-verification.json](local-verification.json), [demo-verification.json](../portfolio/demo-verification.json), [github-verification.json](github-verification.json). Các artifacts P0–P8 cũ giữ nguyên làm snapshot lịch sử; dòng “chưa xác minh” cũ chỉ được đóng trong phạm vi bằng chứng mới bên dưới.

## Coverage theo giai đoạn

| Phase | Implementation/evidence thực tế | Kết luận và giới hạn |
| --- | --- | --- |
| P0 baseline/isolation | `tests/Support/TestApplication.php`, `TestingEnvironment.php`, `MySqlSafety.php`; `tests/Integration/P0` | Test không boot source `.env`; DB test DML riêng. Known risks ban đầu đã có owner và regression ở P2/P4; không dùng incomplete như pass |
| P1 schema | `database/schema/mysql-schema.sql`, migration reconcile; `tests/Integration/P1/SchemaUpgradeTest.php` | Fresh, upgrade/drift, conflict fail-fast, backup/restore trên DB cách ly; rollout lịch sử có receipt. Không migration nguồn trong review này |
| P2 security/price/payment | `app/Modules/Pricing`, `Payments`, API request/policy; `tests/Integration/P2`, `tests/Feature/P2` | Server price, ownership, active user, signed payment ledger và OAuth handoff có tests. VNPay merchant public/Google thật chưa nghiệm thu |
| P3 transition | `Ordering/Application/ChangeOrderStatus.php`, Domain policy; `tests/Integration/P3` | Transaction, allowed transitions, retry và concurrent side effects; status không thay thế bằng chứng thu tiền |
| P4 inventory | `Inventory/Infrastructure/StockWriter.php`, `ReservationManager.php`, `IdempotentOperation.php`; `tests/Integration/P4` | Một kho đủ toàn đơn, khóa/transaction, last-unit race, allocation đúng kho, rollback/crash, expiry/payment races; legacy thiếu bằng chứng vẫn giữ/cách ly |
| P5 modules | `app/Modules`, `tests/Architecture/ModuleBoundaryTest.php`, `tests/Integration/P5` | Contracts/DTO, DAG và model ownership có guard; controllers/read adapters/compatibility root vẫn còn, chưa Clean Architecture mọi CRUD |
| P6 lifecycle | `tests/Integration/P6/{LifecycleTest,LifecycleConcurrencyTest,CouponTest,InventoryDocumentsTest,CatalogSafetyTest,IdentitySafetyTest}.php` | COD/refund manual ledger, quality return, coupon lifecycle, documents/archive; không provider refund thật |
| P7 performance/ops | `app/Reporting/InventoryReadQuery.php`, `CatalogReadCache.php`, `tests/Integration/P7` | Query/RAM budgets, invalidation, real worker/scheduler/restore rehearsal trên sandbox. Không HTTP load SLO hoặc scheduler nguồn |
| P8 prototype | `app/Experiments/P8`, `experiments/p8/worker.php`, `tests/Integration/P8/DeliveryTest.php` | Outbox/inbox/version/crash/replay/process+logical DB isolation. Mặc định no-op, chưa independent deployment/network fault domain |

Các status gốc: [P0](../roadmap/P0_STATUS.md), [P1](../roadmap/P1_STATUS.md), [P2](../roadmap/P2_STATUS.md), [P3](../roadmap/P3_STATUS.md), [P4](../roadmap/P4_STATUS.md), [P5](../roadmap/P5_STATUS.md), [P6](../roadmap/P6_STATUS.md), [P7](../roadmap/P7_STATUS.md), [P8](../roadmap/P8_STATUS.md).

## Những mục chưa xác minh đã xử lý

| Mục trước review | Bằng chứng mới | Trạng thái |
| --- | --- | --- |
| CI mới chỉ có YAML/local | GitHub API xác nhận run P8 [41092107677](https://github.com/longnt20/Ecommerce_apple/actions/runs/41092107677) và merge main [41092131070](https://github.com/longnt20/Ecommerce_apple/actions/runs/41092131070) thành công, gồm regression/lint/P8 benchmark/frontend build | Đóng cho đúng hai SHA trong artifact; thay đổi review hiện tại chưa push nên chưa có CI remote |
| Admin session/CSRF và checkout chỉ có framework tests | `scripts/portfolio-demo.php verify --frontend`, `PortfolioScenario.php`: HTTP Bearer + session login, thiếu CSRF 419, checkout giá server/replay/ownership, confirmed/shipping/completed/COD replay | Đóng smoke E2E backend loopback; không thay thế browser/real provider/load test |
| Frontend/backend hướng dẫn sai paths/contracts | Viết lại `SEPARATION_GUIDE.md`; Vite proxy thực kết nối sandbox `/api/home` | Đóng tài liệu và dev proxy; chưa cấu hình host production |
| P8 lỗi serialize có thể để file tạm | `FileSpool.php`: cleanup outer finally; `tests/Unit/Review/FileSpoolFailureTest.php` dùng invalid UTF-8 | Đã sửa lỗi recoverable; hard kill giữa write vẫn có thể để temp và không bị coi là published |
| Portfolio chưa có entrypoint/scenario/evidence | README riêng dự án, [case study](../portfolio/CASE_STUDY.md), [DEMO](../portfolio/DEMO.md), script serve/verify/cleanup | Demo runnable bằng fixture tổng hợp; chưa có video/screenshot/visual QA |
| Bảo toàn source sau kiểm chứng | Read-only fingerprint cột gốc 20 bảng so private P6 receipt; migrations/P8 tables trong local artifact | Chỉ khẳng định đúng projection đã so, không mọi cột mới hoặc mọi bảng |

## Các vấn đề còn mở theo ưu tiên

| ID / ưu tiên | Vị trí và nguyên nhân | Tác động | Hướng xử lý / điều kiện đóng |
| --- | --- | --- | --- |
| R01 **High**, trước bật online | `config/vnpay.php`, `Payments/Infrastructure`, Google controllers; chưa có merchant/IPN public/OAuth thật | Callback/domain/provider differences có thể chỉ xuất hiện ngoài sandbox | Giữ online tắt; rehearsal credentials/domain sandbox của provider, IPN amount/signature/duplicate/late callback, OAuth state và cookie thực. Không dùng `.env` thật để tự tạo giao dịch |
| R02 **High**, trước vận hành cửa hàng | `routes/console.php`, task `AppleProject-LaravelScheduler` Disabled theo quyết định chủ dự án | Không có scheduler thật thì deadline/maintenance không tự chạy như production | Có runbook/worker+schedule sandbox tests; chỉ bật production sau phê duyệt owner, health/alert/recovery và ownership process. Review giữ nguyên Disabled |
| R03 **High**, dữ liệu legacy | `InventoryLegacyHold`, `InventoryDiscrepancy`, backfill maintenance; P4/P6 receipts | Không đủ chứng cứ để giải phóng tồn/đánh paid/hoàn tiền lịch sử | Giữ nguyên/quarantine, reconciliation vật lý/chứng từ ngân hàng có người duyệt. Không chạy lại backfill hoặc sửa số dư để làm “xanh” báo cáo |
| R04 **Medium**, API route mismatch | `routes/api.php` đăng ký `apiResource`; Api CategoryController chỉ index, PromotionController chỉ index/promotionCategories | Action không có method có thể gây 500; không phải CRUD đã hoạt động | Chốt contract rồi giới hạn routes hoặc implement action có auth/validation; thêm route-method coverage. Chưa tự xóa/thay contract trong review portfolio |
| R05 **Medium**, I/O ngoài | `routes/api.php` province/district/ward dùng `file_get_contents` trực tiếp, chưa timeout/cache/error adapter rõ | Provider chậm/lỗi có thể giữ request hoặc trả nội dung không kiểm soát | Lát cắt riêng: validate ID, HTTP client timeout hữu hạn/cache/fallback và test provider failure; demo hiện dùng fixture router, không chứng minh live provider |
| R06 **Medium**, maintenance scale | `Inventory/Infrastructure/ReconcileInventory.php` đọc toàn stocks/reservations và query movement từng stock | N+1/RAM tăng khi ledger lớn; chưa có SLO cho reconcile | Đo workload ledger lớn rồi chunk/query aggregate hoặc snapshot reconciliation nhất quán. Không coi optimization CSV đã giải quyết reconcile |
| R07 **Medium**, governance CI | GitHub rules API chỉ trả deletion/non-fast-forward/pull_request; không có required status check. Classic protection API trả 404 (không có hoặc không nhìn thấy), chưa xác minh enforcement đầy đủ | Chưa đủ bằng chứng CI được cưỡng chế trước merge | Owner kiểm tra cả ruleset/classic protection và cấu hình required `regression` đúng workflow nếu thiếu. Read-only review không thay settings repository |
| R08 **Medium**, operations còn thiếu | P7 scheduler `withoutOverlapping` có mutex dài; chưa kill scheduler giữa command; production backup/monitor chưa chạy | Abandoned mutex có thể trì hoãn job; chưa có RTO/RPO thật | Rehearsal crash command + xác minh process đã dừng trước clear mutex; diễn tập restore trên môi trường deploy sau khi chọn host |
| R09 **Low**, đã sửa | `app/Experiments/P8/FileSpool.php` cleanup hẹp trước đây | Rác temp sau serialization error, tăng dung lượng spool | Test invalid UTF-8 và outer finally; chưa triển khai automatic prune temp của hard crash |
| R10 **Low**, presentation/compatibility | README/guide cũ; các bridges `app/Services`, legacy CRUD và P8 event feed hẹp | Người đọc hiểu sai kiến trúc; xóa adapter sớm có thể phá caller | Tài liệu đã sửa. Giữ bridges khi provider/legacy còn phụ thuộc; archive/raw SQL/category rename chưa phát đủ P8 event, không chuyển API reader sang projection |

Không tái hiện lại các lỗi Critical/High đã có regression trong workload được chạy không phải bằng chứng “không còn lỗi bảo mật/dữ liệu nào”. Các hàng High phía trên là điều kiện đưa hệ thống vào sử dụng thật, không được tự đánh hoàn thành vì demo chạy.

## Kiểm thử và bảo toàn

Chạy full suite với `P1_SCHEMA_TESTS`, `P2_SCHEMA_TESTS`, `P3_SCHEMA_TESTS`, `P4_SCHEMA_TESTS`, `P6_SCHEMA_TESTS`, `P7_SCHEMA_TESTS`, `P8_SCHEMA_TESTS` đều bằng `1`; JUnit private tại `tests/.runtime/review-junit.xml`. Script `php tests/Support/review-verification.php` chỉ ghi public report khi các test recovery/concurrency bắt buộc không skip và source fingerprints khớp. Một skip dự kiến của nhánh DB-disabled được ghi trong artifact; không coi đó là integration bị bỏ qua.

```powershell
php vendor/bin/phpunit --log-junit tests/.runtime/review-junit.xml
php tests/Support/p7-lint.php
php tests/Support/p8-lint.php
php vendor/bin/pint --test app/Experiments/P8/FileSpool.php tests/Support/PortfolioDemo.php tests/Support/PortfolioScenario.php tests/Unit/Review/FileSpoolFailureTest.php tests/Support/review-verification.php scripts/portfolio-demo.php scripts/support/portfolio-router.php
node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs
npm run build --prefix frontend
php scripts/portfolio-demo.php verify --frontend
php tests/Support/review-verification.php
```

Full tests dùng sandbox/DB test đã provision; CLI demo không yêu cầu `.env.testing` MySQL vì tự tạo fixture schema riêng. Source `.env` không sửa, không chạy migration nguồn, không gửi mail/payment, không bật Windows task. `frontend/dist/` đã có untracked trước review và vẫn là build output; không stage/xóa output của người dùng.

Kết quả lần review: **224 tests, 2.384 assertions, 1 skip dự kiến**, zero failure/error; P7 Pint 38 files, P8 Pint 28 files, Pint phạm vi review 7 files đều đạt. Node 4/4 tests; frontend build 1.990 modules thành công. Demo đạt 24 checks và Vite proxy, serve hết thời gian thoát bình thường; [recovery-verification.json](../portfolio/recovery-verification.json) xác nhận cleanup từ chối server đang chạy, rồi xóa đúng schema/runtime sau khi dừng. Source vẫn 56 migration records và zero P8 tables; cột gốc của 20 business tables khớp receipt. Không đo lại benchmark P7/P8 trong đợt này; budgets P7 vẫn được chạy trong full regression, số liệu portfolio là artifacts lịch sử có chỉ nguồn.

## Thiết kế thay đổi và rollback

Chỉ sửa application code ở P8 FileSpool cleanup, giữ event format/ID/rename semantics. Vite nhận optional dev proxy env, mặc định cũ giữ nguyên. Phần còn lại là test/demo tools, docs và một CI step chạy scenario. Không thay API routes, DB schema hoặc dependencies.

Rollback: review từng diff rồi revert riêng patch FileSpool/Vite/CI và các file mới nếu cần; không reset hard/clean vì workspace có output có trước. Dừng demo bằng normal exit hoặc exact-ID recovery theo [DEMO](../portfolio/DEMO.md). Không rollback migration nguồn vì đợt này không có migration. Giữ artifacts gốc P0–P8 làm bằng chứng lịch sử.

Việc tiếp theo hợp lý là review/merge bộ demo này, quay demo bằng sandbox rồi nghiệm thu từng điều kiện provider/ops nếu muốn deploy. Không tự mở P9 hoặc chuyển Ordering–Inventory thành microservices.
