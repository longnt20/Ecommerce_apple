# Kiến trúc mục tiêu: Modular Monolith trên Laravel

**Tiến độ P4 — 2026-10-29:** đã triển khai PlaceOrder, ReservationManager, StockStore/StockWriter, IdempotentOperation, LegacyReservationBackfill, ExpireReservations và ReconcileInventory trong cấu trúc Laravel hiện tại. MySQL là Unit of Work chung; warehouse locks bảo vệ stock/missing-row creation, operation unique chống lặp. Chốt một kho active/toàn đơn, online 15 phút, COD không hết hạn và cách ly legacy mơ hồ. Schema/metadata đã rollout DB ứng dụng; [P4 contract](p4-inventory-contract.md), [P4_STATUS](../roadmap/P4_STATUS.md). Dependency/module organization bên dưới vẫn là mục tiêu P5, chưa được tự động triển khai. Các trạng thái triển khai cũ phía dưới là lịch sử.

**Tiến độ P3 — 2026-10-29:** OrderTransitionPolicy/OrderService và history/operation đã có trong cấu trúc Laravel hiện tại, khóa order trước operation và inventory; không dùng snapshot stale. Guard proof không thay thế allocation/reservation P4. [P3_STATUS](../roadmap/P3_STATUS.md), [contract](p3-order-transitions.md); DB ứng dụng/legacy remediation chưa rollout. Dependency rules/module namespace dưới đây vẫn là mục tiêu P5.

**Tiến độ 2026-10-29:** đã triển khai lát cắt hardening P2 trong cấu trúc Laravel hiện có; xem [P2 status](../roadmap/P2_STATUS.md). Chưa chuyển namespace modules hoặc áp dụng toàn bộ dependency rules bên dưới. PaymentService còn phụ thuộc Order model là ngoại lệ tạm tới P5; rollout payment schema/merchant chưa được thực hiện trên DB ứng dụng.

Ngày lập: 2026-10-28. **Đề xuất, chưa được phê duyệt triển khai.** Tên class/bảng mới dưới đây là dự kiến; không phải chức năng đã tồn tại.

Đầu vào bổ sung: [phân tích laravel.sql](database-export-analysis.md). Schema export có MySQL 8.0.30/InnoDB, role/blocked/thumbnail/commit; P1 cần khôi phục khả năng tái tạo schema này thay vì thêm mù các cột đã tồn tại. P4 phải xử lý rõ 20 đơn không pending còn net reserve; snapshot không quan sát lệch kho vì toàn bộ orders đang dùng kho 1.

## 1. Mục tiêu và giới hạn

Ưu tiên bảo vệ tiền, quyền truy cập và tồn kho, sau đó cải thiện khả năng kiểm thử và ranh giới module. Giữ Laravel, PHP, Eloquent, MySQL dự kiến, PHPUnit và queue/cache hiện có. Không yêu cầu Kafka, Redis, Kubernetes, database per service hay framework mới. Version/engine MySQL thực tế phải xác minh ở P0.

Áp dụng Clean Architecture có chọn lọc: domain rules độc lập HTTP/persistence; application điều phối use case; infrastructure thực thi I/O. Không ép mọi CRUD thành aggregate/repository/DTO nhiều lớp.

## 2. Module boundaries và quyền sở hữu

| Module | Sở hữu | API nội bộ dự kiến | Được phụ thuộc |
| --- | --- | --- | --- |
| Identity | Users, OAuth identities, account status, permissions | ActorContext, AccountAccess | SharedKernel |
| Catalog | Categories, products, variants, specs, media metadata | SellableSkuReader | SharedKernel |
| Pricing | Quy tắc giá, promotions, coupons, assignments/redemptions | QuoteService, CouponRedemption | Catalog Contracts, SharedKernel |
| Inventory | Warehouses, balances, reservations, movements, transfers, stocktakes | InventoryReservations, InventoryOperations | Catalog Contracts, SharedKernel |
| Ordering | Cart, orders/items, checkout idempotency, transition history | PlaceOrder, ConfirmOrder, CancelOrder | Identity/Catalog/Pricing/Inventory/Payments Contracts |
| Payments | Attempts, provider events, payment/refund state | PaymentInitiation, PaymentStatusReader | SharedKernel; nhận snapshot tiền/order reference |
| Content | Posts/tags, banners/frames | ContentReader | Catalog Contracts |
| Engagement | Wishlist, review và moderation | WishlistActions, ReviewActions | Catalog/Identity Contracts |
| Notifications | Mail templates, delivery tracking | Event handlers | Published event contracts |
| Reporting | Read queries/projections | ReportQueries | Published read contracts; read-only views được ghi nhận |

