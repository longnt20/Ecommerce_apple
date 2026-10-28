# P0 — Baseline và môi trường kiểm chứng

Ngày ghi nhận: 2026-10-28. **P0 đã hoàn thành kiểm chứng và được chủ dự án cho phép chuyển sang P1.** Dùng kết nối quản trị trong `.env` để tạo sandbox schema-only và tài khoản test DML riêng; không chạy regression ghi dữ liệu trên DB ứng dụng. Các đoạn nêu blocker ban đầu bên dưới là lịch sử triển khai và được cập nhật bởi phần đóng P0 này.

## Cập nhật đóng P0

- `.env.testing` ignored đã được cấu hình tự động với mật khẩu ngẫu nhiên cho `apple_project_p0_test`; `.env` gốc không sửa. `apple_project_p0_tests` chỉ chứa schema và fixture tổng hợp; không copy users/orders/tokens/log thật.
- Preflight account/schema/grants thực tế đã chạy trước mỗi lần boot test. COD checkout, ownership order và rollback fixture đã chạy thành công trên MySQL 8.0.30.
- Suite P0 khi bật MySQL: **32 tests, 90 assertions, 1 skipped có chủ đích cho nhánh DB-disabled, 4 incomplete**. Bốn incomplete là C1/C2/C3/H2 đã tái hiện thật, có owner P2/P4, không còn bị chặn do thiếu DB.
- Chế độ không DB chạy riêng: **27 tests, 71 assertions, không skip/error/failure** (`P0_DB_MODE=disabled`, `--testsuite Unit,Feature`).
- Chủ dự án yêu cầu hoàn tất P0 bằng cấu hình `.env` và tiến hành P1; Q1 là bảo toàn dữ liệu, Q2/Q4 đã xác nhận, các chi tiết nghiệp vụ chưa rõ vẫn là blocker của giai đoạn phụ thuộc.
- Chỉ hoàn tất mục tiêu baseline/isolation. C1/C2/C3/H2 vẫn là lỗ hổng hiện có, không được sửa trong P0/P1.

Theo dõi triển khai tiếp theo: [P1_STATUS](P1_STATUS.md).

## 1. Phân tích và kế hoạch thay đổi

Không tìm thấy AGENTS.md trong repository hoặc các thư mục tổ tiên đã kiểm tra. Đã đọc UPGRADE_PLAN, current/target architecture, ADRs và SQL analysis; kiểm tra thực tế TestCase/PHPUnit/bootstrap, providers, database config, routes, Cart/Inventory/Order models, Checkout/Cart/Order controllers và OrderService.

PHPUnit trước đây boot Laravel trực tiếp, không chọn DB test; có thể nạp `.env` đang dùng. Cache/session/queue mặc định có thể dùng DB, Telescope có provider. Laravel 11 hỗ trợ callback vào các bootstrap stages; PHP dotenv/PHPUnit/Pint đã có trong vendor, nên không cần dependency mới.

Kế hoạch đã thực hiện:

1. Tách test bootstrap, không nạp `.env` ứng dụng; fail-fast cấu hình MySQL test theo allowlist.
2. Chặn DB mặc định, dùng adapters trong bộ nhớ/fakes trước BootProviders; tách test caches.
3. Thêm fixture tổng hợp, unit/HTTP contract tests và suite integration opt-in có rollback.
4. Đọc runtime bằng PDO riêng chỉ SELECT/SHOW và transaction READ ONLY; kiểm kê routes mà không gọi endpoint.
5. Ghi bằng chứng, quyết định nghiệp vụ và blockers. Không sửa API hoặc nghiệp vụ.

## 2. Bằng chứng đã xác minh

