# P7 — Hiệu năng, CI và vận hành

Ngày thực hiện: 2026-10-30, Asia/Saigon. **Đã triển khai trong phạm vi local/sandbox; trạng thái kiểm chứng cuối cùng xem [verification](../architecture/p7-verification.json). CI trên GitHub và vận hành production chưa được xác minh.** Không chuyển P8, tự commit/push hoặc deploy.

## Phê duyệt và phạm vi khảo sát

Đã đọc roadmap P7, current/target architecture, ADR và P6 status/contract/runbook; source Inventory reports, API Home, Catalog/attributes/observers, coupon jobs, cache/queue/logging/Telescope, console schedule, test isolation/schema/race harness và SQL baseline. Không tìm thấy AGENTS.md trong repository/ancestor đã khảo sát. Không tuyên bố audit lại mọi file hoặc mọi tổ hợp UI/promotion/provider.

Chủ dự án đã duyệt:

- Dữ liệu tổng hợp 1.000 sản phẩm × 3 SKU × 5 kho; CSV 100.000 dòng.
- Ngưỡng detail ≤8 query, home ≤5 query, CSV peak ≤64 MiB trong subprocess và chunk 1.000; báo cáo p95 trước/sau, chưa là SLO production.
- Chỉ rehearsal scheduler/worker trên sandbox, giữ Windows task hiện tại Disabled.

## Phát hiện và thay đổi

| Vấn đề đã xác minh | Cách xử lý P7 | Contract và giới hạn |
| --- | --- | --- |
| InventoryController::index có OR search nằm ngoài nhóm AND warehouse/status | `Reporting/InventoryReadQuery` dùng một nhóm OR, chia sẻ filter với export | Search không thoát kho/trạng thái; export nay cũng áp dụng search |
| exportExcel eager load toàn bộ inventory trước streamed response | Join scalar rows, `lazyById(1000)`, high-water mark, CSV sink streaming | Giữ route/header/cột/filename; text công thức được neutralize; đọc theo chunk, không phải snapshot tài chính |
| HomeController::productDetail đọc inventory từng variant | Dùng lại tập tồn tải chung theo product, group theo variant và warehouse | Không N+1; vẫn tính tồn đã giữ/hết available, không cache authoritative stock |
| Cache attributes clear trước commit; home/category không có invalidation | Scoped `CatalogReadCache`, generation keys, observer after-commit, bypass shared cache trong tx; banner bulk reorder invalidation rõ ràng | TTL giữ 60/300/3.600 giây; test rollback/commit/in-flight stale build; database cache không query lại theo accessor |
| LoggableTrait chỉ nhận Exception và ghi message/data thô | Nhận Throwable, structured class/code/file/line, Monolog redaction trên channels cấu hình | Giữ correlation/order/reason, bỏ credentials/payload/SQL bindings; không hứa phát hiện mọi PII tự do |
| Telescope mặc định thu thập nhiều dữ liệu, gate local có thể bypass | Opt-in mặc định, watchers mặc định off, auth + active admin middleware, mở rộng sensitive paths | Không xóa dữ liệu Telescope lịch sử hoặc sửa env |
| Queue mặc định có thể enqueue trước commit, thiếu profile retry/timeout | Database after_commit; coupon jobs tries 3, timeout 60, backoff 10/30/60 | retry_after 90 giữ nguyên; Windows thiếu pcntl cần giám sát riêng khi vận hành thật |
| Chưa có CI và bằng chứng recovery P7 | Workflow MySQL/PHP cùng baseline + full tests/budgets/lint/frontend; real worker/scheduler/restore/release-cache tests | Không thêm package; CI config chưa chạy remote; không bật task/worker thật |

Thêm CLI read-only `operations:health` cho counts/age của queue, failed jobs, notices, holds quá hạn và discrepancies. Không tự sửa số dư hoặc tự gửi cảnh báo. StockWriter và DAG sáu module P5 giữ nguyên; Reporting là read adapter bên ngoài Modules, không tạo dependency chéo trong domain.

Pattern sử dụng đúng vấn đề: Query Object/read adapter cho filter dùng chung; keyset streaming cho CSV; scoped memoization + generation cache + after-commit observer cho race/rollback; database queue at-least-once cùng durable identity hiện có cho retry. Không thêm repository tổng quát, broker, Redis, microservice hoặc distributed transaction.

