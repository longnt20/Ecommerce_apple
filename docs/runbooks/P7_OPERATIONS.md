# P7 — Kiểm chứng hiệu năng và phục hồi

Phạm vi đã được duyệt: Laravel/PHP/MySQL hiện có, dữ liệu tổng hợp, worker/scheduler trên sandbox. Giữ task Windows `AppleProject-LaravelScheduler` Disabled. Không deploy, gửi mail, gọi provider, purge Telescope hoặc sửa DB nghiệp vụ trong P7.

## Chạy lại kiểm chứng

Chạy từ repository root với PHP 8.3 có `pdo_mysql`; MySQL 8.0.30 và MySQL clients đã có sẵn. `.env` chỉ cung cấp kết nối quản trị MySQL local cho `DatabaseSandbox` tạo DB ngẫu nhiên allowlisted; `.env.testing` cung cấp tài khoản chỉ DML trên `apple_project_p0_tests`. Không dùng tài khoản ứng dụng làm kết nối PHPUnit mặc định. Không chia sẻ hai file này.

PowerShell, dùng đúng executable đang có:

```powershell
$php = 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'
$env:P1_SCHEMA_TESTS='1'
$env:P2_SCHEMA_TESTS='1'
$env:P3_SCHEMA_TESTS='1'
$env:P4_SCHEMA_TESTS='1'
$env:P6_SCHEMA_TESTS='1'
$env:P7_SCHEMA_TESTS='1'
& $php vendor/bin/phpunit --log-junit tests/.runtime/p7-full-junit.xml
& $php tests/Support/p7-lint.php
node --test tests/Frontend/p2-security.test.mjs tests/Frontend/p4-checkout.test.mjs
npm run build --prefix frontend
```

Nếu `.env.testing` chưa được provision, làm theo runbook P0/P1 trước; không nạp dump thật vào test. `prepare-ci.php` chỉ dành cho workspace Linux GitHub Actions mới, từ chối env đã tồn tại; không chạy để thay cấu hình local. `prepare-p6-sandbox.php --sandbox-only` chỉ chuẩn bị schema test, không phải rollout ứng dụng.

Benchmark sau tối ưu:

```powershell
& $php tests/Support/benchmark-p7.php after
& $php tests/Support/p7-query-plans.php
& $php tests/Support/p7-lock-wait.php
& $php tests/Support/record-p7-verification.php
```

`record-p7-verification.php` cần JUnit đủ suite và receipt P6 riêng tư sẵn có; so sánh read-only những cột gốc, không sửa nguồn. Lệnh benchmark ghi đè **artifact after**, không thay baseline before. Chỉ đo `before` trên code P6 `092c02e` với cùng harness/fixture khi cần tái lập; không chạy `before` trên code đã tối ưu rồi gọi đó là baseline. Các harness dùng stdin/environment cho credentials, không ghi credentials vào artifacts public. `tests/.runtime/` luôn ignored.

## Workload và ngưỡng

- Detail/home: 1.000 sản phẩm × 3 SKU × 5 kho; 15.000 dòng tồn. Cache array lạnh và scope request mới mỗi lượt; 30 mẫu/controller, có SQL/serialize, không gồm boot/seeding/HTTP/network.
- CSV: 6.667 sản phẩm, 20.001 SKU, 100.000 dòng tồn; 3 lượt trong subprocess mới, output sink theo chunk không tích lũy response. `memory_reset_peak_usage` trước từng lượt; báo peak tuyệt đối, gồm bộ nhớ nền PHP/app.
- Đã duyệt: detail ≤8 query, home ≤5 query, CSV ≤64 MiB/subprocess; chunk 1.000. Test 12 SKU chứng minh detail không tăng query theo SKU. Fixture promotion rỗng; không suy rộng query budget home sang mọi đồ thị promotion tùy ý.
- p95 hiện là số liệu chẩn đoán local, **không phải SLO production**; chưa có tải đồng thời HTTP, nhiều app node hoặc network/SMTP thực.
- Lock probe có hai PDO thật, giữ một dòng tồn tới 500 ms sau child READY, child SELECT FOR UPDATE rồi rollback. Năm mẫu chỉ chứng minh quan sát được chờ khóa có kiểm soát; không phải baseline contention thực tế hoặc ngưỡng SLO.

CSV dùng keyset PK + high-water mark ID để download kết thúc dù có insert mới. Không giữ transaction dài. Dữ liệu là ảnh đọc theo từng chunk, không hứa snapshot tài chính tại cùng thời điểm khi các giao dịch thay đổi số dư. Query tăng từ 4 lên 102 trên 100.000 dòng để đổi lấy bộ nhớ giới hạn. Cột/header/filename/route giữ nguyên; export hiện dùng cùng warehouse/status/search với listing. Text bắt đầu bằng ký tự công thức được thêm apostrophe để spreadsheet không thực thi.