- PHP CLI dùng để chạy: `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe` (8.3.30), PHPUnit 10.5.48, Laravel theo lock 11.45.1.
- MySQL từ `.env`: 8.0.30; isolation REPEATABLE-READ; session SQL mode STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION; 40 bảng InnoDB; 50 migrations. Inventory 184, movements 259, orders 52, items 56.
- Cột role/blocked/thumbnail/commit tồn tại trong metadata runtime được đọc. Không suy ra toàn bộ schema giống dump chỉ từ các selected columns và row counts.
- Probe dùng `START TRANSACTION READ ONLY` rồi rollback; `session_default_transaction_read_only=0` là default của session, không phải access mode của transaction vừa yêu cầu. Không thử DML để kiểm tra read-only và không chạy DDL.
- [Runtime JSON](../architecture/p0-runtime-baseline.json) chứa metadata không có password/token/user PII. Probe đọc `.env` riêng, không sử dụng Laravel config cache; cấu hình deployment ngoài endpoint này chưa xác minh.
- [Route inventory](../architecture/p0-route-baseline.md): 188 entries, 40 controller action thiếu public method. Kiểm kê bằng route collection và reflection; method tồn tại chưa có nghĩa endpoint chạy đúng. Không gọi action missing để thử.
- Hai migration blocks trong dump đến từ package Laraberg đã tìm thấy trong vendor. Bốn tên migration promotions cũ chưa có file trong migrations ứng dụng.

### Contract quan trọng trong baseline

| Endpoint | Input/output hiện tại | Kiểm chứng P0 |
| --- | --- | --- |
| GET / | JSON message/version/frontend/endpoints | HTTP test chạy |
| POST /api/checkout | auth:sanctum; payment_method cod/vnpay; items product_id/variant_id/quantity/price; thông tin nhận hàng; COD trả message/order_id/final_amount; VNPay trả payment_url/order_id | Auth/422 chạy; success DB còn skipped. Giá client và behavior hiện tại chưa sửa |
| GET /api/orders | auth:sanctum; status/from_date/to_date; response data là paginator, 10 dòng/trang | Route/auth chạy; query ownership/pagination success DB còn skipped |
| GET /api/orders/{id} | auth:sanctum; response data order với items; chỉ truy vấn user hiện tại | Route/auth chạy; ownership success DB còn skipped |
| GET /api/cart | auth:sanctum; response items/total_price/total_quantity | Auth chạy; behavior tổng tiền có model test |
| PUT/DELETE /api/cart/item/{item} | auth:sanctum; PUT quantity integer min 1; response giỏ hiện tại | H2 ownership regression chuẩn bị, chưa chạy DB |
| GET /api/vnpay/return và /vnpay/return | Public; query vnp_TxnRef/vnp_Amount/vnp_ResponseCode/vnp_TransactionNo; redirect 302; hiện có cập nhật paid không xác minh chữ ký | Route metadata chạy; C2 regression DB còn skipped |

Route có method không đồng nghĩa code path chạy được. Frontend services còn khai báo refresh-token, cart apply-coupon/sync, product listing/search và order payment/status endpoints không có implementation tương ứng trong routes đã audit. Root API info vẫn quảng bá /api/products dù không có handler listing; P0 bảo toàn response đó và ghi nhận mismatch, không tự bổ sung API.

Nguồn drift và dữ liệu cần đối soát: [database-export-analysis](../architecture/database-export-analysis.md). P0 không sửa schema, tổng tiền hoặc reservation lịch sử.

## 3. Thiết kế test isolation

