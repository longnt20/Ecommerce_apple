# P3 — Contract chuyển trạng thái đơn

Ngày triển khai: 2026-10-29. Code đã có kiểm chứng sandbox; rollout DB ứng dụng và xử lý legacy chờ review. Không mở rộng P4.

## Policy duy nhất và thời điểm tác động tồn

`OrderTransitionPolicy` là nguồn quyết định cho cả admin UI và `OrderService`. Giữ graph hiện có:

| Từ | Sang | Tác động tồn P3 |
| --- | --- | --- |
| pending | confirmed | Giữ reservation, không giảm tồn vật lý |
| pending | cancelled | Release hàng đã giữ một lần; set cancelled_at |
| confirmed | shipping | Admin xác nhận bàn giao vận chuyển; giảm quantity/reserved cùng lượng, available giữ nguyên |
| shipping | completed | Không ghi tồn; set delivered_at |
| completed/cancelled | Không có | Chặn transition mới |

Thời điểm giảm tồn tuân theo quyết định đã chốt của chủ dự án: khi xuất/giao cho vận chuyển. Nút shipping là lời xác nhận của admin; P3 chưa có shipment/carrier scan, mã vận đơn hoặc tích hợp hãng vận chuyển. Không mở hủy confirmed/shipping hay tự hoàn tiền; các quy tắc đó chưa được chốt và nằm ngoài P3.

Không thêm confirmed_at/shipped_at vào orders: timestamp từng transition nằm trong lịch sử; cập nhật các cột cancelled_at/delivered_at hiện có. Shipping cập nhật inventory.last_shipped_at. Payment status độc lập; P3 không đặt điều kiện paid mới để thay đổi behavior fulfillment đã có. Nếu hủy đơn đã paid, tiền vẫn paid và cần quy trình refund/đối soát riêng ở P6; không coi release là refund.

## Application service và quyền

`OrderService::changeStatus(Order, newStatus, actorId, operationKey?, reason?, requestId?)` yêu cầu caller truyền actor tường minh. Actor phải là admin active, được đọc lại sau khi lấy order lock. Không dùng snapshot trạng thái/items đã load trước transaction. Caller nội bộ duy nhất tìm thấy là Admin OrderController và đã cập nhật; caller bên ngoài repository **chưa xác minh**.

Transaction theo thứ tự: order FOR UPDATE → đọc operation cũ/claim history mới → kiểm tra evidence → inventory theo `(warehouse_id,variant_id)` tăng dần → movement/status. Callback Payments P2 cũng khóa order trước attempt, nên không giữ attempt rồi đợi order. Retry deadlock tối đa ba lần, không có I/O mạng/mail trong transaction.

Không tìm thấy mail/event chuyển trạng thái Order hiện có cần di chuyển. Không thêm tính năng mail mới trong P3; tests xác minh không gửi mail. Các mail coupon/user không liên quan được giữ nguyên. Nếu bổ sung notification sau này phải dispatch sau outer transaction commit; P3 chưa có outbox/delivery guarantee.

## Idempotency và lịch sử

Migration mới chỉ tạo bảng `order_transitions`, không backfill hoặc sửa orders/inventory. Lưu order, actor, from/to, operation hash, payload hash, reason tùy chọn, request ID, created_at. Unique `(order_id,operation_hash)` chống key lặp, unique `(order_id,to_status)` bảo vệ graph acyclic hiện tại; FKs restrict xóa chứng từ. Nếu sau này cho phép quay lại trạng thái cũ phải review lại schema/key trước.

- Key được scope theo actor; payload hash bao gồm order/target/actor/reason đã chuẩn hóa.
- Cùng key + payload trả history cũ ngay cả khi order đã đi tiếp; không ghi movement/history/timestamp lần nữa. Actor vẫn phải có quyền hiện tại.
- Cùng key khác payload trả conflict. Key mới nhưng transition không còn hợp lệ cũng conflict.
- Client cũ không gửi key dùng identity mặc định theo actor/order/target; graph hiện tại không quay vòng nên tương thích. Form admin mới gửi UUID giữ nguyên khi browser retry request và có ô reason tùy chọn.
- `InventoryService::commitOrder/releaseOrder` nhận thêm OrderTransition context, kiểm tra status/operation và proof từng SKU; không thể dùng history đã hoàn tất để trừ/trả hàng lần nữa.
- Movement P3 có `meta_data.order_transition_id`, actor tường minh và mã `INV-P3-ULID`. Model chỉ sinh mã/actor mặc định khi caller chưa cấp. Generator số cũ bỏ qua prefix P3 để không bị reset sequence. Các writer nhập/xuất/chuyển cũ chưa được chuyển sang operation identity P4.

