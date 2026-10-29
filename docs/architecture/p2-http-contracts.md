# P2 — HTTP contracts và rollout

Ngày: 2026-10-29. Contract của implementation P2 để chủ dự án review; chưa enable thanh toán mới trên DB ứng dụng.

## Giá và checkout

- `POST /api/checkout` giữ response thành công: COD `{message,order_id,final_amount}`, VNPay `{payment_url,order_id}`.
- `items.*.price` không còn bắt buộc và luôn bị bỏ qua. Server dùng `product_variants.price`; `cost_price` là giá vốn. SKU phải thuộc product, chưa soft-delete, product phải published/public. Không lặp SKU/request, tối đa 100 lines, quantity 1–10.000.
- VND nguyên, không làm tròn phần lẻ; giá dương, tổng tối đa 9.999.999.999 VND theo orders DECIMAL(12,2). SKU/giá không hợp lệ trả 422. Không tự thêm phí/discount/coupon hoặc sửa tiền lịch sử.
- `mode` tùy chọn: `cart` mặc định tương thích client cũ; `buy-now` giữ cart. Cart checkout chỉ tiêu thụ quantity tương ứng SKU đã mua trong cart của user; không xóa toàn bộ giỏ. Không bắt buộc SKU phải sẵn trong cart để giữ khả năng đặt trực tiếp trước đây.
- Catalog `final_price` dùng giá bán, discount không lấy chênh lệch giá vốn. Cache home_products có key phiên bản mới. Wishlist đổi `variant.cost_price` thành `variant.final_price`; public variants đổi `cost_price` thành `price`. Frontend cập nhật cùng lượt; cart/order detail không serialize giá vốn của variant.
- Cart cũ có thể có giá tạm tính lỗi thời; UI thông báo đơn dùng giá bán hiện tại khi đặt. Không backfill cart/order thật trong P2.

## Identity và quyền

- Các route token thêm active-account gate; token cũ của inactive/blocked user bị 403. Login kiểm tra status trước cấp token; admin login giữ role gate và tái tạo session.
- Cart update/delete giữ URI/method, policy trả 403 nếu sai owner. Order detail/retry của user khác trả 404.
- Login API/admin: 5 request/phút theo email+IP, đồng thời 20/phút theo IP; vượt giới hạn trả 429.
- Google redirect cần `code_challenge` S256 (43 ký tự URL-safe). Frontend lưu verifier trong sessionStorage của tab. Backend cấp state ngẫu nhiên TTL 300 giây, tiêu thụ dưới cache lock trước gọi provider.
- Callback trả mã handoff TTL 60 giây trong URL, không bearer. `POST /api/auth/google/exchange` nhận `{code,code_verifier}`, kiểm chứng và tiêu thụ một lần dưới cache lock, trả `{status,user,token}` với no-store.
- Google phải xác minh email. User hiện có phải có email_verified_at để auto-link, giữ nguyên password/name. Local account chưa verified trả 409; chưa có UI/quy trình authenticated linking, không tự sửa dữ liệu cũ để vượt guard.
- Chưa có bảng provider-subject identities; hiện liên kết theo email đã xác minh. Email change/merge identity ngoài P2.
- Cần persistent shared cache có lock (database cache hiện có phù hợp); array cache không dùng qua nhiều HTTP request. Cache lưu hash state/code, không thay thế payment ledger.

## Payments

Đối chiếu [VNPay Payment 2.1.0 chính thức](https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html): canonical theo PHP urlencode, HMAC SHA512, amount nhân 100; GET IPN qua HTTPS; return không cập nhật giao dịch. RspCode 00/02 xác nhận đã xử lý; 01/04/97/99 là các nhánh lỗi/retry. Callback mẫu không bắt buộc currency, nên kiểm tra currency đã lưu và từ chối currency khác nếu có.

Các quy tắc dưới đây do implementation P2 quyết định:

- `VNPAY_ENABLED=false` mặc định; checkout online trả 503 trước order/reservation, retry không cấp URL. COD tiếp tục hoạt động. IPN/return của attempt đã tồn tại vẫn được xử lý an toàn khi tắt tạo thanh toán mới. `.env` hiện có không sửa.
- Mỗi order online mới có một attempt: ULID reference, merchant, amount VND/currency, expiry 15 phút. Không dùng order ID làm reference và không backfill attempt cho order cũ.
- `GET /api/vnpay/ipn` public, kiểm tra signature trước DB rồi reference/merchant/method/amount/currency/state. Từ chối array, duplicate hoặc normalized query keys mơ hồ. Thành công cần cả response code và transaction status 00.
- Khóa orders → payment_attempts trong transaction; unique provider/merchant/transaction và event fingerprint chống lặp. Attempt/event/order paid cùng commit; callback không trừ tồn hoặc đổi fulfillment.
- Hai URI return `/api/vnpay/return`, `/vnpay/return` không ghi tiền. Return signed chỉ mang order ID từ attempt về frontend, không truyền amount/paid. Return không xác minh dẫn về trạng thái chưa xác minh.
- `POST /api/orders/{order}/payment` cần auth/active/owner: trả lại URL của pending attempt chưa hết hạn và đúng amount, không tạo order/attempt/reservation mới. Failed/expired/cancelled/paid trả 409, không tự mở lượt mới.
- Thành công đến sau expiry/cancel được lưu reconciliation_required, order chưa paid; không tự refund/re-reserve. Unique conflict do cùng provider transaction cho hai order trả 99 và rollback order thứ hai.
- Callback sau terminal trả 02; xung đột với kết quả trước cần đối soát provider. Chưa có reconciliation worker. Failed attempt không thể được callback khác âm thầm nâng thành paid.
- Frontend payment-success lấy owned order từ API; query amount/method/payment_status không quyết định kết quả. COD hiển thị tiếp nhận đơn, online unpaid hiển thị chờ; nút cập nhật đọc lại API.

## Errors và quan sát

UUID X-Request-ID được gắn mỗi request. Lỗi bất ngờ trả thông báo chung/request ID; global log chỉ chứa exception type và ID, không message/SQL/request body. Validation vẫn 422, auth 401, HTTP 4xx giữ status/thông báo nghiệp vụ; lỗi web không trả debug trace. Admin updateStatus bỏ dd nhưng chưa đổi transition P3. Home/Banner không trả exception message. Telescope loại entry luồng login/register/OAuth/payment/checkout và ẩn credential headers/fields ở mọi môi trường.

Không cam kết mọi log legacy đã redact; cần audit retention/observability P7. Dữ liệu nhạy cảm đã ghi trước P2 không bị tự động xóa.

## Rollout sau phê duyệt

1. Review contract/code/migration hai bảng, backup theo P1 và rehearsal trên clone đích; giữ online tắt. Chưa thực hiện các bước tiếp theo trên DB ứng dụng.
2. Chạy duy nhất migration `2026_10_29_000001_create_verified_payments.php` được duyệt. Không migrate:fresh hoặc chạy lại lịch sử ngoài kế hoạch.
3. Cấu hình HTTPS public IPN `/api/vnpay/ipn`, Google callback URL, frontend return URL và shared cache. Localhost không phải IPN public.
4. Xử lý online order legacy đang chờ theo quyết định riêng: handler mới thiếu attempt sẽ từ chối, không fallback order ID/return cũ.
5. Smoke test merchant sandbox thật: signature, return trước/sau IPN, retry, failure/cancel, late payment. Không charge thẻ thật trong automated tests.
6. Chỉ khi xác nhận các bước trên mới enable VNPAY_ENABLED=true, làm mới config cache theo deploy, deploy frontend tương thích và quan sát attempt/event/ack. Merchant end-to-end và Google thật **chưa xác minh** trong phiên này.

## Rollback

Tắt tạo online payment mới; giữ COD nếu đã được vận hành chấp thuận. Giữ handler an toàn để nhận/đối soát IPN đang bay; tắt toàn bộ trả 99 chỉ dẫn retry hữu hạn, nên cần đối soát thủ công. Không phục hồi return không xác thực. Migration forward-only từ chối down để không xóa chứng từ; giữ ledger và đơn paid. Revert patch/commit được review nhưng giữ guard giá/quyền/thanh toán; không reset/clean toàn workspace hay restore DB cũ lên giao dịch mới.