Inventory không gọi Ordering để reserve; nhận order/item reference từ caller. Payments không import Order model: nhận immutable payment request và phát sự kiện đã xác minh. Handler nằm trong Ordering nhận sự kiện Payments rồi cập nhật đơn. Đây là luồng sự kiện, không tạo vòng phụ thuộc source Payments → Ordering → Payments.

Ban đầu ranh giới là namespace và quyền ghi trong cùng database, không cần tách schema vật lý. Reporting có thể join read-only trong giai đoạn đầu; đó là coupling được ghi nhận, phải thay bằng projection trước khi tách service.

## 3. Dependency rules bắt buộc

1. Module chỉ được ghi bảng do mình sở hữu. Mọi thay đổi tồn đi qua Inventory, mọi kết luận thanh toán đi qua Payments.
2. Giao tiếp liên module qua `Contracts` và DTO/event bất biến; không trả Eloquent model/query builder xuyên boundary.
3. Domain không import `Illuminate`, facade, HTTP Request, Auth, DB, queue hoặc provider SDK. Application dùng port và DTO, không dùng Request/Response.
4. Infrastructure phụ thuộc Application/Domain để triển khai port; Interface/HTTP gọi Application. Service provider là composition root bind port → implementation.
5. Actor ID, clock và operation ID được truyền rõ; không lấy `auth()` trong domain hoặc model event tạo movement.
6. SharedKernel chỉ chứa kiểu ổn định như Money, Clock, Identifier; không đặt business services hoặc base repository vạn năng vào đây.
7. Trong migration kiến trúc, cho phép adapter gọi code cũ có ghi thời hạn và test; code mới không tăng thêm import model xuyên module.
8. Kiểm tra dependency bằng PHPUnit quét namespace/import và review; chưa cần package kiểm tra kiến trúc mới.

## 4. Cấu trúc dự kiến

```text
app/
  Modules/
    Inventory/
      Contracts/InventoryReservations.php
      Application/ReserveStock.php
      Application/Ports/StockStore.php
      Domain/Reservation.php
      Domain/StockBalance.php
      Domain/Exceptions/InsufficientStock.php
      Infrastructure/Persistence/EloquentStockStore.php
      Infrastructure/InventoryServiceProvider.php
      Interfaces/Http/
    Ordering/
      Application/PlaceOrder.php
      Application/ConfirmOrder.php
      Application/CancelOrder.php
      Application/Handlers/OnPaymentConfirmed.php
      Domain/OrderTransitionPolicy.php
    Payments/
      Contracts/PaymentInitiation.php
      Contracts/Events/PaymentConfirmed.php
      Application/HandlePaymentNotification.php
      Application/Ports/PaymentGateway.php
      Infrastructure/Gateways/VnPayGateway.php
    Catalog/...
    Pricing/...
  SharedKernel/
    Money.php
    Clock.php
tests/
  Unit/Modules/
  Feature/Modules/
  Integration/Concurrency/
  Architecture/
```

Không di chuyển toàn bộ code cùng lúc. Controllers/routes hiện tại có thể làm compatibility adapters; model Eloquent cũ được giữ tạm trong Infrastructure adapters. CRUD nội dung ít quy tắc có thể giữ Eloquent và FormRequest, không cần domain model song song.

## 5. Patterns gắn với vấn đề cụ thể

