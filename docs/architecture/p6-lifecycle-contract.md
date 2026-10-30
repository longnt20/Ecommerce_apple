# P6 — Contract tiền, hàng, coupon và bảo toàn lịch sử

Ngày 2026-10-30. Phạm vi được chủ dự án duyệt trong phiên triển khai P6. Không gọi provider refund; không tách microservice hoặc thêm dependency.

## Quyết định nghiệp vụ đã duyệt

- COD: admin ghi nhận **thu đủ** số tiền sau giảm, có mã đối soát. Không suy ra đã thu từ `completed`.
- Refund: ghi yêu cầu, admin đối soát thủ công kết quả. Không gọi VNPay hoặc tự chuyển tiền.
- Hàng trả: chỉ cộng tồn có thể bán khi admin kiểm tra đạt. Refund không cộng kho; nhận hàng không đánh dấu hoàn tiền.
- Coupon: một mã/đơn; giữ lượt tại checkout, redeem khi online paid hoặc COD confirmed; release khi pending bị hủy/hết hạn. Discount được snapshot vào đơn.
- Migration additive được thử sandbox, backup/restore/rehearsal rồi mới áp dụng DB `.env`. Không chỉnh dữ liệu legacy mơ hồ.

## Ranh giới và transaction

Ordering `AfterSales` điều phối khóa order và gọi Payments `ManualPayments`, Inventory `ReturnedGoods` qua Contracts. Các adapter chỉ ghi bảng của owner. `CouponRedemptions` thuộc Pricing; Ordering cập nhật số tiền cuối và gọi lifecycle trong cùng transaction MySQL. Không phát event async để quyết định giá, giữ tồn hoặc đánh dấu paid.

Schema bổ sung tám bảng: `payment_adjustments`, `payment_adjustment_events`, `coupon_redemptions`, `coupon_assignment_deliveries`, `inventory_return_receipts`, `inventory_documents`, `inventory_document_lines`, `inventory_settings`. Bổ sung nullable `coupon_uses.revoked_at/notified_at`, revision mặc định 0 trên coupons. FK mới dùng RESTRICT để giữ chứng cứ; không đổi ID/tiền/status/số dư cũ.

Khóa và concurrency:

- Tiền/hàng trả: order FOR UPDATE trước ledger/receipt. Cùng order được tuần tự hóa. Unique operation hash + payload hash chống retry với nội dung khác. Mã thu COD và mã hoàn đã đối soát không được dùng cho hai chứng từ cùng loại.
- Hàng trả cần reservation item `committed` khớp item/kho/SKU/số lượng đã xuất; tổng mọi biên nhận không vượt lượng xuất. Unique item/reference chặn cùng giấy nhận với operation key khác.
- Checkout giữ protocol warehouse/inventory của P4, sau đó khóa coupon; không khóa coupon rồi chờ warehouse. Cap tính bằng `used_count + reserved`. Dùng **current locking reads** để tránh COUNT trên snapshot cũ của MySQL REPEATABLE READ.
- Confirm/cancel/expiry/payment callback xử lý coupon cùng transaction với order và stock. Redeem/release lần hai không tăng counter; coupon bị sửa/ẩn sau checkout không viết lại snapshot đã giữ.
- Assignment job khóa coupon và kiểm revision. Retry không tạo assignment/delivery thứ hai. Job cũ không tái cấp sau thay đổi người nhận. Legacy duplicate assignment được giữ nguyên và bị từ chối sử dụng, không tự gộp.

## Matrix tiền và hàng

| Thao tác | Điều kiện | Kết quả | Không phát sinh tự động |
| --- | --- | --- | --- |
| Thu COD | COD shipping/completed, unpaid, đúng toàn bộ final_amount, mã đối soát | ledger collected + event; order paid/paid_at | Không xuất kho lần nữa |
| Request refund | paid, có attempt paid đúng amount hoặc COD collected; amount dương, tổng requested+settled không vượt collected | refund requested + event | Không gọi provider, không nhận hàng |
| Settle refund | request còn requested, có reference và lý do | settled + event | Không tăng tồn, không sửa giá/snapshot đơn |
| Reject refund | request còn requested, có lý do | rejected + event; nhả hạn mức refund | Không đảo giao dịch bên ngoài |
| Return sellable | shipping/completed, đủ chứng cứ xuất, còn lượng có thể trả, kiểm tra đạt | receipt + movement import bù vào kho gốc | Không đánh dấu refund |
| Return damaged | cùng điều kiện xuất/giới hạn; kiểm tra không đạt | receipt cách ly; không tăng quantity/available | Không bán lại hay tự hủy hàng |

