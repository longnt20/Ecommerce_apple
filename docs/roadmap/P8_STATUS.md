# P8 — Thử nghiệm Reporting projection có giới hạn

Ngày thực hiện: 2026-10-30, Asia/Saigon. **Đã triển khai và kiểm chứng phạm vi local/sandbox trên nhánh `experiment/p8-catalog-reporting`.** Giữ Modular Monolith mặc định, không deploy service hoặc thay reader API production. Không commit/push tự động.

## Phạm vi và quyết định

Đã đọc P8 roadmap, architecture/ADR/P7 status, Catalog SaveProduct/CRUD, queue/coupon notifications, test isolation/schema/race harness và CI. Không tìm thấy AGENTS.md trong repository/ancestor đã khảo sát. Chọn Reporting catalog cards để thử tách process/database mà không tác động tiền/tồn hoặc SMTP. Không tuyên bố đọc lại toàn bộ repository.

Giả thuyết: precomputed catalog cards giảm query đọc; business write và outbox commit nguyên tử khi consumer offline; replay sau crash/trùng/sai thứ tự không làm projection lùi state. Đo chi phí ghi, catch-up và lưu trữ để đánh giá tách service. Không đặt SLO mới hoặc thêm broker/dependency.

Kết luận sau đo: **giữ monolith cho ứng dụng, giữ prototype cho học tập**. Lợi ích đọc có thể đạt bằng projection nội bộ; chưa có bằng chứng cần deployment service riêng. Thử nghiệm hiện là process độc lập + logical DB riêng trên cùng MySQL/server/filesystem, chưa là microservice production có network/deploy/fault isolation độc lập.

## Thay đổi và thiết kế

| Thành phần | Implementation và nguyên nhân |
| --- | --- |
| Catalog port | `CatalogProjectionEvents`; gọi cuối SaveProduct::save/variant trong transaction đang có; mặc định NoCatalogProjectionEvents để ứng dụng không cần P8 schema |
| Opt-in adapter | ExperimentServiceProvider + CatalogOutbox; đồng thời kiểm tra APP_ENV testing, DB fresh allowlisted, producer schema marker và transaction; không tự cấu hình `.env` |
| Transactional outbox | Snapshot cuối aggregate + monotonic stream version + event JSON cùng business commit; product lock hiện có serialize các writer; không publish trong business tx |
| Versioned contract | Schema v1, UUID identity, product/version, UTC, catalog fields tối thiểu; decimal string; không cost_price, tồn hoặc thông tin khách hàng |
| Publisher/transport | Relay dùng SKIP LOCKED, atomic file publish với flush/fsync + rename trong spool riêng, rồi mới mark published; retry/replay giữ ID; append publish/replay/failure audit |
| Consumer/inbox | Process PDO không boot Laravel/đọc env; inbox unique event_id và product/version, projection/audit cùng tx; hash conflict/unsupported/malformed vào quarantine |
| Ordering | Full snapshot version cao thắng, thấp thành stale; không giả định thứ tự UUID hoặc occurred_at; không dùng quy tắc này cho delta stock/tiền |
| Reporting read boundary | CatalogCards contract, MonolithCards và ProjectionCards cho cùng field/page; không chuyển HomeController/checkout sang read model |
| Schema isolation | Hai migration SQL versioned/checksummed ngoài default Laravel migrations; chỉ helper tạo sandbox mới được apply; role consumer DML riêng, relay chỉ quyền outbox/audit |
| CI/evidence | Bật P8 schema tests, lint phạm vi P8 và chạy benchmark/equivalence; artifact không chứa credentials hoặc SQL dump thật; CI remote chưa chạy |

Design patterns dùng để xử lý vấn đề cụ thể: ports/adapters để opt-in và giữ dependency boundaries; transactional outbox để đóng khoảng trống commit/publish; inbox + versioned full snapshot để dedup/reorder; tách read model cho query projection. Không rewrite hệ thống thành CQRS toàn bộ hoặc đưa dependency vào Ordering–Inventory.

