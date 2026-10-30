# Vận hành P6

Owner xử lý: chủ dự án trong vai trò Admin đối soát tiền/hàng. Scheduler/queue do người vận hành Laravel theo dõi; không tự xác nhận giao dịch thay admin.

## Triển khai và kiểm chứng

PHP tại môi trường hiện tại: `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe`. Thay `php` bên dưới bằng đường dẫn này nếu PATH chưa cấu hình. Không chạy migrate:fresh/reset trên DB ứng dụng.

1. `php tests/Support/prepare-p6-sandbox.php --sandbox-only` chỉ nâng sandbox tổng hợp, dùng account DML giới hạn khi test.
2. Bật `P1_SCHEMA_TESTS=1`, `P2_SCHEMA_TESTS=1`, `P3_SCHEMA_TESTS=1`, `P4_SCHEMA_TESTS=1`, `P6_SCHEMA_TESTS=1` trong process test; chạy `php vendor/bin/phpunit`. Các schema tạm chỉ có dữ liệu tổng hợp và bị dọn bởi allowlist.
3. `php tests/Support/rollout-schema.php p6 --rehearse`: full backup riêng tư, restore DB clone, chạy đúng migration manifest, so projection mọi cột cũ của dữ liệu nghiệp vụ. Bản backup có dữ liệu thật, không commit/chia sẻ. Nếu nguồn đổi trong lúc rehearsal, không apply; làm lại.
4. Chỉ sau checks xanh và quyền áp dụng đã được chủ dự án duyệt: `php tests/Support/rollout-schema.php p6 --apply`. Không chạy script cutover P4 lần hai, không backfill tiền/refund/return/coupon cũ.
5. `php tests/Support/verify-p6-runtime.php --application-read-only`, `php artisan inventory:reconcile`, `php artisan schedule:list`. Với CLI ngoài tests, đặt `TELESCOPE_ENABLED=false` trong process để việc kiểm tra không ghi Telescope.
6. Worker dài hạn cần restart có kiểm soát khi thực tế đang chạy. Không bật VNPay merchant hoặc gửi email thật chỉ để test P6. Google/VNPay E2E giữ trạng thái chưa xác minh từ P5.

Quan sát runtime trong P6: Windows task `AppleProject-LaravelScheduler` đang **Disabled**, LastTaskResult=0 của lượt chạy cũ, 28 missed runs. Chưa xác minh ai/lý do tắt; không tự thay trạng thái tác vụ đã tắt. `schedule:list` xác minh đăng ký, không chứng minh task/worker đang chạy. Operator cần kiểm tra Task Scheduler và quyết định bật lại trước khi dựa vào auto-expiry hoặc auto-retry notification. P6 không bật VNPay; manual recovery commands vẫn sẵn có. Không coi P4 runtime check ở ngày trước là bằng chứng scheduler hiện đang hoạt động.

## Kiểm tra thủ công trên dữ liệu thử

- Tạo một đơn COD có 2 sản phẩm cùng SKU, confirm → shipping → completed. quantity giảm tại shipping, completed vẫn unpaid. Từ chi tiết đơn mở **Đối soát tiền / hàng trả**, nhập mã thu COD và xác nhận đúng toàn bộ final_amount; paid chỉ đổi khi ledger được ghi.
- Request refund một phần, ghi kết quả đã hoàn với mã sao kê và lý do; thử request vượt số tiền còn lại phải bị từ chối. Tạo request khác rồi reject: hạn mức được trả lại. Kiểm tra không phát sinh movement kho.
- Nhận một hàng damaged: chỉ có receipt, available không tăng. Nhận một hàng sellable sau kiểm tra: một import bù, quantity/available tăng đúng một; nhận vượt số đã xuất phải bị từ chối. Payment/refund không đổi.
- Cấp coupon cho user thử. Đặt bằng mã đã cấp; retry cùng operation key không tạo đơn mới. Hủy pending hoặc expiry online trả lượt. COD confirm/online paid tăng used_count đúng một; sửa coupon không đổi discount snapshot trên đơn đã tạo.
- Thực hiện transfer hai đầu, xem inventory_documents và dòng movement. Kiểm kê thấp hơn reserved mở discrepancy; đếm lại chỉ resolve khi số mới đủ reserved. GET document phản ánh chứng từ gốc, details của discrepancy là nguồn resolution.
- Lưu settings, tải lại trang phải giữ threshold. Thử auto_reserve=0 hoặc negative_stock_allowed=1 qua JSON phải 422.
- Thử child ID từ product khác, gallery path của user khác: 422, không thay dữ liệu. Archive product/SKU/user, xem đơn cũ vẫn có snapshot. Force delete product/SKU/user/coupon/category phải bị chặn bằng warning.