- `tests/bootstrap.php` cài môi trường trước Laravel; chỉ parse `.env.testing`, không fallback `.env`.
- Biến APP/DB/cache của ứng dụng bị thay bằng cấu hình test an toàn; DB_URL và credentials ứng dụng không được thừa kế. Config/routes/events cache riêng nếu tồn tại sẽ bị từ chối; services/packages manifests nằm trong `tests/.runtime`, được gitignore.
- Trước RegisterProviders, cấu hình database bị thay hoàn toàn: default mode không có connection nào, kể cả explicit mysql. Cache/session dùng array; Telescope tắt; log dùng null.
- Trước BootProviders, Bus/Queue/Mail/Notification được fake; Laravel HTTP client chặn stray requests. Điều này không phải firewall cho mọi thư viện: PHP streams/Guzzle/SDK trực tiếp chưa được chặn toàn cục. Suite mặc định không gọi geography/OAuth/AI hoặc payment network actions.
- MySQL opt-in chỉ nhận host loopback, DB `apple_project_p0_tests`, user `apple_project_p0_test`, password không rỗng. PDO preflight trước Laravel kiểm tra resolved account/database và SHOW GRANTS: chỉ USAGE global và SELECT/INSERT/UPDATE/DELETE trên schema test; từ chối role/global/cross-schema/DDL/grant option.
- Preflight yêu cầu các bảng fixture đã có và đều InnoDB, không FK sang schema khác. Không tạo DB/user, cấp quyền, import schema hay migrate tự động.
- MySQL tests bọc transaction ngoài rồi rollback khi teardown; nested checkout commit không commit transaction ngoài. Fixtures có IDs cố định và dữ liệu tổng hợp `example.test`, không copy dump. Schema test cần được chuẩn bị riêng, không có triggers/routines/events gây side effects ngoài ý muốn; AUTO_INCREMENT trên DB test có thể tăng dù rollback.

Patterns sử dụng: test fixture builder, characterization/contract tests và test doubles qua Laravel Fakes. Guard là cơ chế fail-fast ở test boundary, không thêm abstraction vào ứng dụng và chưa triển khai Clean Architecture/module refactor ở P0.

## 4. Tests và phạm vi ý nghĩa

| Nhóm | Kiểm chứng | Giới hạn |
| --- | --- | --- |
| TestingEnvironmentTest/MySqlSafetyTest | Từ chối DB ứng dụng/root/remote/URL injection/unsafe grants; scrub inherited app DB env | Grant parser được unit test; chưa verify tài khoản MySQL test thật |
| CommerceBaselineTest | Fixture hai actors/hai warehouse balances; cart quantity/price theo behavior hiện tại | Model chưa persist, không chứng minh locks/SQL constraints |
| ApiContractTest | Root JSON; auth guard cart/orders/checkout; checkout input rỗng trả 422 | Chưa chạy checkout thành công trên DB |
| RouteContractTest | Method/action/auth middleware checkout/orders; hai VNPay return hiện public | Giữ public route không phải xác nhận callback an toàn |
| IsolationTest | Env/cache/session/fakes; explicit mysql không truy cập được ở default mode | Không kiểm thử firewall cho mọi SDK |
| CommerceContractTest (MySQL) | COD checkout/order ownership/stock reservation | SKIPPED do chưa có DB test/account |
| KnownRisksTest (MySQL) | C1 giá 0, C2 return không chữ ký, C3 giữ kho khác order, H2 update cart người khác | SKIPPED; nếu tái hiện sẽ INCOMPLETE kèm ID/owner P2/P4, không báo security pass |

Integration tests cần schema tương thích code hiện tại. Nếu schema chưa đủ, phải ghi blocker/failed setup; không tự sửa migration trong P0. Các test known-risk mô tả lỗi hiện tại, sau khi sửa ở giai đoạn tương ứng phải đổi thành assertions bảo vệ hành vi an toàn.

## 5. Kết quả kiểm tra

Lệnh PowerShell (đường dẫn PHP có thể thay theo máy):

**Entry point được kiểm chứng là chạy PHPUnit trực tiếp.** Chưa dùng `php artisan test`: Artisan boot ứng dụng trước khi test bootstrap chạy, nên guard P0 không bảo vệ được giai đoạn boot đầu tiên đó. Không chạy lệnh này với `.env` ứng dụng để thử khả năng isolation. Chưa thay entry point Artisan vì nằm ngoài thay đổi test harness tối thiểu.

```powershell
$p0Php = 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'
& $p0Php vendor/phpunit/phpunit/phpunit --do-not-cache-result
& $p0Php vendor/bin/pint --test tests
& $p0Php tests/Support/export-route-baseline.php
& $p0Php tests/Support/inspect-application-database.php --application-read-only
```