Schema sandbox producer thêm 4 bảng: migration marker, streams, messages, publish attempts. Consumer riêng có 5 bảng: marker, cards, inbox, delivery audit, quarantine. Không FK sang source DB; message giữ nguyên khi source archive. **Database ứng dụng vẫn 56 migrations và không có P8 tables.** Test schema P0 persistent cũng không cần P8 DDL.

## Số liệu

[Performance artifact](../architecture/p8-performance.json): PHP 8.3.30/MySQL 8.0.30; fixture 1.000 sản phẩm × 3 SKU × 5 kho/15.000 stock rows, 20 catalog cards/page, 30 mẫu tuần tự. Đây là cùng DTO cards, không phải phép so đầy đủ API home P7 với một DTO hẹp. Không có HTTP/concurrent load hoặc network transport.

| Đo trên cùng workload | Monolith | Experiment |
| --- | --- | --- |
| Read queries/page | 2 | 1 |
| Read p95 | 6,497 ms | 1,227 ms |
| SaveProduct ORM queries | 3 | 9 |
| SaveProduct MySQL statements, gồm transaction/PDO guards | 5 | 13 |
| SaveProduct p95 | 7,338 ms | 15,747 ms |

Hash của trang đọc bằng nhau. Trong lúc consumer offline, 30 event vẫn commit; thêm 1.000 bootstrap snapshot tổng hợp, tổng 1.030 event được publish và nhận inbox, 1.000 projection rows, không quarantine hoặc unpublished còn lại. Catch-up cả relay/consumer subprocess khoảng **13,716 giây**; không coi đây là event latency p95. Các event đến sai thứ tự thành stale nhưng version mới nhất khớp nguồn.

Thử nghiệm giữ 1.030 outbox envelopes khoảng 376.958 bytes và 1.030 spool files khoảng 350.178 bytes, chưa tính DB indexes/pages, inbox/audit hoặc bản sao backup. Replay một event giữ ID; đọc lại trang 1.000 event tạo duplicate audit, không áp dụng effect lần hai. Chi phí tăng gồm write statements, version lock, nhiều credential/schema, relay/consumer, backlog/quarantine/storage và eventual consistency.

## Acceptance và kiểm chứng

| Tiêu chí P8 | Bằng chứng đã chạy | Kết luận |
| --- | --- | --- |
| Business + outbox không tách commit | Outer rollback và trigger reject INSERT outbox → product/SKU/stream rollback | Đạt sandbox |
| Crash sau business commit/trước publish | Kill producer process thật tại marker sau commit; relay/consumer mới vẫn nhận event | Đạt, không dựa vào fake queue |
| Publish trùng/crash relay | Kill sau file publish, trước published_at commit; retry và replay cùng ID có audit | Đạt |
| Consumer crash trước/sau effect và sau commit | Kill ba checkpoint; rollback inbox/projection hoặc dedup sau commit | Đạt, effect DB cùng transaction |
| Reorder/version/schema cũ | Version thấp stale; schema unsupported, identity/version conflict quarantine | Không ghi đè state mới |
| Consumer offline rồi replay | 1.030 inbox/1.000 cards, hash tương đương và pending 0 | Đạt workload tổng hợp |
| Concurrent producer/consumer | Hai writer bị chặn ở product lock rồi cùng chạy → versions 1/2; hai consumers → một apply_count | Đạt connection/process thật |
| Database ownership | Consumer SELECT source products bị MySQL từ chối; schema re-entry checksum; default no-op không cần P8 tables | Đạt isolation logic/quyền |
| Poison isolation | Malformed JSON quarantine, event hợp lệ cùng batch vẫn áp dụng | Đạt; file quá lớn/symlink/I/O fail closed |
| API/protocol cũ độc lập | Full P0–P7 regressions và P7 budgets tiếp tục pass; source branch default no-op | Không đổi route/contracts |
| Lợi ích/chi phí và quyết định | Benchmark/ADR-020/runbook | Giữ monolith, prototype ở nhánh thử |

