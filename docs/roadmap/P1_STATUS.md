# P1 — Schema tái lập và upgrade giữ dữ liệu

**Review tới P4, 2026-10-29:** fresh/upgrade/restore tiếp tục được kiểm thử với tổng 55 migrations. Fingerprint test giữ toàn bộ cột gốc để so dữ liệu khi P4 bổ sung operation_id/operation_line nullable. P1 repair/schema snapshot lịch sử giữ nguyên. P2–P4 đã rehearsal/rollout riêng, xem [bằng chứng](../architecture/p4-migration-verification.json), [P4_STATUS](P4_STATUS.md). Các số 51 bên dưới thuộc mốc P1.

Ngày: 2026-10-28. **Đã triển khai và kiểm chứng kỹ thuật P1; dừng để chủ dự án review trước P2.** Đã áp dụng đúng một migration repair vào database từ `.env`, sau backup đầy đủ và restore/upgrade rehearsal thành công. Không triển khai P2 hoặc sửa nghiệp vụ giá, thanh toán, tồn kho, quyền giỏ hàng.

## 1. Phạm vi đã thực hiện

1. Dùng `.env` làm kết nối quản trị để provision schema test riêng, tài khoản `apple_project_p0_test` chỉ có DML trên sandbox; viết credentials ngẫu nhiên vào `.env.testing` ignored. `.env` gốc không sửa.
2. Hoàn tất P0: chạy COD checkout/order ownership và tái hiện C1/C2/C3/H2 trên fixtures tổng hợp, có rollback. Không dùng account ứng dụng để chạy các HTTP regression test ghi dữ liệu.
3. Thêm `database/schema/mysql-schema.sql` theo Laravel schema state: schema-only 40 bảng và 50 migration records. Không rewrite/xóa migration cũ và không tự tạo lại lịch sử đã mất.
4. Thêm migration forward-only, preflight trước DDL; chạy lại an toàn với schema/data đã sửa.
5. Thêm User creating hook cấp UUID 32 ký tự cho code thiếu; giữ code không rỗng do admin/caller đã cấp. Registration/factory không còn phụ thuộc vào code không được cung cấp.
6. Kiểm chứng fresh native migrate, upgrade schema thực và schema drift hỗ trợ, fail-fast conflict, constraints và restore.
7. Backup toàn DB ứng dụng, restore sang DB cách ly, migration trên clone và đối chiếu row fingerprints trước khi apply DB ứng dụng.

## 2. Database thay đổi gì

| Phần | Hành vi migration | Kết quả trên DB ứng dụng |
| --- | --- | --- |
| users.code | Backfill blank/null thành legacy-user-ID; NOT NULL nếu cần; thêm unique sau kiểm tra duplicate/collision | 4 codes rỗng đã map; 0 rỗng, 0 duplicate; 1 unique code index |
| users.role | Thêm admin/user default user nếu thiếu; không đoán quyền admin từ dữ liệu khác | Đã tồn tại; giữ nguyên |
| users.status | Expand block/blocked, map block thành blocked, sau đó canonical enum | Đã đúng blocked; giữ nguyên dữ liệu |
| product_variants.thumbnail | Rename typo nếu cần; phát hiện conflict nếu có hai cột; nullable phù hợp validation | Nullable; các đường dẫn ảnh hiện có giữ nguyên |
| inventory_transactions.type | Thêm commit nếu thiếu, giữ các giá trị enum hiện có | Đã có commit; giữ nguyên |
| inventory unique | Kiểm tra duplicate pair trước; thêm unique warehouse-SKU nếu thiếu | Đã tồn tại; giữ nguyên |
| text/fulltext drift | Mở rộng short_description/spec_value nếu thiếu; thêm posts fulltext nếu thiếu | Đã đúng snapshot; giữ nguyên |
| migration history | Native baseline cho fresh; thêm một applied migration cho upgrade | 50 → 51 records, lịch sử cũ không chỉnh |

Không thêm CHECK stock, cart FKs, coupon constraints, payment/reservation tables hoặc đổi cascade trong P1; chúng cần nghiệp vụ/cleanup và thuộc các giai đoạn sau.

## 3. Bảo toàn và backup

Backup đầy đủ trước P1:

```text
storage/app/private/schema-backups/before-p1-20261028-39dce28d1009.sql
757680934 bytes
SHA-256: 8c265a718783c265d9ef1de7155c407b174e5976a5aa96f25c4a1f3c9138e6c2
```

File chứa dữ liệu thật, nằm trong storage private được gitignore; không đưa nội dung vào docs/test fixtures. Backup dùng mysqldump --single-transaction, không lock tables để ghi dữ liệu. Trước clone đã xác minh schema không có stored triggers/routines/events cần kế hoạch side effects riêng.