## Số liệu và acceptance

Baseline nằm ở [before](../architecture/p7-performance-before.json), code ứng dụng P6 `092c02e`; số liệu sau nằm ở [after](../architecture/p7-performance-after.json). Cùng fixture version 1, cùng controller boundary, SQL/serialize, cache lạnh và subprocess riêng cho workload. JSON/CSV SHA-256 bằng nhau trên dữ liệu benchmark. Số liệu local chịu ảnh hưởng máy/optimizer, không phải HTTP load test.

| Workload | Query trước → sau | p95 trước → sau | Peak RAM trước → sau | Ngưỡng đã duyệt |
| --- | --- | --- | --- | --- |
| Detail | 11 → 8 | 21,158 → 8,258 ms | 24 → 24 MiB | ≤8 query: đạt |
| Home | 5 → 5 | 50,246 → 9,703 ms | 24 → 24 MiB | ≤5 query: đạt |
| CSV 100.000 dòng | 4 → 102 | 11.361,116 → 734,392 ms | 374 → 26 MiB | ≤64 MiB: đạt |

CSV 4 → 102 query gồm high-water mark, 100 chunk và query kết thúc; đây là lựa chọn có chủ đích để giới hạn RAM. Test detail 12 variants vẫn ≤8 query. Test database cache gọi labels 50 lần tiếp theo không thêm query; scope request mới nhìn thấy generation từ process khác.

[EXPLAIN](../architecture/p7-query-plans.json) xác nhận detail stock dùng FK product_id/variant_id, warehouse page dùng index warehouse+variant hiện có, keyset dùng PK range. Phương án correlated SUM từng được thử nhưng MySQL quét inventory trong subquery đã bị loại; artifact ghi rõ `rejected_correlated_variant_totals`. Không thêm/drop index hoặc migration vì chưa có bằng chứng cần thêm. Ước lượng rows trên fixture mới nạp không phải actual scanned rows.

[Lock probe](../architecture/p7-lock-wait.json) gồm hai connection thật/năm mẫu, có chờ row lock khi parent cố ý giữ 500 ms sau READY; không thay đổi balance. Không có đo lock contention production trước/sau; protocol writer không đổi trong P7. Các race P2/P4/P6 tiếp tục chạy trong full regression.

| Tiêu chí roadmap | Bằng chứng | Phạm vi kết luận |
| --- | --- | --- |
| Workload trước/sau và budgets đã duyệt | JSON before/after + subprocess test + response hash | Đạt local trên workload đã nêu |
| Không N+1, OR không thoát warehouse/status, CSV bounded | ReadPerformanceTest | Đạt; không suy ra mọi report đã có SLO |
| Cache commit/rollback, stale writer, database cache accessor | ReadPerformanceTest | Đạt sandbox, chưa đo nhiều app node |
| Redaction, Telescope defaults, CI guard/policy | OperationsConfigurationTest + LogRedactionTest | Đạt tests; legacy telemetry/failed_jobs vẫn nhạy cảm |
| Worker crash/retry và failed jobs | RecoveryTest, queue:work/retry subprocess thật | Đạt sandbox, không hứa email exactly-once |
| Scheduler expiry chạy hai lần | RecoveryTest, schedule:run thật | Một release; Windows task vẫn Disabled |
| Full backup/restore, FK/balance | P7RestoreDrill + RecoveryTest | 56 bảng cùng dữ liệu/schema/FK, không orphan, reconcile exit 0 trên clone |
| Deployment/rollback rehearsal | config:cache/route:cache, smoke, clear cache riêng, smoke lại | Đạt sandbox; không deploy ứng dụng đang dùng |
| CI chặn regression Critical | Workflow không continue-on-error, đủ schema flags, PHP/JS/build; YAML/policy tests | Cấu hình và check local đã xác minh; **remote Actions/branch protection chưa xác minh** |
| Dữ liệu nguồn giữ nguyên | Verification so projection cột gốc với private P6 rollout receipt | Read-only; không kết luận thay cho audit mọi cột mới/ghi bên ngoài |

## Test/build và lệnh

