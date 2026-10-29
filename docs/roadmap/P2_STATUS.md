# P2 — Bảo vệ giá, thanh toán và quyền

Ngày: 2026-10-29. **Code P2 và kiểm chứng tự động đã xong; chờ review contract và phê duyệt rollout. Chưa đóng P2 ở môi trường ứng dụng. Dừng trước P3/P4.**

## Phân tích và thay đổi

Không tìm thấy AGENTS.md trong repository/phạm vi tổ tiên đã khảo sát. Đã đọc roadmap, current/target architecture, ADRs, P0/P1 status, baseline schema và source controllers/models/routes/providers/Vue liên quan.

Dependencies bị ảnh hưởng: cart/catalog/wishlist dùng cost_price như giá giảm; checkout gọi Inventory::reserve và snapshot OrderItem; return VNPay có hai URI; Google qua Socialite; token qua Sanctum; admin qua session/role; Telescope có thể ghi credential response. Không thêm package, không đổi lockfiles.

Các lát cắt đã thực hiện: quote/validation → policy/account/throttle → gateway/ledger/IPN → OAuth/redaction → frontend adapters → regression/schema/concurrency/docs.

- Server quyết định giá bán SKU; validation sellable/product ownership; snapshot VND nguyên có giới hạn, không tin price client. Tách buy-now/cart, không xóa cả giỏ.
- Cart owner policy, active-account gate, login throttle, admin session regeneration.
- Attempts/events durable, IPN verified + atomic + dedup; browser return chỉ đọc; retry URL cùng attempt; late/cancelled payment sang đối soát.
- OAuth state/handoff một lần gắn browser proof; giữ password cũ, không bearer trong URL hoặc auto-link unverified account.
- Correlation ID, lỗi chung/redacted global log, bỏ dd; Telescope bỏ entry các luồng credential nhạy cảm.
- Frontend đọc trạng thái thanh toán từ owned API order; catalog hiển thị giá bán thay giá vốn.

Thiết kế: FormRequest, QuoteService/Vnd, Policy, PaymentGateway adapter và application services có chọn lọc; row locks + transaction + unique identities cho idempotency. Không generic repository/SDK mới. PaymentService tạm import Order model để giữ local transaction; tách module/ports đầy đủ thuộc P5. Không gọi đây là Clean Architecture hoàn chỉnh.

Contract/API changes và rollout/rollback: [P2 HTTP contracts](../architecture/p2-http-contracts.md).

## File thay đổi

Application/config:

- `app/Http/Requests/CheckoutRequest.php`
- `app/Services/Pricing/{Vnd,QuoteService}.php`
- `app/Policies/CartItemPolicy.php`
- `app/Http/Middleware/{ActiveAccount,RequestId}.php`
- `app/Services/Payments/{PaymentGateway,VnPayGateway,PaymentService}.php`
- `app/Services/Identity/GoogleHandoff.php`, `app/Models/PaymentAttempt.php`
- `app/Http/Controllers/Api/{Checkout,Cart,Auth,GoogleAuth,Home,Promotion,Wishlist,Order,Banner}Controller.php`
- `app/Http/Controllers/AuthController.php`, `app/Http/Controllers/Admin/OrderController.php`
- `app/Providers/{AppServiceProvider,TelescopeServiceProvider}.php`
- `bootstrap/app.php`, `routes/{api,web}.php`, `config/{services,vnpay}.php`, `.env.example`
- `database/migrations/2026_10_29_000001_create_verified_payments.php`

Frontend:

- `frontend/src/services/{googleAuth,paymentResult}.js`
- `frontend/src/components/Cart/{CheckoutPage,PaymentSuccess}.vue`
- `frontend/src/components/status/LoginSuccess.vue`
- `frontend/src/components/{common/AuthModal,header/UserButton,Profile/ProfilePage}.vue`
- `frontend/src/pages/Home.vue`

Tests/support:

- `tests/Unit/VndTest.php`, `tests/Feature/P2/SecurityBoundaryTest.php`
- `tests/Integration/P2/{CheckoutSecurityTest,PaymentSecurityTest,GoogleSecurityTest,PaymentAtomicityTest}.php`
- `tests/Frontend/p2-security.test.mjs`
- `tests/Support/{prepare-p2-sandbox,inspect-p2-databases,payment-notify-worker}.php`
- `tests/Integration/P0/KnownRisksTest.php`: C1/C2/H2 thành safe regression, giữ C3 incomplete cho P4.
- `tests/Feature/P0/ApiContractTest.php`: isolate active middleware chỉ ở test FormRequest không DB; active gate thật được kiểm tra ở P2.
- `tests/Integration/P1/SchemaUpgradeTest.php`: fresh/restore hiện có 52 migration records; không sửa baseline/migration lịch sử.

Docs: file này, P2 contracts, append ADR/current/target architecture và status link trong roadmap. Không sửa `.env`, không commit/push.

## Database và dữ liệu

Chỉ tạo payment_attempts/payment_events/indexes/FKs mới; không đổi orders/inventory hoặc backfill dữ liệu thật. Migration chỉ áp dụng lên sandbox synthetic và sandbox disposable, chưa áp dụng DB ứng dụng.

Probe read-only `php tests/Support/inspect-p2-databases.php` ghi nhận:

| Chỉ số | DB ứng dụng | Sandbox synthetic sau tests |
| --- | ---: | ---: |
| Migrations | 51 | 52 |
| Bảng payment P2 | 0 | 2 |
| Users | 8 | 0 |
| Orders/items | 52 / 56 | 0 / 0 |
| Inventory/movements | 184 / 259 | 0 / 0 |
| Attempts/events | Chưa có bảng | 0 / 0 |

Counts ứng dụng khớp P1 đã ghi, không phải hash toàn DB/chứng minh mọi row không đổi. Không chạy DML/DDL ứng dụng. Account integration chỉ có DML trên DB test. Provision dùng `.env` để quản trị sandbox allowlist, từ chối tên DB ứng dụng. Sandbox random chỉ bị drop khi được tạo ở cùng invocation.

MySQL local ban đầu tắt nên test đầu lỗi 2002; đã khởi động MySQL có sẵn và rerun. Pint ban đầu báo not writable trong sandbox; chạy lại với quyền phù hợp thành công. Không đổi cấu hình server/cài package để chữa test.

## Acceptance và kết quả

- [x] Giá client không quyết định tiền; SKU mismatch/hidden/draft/deleted/duplicate/fraction/overflow bị chặn.
- [x] Cart owner update/delete; blocked token; login throttle.
- [x] OAuth giữ password, state/code expiry/replay và verifier sai bị chặn; không bearer trong callback URL.
- [x] Callback sai signature/amount/currency/merchant/reference, COD và duplicate query không paid.
- [x] Callback đúng một lần; hai process độc lập trả 00/02 và chỉ một event; event write failure rollback attempt/order.
- [x] Retry không tạo order/attempt/reservation mới; một provider transaction không pay hai order; late/cancelled sang đối soát.
- [x] JSON lỗi không lộ trace/secret dù debug; frontend không coi query URL là thanh toán thành công.
- [ ] Chủ dự án review contract/migration và duyệt rollout. DB ứng dụng chưa migration; online mặc định tắt.
- [ ] Merchant-specific HTTPS IPN, callback sandbox merchant và Google thật: **chưa xác minh**, không gọi provider thật trong test.

Các lệnh dùng PHP 8.3.30 tại `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe`; thay bằng php khi PATH đúng:

```powershell
php tests/Support/prepare-p2-sandbox.php --sandbox-only
$env:P1_SCHEMA_TESTS='1'; $env:P2_SCHEMA_TESTS='1'
php vendor/bin/phpunit
# Lượt không DB độc lập:
$env:P0_DB_MODE='disabled'
php vendor/bin/phpunit --testsuite Unit,Feature
node --test tests/Frontend/p2-security.test.mjs
# Từ thư mục frontend:
npm run build
# Chỉ các file PHP P2 thay đổi/new:
$changedPhp = @(git diff --name-only -- '*.php')
$newPhp = @(git ls-files --others --exclude-standard -- '*.php')
php vendor/bin/pint --test @changedPhp @newPhp
php tests/Support/inspect-p2-databases.php
```

Lượt tổng bật cả schema/concurrency opt-in: **85 tests, 425 assertions, 0 failure/error; 1 skipped, 1 incomplete**. Skip là test nhánh DB-disabled khi đang bật MySQL; incomplete C3 warehouse allocation thuộc P4. Có regression tắt tạo payment mới vẫn nhận IPN đang bay. Schema suite riêng: 5 tests/90 assertions; hai-process + injected failure riêng: 2 tests/18 assertions. No-DB Unit/Feature: **40 tests/99 assertions, tất cả pass**. Frontend Node tests: **3 passed**; Vue Vite build thành công, **1.989 modules**. Pint `--test` **42 file PHP pass**; `git diff --check` pass. Đã xóa riêng frontend/dist vừa sinh sau khi xác minh đúng đường dẫn và không có tracked file; không đưa build artifacts vào patch.

## Còn lại và kiểm tra thủ công

1. COD trên môi trường test: sửa client price về 0, kiểm tra order snapshot theo SKU. Buy-now giữ cart; cart checkout giữ phần không mua.
2. Hai user: A không sửa/xóa item hoặc xem/retry order B. Block user trong môi trường test được duyệt, token cũ nhận 403.
3. Google trên dev có cấu hình hợp lệ: bắt đầu từ nút frontend; URL chỉ có code ngắn hạn, replay không cấp token; verified user cũ giữ password. Unverified local account phải xử lý liên kết riêng.
4. Merchant sandbox sau phê duyệt: dùng IPN HTTPS; return không paid, UI đọc API và cập nhật sau IPN. Sửa query amount/method/paid không thành công. Không thử callback giả trên DB thật.
5. C3 allocation kho và confirm/cancel concurrency vẫn thuộc P3/P4. Không triển khai expiry release/refund, giải quyết đơn online legacy hoặc tuyên bố inventory an toàn trong P2.
6. Chưa có reconciliation worker/provider-subject table/linking UI. Logging legacy và dữ liệu nhạy cảm cũ cần audit P7.

Rollback: giữ VNPAY_ENABLED=false, giữ ledger/paid records; không migration down hoặc phục hồi unsigned return. Với code chưa commit, lưu/review patch rồi hoàn nguyên đúng file P2 nếu cần, giữ security guards; không reset/clean toàn workspace. Chi tiết rollback thanh toán đang bay xem contract. Dừng để review, không tự chuyển giai đoạn.