- PHPUnit: **32 tests, 71 assertions, 5 skipped**, exit 0. Không gọi đó là toàn bộ integration xanh.
- `php vendor/bin/pint --test tests`: PASS, 19 files.
- `php -l` cho từng file PHP trong tests, bỏ qua tests/.runtime: 19 files, 0 syntax errors.
- `git -c safe.directory=F:/laragon/www/Apple-Project diff --check -- .gitignore phpunit.xml tests docs`: exit 0. safe.directory chỉ áp dụng cho lệnh, không sửa Git config toàn cục. Kiểm tra link tài liệu mới được thực hiện riêng vì file mới chưa tracked không thuộc diff --check.
- Route exporter: 188 entries, 40 missing; không sửa các route đó ở P0.
- Read-only probe: thành công; kết quả metadata ở JSON baseline.
- Build frontend không áp dụng: P0 không sửa assets/frontend/dependencies. Có các thay đổi frontend đã tồn tại trước tác vụ; không ghi đè hoặc chạy build tạo artifacts lên chúng.

## 6. Quyết định và blockers

| Nội dung | Trạng thái |
| --- | --- |
| Q1 dữ liệu cần bảo toàn | Mặc định bảo toàn; đã có export và live metadata, chưa phê duyệt xóa dữ liệu |
| Q2 cost_price | Chủ dự án xác nhận là **giá vốn**; đơn vị/rounding/phí/discount còn mở |
| Q3 một kho/đơn hay chia kho | Chưa chốt; blocker cho P4 allocation |
| Q4 thời điểm trừ vật lý | Chủ dự án xác nhận **khi xuất/giao cho vận chuyển**; code hiện tại vẫn trừ tại confirm, cần sửa giai đoạn sau |
| Q5 TTL/COD/refund/return/callback muộn | Chưa chốt; blocker cho lifecycle tương ứng |
| Q6 quyền kho/retention | Chưa chốt; giữ ownership boundary hiện tại trong baseline |
| MySQL DB test/account | Đã tạo/xác minh bằng quyền quản trị từ .env; test runner chỉ dùng account DML riêng, không dùng tài khoản ứng dụng |

Không sửa các lỗ hổng C1/C2/C3/H2, drift migrations, sai tổng tiền hoặc reservation tại P0. Cụ thể cost_price là giá vốn làm tăng mức chắc chắn của phát hiện giá bán/giá vốn nhập nhằng; không tự sửa dữ liệu giá khi chưa thực hiện P2/P6.

**Diễn giải lại dữ liệu reservation sau Q4:** 15 đơn confirmed còn net reserve có thể đúng với quy trình đích chỉ commit lúc bàn giao. Báo cáo SQL trước đó đối chiếu với code hiện tại commit tại confirm; không đủ căn cứ để tự coi mọi confirmed reservation là tồn sai. Ba đơn cancelled còn reserve và các đơn shipping/completed cần đối soát trạng thái thực, movement và thời điểm bàn giao. Không tự trừ/hoàn 22 đơn vị từ thống kê lịch sử.

## 7. Acceptance checklist

- [x] Suite mặc định không truy cập DB ứng dụng; boot không nạp `.env`; các kết nối Laravel bị chặn trước providers.
- [x] Schema drift được dẫn chứng; runtime metadata và routes có baseline tái tạo được.
- [x] Fixture tổng hợp và unit/HTTP baseline chạy được, không gửi mail/job/network từ các luồng được test.
- [x] Critical/High có integration tests được ghi nhận ID/owner; tình trạng chưa chạy do DB test được báo rõ.
- [x] Q2/Q4 ghi nhận theo xác nhận của chủ dự án; các phần chưa rõ được ghi blocker.
- [x] Chứng minh database MySQL test cách ly bằng credential giới hạn và chạy integration suite thực tế.
- [x] Chủ dự án yêu cầu hoàn tất P0 và cho phép chuyển sang P1; baseline và kết quả thực tế đã được bàn giao.

