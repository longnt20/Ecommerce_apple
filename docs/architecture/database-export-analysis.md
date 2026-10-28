# Đối chiếu database export laravel.sql

Ngày phân tích: 2026-10-28. Nguồn: [laravel.sql](../../laravel.sql), 763.116.460 bytes (khoảng 763 MB thập phân). Header ghi export `Oct 28, 2026 at 09:39 AM`, server version `8.0.30`. Không suy ra timezone export từ giờ client.

## Phạm vi và phương pháp

Đã quét toàn file theo streaming để đọc 40 CREATE TABLE, các ALTER TABLE/index/FK, migration history và thống kê INSERT. Đã parse các tuples nghiệp vụ inventory, inventory_transactions, orders/items, products/variants, warehouses, carts/items, coupons/uses, users và migrations để kiểm tra số học, quan hệ và đối chiếu trạng thái. Kiểm tra số trường khớp danh sách cột INSERT; không có lỗi parse trong tập bảng này.

Không thực thi SQL, không import, không kết nối DB, không thay đổi source/dependencies hoặc dữ liệu. Dữ liệu user/order chỉ dùng các trường cần thiết trong bộ nhớ; báo cáo không chứa tên, email, địa chỉ, password, token hoặc nội dung session/log. Telescope được thống kê kích thước/số tuple, không audit từng payload. Đây là snapshot export; không chứng minh trạng thái server sau export, isolation level, runtime SQL mode hoặc tính nhất quán thời điểm export giữa các bảng.

## 1. Schema thực tế và điều chỉnh audit

Toàn bộ 40 bảng khai báo InnoDB, utf8mb4_0900_ai_ci. Phiên bản và engine này xác minh cho dump, chưa phải kết quả kiểm tra server đang chạy.

| Điểm audit trước | Bằng chứng trong SQL | Kết luận cập nhật |
| --- | --- | --- |
| Không thấy migration users.role | Dòng 718974: role enum admin/user, default user | Cột tồn tại trong export; thiếu đường tái tạo trong migrations đã khảo sát |
| Migration status dùng block | Dòng 718980: active/inactive/blocked | Export phù hợp code blocked; vẫn có schema drift |
| Migration tạo thumnail | Dòng 1564: thumbnail NOT NULL | Export có tên đúng; không kết luận lỗi thiếu cột trên snapshot này |
| Movement enum thiếu commit trong migration | Dòng 552 có commit; dữ liệu có 6 commit movements | Export hỗ trợ commit; audit phải phân biệt migration với DB thực |
| user code bắt buộc và unique trong migration | Dòng 718978: NOT NULL; index users tại 719349 chỉ PK/email unique | Export không có unique code; 4 users có code rỗng. Nguồn tạo dữ liệu này chưa xác minh |
| Bộ migration kho trùng create | Migration history dòng 899–905 ghi cả hai bộ đã chạy | History không chứng minh source migration hiện tại chạy được tuần tự trên DB trống; cách vượt xung đột lịch sử chưa xác minh |

Các drift bổ sung: products.short_description là TEXT (dòng 1262); product_specs.spec_value là VARCHAR(2000) (dòng 1410); posts có FULLTEXT(title, description, content) tại dòng 719227. Cần đưa các thay đổi ngoài luồng migrations vào schema baseline, không chỉ sửa các cột gây lỗi rõ ràng.

## 2. Quy mô dữ liệu

| Bảng/nhóm | Số tuples export |
| --- | ---: |
| products / product_variants | 28 / 194 |
| warehouses | 4 (3 active, 1 inactive) |
| inventory / inventory_transactions | 184 / 259 |
| orders / order_items | 52 / 56 |
| carts / cart_items | 3 / 1 |
| coupons / coupon_uses | 2 / 2 |
| users | 8 |
| migrations | 50 |
| failed_jobs | 2 |
| telescope_entries / telescope_entries_tags | 426.789 / 278.626 |

Không dùng AUTO_INCREMENT để suy ra số bản ghi. Inventory movements gồm import 190, adjust 1, export 3, transfer 1, reserve 56, commit 6, release 2. Đây có thể là dữ liệu thử nghiệm; không mặc định là giao dịch kinh doanh thật.

## 3. Kiểm tra tồn kho