[Verification artifact](../architecture/p8-verification.json) xác nhận tests bắt buộc không bị skip, source migration count 56, P8 tables nguồn 0 và projection cột gốc 20 business tables vẫn khớp private P6 rollout receipt. Không dùng kết quả này để khẳng định đã audit mọi cột mới hoặc ghi từ người dùng bên ngoài.

## Lệnh và kết quả

```powershell
$env:P1_SCHEMA_TESTS='1'
$env:P2_SCHEMA_TESTS='1'
$env:P3_SCHEMA_TESTS='1'
$env:P4_SCHEMA_TESTS='1'
$env:P6_SCHEMA_TESTS='1'
$env:P7_SCHEMA_TESTS='1'
$env:P8_SCHEMA_TESTS='1'
php vendor/bin/phpunit --log-junit tests/.runtime/p8-full-junit.xml
php tests/Support/p8-lint.php
php tests/Support/p7-lint.php
php tests/Support/benchmark-p8.php
php tests/Support/record-p8-verification.php
node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs
npm run build --prefix frontend
git diff --check
```

Thực tế dùng executable PHP Laragon 8.3.30. Full suite: **223 tests, 2.382 assertions, 0 failure/error, 1 skip dự kiến**, 6 phút 12,615 giây. Skip thuộc nhánh DB-disabled isolation khi chạy MySQL; P8 schema/crash/race không skip. P8 Pint **28 file pass**, P7 Pint 38 file pass. Node **4/4 pass**; build **1.990 modules thành công**. CI YAML/policy tests được chạy lại sau thêm step benchmark và pass; remote Actions chưa xác minh. `frontend/dist/` chỉ là output local untracked. Không chạy install, migrate nguồn hoặc commit/push.

## Giới hạn, kiểm tra thủ công và rollback

- Event coverage hiện là SaveProduct/save SKU và bootstrap fixture có lock; archive/restore trực tiếp, raw SQL và category labels chưa có feed đầy đủ. Projection không phục vụ API/UI thật; không dùng làm giá/tồn authoritative.
- Shared host/MySQL/filesystem/codebase; network/TLS/auth, độc lập artifact deploy, host failure/power-loss durability, concurrent load và production SLO: **Chưa xác minh**. File fsync/rename không thay cho disaster recovery; giữ outbox để replay.
- Spool retained, paging cần tập file ổn định hoặc freeze relay. UUID/offset không phải global watermark; event version theo aggregate và inbox mới là bằng chứng. Không hứa stream ordering từ transport.
- Retention/GC chưa chốt; không xóa outbox vì đã published hoặc prune inbox rồi hứa dedup vô thời hạn. Hard-kill trước commit có thể không ghi attempt audit; pending/file/checkpoint vẫn còn để phục hồi.
- Không email/provider side effect trong P8. Guarantee projection DB không chuyển thành guarantee exactly-once SMTP.
- Windows scheduler giữ Disabled; không task/service/worker production mới, không thay env, schema/dependencies nguồn. CI remote và branch protection chưa xác minh.

Kiểm tra thủ công an toàn: chạy các case DeliveryTest riêng theo tên để quan sát từng checkpoint; xem JSON benchmark/verification sau khi helper tự dọn tài nguyên tổng hợp. [README](../../experiments/p8/README.md) mô tả stdin protocol; [runbook](../runbooks/P8_EXPERIMENT.md) có paging, quyền, poison, replay và watermark. Không nhập credentials vào argv/log hoặc dữ liệu thật vào fixture.

Rollback: dừng consumer độc lập, ghi inbox/version watermark, giữ event IDs và consumer DB nếu replay/fallback. Consumer in-process dùng cùng inbox/projection để không tăng effect khi đọc lại. Tắt opt-in trong sandbox; production reader chưa chuyển nên monolith vẫn hoạt động. Không reset --hard, migration down hoặc restore nguồn. Chỉ drop tài nguyên ngẫu nhiên do helper tạo; không xóa backlog chưa xử lý. Chưa có rollout production cần đảo ngược.

## File thay đổi