Hai mục cuối đã được đáp ứng trong lượt triển khai tiếp theo; **P0 đã đóng**. Không coi các ca lỗi known-risk incomplete là security pass.

## 8. Kiểm tra thủ công

1. Chạy PHPUnit mặc định: không cần MySQL test và không nạp `.env`. Kiểm tra 5 skips được nêu rõ.
2. Xem JSON runtime/route Markdown, đối chiếu với source. Có thể chạy probe riêng khi có quyền đọc DB ứng dụng; không chạy `artisan migrate`/`migrate:fresh`.
3. Khi có schema và tài khoản test riêng được chuẩn bị/phê duyệt: copy `.env.testing.example` sang `.env.testing`, điền password local, đặt P0_DB_MODE=mysql. Không gửi password qua chat hoặc commit file này.
4. Chỉ dùng schema fixture tổng hợp, không import dump chứa dữ liệu thật. Chạy `--testsuite Integration`; preflight phải từ chối nếu user có quyền global/root hoặc schema thiếu bảng InnoDB. Không cấp DDL cho test runner để vượt preflight.
5. Kiểm tra rollback không để lại fixture rows; xem known-risk tests INCOMPLETE nếu lỗi vẫn tái hiện. Ghi kết quả thực tế rồi mới đề nghị đóng P0.

## 9. Rollback thay đổi P0

Không có app source/schema/data migration để rollback. Khôi phục riêng thay đổi P0 ở `.gitignore`, `phpunit.xml`, `tests/TestCase.php`; bỏ các file test/support/fixture/sample và tài liệu P0 mới theo danh sách bàn giao nếu muốn. Giữ tài liệu audit/roadmap có từ trước; revert chỉ các đoạn bổ sung P0 khi cần. Không dùng reset/clean toàn repository vì đang có thay đổi frontend và docs ngoài tác vụ.

Có thể xóa riêng cache ignored `tests/.runtime` sau khi kiểm tra đường dẫn nằm trong workspace; không xóa bootstrap/cache của ứng dụng. Không xóa DB hoặc account khi rollback code. Nếu đã bật integration sau này, mọi fixture chạy trong transaction nhưng cần xác minh state DB test và AUTO_INCREMENT riêng. Cân nhắc giữ guard vì bỏ bootstrap bảo vệ sẽ khôi phục rủi ro nạp DB ứng dụng trong test.

## 10. Danh sách file trong thay đổi P0

File hiện có được sửa:

```text
.gitignore
phpunit.xml
tests/TestCase.php
docs/architecture/current-architecture.md
docs/architecture/target-architecture.md
docs/architecture/architecture-decisions.md
docs/architecture/database-export-analysis.md
docs/roadmap/UPGRADE_PLAN.md
```

File mới:

```text
.env.testing.example
tests/bootstrap.php
tests/Support/TestingEnvironment.php
tests/Support/TestApplication.php
tests/Support/MySqlSafety.php
tests/Support/MySqlTestCase.php
tests/Support/CommerceFixture.php
tests/Support/inspect-application-database.php
tests/Support/export-route-baseline.php
tests/Unit/TestingEnvironmentTest.php
tests/Unit/MySqlSafetyTest.php
tests/Unit/CommerceBaselineTest.php
tests/Feature/P0/IsolationTest.php
tests/Feature/P0/ApiContractTest.php
tests/Feature/P0/RouteContractTest.php
tests/Integration/P0/CommerceContractTest.php
tests/Integration/P0/KnownRisksTest.php
docs/architecture/p0-runtime-baseline.json
docs/architecture/p0-route-baseline.md
docs/roadmap/P0_STATUS.md
```

Các ExampleTest có sẵn không sửa. Không có file app/routes/config/database/composer hoặc frontend nào được sửa bởi P0. Các tài liệu kiến trúc đã tồn tại từ tác vụ trước đang untracked trong Git; danh sách này chỉ xác định phần thay đổi của P0, không tự stage/commit chúng. `tests/.runtime` là cache phát sinh ignored, không phải deliverable.