- Tổng quantity = 2.989; available = 2.931; reserved = 58; incoming = 0.
- Cả 184 dòng không có quantity/available/reserved âm; không có reserved vượt quantity.
- Cả 184 dòng thỏa quantity = available + reserved.
- Không thấy duplicate warehouse-SKU hoặc inventory trỏ variant soft-deleted trong snapshot.
- Reserved từng warehouse-SKU khớp tổng reserve trừ commit/release đã ghi trong dump, tính quantity theo trị tuyệt đối và chiều nghiệp vụ.

Đây là kiểm tra snapshot và reservation movements, không phải chứng minh toàn bộ ledger tồn vật lý có opening balance đầy đủ hoặc code an toàn khi đồng thời.

### Trạng thái đơn không khớp reservation còn lại — High

Có 20 đơn không còn pending nhưng vẫn có net reserve dương trong các movement tham chiếu order:

| Trạng thái | Order IDs | Số đơn | Net reserve |
| --- | --- | ---: | ---: |
| confirmed | 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 29, 32, 34, 35, 40 | 15 | 17 |
| cancelled | 30, 31, 36 | 3 | 3 |
| shipping | 37 | 1 | 1 |
| completed | 39 | 1 | 1 |

Tổng 22 đơn vị vẫn nằm trong net reserve của nhóm này. Với policy code hiện tại trừ hàng tại confirm và release tại cancel, đây là điểm bất nhất cần đối soát. Bằng chứng orders ở dòng 988–1009; movement INSERT bắt đầu dòng 576. Không tự suy ra phải trừ/hoàn thêm 22: cần xác minh hàng thực tế, lịch sử code, thao tác thủ công và tính đầy đủ của ledger.

**Cập nhật theo Q4 đã xác nhận tại P0:** chủ dự án muốn chỉ giảm tồn vật lý khi xuất/giao cho vận chuyển. Vì vậy 15 đơn confirmed còn reserve không tự thân là sai theo nghiệp vụ đích; nhận xét phía trên mô tả độ lệch so với code hiện tại commit khi confirm. Các đơn cancelled/shipping/completed vẫn cần đối soát; không tự động điều chỉnh số lượng từ thống kê này.

Cả 52 đơn ghi warehouse_id=1. Các movement có reference_type=order và order tồn tại không lệch warehouse_id so với order. Vì vậy lỗi thiết kế reserve sai kho trong audit vẫn tồn tại trong code, nhưng **chưa quan sát được ca lệch kho trong snapshot**. Dữ liệu này chưa kiểm chứng khả năng fulfillment đa kho.

Movement id=3 (dòng 579) có reference_type=order nhưng reference_id=NULL. Đây là tham chiếu thiếu, không phải bằng chứng một order đã bị xóa.

## 4. Đối chiếu tiền và trạng thái — High

| Phát hiện | Bằng chứng | Tác động cần kiểm tra |
| --- | --- | --- |
| Order 4: tổng items 138.660.000 nhưng total_price/final_amount đều 0 | Dòng 980 và order_items từ dòng 1057 | Tổng đơn và dòng hàng không khớp; không dùng tự động làm dữ liệu test chuẩn |
| Order 5: total_price 37.990.000, shipping/discount 0 nhưng final_amount 0 | Dòng 981 | Tổng thanh toán không theo công thức hiện tại |
| 3 đơn paid nhưng paid_at NULL | orders INSERT từ dòng 979 | Thiếu mốc đối soát; snapshot không xác nhận đã nhận tiền thật |
| 2 đơn COD completed vẫn unpaid | orders INSERT | Có thể thiếu bước collected hoặc quy tắc riêng; cần chốt nghiệp vụ, không tự chuyển paid |
| Pending có created_at từ 2025-12-02 đến 2026-03-27 | orders INSERT | Có đơn pending lâu so với ngày export; cần kiểm tra TTL/cleanup, không mặc định tự hủy |

Toàn bộ 56 items thỏa total = quantity × price. Không thấy item có variant thuộc product khác. Ngoài order 4, tổng items khớp total_price; ngoài order 5, final_amount khớp total_price + shipping_fee - discount_amount. Các phép kiểm này không chứng minh giá bán đúng hoặc payment đã được provider xác nhận.

## 5. Constraints và indexes được xác nhận