| Pattern/cơ chế | Vấn đề audit | Class cũ → class dự kiến | Giới hạn |
| --- | --- | --- | --- |
| Application Service | Checkout gánh HTTP, tiền, tồn và payment | CheckoutController → PlaceOrder; OrderService → ConfirmOrder/CancelOrder | Không tạo service chỉ để bọc một lệnh CRUD |
| Ports and Adapters | VNPay gắn trực tiếp checkout | createVnPayUrl/vnpReturn → PaymentGateway, VnPayGateway, HandlePaymentNotification | Interface theo năng lực cần test, không theo mọi class |
| Transition policy | Chuyển trạng thái trùng/rải rác | OrderService/Admin OrderController → OrderTransitionPolicy | Bảng transition + enum đủ trước; chưa cần class cho từng state |
| Repository hẹp | Cần khóa, load aggregate và test transaction | InventoryService → StockStore/EloquentStockStore; OrderStore nếu cần | Không generic Repository cho mọi model; không mock để thay integration test |
| Money value object | Giá client, cost_price nhập nhằng, tính toán tiền | Cart/Checkout/Home → Money, QuoteService | Chuẩn hóa currency/đơn vị trước, không tự convert dữ liệu cũ |
| Idempotent command | Retry tạo đơn, callback, confirm/cancel | PlaceOrder, HandlePaymentNotification, InventoryReservations | Unique constraint + trạng thái xử lý; không chỉ cache key |
| Allocation Strategy | Kho giữ và kho xuất không khớp | InventoryService → WarehouseAllocationPolicy | Một policy ban đầu; Strategy chỉ hữu ích nếu cần đổi policy |
| Domain event + after commit | Mail/job phụ thuộc transaction | AssignCouponJob, user events → event handlers | Không dùng eventual consistency cho reserve ở monolith |
| Transactional Outbox/Inbox | Cần không mất/lặp sự kiện qua worker/service | OutboxPublisher, InboxConsumer | Bổ sung P8 khi có yêu cầu delivery; không giả định exactly-once |
| Observer | Cache thuộc tính đã có | ProductAttributeObserver | Giữ invalidation; không giấu nghiệp vụ tiền/tồn trong observer |

SOLID được đánh giá bằng kết quả: mỗi use case có một trách nhiệm, provider thay được qua port, hợp đồng kiểu rõ, interface nhỏ, domain không phụ thuộc I/O. Không đo SOLID bằng số interface hoặc tầng thư mục.

## 6. Transaction, concurrency và invariants

### Mô hình tồn đề xuất

- `on_hand` tương ứng quantity hiện tại; `reserved` là hàng đã cam kết nhưng chưa xuất; `available = on_hand - reserved` trong mô hình chưa có quarantine.
- Điều kiện: `on_hand >= 0`, `reserved >= 0`, `reserved <= on_hand`. Incoming không được tính là available.
- P4 giữ tên cột cũ để giảm rủi ro. Nếu còn lưu available, cập nhật cả ba trong cùng transaction và reconciliation; chỉ bỏ/chuyển generated column sau khi đo và xác minh DB hỗ trợ.
- Reservation item là bằng chứng nguồn cho commit/release, gắn order item + warehouse + SKU + quantity và trạng thái. Không chọn lại kho khi confirm/cancel.
- Kiểm kê actual thấp hơn reserved phải sinh discrepancy và xử lý đơn bị ảnh hưởng, không âm thầm làm available âm.

### Protocol khóa

Một UnitOfWork dùng cùng connection cho order, quote redemption và inventory trong checkout. Mọi đường ghi tồn, kể cả admin nhập/xuất/chuyển/kiểm kê, phải dùng cùng protocol.

Thứ tự dự kiến: idempotency record → order → payment attempt nếu có → coupon counter nếu có → reservation items → inventory theo `(warehouse_id, variant_id)` tăng dần. Luồng không dùng entity nào bỏ qua bước đó; không giữ khóa ở bước sau rồi quay lại bước trước. Payments xử lý callback không được giữ attempt lock rồi gọi trực tiếp Ordering để lấy order lock; đồng bộ bằng handler sau transaction hoặc coordinator theo thứ tự chung.