Đã khôi phục toàn bộ backup bằng mysql client vào database tên ngẫu nhiên trong namespace sandbox, chạy migration, kiểm tra counts và hash mọi row orders/items/inventory/movements. Sandbox full-restore đã được xóa sau kiểm chứng; chỉ drop schema được tạo trong đúng invocation, không drop DB ứng dụng/P0.

Sau apply DB ứng dụng:

- Users: 8; orders: 52; order_items: 56; inventory: 184; inventory_transactions: 259.
- Hash toàn bộ rows orders/items/inventory/movements trước/sau giống nhau.
- User rows trước/sau giống nhau ngoài mapping code rỗng được duyệt; status block cũng có mapping rõ nếu xuất hiện, nhưng runtime hiện đã blocked.
- Không tự cân tồn, đổi tổng tiền, payment status, giá vốn, hay trừ tồn tại shipping.

Bằng chứng không chứa PII: [p1-verification.json](../architecture/p1-verification.json), [p1-runtime-baseline.json](../architecture/p1-runtime-baseline.json). Full backup cũng đã phục hồi các bảng Telescope lớn; không chỉ thử trên vài bảng nghiệp vụ.

## 4. Lệnh đã chạy và kết quả

PHP thực tế: `F:/laragon/bin/php/php-8.3.30-nts-Win32-vs16-x64/php.exe`. Các lệnh bên dưới dùng `php` để rút gọn; trên PowerShell gọi bằng `& $p1Php ...` với đường dẫn đó nếu PATH chưa cấu hình.

```text
php tests/Support/prepare-database-sandbox.php --provision-from-env
php vendor/phpunit/phpunit/phpunit --do-not-cache-result
P1_SCHEMA_TESTS=1 php vendor/phpunit/phpunit/phpunit --filter SchemaUpgradeTest --do-not-cache-result
P1_SCHEMA_TESTS=1 php vendor/phpunit/phpunit/phpunit --do-not-cache-result
P0_DB_MODE=disabled php vendor/phpunit/phpunit/phpunit --testsuite Unit,Feature --do-not-cache-result
php tests/Support/rehearse-p1-upgrade.php --backup-and-rehearse-from-env
php tests/Support/apply-p1-schema.php --application-from-env
php tests/Support/inspect-application-database.php --application-read-only
php vendor/bin/pint --test app/Models/User.php database/migrations/2026_10_28_000001_reconcile_application_schema.php tests
```

`VAR=... php ...` là ký hiệu môi trường; trong PowerShell dùng `$env:P1_SCHEMA_TESTS='1'` hoặc `$env:P0_DB_MODE='disabled'` cho process tương ứng.

| Kiểm tra | Kết quả |
| --- | --- |
| P0 với MySQL sandbox | 32 tests, 90 assertions, 1 intentional skip, 4 known-risk incomplete |
| Toàn suite bật P1 schema tests | 37 tests, 178 assertions, không errors/failures; 1 intentional skip, 4 known-risk incomplete |
| Unit + Feature, chặn DB | 27 tests, 71 assertions, không skip/error/failure |
| Fresh migrate | Native Laravel schema loader nạp baseline; repair chạy; registration/factory/variant không ảnh thành công; migrate lại không pending |
| Upgrade từ schema snapshot | IDs, existing nonempty codes, role admin, tiền/snapshots/balances/movements giữ nguyên; map nhiều code rỗng ổn định |
| Legacy drift được hỗ trợ | role thiếu, block, typo ảnh, enum thiếu commit, text/fulltext thiếu được sửa |
| Conflict | Nonempty duplicate code dừng trước DDL; không tự đổi mã mơ hồ |
| Constraints | Unique code và FK orphan được MySQL từ chối thực tế |
| Restore synthetic/full backup | Thành công, counts/fingerprints khớp |
| Pint / PHP syntax / diff check | Pint PASS 26 files; PHP syntax 26 files, 0 errors; diff --check exit 0. Không lint-fix source cũ ngoài User |

Trong lượt đầu phát hiện SELECT * GROUP BY không hợp lệ dưới ONLY_FULL_GROUP_BY; đã sửa query preflight chỉ chọn cột group. Một lỗi fixture status khi bổ sung user thứ ba cũng đã sửa trong test setup; không nới SQL mode hoặc sửa business code để làm test xanh.

Build frontend không áp dụng vì không thay assets/frontend/dependencies. Các thay đổi frontend có trước tác vụ được giữ nguyên. Không có package mới, commit hoặc push.

## 5. Thiết kế và dependency