```powershell
$env:P1_SCHEMA_TESTS='1'
$env:P2_SCHEMA_TESTS='1'
$env:P3_SCHEMA_TESTS='1'
$env:P4_SCHEMA_TESTS='1'
$env:P6_SCHEMA_TESTS='1'
$env:P7_SCHEMA_TESTS='1'
php vendor/bin/phpunit --log-junit tests/.runtime/p7-full-junit.xml
php tests/Support/p7-lint.php
node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs
npm run build --prefix frontend
php tests/Support/benchmark-p7.php after
php tests/Support/p7-query-plans.php
php tests/Support/p7-lock-wait.php
php tests/Support/record-p7-verification.php
git diff --check
```

Thực tế sử dụng PHP executable Laragon 8.3.30; `php` ở đây viết gọn. Full regression cuối: **209 tests, 2.284 assertions, 0 failure/error, 1 skip dự kiến**, 4 phút 17,834 giây; bằng chứng verification JSON/JUnit private. Skip là nhánh DB-disabled isolation khi đang chạy MySQL; không skip schema/race/recovery đã opt-in. Node contract tests 4/4 pass. Frontend build pass, 1.990 modules; `frontend/dist/` là output local untracked, không đưa vào thay đổi P7. Pint **38 file pass**, chỉ các file P7; không đưa formatting legacy ngoài phạm vi vào thay đổi. `git diff --check` pass.

## Giới hạn và công việc chưa thực hiện

- CI remote và branch protection chưa chạy/được cấu hình trong phiên này. Không coi việc có YAML là bằng chứng GitHub runner đã xanh.
- Task Windows giữ Disabled, có [artifact read-only](../architecture/p7-scheduler-state.json); chưa dựng worker/scheduler tự chạy production hoặc watchdog Windows. SMTP/provider thật, tải HTTP đồng thời, cache nhiều node và disaster recovery production chưa xác minh.
- Không đặt production SLO từ 3 mẫu CSV/local p95; không có index migration, runtime upgrade hoặc dependencies mới.
- `inventory:reconcile` hiện vẫn là báo cáo đầy đủ nạp dữ liệu, có query movement theo stock; chưa tối ưu/đặt SLO riêng khi lịch sử lớn. CSV live không có snapshot cùng thời điểm.
- Không purge Telescope/failed_jobs; retention cần chốt riêng. Không thay policy kho/retention, legacy holds, 3 paid orders thiếu chứng cứ hoặc bật VNPay.

## Kiểm tra thủ công và rollback

Trên môi trường sandbox: mở inventory với warehouse + status + search, xác nhận mỗi dòng thuộc đúng kho; export cùng filter có cùng tập ID/số dòng. Xem detail sản phẩm nhiều SKU, đối chiếu stock/available/reserved với fixture. Sửa nhãn màu rồi commit/rollback trong test và xem lại home/detail. Chạy P7 RecoveryTest để diễn tập queue/scheduler/restore có guard thay vì chạy worker trên DB đang dùng. Xem `operations:health` và reconcile trên sandbox. Runbook có command, đầu vào, kết quả và giới hạn: [P7_OPERATIONS](../runbooks/P7_OPERATIONS.md).

Rollback code P7 riêng về P6 `092c02e` sau khi lưu diff/file mới và review. Rebuild config/routes cache theo release, restart worker khi được duyệt vận hành; giữ DB/jobs/movements. Không migration down, restore snapshot cũ lên DB đang nhận ghi, reset --hard hoặc bật task bị tắt. Rehearsal rollback chỉ xóa cache release sandbox đã tạo, không xóa dữ liệu nguồn.

## File thay đổi

