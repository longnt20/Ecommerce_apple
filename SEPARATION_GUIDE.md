# Frontend và Backend thực tế

Cập nhật review 2026-10-31. Hướng dẫn này thay mô tả cũ có thư mục `backend/` và các endpoint không tồn tại. Laravel nằm ngay root; admin vẫn dùng Blade và session.

```text
app/Http/Controllers/     HTTP adapters API/admin
app/Modules/              Identity, Catalog, Pricing, Inventory, Payments, Ordering
routes/api.php            Storefront API, prefix /api
routes/web.php            Admin/login Blade, cùng các web callbacks
resources/views/          Admin UI
frontend/src/             Vue storefront
frontend/vite.config.js   Vite và dev proxy
```

## Kết nối local

Axios tại `frontend/src/services/api.js` dùng base URL `/api`; Bearer token được gắn cho API cần đăng nhập. Admin đăng nhập `/login` dùng session cookie và CSRF. Hai cơ chế không thay thế nhau.

Trong cấu hình dev thông thường, Laravel phục vụ cổng 8000 và Vite cổng 3000. Vite proxy `/api` tới `http://127.0.0.1:8000`; có thể đặt `VITE_DEV_API_TARGET` cho một backend local khác. Proxy chỉ phục vụ dev, không cấu hình reverse proxy production.

```powershell
# Demo portfolio: dùng schema/process cách ly, không cần tự mở artisan serve.
php scripts/portfolio-demo.php serve --frontend --seconds=900
```

Script in cổng admin/storefront thực tế. Không chạy thêm backend mặc định dùng dữ liệu thật cho kịch bản này. Chi tiết tài khoản, fixture địa chỉ và cleanup: [DEMO.md](docs/portfolio/DEMO.md).

Nếu chạy ứng dụng bình thường trên một môi trường development đã chuẩn bị, dùng `php artisan serve --host=127.0.0.1 --port=8000` và `npm run dev --prefix frontend`. Hai lệnh này dùng cấu hình development của bạn, **không tự cách ly database** như script demo. Không ghi đè `.env` đã tồn tại.

## Endpoint có bằng chứng trong routes/api.php

| Chức năng | HTTP |
| --- | --- |
| Đăng ký/đăng nhập | POST `/api/register`, POST `/api/login` |
| Tài khoản có Bearer + active | GET `/api/user`, POST `/api/logout` |
| Home/catalog | GET `/api/home`, `/api/home-main`, `/api/products/{slug}`, `/api/products/{product}/variants` |
| Nội dung | GET `/api/categories`, `/api/promotions`, `/api/promotion-categories`, `/api/banners`, `/api/blog` |
| Cart có Bearer + active | GET `/api/cart`, POST `/api/cart/add`, PUT/DELETE `/api/cart/item/{item}`, DELETE `/api/cart` |
| Checkout/order có Bearer + active | POST `/api/checkout`, POST `/api/orders/{order}/payment`, GET `/api/orders`, `/api/orders/{id}` |
| Wishlist có Bearer + active | GET `/api/wishlist`, POST `/api/wishlist/toggle`, DELETE `/api/wishlist/{id}` |

Checkout yêu cầu `idempotency_key`, server quote giá từ SKU. Retry giữ cùng key và payload; browser return không được quyết định paid. Xem [P4 contract](docs/architecture/p4-inventory-contract.md), [P2 contract](docs/architecture/p2-http-contracts.md).

Không có GET `/api/products` listing hoặc POST `/api/orders` checkout. `apiResource` categories/promotions hiện đăng ký dư action không có method; chỉ index và endpoint promotion-categories kể trên được xác minh implementation. Vấn đề này được giữ trong [review](docs/review/P0_P8_REVIEW.md), chưa tự thay API contract.

## Khi build/deploy

`npm run build --prefix frontend` sinh `frontend/dist/`. `vite preview` không thay thế backend và không chứng minh cấu hình deploy `/api`. Cần thiết kế host/reverse proxy, history fallback, cookie/domain/HTTPS và callback URL riêng trước khi publish. Chưa deploy trong đợt review này. Không import `laravel.sql`, copy `.env`, dùng dữ liệu khách hàng hoặc bật P8 để trình diễn.