- Native Laravel schema state giải quyết fresh install mà không sửa migration lịch sử. Đây là cơ chế framework hiện có, không thêm công nghệ.
- Expand–migrate–contract có chọn lọc: mapping xác định, thêm constraint sau preflight, không drop cột typo khi còn cần compatibility.
- User lifecycle hook nhỏ cho invariant identity; unique DB bảo vệ cuối. Không tạo Repository hoặc module mới cho một trường code.
- Fixture builder/fakes giữ side effects ngoài regression tests. Migration schema tests opt-in dùng quyền quản trị chỉ trên database ngẫu nhiên được allowlist, DML runner P0 vẫn giới hạn riêng.
- Schema repair không transaction-atomic vì MySQL DDL implicit commit; hỗ trợ rerun sau partial failure. Luồng nguồn chỉ là rich warehouse schema đã xác minh. Schema v1 còn reserved/change cần kế hoạch mapping riêng và bị chặn.

## 6. Acceptance criteria

- [x] P0 isolation và integration đã kiểm chứng; lỗ hổng đã tái hiện được ghi đúng owner.
- [x] Native fresh migrations thành công; không replay xung đột create kho.
- [x] Upgrade current/legacy-supported schema không mất ID, row count, money hoặc order snapshots.
- [x] Registration cấp code hợp lệ; variant thumbnail tùy chọn ghi được; movement commit được DB chấp nhận.
- [x] Repair chạy lại không tạo duplicate data/index hoặc đổi mapping.
- [x] Duplicate nonempty code dừng trước DDL; FK/unique được test trên MySQL thật.
- [x] Full backup restore và upgrade clone thành công trước apply DB ứng dụng.
- [x] Apply đúng migration P1 từ `.env` thành công; metadata và business fingerprints sau apply khớp kỳ vọng.
- [x] Không có schema drift chưa giải quyết trong phạm vi P1 trên DB hiện tại; các trường hợp legacy không đủ bằng chứng được fail-fast, không tự mapping tồn.

Đề nghị review bàn giao này trước giai đoạn P2. Bốn known-risk tests vẫn incomplete vì P1 không giải quyết security/stock orchestration; không gọi toàn bộ Backend production-ready.

## 7. Kiểm tra thủ công và rollback

1. Chạy unit/feature ở P0_DB_MODE=disabled để kiểm tra harness an toàn; chạy suite có MySQL để xem success và known-risk incomplete.
2. Chạy read-only inspector; kỳ vọng 51 migrations, code rỗng/duplicate bằng 0, unique code index bằng 1, thumbnail nullable.
3. Trên sandbox, chạy P1 schema suite để dựng mới, upgrade, rollback test fixtures và restore. Không chạy `migrate:fresh` trên DB ứng dụng.
4. Không cần đăng ký tài khoản thật hoặc tạo đơn thật để xác minh; registration và order đã test trên sandbox.

`down()` của repair cố ý từ chối đảo ngược dữ liệu. Nếu phát hiện lỗi sau release, dừng writer liên quan và dùng forward repair được review; không bỏ User code hook trong khi code vẫn NOT NULL. Backup có thể restore sang database cách ly để so sánh/phục hồi có chọn lọc. Không ghi đè backup lên DB đang nhận giao dịch mới.

Nếu cần rollback code, revert riêng các file P1, giữ bản migration/history đã chạy và đánh giá compatibility trước khi deploy. Không reset/clean repository vì có thay đổi frontend/docs của chủ dự án. Account/schema P0 được giữ cho các giai đoạn sau; mật khẩu không nằm trong repository.

## 8. File thay đổi ở lượt hoàn tất P0/P1

```text
app/Models/User.php
database/schema/mysql-schema.sql
database/schema/README.md
database/migrations/2026_10_28_000001_reconcile_application_schema.php
tests/Integration/P1/SchemaUpgradeTest.php
tests/Support/DatabaseSandbox.php
tests/Support/prepare-database-sandbox.php
tests/Support/rehearse-p1-upgrade.php
tests/Support/apply-p1-schema.php
tests/Support/inspect-application-database.php
.env.testing.example
docs/roadmap/P0_STATUS.md
docs/roadmap/P1_STATUS.md
docs/roadmap/UPGRADE_PLAN.md
docs/architecture/current-architecture.md
docs/architecture/architecture-decisions.md
docs/architecture/p1-runtime-baseline.json
docs/architecture/p1-verification.json
```

File phát sinh local ignored: `.env.testing`, `tests/.runtime/*`, full backup trong storage private. Không đổi `.env`, historical migrations, API routes/controllers hoặc dependencies. Lượt P0 trước có các file harness đã liệt kê trong P0_STATUS; chúng vẫn thuộc working tree chưa commit.