- [.github/workflows/quality.yml](../../.github/workflows/quality.yml)
- [app/Experiments/P8/CatalogOutbox.php](../../app/Experiments/P8/CatalogOutbox.php)
- [app/Experiments/P8/Contracts/CatalogCards.php](../../app/Experiments/P8/Contracts/CatalogCards.php)
- [app/Experiments/P8/Envelope.php](../../app/Experiments/P8/Envelope.php)
- [app/Experiments/P8/ExperimentServiceProvider.php](../../app/Experiments/P8/ExperimentServiceProvider.php)
- [app/Experiments/P8/FileSpool.php](../../app/Experiments/P8/FileSpool.php)
- [app/Experiments/P8/MonolithCards.php](../../app/Experiments/P8/MonolithCards.php)
- [app/Experiments/P8/ProjectionCards.php](../../app/Experiments/P8/ProjectionCards.php)
- [app/Experiments/P8/ProjectionConsumer.php](../../app/Experiments/P8/ProjectionConsumer.php)
- [app/Experiments/P8/Relay.php](../../app/Experiments/P8/Relay.php)
- [app/Experiments/P8/SandboxGuard.php](../../app/Experiments/P8/SandboxGuard.php)
- [app/Experiments/P8/Schema.php](../../app/Experiments/P8/Schema.php)
- [app/Modules/Catalog/Contracts/CatalogProjectionEvents.php](../../app/Modules/Catalog/Contracts/CatalogProjectionEvents.php)
- [app/Modules/Catalog/Infrastructure/NoCatalogProjectionEvents.php](../../app/Modules/Catalog/Infrastructure/NoCatalogProjectionEvents.php)
- [app/Modules/Catalog/Infrastructure/SaveProduct.php](../../app/Modules/Catalog/Infrastructure/SaveProduct.php)
- [app/Modules/ModuleServiceProvider.php](../../app/Modules/ModuleServiceProvider.php)
- [bootstrap/providers.php](../../bootstrap/providers.php)
- [config/p8.php](../../config/p8.php)
- [docs/architecture/architecture-decisions.md](../../docs/architecture/architecture-decisions.md)
- [docs/architecture/current-architecture.md](../../docs/architecture/current-architecture.md)
- [docs/architecture/p8-performance.json](../../docs/architecture/p8-performance.json)
- [docs/architecture/p8-verification.json](../../docs/architecture/p8-verification.json)
- [docs/architecture/target-architecture.md](../../docs/architecture/target-architecture.md)
- [docs/roadmap/P8_STATUS.md](../../docs/roadmap/P8_STATUS.md)
- [docs/roadmap/UPGRADE_PLAN.md](../../docs/roadmap/UPGRADE_PLAN.md)
- [docs/runbooks/P8_EXPERIMENT.md](../../docs/runbooks/P8_EXPERIMENT.md)
- [experiments/p8/README.md](../../experiments/p8/README.md)
- [experiments/p8/database/001_consumer.sql](../../experiments/p8/database/001_consumer.sql)
- [experiments/p8/database/001_producer.sql](../../experiments/p8/database/001_producer.sql)
- [experiments/p8/worker.php](../../experiments/p8/worker.php)
- [tests/Feature/P7/OperationsConfigurationTest.php](../../tests/Feature/P7/OperationsConfigurationTest.php)
- [tests/Feature/P8/DefaultIsolationTest.php](../../tests/Feature/P8/DefaultIsolationTest.php)
- [tests/Integration/P8/DeliveryTest.php](../../tests/Integration/P8/DeliveryTest.php)
- [tests/Support/P8Sandbox.php](../../tests/Support/P8Sandbox.php)
- [tests/Support/TestApplication.php](../../tests/Support/TestApplication.php)
- [tests/Support/benchmark-p8.php](../../tests/Support/benchmark-p8.php)
- [tests/Support/p8-lint.php](../../tests/Support/p8-lint.php)
- [tests/Support/p8-producer-worker.php](../../tests/Support/p8-producer-worker.php)
- [tests/Support/record-p8-verification.php](../../tests/Support/record-p8-verification.php)
- [tests/Unit/P8/ProtocolTest.php](../../tests/Unit/P8/ProtocolTest.php)
