# Contract inventory P4

Ngày 2026-10-29. Chủ dự án đã duyệt triển khai P4, migration sau rehearsal và ba quyết định dưới đây. Phạm vi không bao gồm P5/P6.

## Quy tắc nghiệp vụ

- Một kho active phải đáp ứng toàn bộ đơn. Duyệt kho theo ID tăng dần, kiểm tra tồn sau khóa. Tổng nhiều kho đủ vẫn từ chối nếu không kho nào đủ toàn đơn.
- Checkout giữ hàng: `quantity` không đổi, `reserved_quantity += quantity đặt`, `available_quantity = quantity - reserved_quantity`.
- Confirm giữ hàng, xóa deadline reservation. Shipping là bàn giao: giảm cả on hand và reserved cùng lượng. Complete không ghi stock.
- Cancel pending giải phóng đúng reservation của đơn. Không thêm cancel confirmed/shipping, return, refund hoặc xác nhận thu COD.
- Online mới giữ 15 phút; COD không tự hết hạn. Đơn confirmed/paid không tự giải phóng. Callback sau expiry/cancel chỉ ghi nhận cần đối soát, không tự giữ lại hàng hoặc hoàn tiền.
- Legacy không áp deadline hồi tố. Chỉ tạo allocation khi snapshot items, movement có dấu và balance chứng minh được. Thiếu bằng chứng/overallocated: cách ly đơn. Reserved chưa xác định thuộc đơn nào vào `inventory_legacy_holds`, không đưa trở lại available.
- Số dư legacy sai invariant được ghi discrepancy và chặn stock tương ứng; không sửa số dư để làm reconciliation xanh.

## HTTP thay đổi có chủ đích theo P4

Routes, ownership, fields nhận hàng và response COD/VNPay hiện có giữ nguyên. COD `final_amount` vẫn là số nguyên JSON. Trường giá client tiếp tục bị bỏ qua.

| Caller | Trường mới | Retry |
| --- | --- | --- |
| `POST /api/checkout` | `idempotency_key`: UUID bắt buộc; có thể dùng `Idempotency-Key` header, header/body phải trùng nếu gửi cả hai | Cùng actor/key/payload trả lại order gốc, kể cả giá hiện tại đã đổi; khác payload trả 409 |
| Admin import/export/transfer/adjust/stocktake | `operation_key`: UUID bắt buộc; Blade forms tự sinh và giữ khi retry | Bao phủ cả batch; thêm/sửa SKU trong cùng key trả 409, không ghi một phần |

Checkout thiếu key trả 422. Không đủ một kho trả 400 giữ contract cũ; operation/state/quarantine conflict trả 409; protocol tắt trả 503. Admin browser nhận flash/redirect như cũ, JSON nhận conflict. Không dùng request ID ngẫu nhiên để giả làm idempotency key.

Frontend giữ digest payload và key trong sessionStorage của tab, không lưu thêm thông tin giao hàng. Retry cùng ý định dùng lại key; nhấn Mua ngay/Thanh toán giỏ tạo ý định mới. Online replay đã paid/cancelled trả `payment_url: null` và thông tin order; frontend sang trang kết quả để đọc trạng thái server.

Các cờ `action=draft`, `is_draft`, `save_progress` trả 422, không ghi tồn. Các nút nháp trước đây chưa có storage/lifecycle riêng; không coi gọi endpoint thực thi là lưu nháp. P6 sẽ thiết kế draft thật.

## Transaction và khóa

`PlaceOrder` là application service: chuẩn hóa payload → operation unique → server quote → allocation → order/items → reservation/items/movements → payment attempt nội bộ → giảm cart. Tất cả cùng connection/outer transaction. Tạo URL thanh toán là ký local sau transaction; không gọi provider trong khóa.

Các writer khóa warehouse theo ID tăng dần trước inventory. Khóa warehouse là mutex thô có chủ đích để bảo vệ cả trường hợp inventory đích chưa có; insert zero balance diễn ra trong protocol này. Checkout duyệt warehouse tăng dần, SKU tăng dần. Bulk stocktake khóa trước tất cả warehouse liên quan.

Đối với đơn đã có: order → payment attempt (nếu payment/expiry) → reservation parent → warehouse tăng dần → stock/current reservation lines. Stock accounting đọc child rows bằng current locking read, không join khóa parent của đơn khác. Cách này tránh stale SUM trong MySQL REPEATABLE READ và vòng khóa parent–warehouse.

Operation record, stock changes, movements và kết quả replay commit/rollback cùng nhau. Unique operation hash và operation/line bảo vệ retries. Transfer có hai movement có dấu đối nhau cùng operation. `StockWriter::change` ghi before/after on hand và reserved; actor được truyền tường minh, system expiry có actor null.

Invariant: `0 <= reserved <= on_hand <= 2147483647`, `available = on_hand - reserved`, `reserved = active reservation items + legacy hold`. Kiểm kê thấp hơn reserved tạo discrepancy, giữ nguyên balance, chặn allocation/export/ship ở stock đó; cancellation của allocation đã chứng minh vẫn có thể release an toàn.

Không có distributed lock, queue mới, repository framework hoặc dependency mới. Đây vẫn là một transaction MySQL trong Modular Monolith. P5 mới tổ chức lại namespace/module ports.

## Vận hành

- `inventory:backfill-legacy`: dry run; `--apply` chỉ maintenance/testing, chỉ metadata. Chạy khi writers đã dừng; không dùng làm tác vụ định kỳ.
- `inventory:reconcile`: read-only snapshot, kiểm invariant/accounting/chuỗi movement protocol/transfer pair, tách quarantine legacy khỏi drift mới. Exit 1 khi có unexplained; không tự sửa.
- `inventory:expire-reservations --limit=100`: khóa/đọc lại từng order trước expiry; replay không double release. Scheduler mỗi phút; `withoutOverlapping` giảm chạy trùng, DB là nguồn đảm bảo correctness.
- `INVENTORY_PROTOCOL_ENABLED` và `ORDER_TRANSITIONS_ENABLED` là cổng dừng writer. `VNPAY_ENABLED=false` tới khi public merchant IPN được kiểm chứng thật. Incoming IPN có chữ ký hợp lệ vẫn được xử lý cho các attempt đang bay.

Đối soát là công cụ chẩn đoán O(stock + protocol movements), chưa benchmark cho tập dữ liệu lớn. Khóa cấp warehouse ưu tiên correctness, có thể gây contention; chỉ thu hẹp sau khi có số đo và tests tương đương. Không tuyên bố throughput/SLO chưa đo.

Xem [P4_STATUS](../roadmap/P4_STATUS.md) cho kết quả rollout, scheduler, test và rollback thực tế.