- [.github/workflows/quality.yml](../../.github/workflows/quality.yml)
- [app/Http/Controllers/Admin/BannerController.php](../../app/Http/Controllers/Admin/BannerController.php)
- [app/Http/Controllers/Admin/InventoryController.php](../../app/Http/Controllers/Admin/InventoryController.php)
- [app/Http/Controllers/Admin/ProductController.php](../../app/Http/Controllers/Admin/ProductController.php)
- [app/Http/Controllers/Api/HomeController.php](../../app/Http/Controllers/Api/HomeController.php)
- [app/Jobs/AssignCouponJob.php](../../app/Jobs/AssignCouponJob.php)
- [app/Jobs/NotifyCouponAssignment.php](../../app/Jobs/NotifyCouponAssignment.php)
- [app/Logging/RedactSensitiveData.php](../../app/Logging/RedactSensitiveData.php)
- [app/Observers/CatalogReadCacheObserver.php](../../app/Observers/CatalogReadCacheObserver.php)
- [app/Observers/ProductAttributeObserver.php](../../app/Observers/ProductAttributeObserver.php)
- [app/Providers/AppServiceProvider.php](../../app/Providers/AppServiceProvider.php)
- [app/Providers/TelescopeServiceProvider.php](../../app/Providers/TelescopeServiceProvider.php)
- [app/Reporting/InventoryReadQuery.php](../../app/Reporting/InventoryReadQuery.php)
- [app/Reporting/OperationsHealth.php](../../app/Reporting/OperationsHealth.php)
- [app/Services/CatalogReadCache.php](../../app/Services/CatalogReadCache.php)
- [app/Services/ProductAttributeService.php](../../app/Services/ProductAttributeService.php)
- [app/Traits/LoggableTrait.php](../../app/Traits/LoggableTrait.php)
- [bootstrap/app.php](../../bootstrap/app.php)
- [config/logging.php](../../config/logging.php)
- [config/queue.php](../../config/queue.php)
- [config/telescope.php](../../config/telescope.php)
- [docs/architecture/architecture-decisions.md](../../docs/architecture/architecture-decisions.md)
- [docs/architecture/current-architecture.md](../../docs/architecture/current-architecture.md)
- [docs/architecture/p7-lock-wait.json](../../docs/architecture/p7-lock-wait.json)
- [docs/architecture/p7-performance-after.json](../../docs/architecture/p7-performance-after.json)
- [docs/architecture/p7-performance-before.json](../../docs/architecture/p7-performance-before.json)
- [docs/architecture/p7-query-plans.json](../../docs/architecture/p7-query-plans.json)
- [docs/architecture/p7-scheduler-state.json](../../docs/architecture/p7-scheduler-state.json)
- [docs/architecture/p7-verification.json](../../docs/architecture/p7-verification.json)
- [docs/architecture/target-architecture.md](../../docs/architecture/target-architecture.md)
- [docs/roadmap/P7_STATUS.md](../../docs/roadmap/P7_STATUS.md)
- [docs/roadmap/UPGRADE_PLAN.md](../../docs/roadmap/UPGRADE_PLAN.md)
- [docs/runbooks/P7_OPERATIONS.md](../../docs/runbooks/P7_OPERATIONS.md)
- [routes/console.php](../../routes/console.php)
- [tests/Feature/P7/OperationsConfigurationTest.php](../../tests/Feature/P7/OperationsConfigurationTest.php)
- [tests/Integration/P7/ReadPerformanceTest.php](../../tests/Integration/P7/ReadPerformanceTest.php)
- [tests/Integration/P7/RecoveryTest.php](../../tests/Integration/P7/RecoveryTest.php)
- [tests/Support/P7LockWait.php](../../tests/Support/P7LockWait.php)
- [tests/Support/P7RecoveryProbe.php](../../tests/Support/P7RecoveryProbe.php)
- [tests/Support/P7RestoreDrill.php](../../tests/Support/P7RestoreDrill.php)
- [tests/Support/P7Runtime.php](../../tests/Support/P7Runtime.php)
- [tests/Support/P7Sandbox.php](../../tests/Support/P7Sandbox.php)
- [tests/Support/benchmark-p7.php](../../tests/Support/benchmark-p7.php)
- [tests/Support/p7-benchmark-worker.php](../../tests/Support/p7-benchmark-worker.php)
- [tests/Support/p7-lint.php](../../tests/Support/p7-lint.php)
- [tests/Support/p7-lock-wait.php](../../tests/Support/p7-lock-wait.php)
- [tests/Support/p7-lock-worker.php](../../tests/Support/p7-lock-worker.php)
- [tests/Support/p7-query-plans.php](../../tests/Support/p7-query-plans.php)
- [tests/Support/prepare-ci.php](../../tests/Support/prepare-ci.php)
- [tests/Support/record-p7-verification.php](../../tests/Support/record-p7-verification.php)
- [tests/Unit/P7/LogRedactionTest.php](../../tests/Unit/P7/LogRedactionTest.php)