`payment_status=paid` vẫn là chứng cứ đơn đã thu; kết quả hoàn nằm ở refund ledger, không thêm enum mới hoặc đổi paid thành unpaid. UI đối soát hiển thị ledger riêng. `completed` không đồng nghĩa đã thu COD. Refund một phần hoặc nhiều phần có thể đạt toàn bộ collected amount, không vượt cap. Một request có một terminal outcome; sửa sai terminal bằng đối soát/forward fix, không xóa event hoặc giả vờ hoàn tác chuyển khoản.

Return damaged là lượng vật lý cách ly ghi trong biên nhận, **nằm ngoài pool quantity có thể phân bổ**. Kiểm kê pool Inventory phải tách hàng cách ly này; không cộng chung để vô tình bán lại. Chuyển damaged sang sellable/hủy vật lý và nhận trả không có chứng cứ legacy là workflow riêng chưa được duyệt, nên không có endpoint tự xử lý.

Graph trạng thái order vẫn giữ P3/P4; không thêm hủy confirmed/shipping. Online callback đến sau hủy/hết hạn vẫn `reconciliation_required`, không redeem lại coupon hoặc tự giữ hàng. Đối soát callback muộn cần owner Payments xác minh và quy trình riêng; không dùng cờ legacy paid làm chứng cứ refund.

## Coupon

Assignment và redemption là hai khái niệm khác nhau. Mỗi user dùng một assignment của coupon tối đa một lần. `max_usage=null` không giới hạn; 0 không còn lượt. Counter legacy `used_count` là mức đã dùng giữ nguyên; reserved mới cộng thêm khi kiểm cap, không backfill từ dữ liệu không chứng minh được.

Fixed discount/cap là nguyên VND. Percentage hỗ trợ hai chữ số thập phân, tính bằng integer và làm tròn xuống VND rồi áp cap; không dùng float. Chưa hỗ trợ đơn miễn phí: coupon làm final_amount về 0 bị từ chối để giữ invariant tiền dương P2. Không ghép thêm promotion/shipping rule mới; quote vẫn dùng SKU.price theo contract cũ.

Ngày bắt đầu/kết thúc tính theo ngày ứng dụng, bao gồm ngày hết hạn. Assignment mới giữ giới hạn 30 ngày của job cũ, không vượt ngày hết hạn coupon. Update trước đây tự tạo hạn 7 ngày không còn đường ghi riêng; dùng chung assignment service. Re-select assignment unused bị revoke chỉ bỏ revoke, không gia hạn ngày cũ; assignment used không cấp lại. Assignment trùng legacy vẫn cách ly.

Admin update coupon khóa cùng coupon row, không cho giảm max_usage thấp hơn used+reserved. Danh sách bỏ chọn được revoke thay vì xóa. Assignment revision ngăn job cũ cấp lại danh sách đã bị thay thế. Notification chỉ có delivery record cho assignment mới, không gửi lại hàng loạt legacy.

`NotifyCouponAssignment` chạy sau commit; ledger pending có thể dispatch lại bằng `coupons:retry-notifications` (scheduler mỗi 5 phút). `queued` là queue đã nhận, không phải SMTP đã giao. Mail là at-least-once: worker chết sau khi gửi trước khi ghi nhận có thể gửi trùng thông báo; không tạo trùng quyền lợi. Failed mailable dùng queue retry thông thường. Không tuyên bố exactly-once email hoặc đã xác minh SMTP thật.

## Phiếu kho và discrepancy

Admin batch tạo header và các dòng kết quả trong transaction chung với paired transfer/import/export/stocktake. Root operation replay trả cùng document_id. Header giữ payload được validate, dòng giữ movement/discrepancy IDs. Không suy diễn trạng thái in-transit vì chưa có quy trình vận chuyển liên kho được duyệt; transfer vẫn ghi nhận hai đầu tức thời trong một transaction.