## Guard allocation legacy

Không suy ra kho từ default 1, không tự đổi order.warehouse_id hoặc chọn lại kho. Guard so khớp items và mọi movement có reference_type=order/reference_id:

- Items phải có SKU/quantity hợp lệ; aggregate nhiều dòng cùng SKU.
- Mọi movement thuộc đúng warehouse của order, đúng SKU, completed, có loại/sign/before/after hợp lệ.
- Tổng reserve đúng items, chưa release. Trước bàn giao không được có commit; khi shipping → completed phải có commit đúng lượng.
- Lock và kiểm tra inventory hiện tại: quantity/reserved không âm, reserved không vượt quantity, available = quantity - reserved; chưa ship phải còn đủ reserved.

Thiếu/sai bằng chứng trả conflict; không tạo allocation hay sửa tồn. Đây là guard bảo thủ, không thay thế reservation rows hoặc protocol mọi writer trong P4. Proof theo order không chứng minh tổng reservation toàn hệ thống; các writer admin khác vẫn còn race ngoài phạm vi P3.

## HTTP contract và UI

Giữ `PATCH /admin/orders/{order}` và response thành công redirect/flash hiện có. Payload status giữ nguyên; thêm operation_key tùy chọn (tối đa 128 byte trong service), reason tùy chọn (500 ký tự). Actor lấy từ user đã auth, không từ payload. Không thêm API công khai cho customer.

JSON conflict trả HTTP 409 `{message,code,request_id}`; forbidden actor 403; validation 422. Mã conflict ổn định: invalid_transition, operation_payload_conflict, transitions_unavailable, invalid_operation; nhóm evidence gồm invalid_items, missing_allocation, ambiguous_allocation, invalid_movement_evidence, reservation_not_proven, legacy_stock_state, missing_inventory, stock_balance_conflict. Context tồn sai có stock_operation_conflict/stock_quantity_conflict. Browser conflict quay lại với flash error để giữ luồng form.

Admin detail hiển thị history, actor ID, reason và thời điểm; dùng escaping Blade. Nút shipping ghi rõ bàn giao sẽ trừ tồn. Khi rollout tắt/chưa có bảng, ẩn nút và hiển thị tạm dừng; history đã có vẫn đọc được. Không có dd hoặc trả stack trace.

## Dry-run, rollout và rollback

[Audit legacy](p3-legacy-order-audit.json) là snapshot READ ONLY có ID/status/mã phân loại, không thông tin khách hàng. 52 đơn: 18 phù hợp evidence tại thời điểm đọc; 27 bị chặn (22 invalid_items, 1 missing_allocation, 4 legacy_stock_state); 7 terminal. Phân loại không phải phê duyệt xử lý và phải chạy lại ngay trước cutover.

Đường xử lý đề xuất, chưa tự thực hiện:

1. Giữ `ORDER_TRANSITIONS_ENABLED=false`. Reviewer xác nhận graph/ý nghĩa bàn giao và danh sách legacy; backup/rehearse theo P1.
2. 22 invalid_items: đối chiếu snapshot SKU/chứng từ; không gán variant theo product hiện tại. 1 missing_allocation: cần chứng từ giữ hàng; không dùng default kho. 4 legacy_stock_state: đối soát đã xuất thực tế hay chỉ commit do confirm cũ; không giảm tồn lần hai.
3. Không mở transition cho nhóm chặn cho tới khi có phương án sửa/backfill/movement bù riêng được phê duyệt. P3 không cung cấp nút bypass. 18 đơn phù hợp cần kiểm tra lại evidence/balance tại thời điểm thao tác; 7 terminal không mở lại.
4. Chỉ chạy migration `2026_10_29_000002_create_order_transitions.php` trên DB ứng dụng sau phê duyệt. P2 payments rollout độc lập và vẫn có gate riêng; không tự migrate toàn bộ lịch sử hoặc enable VNPay.
5. Deploy code/UI cùng lượt, rồi enable ORDER_TRANSITIONS_ENABLED=true và làm mới config cache theo deploy đã duyệt. Smoke test trên môi trường staging được phép, theo dõi conflict/history/movement.

Rollback: tắt transition mới bằng flag, giữ history/stock/paid data; không migrate down (migration cố ý forward-only), không phục hồi confirm trừ kho hoặc stale update cũ. Đơn đã confirmed theo P3 còn reserved; quay về code confirm-commit cũ sẽ làm sai semantics. Các điều chỉnh dữ liệu chỉ được thực hiện bằng kế hoạch riêng/movement bù có duyệt; không restore đè DB đang có giao dịch mới. Dừng P3 để review, không triển khai P4.
