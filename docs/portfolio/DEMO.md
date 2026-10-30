# Demo Backend portfolio

Ngày chuẩn bị: 2026-10-31. Demo local dùng dữ liệu tổng hợp; không publish và không liên hệ khách hàng/provider. Kết quả chạy máy được lưu trong [demo-verification.json](demo-verification.json). Không dùng kết quả HTTP này để khẳng định đã kiểm tra trực quan giao diện hay đo tải đồng thời.

Đã chạy 24 checks HTTP và Vite proxy; kiểm tra recovery ở [recovery-verification.json](recovery-verification.json). Full regression P0–P8: 224 tests, 2.384 assertions, 1 skip dự kiến; xem [báo cáo review](../review/P0_P8_REVIEW.md).

## Khởi động

PHP 8.3/PDO MySQL, MySQL 8.0.30 local, Node và dependencies từ lockfiles phải có sẵn. Trên clone mới, tạo `.env` nếu chưa có, trỏ tới một database local rỗng dành cho demo; tài khoản cần quyền tạo/xóa schema sandbox. Script không provision MySQL server và không tự sửa quyền tài khoản. Không dùng DB remote. Không cần import SQL thật hoặc migrate database nguồn.

```powershell
php scripts/portfolio-demo.php verify --frontend
php scripts/portfolio-demo.php serve --frontend --seconds=900
```

Windows máy hiện tại nếu PHP chưa trong PATH: thay `php` bằng `& 'F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe'`. Script tạo `apple_project_p1_fresh_<12hex>` hoàn toàn mới, dùng schema fixture và sáu migration bổ sung; migration metadata fixture vẫn là baseline 50, không phải receipt rollout 56 của ứng dụng. Không chạy `migrate` trong demo này.

`verify` chạy HTTP tự động, xuất JSON kiểm chứng rồi xóa sandbox. `serve` tạo sandbox mới chưa có đơn, mở backend/Vite trên `127.0.0.1` với cổng được in trong terminal. Mặc định 900 giây, tối đa 3.600 giây; để process kết thúc bình thường cho cleanup. Không đổi source `.env`, database nguồn, task Windows, queue worker hoặc cache ứng dụng.

| Tài khoản tổng hợp | Mật khẩu |
| --- | --- |
| `admin@example.test` | `PortfolioDemo!2026` |
| `buyer@example.test` | `PortfolioDemo!2026` |
| `other@example.test` | `PortfolioDemo!2026` |

Đây là credentials công khai **chỉ cho sandbox**. Không tạo các tài khoản này trong DB ứng dụng. Mail là array, VNPay/P8 tắt; router demo chặn payment/OAuth/AI, dùng địa chỉ giả và chặn `/storage` nguồn. Không có ảnh sản phẩm tổng hợp; UI có thể dùng ảnh fallback. Một số tài nguyên giao diện ngoài máy chưa được kiểm chứng offline. Không hứa demo toàn bộ tích hợp Internet.

## Kịch bản trình bày 8–10 phút

1. **1 phút — bài toán:** mở [case study](CASE_STUDY.md), giải thích giá vốn khác giá bán; giữ tồn khác xuất tồn; chọn một kho đủ toàn đơn; tiền COD độc lập trạng thái giao hàng.
2. **1 phút — kiến trúc:** mở `app/Modules/Ordering/Application/PlaceOrder.php`, Contracts Inventory/Payments, [module DAG](../architecture/p5-module-boundaries.md). Chỉ ra transaction vẫn local vì tiền và tồn cần nhất quán.
3. **2 phút — storefront:** mở URL Storefront được in, đăng nhập buyer qua UI, vào `/p7-phone-1`, chọn SKU, số lượng 1, checkout COD bằng địa chỉ Demo Province/District/Ward. Nếu UI gặp vấn đề trình duyệt, không sửa dữ liệu thủ công; dùng kết quả `verify` đã kiểm chứng HTTP để phân biệt lỗi UI với nghiệp vụ.
4. **2 phút — admin:** vào URL Admin `/login`, đăng nhập admin. Xem đơn mới ở `/admin/orders`, mở chi tiết, chuyển pending → confirmed → shipping → completed. Với SKU/kho được phân bổ, lúc confirmed: physical 10/reserved 1/available 9; khi shipping: physical 9/reserved 0/available 9. Completed chưa đồng nghĩa COD đã paid.
5. **1 phút — đối soát:** mở mục after-sales ở đơn; ghi thu đủ 25.000.000 VND, reference `DEMO-COD-001`. Paid chỉ xuất hiện sau bước này. Refund chỉ ghi yêu cầu/đối soát; không demo hoàn tiền provider thật. Hàng trả phải có disposition; không cộng available nếu damaged.
6. **1 phút — safety:** mở JSON verify: giá client `1` không được tin; retry checkout/xuất kho/COD không tạo hiệu ứng lần hai; buyer khác nhận 404; session thiếu CSRF nhận 419. Mở concurrency tests P4/P6 để chỉ bằng chứng hai connection/process độc lập, không dùng HTTP demo tuần tự làm bằng chứng race.
7. **1 phút — tradeoff:** trình bày detail 11→8 query, CSV 374→26 MiB trên workload P7; P8 read nhanh hơn nhưng write đắt hơn nên giữ monolith. Chỉ ra rõ giới hạn provider/SLO/legacy trong review.

Luồng API/CSRF được tự động kiểm tra trong `tests/Support/PortfolioScenario.php`. Thứ tự screenshot nên chụp thủ công từ **sandbox**: storefront, checkout COD, đơn confirmed, shipping với số dư tồn, COD paid, JSON kiểm chứng. Không dùng DevTools hiển thị Bearer/cookie hoặc quay `.env`. Chưa có screenshot/video được tạo trong đợt này.

## Dừng và recovery

Kết thúc thời gian `serve`: PHP/Vite process, database do script sở hữu, config/session/view/uploads tạm được xóa. Cổng là ngẫu nhiên, không đụng backend khác. Đóng terminal cưỡng bức/Ctrl+C hoặc mất điện có thể bỏ qua `finally`; **không bảo đảm cleanup khi hard kill**.

Script in `Runtime: tests/.runtime/portfolio-<id>` và lệnh recovery tương ứng:

```powershell
php scripts/portfolio-demo.php cleanup <id-12-hex-da-duoc-in>
```

Chỉ chạy sau khi demo server đã dừng. Recovery kiểm tra source identity, DB allowlist, exact marker và cache paths; từ chối khi server còn phản hồi health. Nếu Vite còn chạy sau terminal crash, dừng đúng process demo dựa trên command line/cổng trước khi recovery, không kill toàn bộ `node.exe`/`php.exe`. Nếu DB/manifest mất hoặc môi trường đã thay đổi, cleanup fail closed; cần review manifest private thay vì xóa theo wildcard. Tuyệt đối không dùng `migrate:fresh`, `db:wipe` hoặc DROP database nguồn.

## Phạm vi kiểm chứng

Đã có actual loopback HTTP, Bearer, admin session + CSRF, cart/checkout, ownership, chuyển trạng thái, shipping và COD replay; Vite proxy `/api/home` trả thành công. PHP development server tuần tự, không đại diện production webserver. Chưa browser automation/visual QA, chưa provider end-to-end, chưa deploy/HTTPS, network partition hoặc tải HTTP đồng thời. Recovery sau process chết cần exact marker; không có “dọn mọi sandbox cũ” tự động.