## Cache

`CatalogReadCache` chỉ cho dữ liệu hiển thị: home, danh sách category admin, nhãn attributes. Không cache giá checkout hoặc tồn authoritative. Model observers chạy sau outer commit; query bulk reorder banner gọi invalidation sau commit rõ ràng. Bên trong transaction bỏ shared cache để không phát tán dữ liệu chưa commit hoặc snapshot cũ. Generation key ngăn callback đang chạy ghi lại giá trị cũ vào generation mới; generation cũ tự hết TTL 60/300/3.600 giây.

Cache helper bind scoped theo request/job, memoize generation và từng family; test database cache xác nhận 50 lần đọc nhãn tiếp theo không thêm query. Request kế tiếp nạp generation mới để thấy commit từ process khác. Bulk SQL/pivot không phát Eloquent events: khi bổ sung đường ghi mới phải gọi invalidation trong transaction hoặc sau thành công. Banner store sync hiện nằm trong cùng transaction với model save. Truy cập cache database có thêm chi phí SQL so với array; benchmark đã nêu rõ store, chưa đo tải nhiều node. Không coi display cache là hàng rào nhất quán nghiệp vụ. Không chỉ xóa generation key riêng lẻ; nếu cần làm sạch, dùng cơ chế cache invalidation của ứng dụng và lưu ý `cache:clear` còn ảnh hưởng locks/idempotency Google trong shared store.

## Worker, retry và scheduler

Diễn tập thực tế nằm trong `tests/Integration/P7/RecoveryTest.php`. `P7Runtime` tạo config/routes/events/services/packages cache riêng dưới `tests/.runtime/p7-<random>-*`; kết nối duy nhất vào sandbox, mail array, Telescope tắt, VNPay tắt. Các bước đã chạy:

1. Database queue dispatch trong transaction: rollback không enqueue, commit mới enqueue.
2. `queue:work database --once --tries=1 --sleep=0 --timeout=60` thất bại có kiểm soát → có `failed_jobs` → `queue:retry <uuid>` → worker nhận lại thành công.
3. Kill worker sau durable effect, trước acknowledge; chỉ chỉnh đồng hồ visibility của **job tổng hợp** quá 90 giây rồi retry. Một effect/identity, job được xóa khi thành công. Đây là probe cơ chế queue; không chứng minh mọi side effect ngoài DB có exactly-once.
4. `config:cache`, `route:cache`, `schedule:run` thật. Một hold tổng hợp quá hạn được hủy; chạy scheduler lần nữa không phát sinh movement thứ hai. `inventory:reconcile` exit 0.
5. `route:clear`, `config:clear` trên đường dẫn sandbox, rồi reconcile lại: rehearsal rollback release cache. Không chạm `bootstrap/cache` của ứng dụng đang dùng.

Database queue `retry_after=90`; coupon jobs tối đa 3 attempts, timeout 60, backoff 10/30/60. Khi cấu hình worker thật, timeout phải nhỏ hơn retry_after. PHP Windows không có `pcntl`; `--timeout` không thay thế cơ chế giám sát/kill của hệ điều hành. Chưa thiết lập supervisor/watchdog Windows trong P7. Notification email vẫn at-least-once; crash sau SMTP acceptance có thể gửi lặp. P6 delivery ledger hỗ trợ retry khoảng trống commit/enqueue; chưa thay bằng outbox P8.

Task thật vẫn Disabled theo yêu cầu. Sandbox chạy `schedule:run` thủ công chứng minh command/mutex/database logic, không chứng minh Windows Task Scheduler tự khởi động theo phút hoặc worker production luôn sống. Không bật task hay worker thật trước review cấu hình, backlog, SMTP và tài khoản chạy.

`withoutOverlapping()` hiện giữ mặc định mutex tối đa 24 giờ nếu scheduler process bị kill trước cleanup. Khi có backlog và lock mồ côi, xác minh process cũ đã dừng, xem schedule/lock trong đúng môi trường rồi mới dùng `schedule:clear-cache` để bỏ mutex scheduler. Không `cache:clear` toàn store để xử lý một task và không bỏ lock khi process vẫn chạy. P7 chưa diễn tập kill scheduler giữa command; retry/kill đã diễn tập là queue worker.

## Chẩn đoán và dữ liệu nhạy cảm

`php artisan operations:health` là CLI read-only trả số jobs/reserved/failed, tuổi job cũ nhất, pending notices, overdue active holds và open discrepancies; không trả payload/customer. Đây là metrics snapshot, không tự gửi cảnh báo hoặc sửa dữ liệu. Với DB ứng dụng chỉ chạy chẩn đoán sau khi tắt Telescope trong process nếu cần tránh telemetry phát sinh. `inventory:reconcile` cũng read-only nhưng hiện nạp dữ liệu/đọc movement theo SKU; cân nhắc lịch chạy thủ công khi lượng lịch sử lớn, chưa có SLO cho báo cáo này.