Stocktake thấp hơn reserved giữ nguyên số dư và mở discrepancy. Resolve cần admin, operation key, **số đếm mới >= reserved**, lý do; tạo movement adjust, giữ số đếm ban đầu và bổ sung actor/time/count/reason resolution trong details. Không tự hủy reservation để khớp số đếm. Header `needs_reconciliation` là kết quả lúc lập phiếu; trạng thái đóng thực tế đọc từ discrepancy và resolution operation, không ghi lại dòng chứng từ cũ.

Settings lưu threshold vào database, có actor/time; ngưỡng tổng quát chỉ dùng báo cáo tổng quát. Min stock riêng SKU vẫn áp dụng ở filter low-stock cũ. Không cho tắt auto_reserve hoặc bật negative stock, tránh phá invariant P4.

## Catalog, identity và media

`SaveProduct` là persistence adapter admin của Catalog: transaction product/specs/SKUs, khóa product trước variant, scope mọi child ID, chặn reparent SKU. Chỉ lấy fields được validate; không nhận đường dẫn ảnh mới từ client. Upload mỗi file một lần; lỗi file/DB xóa file mới của lần lưu đó. Ảnh cũ chỉ cleanup sau outer commit và khi không còn product/SKU (kể cả archive) tham chiếu. File không thuộc gallery bị từ chối. Process kill hoặc outer rollback sau service có thể để file mới không được tham chiếu; không tự quét/xóa file lịch sử.

`SaveAccount` dùng validated payload; lưu avatar trên public disk, cleanup sau commit/rollback đúng hướng; status/role events sau commit, revoke token khi đổi quyền/trạng thái. Không tự hạ quyền/khóa hoặc xóa tài khoản đang đăng nhập; admin không bị archive. User destroy sửa lỗi phép gán role và soft archive đúng tài khoản.

Product/SKU/user/coupon/category chỉ archive/restore; force-delete endpoints trả warning, giữ dữ liệu. Category cũng bị chặn vì FK legacy cascade từ category tới product. Order relations đọc được user/product/variant đã archive; tên/giá trên chi tiết đơn ưu tiên snapshot. Không thay FK cascade legacy; raw SQL/maintenance ngoài các entrypoint này phải được review riêng, không được dùng để xóa lịch sử.

## HTTP contracts

Các endpoint sau nằm trong `auth + is_admin`, web session + CSRF. Client JSON thêm Accept: application/json; validation 422, domain conflict 409, thiếu quyền 403.

| Endpoint | Request chính | Response JSON |
| --- | --- | --- |
| GET /admin/orders/{order}/after-sales | — | order_id, payment_status, payments[], returns[]; HTML nhận trang đối soát |
| POST /admin/orders/{order}/money | operation_key, action, amount khi collect/request; refund_id khi settle/reject; reference khi collect/settle; reason khi refund | id, status |
| POST /admin/orders/{order}/returns | operation_key, order_item_id, quantity, disposition sellable/damaged, reference, reason | receipt_id, disposition, movement_ids, discrepancy_id |
| GET /admin/inventory/documents/{id} | — | document, lines[] |
| POST /admin/inventory/discrepancies/{id}/resolve | operation_key, quantity, reason | movement_ids, discrepancy_id, status |

Checkout giữ payload/response cũ, thêm optional `coupon_code`. Vue checkout gửi mã trong cùng payload và fingerprint; thay coupon tạo operation key mới. Không gọi các cart coupon placeholder. Voucher profile query theo assignment của authenticated user, thay quan hệ creator sai trước đây.

Không công bố các placeholder không có handler: uploadTemp product/SKU, coupon import/export, user filter POST. GET list/filter user vẫn giữ nguyên; coupon archive/list/restore được triển khai; warehouse show và inventory transaction detail có handler read-only. Không xóa chức năng import/export đang hoạt động: hai coupon handlers này không tồn tại trước P6.

Chi tiết vận hành: [runbook](../runbooks/P6_OPERATIONS.md). Trạng thái kiểm chứng: [P6_STATUS](../roadmap/P6_STATUS.md).