Khóa dòng bằng `SELECT ... FOR UPDATE` bên trong transaction; kiểm tra invariant sau khi khóa. Với inventory chưa tồn tại, dùng unique pair và insert/upsert an toàn rồi đọc khóa; xử lý duplicate race. Không dùng thao tác firstOrCreate đọc-sửa mà không bảo vệ.

Deadlock retry có giới hạn chỉ bao quanh transaction không có external side effect. Gateway/mail không gọi trong transaction giữ khóa. Xung đột idempotency cùng key khác payload trả lỗi; cùng payload trả kết quả đã ghi hoặc trạng thái đang xử lý có quy tắc rõ.

### Luồng mục tiêu

1. Chuẩn hóa cart/buy-now và SKU; xác minh ownership, tài khoản, SKU thuộc product và đang bán.
2. Tạo server quote từ snapshot giá; chính sách quote và cập nhật giá phải có version/lock tương ứng.
3. Transaction: nhận idempotency key, chọn allocation, khóa và kiểm tra tồn, tạo order snapshot/reservations, giữ quyền dùng coupon nếu áp dụng.
4. Commit; chỉ xóa items đã checkout. Tạo/retry payment attempt riêng. Lỗi tạo URL không được giả vờ rollback order đã commit.
5. Xác minh callback theo đặc tả nhà cung cấp đã kiểm chứng ở P2; đối chiếu merchant, attempt, amount/currency và kết quả; ghi payment event idempotent.
6. Order transition và reservation transition chạy atomic theo policy. Payment success không tự đồng nghĩa delivered.
7. Expiry/cancel release đúng reservation một lần. Callback thành công đến sau expiry đưa vào reconciliation/refund decision, không tự giữ lại tồn đã bán cho người khác.

### Quyết định nghiệp vụ còn mở

Đề xuất MVP một kho đủ toàn bộ đơn; schema reservation vẫn theo item để không khóa khả năng đa kho về sau. Nếu không có một kho đủ, trả lỗi rõ ràng; không âm thầm chia đơn. Cần phê duyệt tại P0/P4.

Thời điểm trừ tồn vật lý (confirm hay ship), TTL theo phương thức, hủy sau confirm, COD collected và hoàn tiền cần quyết định riêng. Audit chỉ chứng minh code hiện trừ khi confirm; không khẳng định đó là nghiệp vụ mong muốn.

**Cập nhật P0 theo xác nhận chủ dự án:** cost_price là giá vốn; tồn vật lý phải giảm khi xuất/giao cho vận chuyển. Vì vậy confirm giữ reservation, use case bàn giao mới commit stock trong kiến trúc đích. TTL, bằng chứng bàn giao, split fulfillment, cancel và return vẫn cần chốt trước triển khai phụ thuộc. Không thay đổi code nghiệp vụ ở P0.

## 7. Kế hoạch database

Không có migration nào được tạo trong giai đoạn tài liệu. Tất cả thay đổi dưới đây cần profiling dữ liệu và kế hoạch forward migration.

