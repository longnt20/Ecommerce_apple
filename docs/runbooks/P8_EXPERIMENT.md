# P8 — Runbook thử nghiệm outbox/inbox và Reporting

## Mục tiêu và giới hạn môi trường

Giả thuyết: một read model catalog cards có thể giảm số query đọc, trong khi business write vẫn commit khi consumer offline; event được giữ và replay an toàn sau crash. Đo thêm chi phí ghi/outbox/storage và catch-up để quyết định có đáng tách process/service.

Thử nghiệm dùng PHP 8.3.30, Laravel 11 và MySQL 8.0.30 đang có. Hai **logical database trên cùng MySQL local**, process relay/consumer riêng và file spool local; không broker, Redis, Docker local, HTTP server hoặc package mới. Consumer không boot Laravel/đọc `.env`, chỉ nhận credential DML của chính nó trên stdin. Relay chỉ được SELECT/UPDATE outbox, INSERT audit và đọc migration marker. Producer Laravel chỉ ghi trong DB fresh tổng hợp. Không gửi thông báo, email hoặc thanh toán.

Không coi đây là chứng minh network isolation, deploy độc lập, mất cả host/disk hoặc khả năng vận hành service production. Có cùng codebase/vendor/autoload và shared filesystem; transport sẽ phải thay nếu thật sự tách host. API/frontend mặc định vẫn đọc nguồn monolith. Source database không có P8 schema, không bật feature và task Windows giữ Disabled.

## Protocol và transaction

Producer gọi `CatalogProjectionEvents::record(productId)` cuối `SaveProduct::save/variant`, trước commit. Product lock đang có được dùng lại; stream lock tăng version, snapshot lấy sau khi toàn bộ SKU/spec đã lưu. Product + variants + stream version + outbox cùng rollback/commit. Không gọi transport trong business transaction. Port mặc định no-op, không query outbox khi tắt.

Event `catalog.product-snapshot`, schema_version 1: UUID `id`, product_id, aggregate_version tăng dần theo product, occurred_at UTC; payload gồm name/slug/category_id/listed/sku_count/min_price/max_price. Không có khách hàng, tồn kho hoặc giá vốn. Giá là decimal string để giữ độ chính xác, chỉ dùng cho báo cáo. Đây là **full snapshot**, nên version mới có thể thay version cũ khi đến sai thứ tự; không áp dụng quy tắc này cho delta stock hoặc giao dịch tiền.

Publisher đọc pending outbox bằng FOR UPDATE SKIP LOCKED, ghi tempfile riêng, flush/fsync rồi rename sang `<event-id>.json`, sau đó mới ghi published_at/audit và commit. Chỉ giữ lock outbox của lần publish, không giữ lock product/order/inventory trong transport. Publish thành công chỉ có nghĩa đã đưa vào spool, **không phải consumer đã xử lý**. Nếu crash giữa file publish và DB commit, retry cùng ID/hash; file cũ tương đương được chấp nhận. Lỗi transport giữ pending, ghi failed attempt, dừng batch để không spin liên tục.

Consumer inbox unique event_id và unique product_id/version. Inbox + projection + delivery audit trong cùng transaction. Event trùng/hash giống → duplicate; ID khác nội dung hoặc version trùng ID khác → quarantine, không ghi đè state. Aggregate version thấp hơn current → stale nhưng vẫn lưu inbox/audit; cao hơn → thay full snapshot. Unknown schema/type/field hoặc malformed JSON được ghi reason/hash trong quarantine, không gọi nguồn để tự đoán nội dung. Giá trị JSON không chuyển thành PHP serialized object.

Thời điểm crash được kiểm chứng: trước effect, sau effect/trước commit, sau commit/trước acknowledge. Hai trường hợp đầu rollback cả inbox và projection; trường hợp cuối replay không tăng apply_count. Đảm bảo này chỉ áp dụng cho effect DB projection cùng transaction và event identity được giữ. Không hứa exactly-once delivery, SMTP hoặc provider side effects. Chọn Reporting để không phải giải quyết SMTP idempotency trong P8.

## Chạy lại an toàn