Xử lý: failed jobs tăng → xem loại lỗi và operation identity với quyền phù hợp → sửa nguyên nhân → retry UUID cụ thể. Overdue active holds tăng → kiểm tra scheduler/mutex/payment reconciliation; không release bằng SQL. Pending notices tăng → kiểm tra queue và `coupons:retry-notifications`. Open discrepancy không được che bằng timeout tuning hay sửa số dư trực tiếp.

Monolog channels cấu hình có processor redaction: bỏ payload/bindings/credentials, tóm tắt Throwable bằng class/code/file/line, loại SQL exception message, che email/bearer/token; giữ order_id/reason_code/request_id. `LoggableTrait` nhận Throwable, không ghi raw request/error message. Không ghi PII tự do vào log message; redaction không phải bộ phát hiện mọi dữ liệu nhạy cảm có thể có. Không dùng channel tự tạo thiếu tap. `failed_jobs` và dữ liệu Telescope cũ vẫn là dữ liệu DB nhạy cảm, chỉ người vận hành có quyền mới đọc; không upload vào CI artifacts.

Telescope mặc định opt-in, watchers mặc định tắt; `auth` + active `is_admin` bảo vệ dashboard cả local. Không purge lịch sử tự động; lượng dump Telescope lớn đã ghi ở audit cần retention policy riêng. `.env` explicit có thể ghi đè default: kiểm tra trước vận hành. P7 không đổi env hoặc tự bật watcher/prune.

## Backup/restore và release

`P7RestoreDrill` dùng MySQL clients có sẵn: mysqldump single-transaction/full tables → DB `apple_project_p1_restore_<random>` mới → so hash tất cả 56 bảng, schema/FK, anti-join kiểm tra từng FK và reconcile. Fixture có order/hold/confirm/shipping/movements để kiểm tra tồn vật lý 10→9. Backup tổng hợp chỉ trong ignored runtime và xóa sau drill; DB sandbox được drop bởi instance tạo ra. Baseline schema fixture có 50 metadata migrations, nạp tiếp DDL 6 repair/lifecycle; test P1 riêng kiểm chứng native migrate đủ 56. Không dùng drill này làm bằng chứng đã backup production trong P7. Backup/restore nguồn thật trước rollout P6 nằm ở [P6](../roadmap/P6_STATUS.md).

Release thật sau review: giữ bản code/config trước, kiểm tra env/gates, chạy các check nêu trên, chuẩn bị backup và cửa sổ thay đổi, rebuild config/routes cache theo môi trường đích, restart worker để nạp code mới, smoke API/admin/export/reconcile và theo dõi backlog. P7 không thêm migration nên không chạy migrate/down để triển khai tối ưu này. Build `frontend/dist/` hiện là output local untracked, không tự commit.

Rollback: lưu diff và các file mới; khôi phục riêng thay đổi P7 về code P6 an toàn `092c02e`, tạo lại config/routes cache tương ứng và restart worker sau khi được duyệt vận hành. Giữ dữ liệu/jobs/movements. Không `reset --hard`, migrate:fresh, migration down hoặc restore snapshot cũ lên DB đang có đơn mới. Nếu chỉ tắt cache tối ưu, không sửa protocol giá/tồn/payment. Khôi phục CSV cũ có thể tăng RAM; ưu tiên roll forward streaming khi lỗi chỉ nằm ở một filter.

## CI và giới hạn kiểm chứng

Workflow [quality.yml](../../.github/workflows/quality.yml) dùng service MySQL cùng engine 8.0.30 trên runner Ubuntu; không thêm Docker local. `.env` CI là marker schema rỗng, tài khoản DML test riêng, tất cả P1/P2/P3/P4/P6/P7 schema/race/recovery/budget tests bật, không `continue-on-error`. Install từ lock, chạy Pint riêng file P7, frontend contract/build. Không upload env, SQL backup hoặc private runtime. Actions pin commit SHA, quyền contents read; không deploy.

Đã đối chiếu [GitHub service containers](https://docs.github.com/en/actions/tutorials/use-containerized-services/use-docker-service-containers), [checkout](https://github.com/actions/checkout), [setup-node](https://github.com/actions/setup-node), [setup-php](https://github.com/shivammathur/setup-php). Workflow parse/policy được kiểm tra local; chưa chạy remote, chưa xác minh runner/package download/branch protection thực tế. MySQL/PHP pin theo baseline hiện tại để tái lập, không phải kết luận runtime đã cập nhật bảo mật. Cập nhật runtime là công việc riêng sau review compatibility/security.