| Dữ liệu | Thay đổi dự kiến | Backfill/constraint và điều kiện |
| --- | --- | --- |
| Migration baseline | Đối chiếu bảng kho trùng, thumbnail, users.role/code/status, movement enum | Không sửa lịch sử đã chạy khi chưa biết môi trường nào sử dụng; test fresh install và upgrade riêng |
| Products/variants/pricing | Phân biệt sale/compare-at/cost, chuẩn đơn vị tiền | Xác minh cost_price trước mapping; không ghi đè mù; giá không âm và SKU unique |
| Cart/items | FK đúng, index cart_id, unique cart-SKU theo mô hình đã chốt | Gộp duplicates và orphan qua báo cáo; nếu variant nullable, xử lý semantics NULL unique, không coi unique nullable là đủ |
| Orders/items | Idempotency, currency, snapshot SKU/giá/discount/tax nếu có, status history; hạn chế cascade | Giữ code unique và ID cũ; warehouse_id cũ chỉ bỏ sau chuyển toàn bộ readers |
| Reservations/items | Order/item reference, warehouse, SKU, quantity, lifecycle, expires_at, committed/released timestamps | Index `(status, expires_at)` và order reference; unique identity allocation và operation key |
| Inventory | Unique warehouse-SKU, nonnegative/invariant constraints nếu DB hỗ trợ, protocol atomic | Kiểm tra dữ liệu cũ trước add constraint; không auto sửa số âm không rõ nguyên nhân |
| Inventory movements | Operation key, reservation reference, actor, direction, before/after phù hợp | Transfer có hai movement cùng transfer_id; append-only, sửa sai bằng bút toán bù |
| Payment attempts/events | Provider, merchant ref unique, amount/currency, status, provider transaction, verified event key | Một order nhiều attempts; uniqueness transaction theo provider/account scope sau kiểm chứng đặc tả; payload được redact |
| Coupon redemption | Unique assignment nếu đúng nghiệp vụ, reserve/redeem/release và usage cap atomic | Quy tắc một user nhiều lần dùng phải chốt trước; phân biệt assignment với redemption |
| OAuth identity | Provider + subject unique, user reference | Không tự liên kết chỉ bằng email chưa xác minh; không đổi password tài khoản hiện có |
| Transfers/stocktakes | Header/lines và discrepancy khi cần lifecycle nghiệp vụ | MVP chuyển tức thời có hai movement; chỉ thêm in-transit khi quy trình thực yêu cầu |
| Outbox/inbox | Event ID, type/version, occurred_at, attempts, processed_at | Chỉ thêm P8; index pending delivery, retention và consumer dedup |

Index ứng viên: orders `(user_id, created_at)`, `(status, created_at)`; movements `(warehouse_id, product_variant_id, created_at)` và `(reference_type, reference_id)`; reservations expiry. Xác minh EXPLAIN và workload trước khi thêm; tránh index trùng unique pair hiện có.

Đối chiếu reservation cũ từ movements chỉ là nguồn bằng chứng, không bảo đảm suy ra đủ. Đơn không xác định được kho giữ phải đưa vào danh sách xử lý thủ công; không backfill mặc định kho 1. Không khẳng định số dư lịch sử hợp lệ khi ledger thiếu.

## 8. Bảo mật, hiệu năng và khả năng kiểm thử

- Policies bảo vệ ownership và warehouse permissions; active-account middleware nhất quán. FormRequest kiểm tra cấu trúc, domain kiểm tra invariant.
- Error mapping: 401/403 quyền; 422 input; 409 state/idempotency conflict; lỗi hệ thống trả mã correlation, log được redact. Quy ước status cụ thể phải cập nhật contract trước rollout.
- Dùng API Resources/DTO để không lộ cost/internal fields; giữ response tương thích qua adapter khi chuyển đổi.
- Cache read catalog có invalidation sau commit; không dùng cache để quyết định stock availability khi đặt đơn.
- Eager load/aggregate có mục tiêu, pagination, chunk export; đo p95, query count, peak memory, lock waits trước/sau. Chưa có số liệu để đặt SLO tùy ý.
- Unit test domain thuần; HTTP feature test quyền/contract; MySQL integration test constraints và transaction; concurrency test nhiều process/connection có barrier. Không dùng SQLite để chứng minh row-lock safety.
- Test gateway dùng fixtures xác thực và fake HTTP; worker test kiểm tra duplicate, retry, rollback và crash window. Không gọi thanh toán thật trong CI.

## 9. Chuẩn bị thử nghiệm Microservices

Ứng viên đầu tiên: Notifications hoặc Reporting projection vì không nằm trong transaction tồn/đơn. Điều kiện: contract versioned, outbox/inbox, idempotency, replay, tracing và vận hành độc lập có lợi ích đo được.

Payments chỉ tách sau khi callback/reconciliation ổn định. Không tách Ordering–Inventory khi vẫn cần transaction chung; saga/compensation chỉ được thiết kế khi có quyết định tách thật. Nhánh thử nghiệm phải so sánh độ phức tạp, latency và failure recovery với monolith, không đổi production mặc định.

Tham chiếu: [ADRs](architecture-decisions.md), [UPGRADE_PLAN](../roadmap/UPGRADE_PLAN.md).