- Inventory có unique warehouse-SKU, đồng thời có index thường cùng cột (dòng 719143–719149). Cần xem xét index dư sau EXPLAIN, không tự drop.
- InventoryTransaction có unique transaction_code và index thường cùng cột (dòng 719154–719165).
- Cart_items chỉ có primary key (719101); không có index cart_id, unique cart-SKU hoặc FK. Carts có user_id unique nhưng không FK user.
- Coupon_uses có FK/index user/coupon nhưng không unique chống duplicate assignment (719122, 719592). Dữ liệu hiện chưa có duplicate, không đồng nghĩa retry an toàn.
- Không tìm thấy CHECK constraint trong DDL dump bảo vệ stock invariants.
- Orders có unique code và FK indexes; không có payment attempt/event/reservation tables trong 40 bảng export.
- Orders.user_id và warehouse_id ON DELETE CASCADE (719617); order_items.product_id ON DELETE CASCADE (719624). Các FK khác có thể chặn một số hard delete, không được coi cascade là đã chắc chắn xảy ra.

## 6. Migration history

Toàn bộ tên migration đang có trong `database/migrations` xuất hiện trong dump. Có 6 tên đã ghi applied nhưng không có file trong thư mục đó:

```text
2019_02_08_105647_create_blocks_contents_tables
2021_11_12_153947_remove_blocks_contents_tables
2025_10_14_130003_create_promotions_table
2025_10_14_130004_create_promotions_table
2025_10_14_130155_create_promotion_items_table
2025_10_14_130355_create_promotion_items_table
```

Hai tên blocks có thể liên quan package nhưng nguồn chưa xác minh ở lần đọc SQL này. Không kết luận file bị xóa thủ công hoặc schema đã sửa thủ công; có thể có nhiều lịch sử khác nhau. Bảng migrations không lưu hash nội dung file nên không chứng minh file hiện tại giống file khi đã chạy.

**Bổ sung xác minh P0:** hai migration blocks đã được tìm thấy trong `vendor/van-ons/laraberg/database/migrations/`. Vì vậy đó là package migrations, không phải bằng chứng repository mất file ứng dụng. Bốn tên promotions/promotion_items cũ vẫn chưa tìm thấy trong thư mục migrations ứng dụng hiện tại. Metadata runtime đọc riêng trong [p0-runtime-baseline.json](p0-runtime-baseline.json) khớp 50 migration names/batches trong snapshot khi đối chiếu; chưa xác minh lịch sử nội dung từng file.

## 7. Dung lượng observability

INSERT Telescope entries chiếm 746.655.614 bytes, tags 15.663.605 bytes; cộng khoảng 99,896% dung lượng dump. Đây là dung lượng văn bản export, không phải kích thước vật lý InnoDB hoặc số đo query latency.

Đề xuất P7: xác minh retention/pruning và cấu hình watchers; tách schema/fixtures nghiệp vụ đã ẩn danh khỏi log khi làm môi trường test/portfolio. Dump chứa bảng users, sessions, personal_access_tokens và log; không sử dụng nguyên file làm fixture công khai. Chưa kiểm tra payload Telescope để kết luận có lộ credential cụ thể.

## 8. Ảnh hưởng đến lộ trình

- P0 đã có schema snapshot và migration history làm baseline đầu vào; vẫn cần kiểm chứng runtime, DB test cách ly và quyết định giá/kho. Chưa coi P0 hoàn thành.
- P1 phải tái tạo đúng schema export có role/blocked/thumbnail/commit và ghi nhận index/code khác migration. Không chạy lại migrations cũ hoặc thêm cột đã tồn tại một cách mù quáng.
- P3/P4 cần dataset regression ẩn danh mô phỏng 20 đơn còn net reserve ngoài pending; kiểm tra reconciliation trước khi backfill reservation.
- P2/P6 cần test các tổng đơn lệch và paid thiếu timestamp; không tự sửa số tiền hoặc trạng thái tài chính lịch sử.
- P7 ưu tiên kiểm chứng retention Telescope vì dump gần như toàn bộ là observability data.

Schema snapshot làm rõ audit; nó không loại bỏ các lỗi code giá client, callback thiếu xác minh, cart ownership và concurrency đã tìm thấy.