Các bước ghi dữ liệu thủ công dùng sandbox/dev fixture; không tạo đơn, chuyển tiền hay thay tồn thật trong lúc nghiệm thu tự động.

## Đối soát và recovery

| Tình huống | Owner và cách xử lý | Điều kiện đóng |
| --- | --- | --- |
| COD completed nhưng unpaid | Admin kiểm tra sao kê đơn vị giao hàng. Chưa có tiền thì giữ unpaid; đủ tiền mới collect_cod với mã duy nhất | ledger collected và order paid cùng transaction, amount/reference khớp chứng từ |
| Refund requested chưa có kết quả | Admin tra ngân hàng trước khi settle/reject; không gửi chuyển khoản lần hai chỉ vì request retry | một terminal event, reference có thể tra cứu; tổng settled không quá collected |
| Callback muộn hoặc legacy paid thiếu chứng cứ | Payments owner kiểm tra attempt/event và provider theo runbook P4. Không fake cờ paid/receipt hoặc sửa SQL để vượt guard | có bằng chứng và phương án điều chỉnh được duyệt riêng; không tự nhả/giữ kho hoặc redeem coupon |
| Hàng trả damaged | Inventory owner để khu vực cách ly vật lý; theo dõi lượng theo receipt/order item, tách khỏi stocktake pool bán được | biên nhận khớp hàng thực tế; disposition tiếp theo cần workflow được phê duyệt, không tự nhập bán |
| Stocktake dưới reserved | Inventory owner xác minh hàng và các đơn đang giữ. Chỉ cancel đơn pending có căn cứ qua workflow cũ, hoặc đếm lại | fresh count >= reserved, resolve có reason/actor/operation/movement; reconciliation không còn lệch không giải thích |
| Legacy assignment trùng / counter mơ hồ | Pricing owner lập danh sách từ truy vấn read-only; giữ nguyên hàng cũ, từ chối áp dụng | có mapping/counter reconciliation được duyệt; không tự xóa/gộp duplicate |
| Assignment job lỗi sau một phần | Queue retry cùng job; coupon lock/check revision giữ assignment duy nhất | số assignment/delivery không tăng trùng; assignment đã revoke không bị job revision cũ hồi sinh |
| Chưa dispatch email sau cấp mã | `php artisan coupons:retry-notifications --limit=100`; scheduler mỗi 5 phút cũng dispatch pending | delivery queued/suppressed; queued chỉ chứng minh queue nhận, kiểm failed_jobs/mail transport riêng |
| Mailable failed | Operator xem `queue:failed`, retry đúng failed job sau khi sửa transport; không tạo lại assignment | job xử lý xong và mail transport xác nhận; có thể có thông báo trùng khi crash, quyền lợi vẫn duy nhất |
| Upload/DB lỗi | Không xóa ảnh cũ; service rollback DB và file mới đã biết. Nếu process kill, kiểm file orphan theo thời gian + toàn bộ references, giữ ảnh archive | ảnh đang tham chiếu còn đọc được, không có partial aggregate; không quét xóa hàng loạt storage |

## Rollback

- Chưa có P6 business writes: có thể hoàn lại đúng các file trong diff P6 sau khi lưu patch review, giữ nguyên dữ liệu/schema additive. Không reset cả workspace hoặc frontend/dist của người dùng.
- Đã có redemption/thu tiền/refund/return: tạm dừng ghi mới trong cửa sổ bảo trì, giữ code xử lý lifecycle và schema, đối soát ledger trước khi đưa compatibility/forward fix. Không rollback về P5 thuần vì P5 không redeem/release coupon mới và không biết after-sales ledger.
- Không restore backup đè lên giao dịch mới, không xóa event, không sửa movement cũ. Sai số tiền/hàng phải có quyết định đối soát và operation bù được review.
- Migration down từ chối khi các bảng mới có dữ liệu. Metadata assignment được giữ ngay cả khi bỏ schema trống. DDL MySQL không atomic: lỗi DDL giữa chừng phải kiểm các bảng/index đã tạo và chạy lại up có guard; không xóa bảng cho “sạch”.

Chỉ bước sang P7 khi chủ dự án review P6; runbook này không triển khai worker mới, CI, provider integration hoặc Microservices.