Chạy từ repo root; PHP bên dưới là executable 8.3.30 đã cấu hình trong PATH. Trên máy hiện tại có thể dùng `& 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'` thay `php`.

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
```

Benchmark tạo/seed 1.000 sản phẩm × 3 SKU × 5 kho. So cùng 20 card fields bằng eager-loaded monolith và PDO projection; 30 mẫu đọc/ghi, không đo HTTP, uploads hoặc tải đồng thời. Chế độ ghi mặc định và outbox dùng cùng input shape; báo ORM query riêng và MySQL statement count (có transaction và PDO guards). Bootstrap snapshot tổng hợp 1.000 products có khóa được loại khỏi sample ghi.

30 event sinh trong lúc consumer offline + 1.000 snapshot bootstrap → 1.030 event. Relay và consumer chạy subprocess, paging theo 1.000 file trên tập transport đã dừng publish; đối chiếu toàn bộ inbox count, 1.000 card rows và hash trang đọc. Thời gian catch-up gồm nhiều subprocess/batch, không phải event latency p95. Script dọn đúng sandbox/accounts/spool đã tạo, artifact chỉ giữ counts/hash/timing, không credentials. `record-p8-verification.php` cần JUnit đủ suite và private P6 receipt, chỉ đọc nguồn để so cột gốc và chứng minh không có P8 tables/migration nguồn mới.

## Offline, poison và replay

Không cần bật task/daemon: worker CLI hữu hạn `relay|consume`, helper truyền JSON qua stdin. Limit tối đa 1.000. File đã consume vẫn được giữ để replay, nên chạy lại sẽ tạo duplicate audit. Với backlog lớn hơn limit, freeze relay hoặc chụp danh sách file ổn định trước khi paging bằng offset; UUID filename không phải thứ tự commit và offset không phải durable watermark khi có insert đồng thời. Không chạy mặc định chỉ trang đầu rồi kết luận đã drain toàn backlog.

Quan sát producer: số unpublished, attempts, audit failed/replay; consumer: inbox count, quarantine reason/attempts, audit outcomes, last_event_id/aggregate_version theo từng product. Watermark là version theo aggregate + inbox IDs; không dùng MAX(UUID) hoặc một timestamp làm chứng cứ đủ event. Đối chiếu version mới nhất của từng producer stream với projection sau khi drain. Có thể thiếu một version trung gian nhưng snapshot mới nhất vẫn hợp lệ; inbox giữ bằng chứng event đã nhận.

Poison schema/type/identity/malformed JSON: giữ file và event gốc, xem reason/hash, sửa consumer/schema sau review rồi replay; không thay event body dưới ID cũ. File quá lớn/symlink hoặc lỗi I/O bị từ chối và batch fail closed; cần kiểm tra transport trước khi chạy lại. Không purge outbox chỉ vì published_at khác null. Không prune inbox khi còn khả năng replay chưa có policy. Audit không thể ghi một attempt bị hard-kill trước DB commit; lúc đó pending outbox + file và test checkpoint là bằng chứng cửa sổ phục hồi.

`Relay::replay(eventId)` chỉ reset published_at cho event có sẵn và append replay_requested audit, giữ ID/version. Không tạo lại event ID để retry. Không tự bỏ quarantine hoặc dispatch email. Retention và garbage collection chưa được chốt; thử nghiệm giữ toàn bộ và chỉ cleanup tài nguyên tổng hợp sau drill.

## Rollback và quyết định

Mặc định reader production chưa chuyển sang projection, vì vậy rollback thử nghiệm không cần rollback API/schema ứng dụng. Dừng consumer độc lập trước, ghi inbox/stream watermark; nếu cần chạy consumer trong monolith, dùng cùng `ProjectionConsumer` trên **cùng consumer DB/inbox và cùng event IDs**, không hai consumer side effect khác semantics. Tests đã dùng cả consumer in-process và process độc lập trên cùng ledger, replay được nhận là duplicate/stale.

Tắt opt-in trong sandbox để source trở về no-op; monolith vẫn lưu bình thường ngay cả khi không có experimental schema/worker. Giữ outbox chưa xử lý, file spool/inbox cho replay nếu muốn tiếp tục. Không migration down, restore DB nguồn, reset --hard hoặc xóa dữ liệu lịch sử. Schema P8 nằm ngoài default Laravel migrations; chỉ drop schema/account/spool **do chính fixture tạo**, không áp cleanup lên database bất kỳ.

Quyết định hiện tại: giữ Modular Monolith cho ứng dụng; giữ prototype trên nhánh thử để học outbox/inbox/replay. Query đọc hẹp giảm nhưng source write nhiều statement hơn, thêm hai role/schema ownership, transport/audit/storage và eventual consistency. Nếu cần read model thực tế, trước hết cân nhắc projection nội bộ monolith. Chưa có lợi ích đủ chứng minh service production riêng. Event coverage archive/restore/raw updates, network transport/auth, deployment, retention, load/host failure và SLO thật **chưa xác minh**; phải được thiết kế/duyệt trước một thử nghiệm tiếp theo.
