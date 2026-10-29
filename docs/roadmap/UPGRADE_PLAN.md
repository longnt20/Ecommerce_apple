# Kế hoạch nâng cấp Backend

Ngày lập: 2026-10-28. **Kế hoạch gốc và theo dõi triển khai; trạng thái phê duyệt ghi bên dưới.**

**Cập nhật trạng thái 2026-10-28:** chủ dự án đã cho phép dùng cấu hình `.env` để hoàn tất P0 và triển khai P1. [P0_STATUS](P0_STATUS.md): đã đóng baseline/isolation, bốn lỗ hổng đã tái hiện và để lại đúng owner. [P1_STATUS](P1_STATUS.md): đã kiểm chứng fresh/upgrade/restore và áp dụng riêng migration repair vào DB ứng dụng sau backup đầy đủ. P2–P8 chưa được phê duyệt. Chủ dự án xác nhận cost_price là giá vốn, trừ tồn vật lý khi bàn giao vận chuyển; P1 không thay đổi nghiệp vụ giá/tồn đó.

Tài liệu đi kèm: [hiện trạng](../architecture/current-architecture.md), [mục tiêu](../architecture/target-architecture.md), [ADRs và câu hỏi cần chốt](../architecture/architecture-decisions.md).

**Đầu vào SQL bổ sung 2026-10-28:** [database-export-analysis](../architecture/database-export-analysis.md). Đã có snapshot 40 bảng MySQL 8.0.30/InnoDB và 50 migrations applied. P0 không cần suy đoán schema nữa, nhưng chưa hoàn thành runtime/test isolation. P1 phải bảo toàn schema export có role/blocked/thumbnail/commit và giải quyết migration drift. Thêm test dữ liệu ẩn danh cho order 4/5 lệch tổng tiền và 20 đơn không pending còn net reserve tại P2–P4; không tự sửa lịch sử. P7 bổ sung kiểm chứng retention Telescope (khoảng 99,896% dung lượng văn bản dump).

## 1. Nguyên tắc thực hiện

- Mỗi giai đoạn là một cổng phê duyệt và có thể chia thành PR nhỏ; không tự suy ra quyền deploy/migrate từ quyền sửa code.
- Trước mọi test có ghi DB: guard môi trường testing, allowlist database test và credentials riêng; không dùng DB ứng dụng đang hoạt động.
- Không thay API ngầm. Ghi contract trước/sau, giữ adapter tương thích hoặc phê duyệt thay đổi có chủ đích.
- Không chạy network side effects trong transaction giữ khóa. Không gửi mail/gọi thanh toán thật trong test.
- Không thêm công nghệ/package nếu chưa có benchmark hoặc khả năng cụ thể mà stack hiện tại thiếu.
- Mọi backfill có dry-run, checkpoint, thống kê đầu/cuối, xử lý trường hợp mơ hồ; không suy đoán sửa dữ liệu lịch sử.
- Rollback code chỉ về phiên bản an toàn tương thích schema. Không rollback về lỗ hổng đã biết. Nếu không có phiên bản an toàn, vô hiệu hóa luồng bị ảnh hưởng có kiểm soát và roll forward.
- Không restore snapshot cũ lên DB đang nhận giao dịch vì sẽ mất đơn mới; cần dừng ghi và reconciliation được phê duyệt. Migration down phá dữ liệu không phải rollback mặc định.

## 2. Thứ tự và dependencies

| Giai đoạn | Ưu tiên/rủi ro | Phụ thuộc | Đầu ra chính |
| --- | --- | --- | --- |
| P0 — Baseline và test cách ly | Bắt buộc / thấp đến trung bình | Phê duyệt bắt đầu | Schema/contract inventory, test guard, quyết định nghiệp vụ |
| P1 — Schema tái lập | High / cao | P0, Q1 | Fresh install và upgrade có kiểm chứng |
| P2 — Giá, thanh toán, quyền | Critical / cao | P0; phần DB trên P1; Q2 | Chặn giá giả, paid giả, IDOR, account bypass |
| P3 — Transition atomic | High / cao | P1; Q4; phối hợp contract P2 | Confirm/cancel một lần, error mapping |
| P4 — Inventory và checkout nhất quán | Critical / rất cao | P1–P3; Q3–Q5 | Reservation, allocation, protocol khóa, checkout idempotent |
| P5 — Module hóa có kiểm chứng | Medium / trung bình | P2–P4 ổn định | Boundaries và use cases thay code cũ từng phần |
| P6 — Hoàn thiện lifecycle | High / cao | P4–P5; Q5–Q6 | COD/refund/return, coupon, archive, transfer/stocktake |
| P7 — Hiệu năng và vận hành | Medium / trung bình | Baseline P0; benchmark cuối trên P6 | CI, số liệu hiệu năng, runbook/restore |
| P8 — Thử nghiệm service tùy chọn | Tùy chọn / cao | P5–P7 và phê duyệt riêng | Outbox/inbox, một thử nghiệm đo được |

Các phần P2 chỉ sửa quyền/validation có thể chuẩn bị và test trên fixture cách ly trong khi P1 đang xử lý; không cần chờ refactor module để chặn lỗ hổng. Không deploy code dựa trên schema chưa xác minh. CI tối thiểu có thể bắt đầu P0; P7 hoàn thiện vận hành, không trì hoãn test đến cuối.

## P0 — Xác lập baseline và môi trường kiểm chứng

**Mục tiêu:** có cơ sở dữ liệu và nghiệp vụ đáng tin trước khi thay đổi tiền/tồn.

**Phạm vi:** inventory routes/schema/config, test isolation, bằng chứng hiện trạng; không sửa nghiệp vụ trong PR baseline.

**File/module dự kiến:** `phpunit.xml`, `tests/TestCase.php`, `tests/Support/*` mới, `tests/Feature/*`, tài liệu architecture; đọc `database/migrations`, `routes`, `config`. `.env.testing` local không commit secrets; sample chỉ chứa placeholders.

**Công nghệ/pattern:** PHPUnit có sẵn, MySQL test cùng engine/version đã xác minh, characterization/contract tests; không cần package mới.

**Các bước triển khai:**
1. Xác minh DB/version/engine/isolation/migration history bằng truy cập chỉ đọc được cho phép; lập diff schema-code-migrations, không xuất dữ liệu cá nhân.
2. Liệt kê endpoint thực, handler thiếu, input/output hiện tại và luồng nào chỉ là placeholder.
3. Chốt Q1–Q6 theo các giai đoạn phụ thuộc; ưu tiên Q1/Q2/Q4. Ghi điều còn mở, không tự biến giả định thành quyết định.
4. Chuẩn bị DB test riêng với quyền giới hạn; thêm fail-fast guard tên DB/environment trước bootstrap có thể ghi; fake mail/gateway/queue side effects.
5. Viết fixture tối thiểu và characterization tests cho hành vi hợp lệ, regression tests mô tả lỗi Critical/High. Test tái hiện lỗi chưa sửa được đánh dấu expected failure có ID/owner, không coi đó là pass an toàn.

**Test cases:** guard từ chối database ngoài allowlist; test hai user/two warehouses; root smoke; contract checkout/order; inventory và giá fixture xác định; không phát mail/network ngoài test.

**Acceptance criteria:** không test nào chạm DB ứng dụng; danh sách schema drift và endpoint mismatch có bằng chứng; fixture tái tạo được; các lỗ hổng ưu tiên có test chứng minh baseline hoặc ghi rõ bị chặn bởi schema.

**Rủi ro/rollback:** cấu hình test nhầm DB là rủi ro lớn nhất. Dùng user DB không có quyền với DB thật, guard trước boot; revert test/config nếu lỗi, chỉ xóa DB test đã xác minh nếu cần. Chưa chạy migrate:fresh trên DB đang dùng.

**Điều kiện hoàn thành:** baseline review xong, DB cách ly được chứng minh, Q1/Q2 và semantics cần cho P3 có quyết định hoặc blocker được ghi; phê duyệt P1/P2 riêng.

## P1 — Sửa khả năng tái tạo và nâng cấp schema

**Mục tiêu:** schema từ repository phù hợp code, có đường upgrade giữ dữ liệu.

**Phạm vi:** lỗi migration trùng bảng; thumbnail; users role/code/status; movement enum; constraint thiết yếu. Chưa redesign toàn bộ tables.

**File/module dự kiến:** migrations mới chỉ sau phê duyệt, migration baseline nếu P0 chứng minh cần; User/ProductVariant/InventoryTransaction và Requests liên quan; fixtures/seeders test; test schema upgrade.

**Công nghệ/pattern:** Laravel migrations, expand–migrate–contract, validation dữ liệu; ADR-009.

**Các bước triển khai:**
1. Chọn chiến lược fresh baseline và upgrade dựa trên migration history; không sửa migration đã chạy trên môi trường cần giữ một cách ngầm định.
2. Tạo repair migrations additive và backfill dry-run; map block/blocked, code và thumbnail sau kiểm tra dữ liệu.
3. Kiểm tra giá trị enum/role và nullable/default với mọi writer; quyết định mã movement bán hàng nhất quán.
4. Kiểm tra FK/orphans/duplicates trước thêm constraint; chuẩn bị backup và restore drill trên clone.
5. Chạy hai tuyến test: DB trống và clone schema cũ với fixtures đại diện.

**Test cases:** fresh migrations chạy hết; upgrade không mất count/ID/snapshot; đăng ký cấp code hợp lệ; variant ghi ảnh đúng cột; movement bán hàng được lưu; rerun/backfill không đổi dữ liệu lần hai; invalid FK bị chặn.

**Acceptance criteria:** fresh/upgrade đều xanh; mỗi repair có mapping và thống kê; không còn drift được liệt kê trong phạm vi P1; dữ liệu không xác định có danh sách xử lý.

**Rủi ro/rollback:** DDL locking và mapping sai, mức cao. Thử trên clone, additive trước; rollback app tương thích hoặc roll forward. Không drop cột cũ cho đến hết cửa sổ kiểm chứng; không dùng down làm mất dữ liệu mới.

**Điều kiện hoàn thành:** reviewer xác nhận schema diff và restore drill; môi trường đích có kế hoạch migration cụ thể được duyệt.

## P2 — Bảo vệ giá, thanh toán và quyền

**Cập nhật 2026-10-29:** code/regression P2 đã triển khai; xem [P2_STATUS](P2_STATUS.md) và [HTTP contracts/rollout](../architecture/p2-http-contracts.md). Chờ review và rollout; DB ứng dụng chưa áp dụng payment migration, online mới mặc định tắt. Không chuyển P3.

**Mục tiêu:** loại bỏ C1/C2/H2/H6 mà không chờ module hóa lớn.

**Phạm vi:** server pricing, validation SKU, payment verification/dedup, cart ownership, account status/OAuth, error redaction. API behavior thay đổi phải có contract.

**File/module dự kiến:** API Checkout/Cart/Auth/GoogleAuth controllers; middleware/Policies mới; Requests; routes; payment attempts/events migrations và gateway classes dự kiến; frontend services chỉ nếu cần cập nhật contract được duyệt; regression tests.

**Công nghệ/pattern:** FormRequest, Policies, Money/QuoteService tối thiểu, PaymentGateway adapter, idempotent event handling, Laravel HTTP fake hoặc port fake; không thêm payment SDK mặc định.

**Các bước triển khai:**
1. Bỏ quyền quyết định giá từ client; lấy SKU thuộc product đang bán, tính tổng server và snapshot theo Q2. Phân biệt cart và buy-now.
2. Scope cart item theo user; active-account gate; sửa OAuth không ghi đè password, thiết kế handoff tránh bearer trong URL và state verification.
3. Kiểm chứng đặc tả VNPay chính thức và merchant configuration; triển khai verify signature/canonicalization, amount/currency, merchant/attempt reference, response semantics.
4. Tạo attempt/event identity và unique dedup; return UI chỉ đọc kết quả đã xác thực. Bổ sung notification/reconciliation path theo khả năng provider đã xác minh.
5. Loại dd/exception trace khỏi response; thêm correlation ID và log redact; xác định login rate limiting bằng cơ chế Laravel hiện có.

**Test cases:** price=0/giá sửa bị bỏ qua hoặc từ chối đúng contract; variant-product sai bị chặn; user A không sửa item B; blocked token bị chặn; OAuth user cũ giữ password; callback giả/sai amount/sai merchant không paid; callback đúng xử lý một lần; COD không bị callback VNPay giả đổi paid; retry gateway không tạo side effect lặp.

**Acceptance criteria:** không request client nào tự quyết định giá/paid; callback fixtures được kiểm chứng; ownership/status tests xanh; response không có trace/secrets; pending online payment không bị báo thành công từ query URL.

**Rủi ro/rollback:** xác minh signature sai có thể từ chối thanh toán thật. Dùng sandbox/fixtures đã kiểm chứng; triển khai quan sát và reconciliation. Nếu lỗi, dừng tạo thanh toán mới hoặc chuyển phương thức được duyệt; không bật lại vnpReturn không xác minh. Đơn đã paid giữ nguyên để đối soát.

**Điều kiện hoàn thành:** toàn bộ regression Critical về giá/payment và quyền xanh; Q2 chốt; endpoint contract được review; không tuyên bố inventory đã an toàn trước P4.

## P3 — Chuyển trạng thái đơn atomic và có thể chạy lại

**Cập nhật 2026-10-29:** chủ dự án đã yêu cầu triển khai P3. Code/tests và dry-run legacy đã có; xem [P3_STATUS](P3_STATUS.md), [contract/rollout](../architecture/p3-order-transitions.md). Migration chỉ chạy sandbox; transition mới mặc định tắt, chờ review legacy và cutover. Không triển khai P4.

**Mục tiêu:** confirm/cancel không chạy lặp, transition được kiểm soát trong service.

**Phạm vi:** lock/refresh order, transition policy, operation identity, error mapping, timestamps/history. Kho allocation sai vẫn là blocker P4, không che bằng default 1.

**File/module dự kiến:** `OrderService`, Admin OrderController, InventoryService commit/release, Order model, transition/operation storage nếu cần, tests trạng thái.

**Công nghệ/pattern:** Application Service, enum/transition table, row locks, idempotency và DB transaction; ADR-005/006/007.

**Các bước triển khai:**
1. Chốt Q4 và bảng transition; chỉ service/use case được quyết định transition.
2. Khóa và đọc lại order trong transaction trước kiểm tra state; operation identity ngăn effect lặp.
3. Trả lỗi conflict ổn định; ghi actor/reason/timestamps; reject order thiếu allocation đáng tin thay vì tự chọn kho.
4. Đưa side effects mail ra sau commit; thống nhất lock order với payment handler và Inventory.

**Test cases:** confirm hai request đồng thời chỉ một effect; confirm/cancel chạy cùng lúc chỉ một transition hợp lệ; transition từ terminal bị chặn; lỗi movement rollback cả status; invocation trực tiếp service không bỏ qua policy; retry cùng operation không ghi thêm lịch sử.

**Acceptance criteria:** state/effect atomic; không reserved âm do xử lý lặp; admin và service dùng một policy; order legacy không xác minh kho bị đưa vào xử lý riêng.

**Rủi ro/rollback:** đơn legacy bị chặn và deadlock. Dry-run phân loại đơn trước rollout; rollback về phiên bản đã có guard hoặc tạm dừng transition; không undo movement đã commit bằng update số lượng thủ công.

**Điều kiện hoàn thành:** concurrency tests trên MySQL thật xanh, không còn dd, có danh sách đơn legacy và đường xử lý được phê duyệt.

## P4 — Reservation, allocation và mọi đường ghi tồn

**Mục tiêu:** giải quyết C3 và chống lỗi tồn khi nhiều luồng hoạt động đồng thời.

**Phạm vi:** reservation schema, warehouse policy, checkout idempotency, protocol import/export/transfer/adjust/reserve/commit/release, expiry tối thiểu. Đây là giai đoạn rủi ro cao nhất, chia thành PR P4a schema/backfill, P4b stock operations, P4c checkout/cutover.

**File/module dự kiến:** CheckoutController, InventoryService/Inventory model/InventoryTransaction, OrderService, Admin InventoryController; Inventory/Ordering application ports/adapters mới; migrations reservation/movement operation key; scheduler/job expiry; tests Integration/Concurrency.

**Công nghệ/pattern:** reservation lifecycle, allocation policy, UnitOfWork cùng connection, narrow StockStore, pessimistic locking, unique operation key, idempotent commands; không dùng distributed locks.

**Các bước triển khai:**
1. Chốt Q3–Q5; xây reservation items và stock invariant; backfill chỉ khi movement chứng minh allocation, đánh dấu ambiguous để xử lý riêng.
2. Khóa inventory theo thứ tự cố định cho mọi writer; xử lý create-row race và kiểm tra quantities sau lock; actor truyền từ caller.
3. Transfer tạo cặp movement có transfer reference, giữ atomic cả nguồn/đích; stocktake thiếu so với reserved sinh discrepancy.
4. PlaceOrder chuẩn hóa items, server quote, allocation, idempotency và order/reservation trong một transaction; không call network trong lock.
5. Confirm/cancel dùng reservation identity, không dùng kho mặc định. Expiry cạnh tranh với payment/confirm phải có một kết quả hợp lệ.
6. Chạy reconciliation và shadow read; chuyển từng luồng sang writer mới. Không dual-write tồn qua hai implementation. Cutover bằng cổng kiểm soát có audit.

**Test cases:** hai checkout mua SKU cuối; nhiều SKU request đảo thứ tự; import vs checkout; export/transfer vs reserve; destination inventory chưa tồn tại; stocktake nhỏ hơn reserved; rollback một item; reserve kho B/commit kho B; release/expiry hai lần; callback muộn không tự tái reserve; retry checkout cùng key và khác payload; tổng transfer không đổi; chết process giữa transaction không lưu nửa kết quả.

**Acceptance criteria:** on_hand/reserved/available đúng invariant; một operation chỉ có một effect; mọi order mới có allocation đầy đủ; movement và balance đối chiếu được; không bán từ kho inactive; không còn đường ghi tồn ngoài Inventory protocol; không query network khi giữ lock.

**Rủi ro/rollback:** backfill sai, contention, writer cũ ghi đè. Rehearsal clone, báo cáo số dư trước/sau, cutover ngắn có kiểm soát. Nếu cần rollback sau phát sinh reservation mới, dùng adapter đọc được schema mới hoặc dừng writer; không quay lại code chọn kho đầu tiên/default 1. Sửa tồn bằng movement bù được duyệt.

**Điều kiện hoàn thành:** concurrency suite với connection độc lập xanh; reconciliation không còn chênh lệch mới; legacy ambiguous đã xử lý hoặc cách ly; phê duyệt kết thúc cửa sổ tương thích trước drop schema cũ.

## P5 — Hoàn thiện module boundaries và giảm coupling

**Mục tiêu:** biến các use case đã đúng thành kiến trúc dễ bảo trì mà không đổi hành vi ngoài ý muốn.

**Phạm vi:** namespace modules, ports/DTO, providers, HTTP adapters, dependency checks; refactor từng use case. Không rewrite toàn bộ CRUD.

**File/module dự kiến:** `app/Modules/{Ordering,Inventory,Payments,Catalog,Pricing,Identity}`, providers/bootstrap registration, Controllers/Services cũ làm adapter; `tests/Architecture`; BaseCrud/Category contract khi chạm tới.

**Công nghệ/pattern:** Ports and Adapters, DI, Application Services, typed contracts; giữ Eloquent Infrastructure. Không generic repository package.

**Các bước triển khai:** lập ownership map → chuyển một use case và bind provider → chạy contract/regression → loại đường gọi cũ → lặp lại. Tách Auth/Request khỏi domain, truyền ActorContext/Clock. Ghi ngoại lệ Reporting read-only và thời hạn adapter cũ.

**Test cases:** domain unit không boot Laravel; HTTP contracts giữ payload đã duyệt; architecture test cấm import Illuminate trong Domain và model xuyên module; services chạy trong job/CLI không cần auth global; adapter cũ/mới cho cùng kết quả.

**Acceptance criteria:** modules có public Contracts; không vòng dependency source; chỉ owner ghi tables; một active writer mỗi use case; không tăng query/latency bất hợp lý so baseline.

**Rủi ro/rollback:** binding/container/autoload hoặc response thay đổi. PR nhỏ theo use case, giữ compatibility adapter; revert binding về implementation an toàn trước đó tương thích schema, không phục hồi lỗi đã sửa.

**Điều kiện hoàn thành:** architecture/regression tests xanh; không còn adapter tạm không có owner/lộ trình; review boundary và tài liệu đồng bộ.

## P6 — Hoàn thiện nghiệp vụ và bảo toàn lịch sử

**Mục tiêu:** hoàn thiện vòng đời tiền/hàng/coupon thay vì chỉ happy path.

**Phạm vi:** chia PR độc lập P6a COD/refund/return, P6b coupon, P6c transfer/stocktake, P6d catalog/history/identity. Không triển khai chức năng chưa chốt nghiệp vụ.

**File/module dự kiến:** Payments/Ordering/Inventory/Pricing/Identity; CouponController/AssignCouponJob và listeners; ProductController; archive actions và FK migrations; Requests/Resources; frontend adapters theo contract được duyệt.

**Công nghệ/pattern:** transition policy, payment adapter, immutable snapshots, compensating movements, idempotent after-commit jobs. Chưa cần saga phân tán.

**Các bước triển khai:**
1. Định nghĩa thu COD, refund request/provider outcome và returned stock disposition. Hoàn tiền không tự cộng tồn; nhận hàng trả không tự xác nhận refund.
2. Coupon assignment khác redemption; reserve/redeem/release usage cap atomic, xử lý expiry/cancel; job upsert/dedup và after-commit.
3. Transfer headers/lines và stocktake discrepancy; thêm in-transit chỉ nếu quy trình đã yêu cầu.
4. Product multi-entity save transaction, scope variants, staging/cleanup file; archive thay cascade lịch sử theo policy; chuẩn hóa account/warehouse rights.
5. Sửa settings persist, user destroy và voucher query; đồng bộ route/handler và tài liệu, không công bố endpoint placeholder.

**Test cases:** partial/full refund theo phạm vi được duyệt; duplicate refund callback; hàng trả hỏng không thành available; COD completed nhưng chưa collected được biểu diễn đúng; coupon last-use concurrent; job retry sau partial batch; archive product/user không mất order snapshot; variant khác product không sửa được; failure upload/DB không mất ảnh đang dùng; transfer discrepancy có audit.

**Acceptance criteria:** transition matrix tiền/hàng đầy đủ; snapshot và ledger giữ lịch sử; coupon không vượt cap/cấp lặp; partial failure có đường recovery; mọi endpoint trong phạm vi có handler và contract tests.

**Rủi ro/rollback:** refund có side effect bên ngoài không thể revert code để hủy. Pause request mới, đối soát provider, giữ events; thay đổi dữ liệu bằng operation bù. Chỉ rollback từng PR nếu không phá trạng thái mới; không xóa lịch sử đã tạo.

**Điều kiện hoàn thành:** nghiệp vụ được duyệt, tests từng PR xanh, runbook cho callback muộn/refund lỗi/discrepancy có người xử lý và tiêu chí đóng.

## P7 — Hiệu năng, CI và vận hành

**Mục tiêu:** chứng minh hiệu năng và khả năng recovery bằng số liệu.

**Phạm vi:** query/index/cache, export/report, CI, worker/scheduler, logs/metrics, backup/deployment runbook; không tự deploy hoặc cài hạ tầng.

**File/module dự kiến:** Reporting, API Home/Resources, Inventory reports, config cache/queue/logging/telescope; index migrations nếu đo cần; CI config mới nếu được duyệt; docs runbooks và benchmark harness.

**Công nghệ/pattern:** EXPLAIN, eager loading, aggregates, chunk streaming, cache invalidation sau commit, PHPUnit CI, structured logs. Dùng công cụ sẵn có trước; Docker chỉ thêm nếu giúp tái lập môi trường rõ ràng và được phê duyệt.

**Các bước triển khai:** chốt workload dữ liệu đại diện → đo query count/p95/memory/lock wait → sửa từng điểm → đo lại → chốt ngưỡng regression/SLO từ baseline. Hoàn thiện CI test DB cách ly, worker retry/failed jobs, scheduler expiry/reconciliation, sensitive log redaction; rehearsal deploy và restore trên môi trường thử.

**Test cases:** OR filter không vượt warehouse; không N+1 variants; CSV lớn dùng bộ nhớ giới hạn; cache invalidation sau commit/rollback; worker crash/retry; scheduler không chạy expiry lặp; backup restore giữ FK/balance; CI không gọi mail/payment thật.

**Acceptance criteria:** báo cáo trước/sau với cùng dataset/workload; ngưỡng đã duyệt đạt; không index trùng vô ích; CI chặn regression Critical; runbook có lệnh/đầu vào/kiểm tra kết quả và rollback đã diễn tập.

**Rủi ro/rollback:** cache stale, index DDL lock, timeout tuning che lỗi. Tắt optimization có kiểm soát, giữ correctness path; roll forward index nếu rollback tốn khóa; không cache authoritative stock. Runtime version/security patch chỉ đánh giá riêng sau kiểm chứng tài liệu chính thức.

**Điều kiện hoàn thành:** số liệu có thể lặp lại, restore drill thành công, worker/scheduler được kiểm tra ở môi trường đích được phép; không coi file config là bằng chứng đã vận hành.

## P8 — Thử nghiệm Microservices có giới hạn, tùy chọn

**Mục tiêu:** học delivery semantics và đánh giá lợi ích tách module mà không gây rủi ro luồng order/inventory.

**Phạm vi:** một nhánh/môi trường thử cho Notifications hoặc Reporting projection. Không tách Ordering–Inventory và không chuyển production mặc định.

**File/module dự kiến:** Notifications/Reporting Contracts, outbox/inbox migrations, publisher/consumer jobs, event fixtures, integration tests và ADR đánh giá thử nghiệm. Repo/deployment riêng chỉ sau phê duyệt.

**Công nghệ/pattern:** transactional outbox, inbox dedup, versioned events, at-least-once, replay; transport đơn giản đáp ứng thử nghiệm, không mặc định Kafka/Kubernetes.

**Các bước triển khai:** đặt giả thuyết đo được → thêm outbox cùng transaction → consumer idempotent với inbox → kiểm thử crash và message reorder → chạy độc lập trên sandbox → so latency/ops cost/recovery với monolith → quyết định giữ hoặc bỏ.

**Test cases:** crash sau DB commit trước publish; publish trùng; consumer crash trước/sau effect; event reorder/version cũ; service offline rồi replay; Notification không gửi lặp trong phạm vi delivery semantics thực sự hỗ trợ. Nếu provider mail không hỗ trợ idempotency, ghi rõ cửa sổ duplicate còn lại, không hứa exactly-once.

**Acceptance criteria:** business state không mất event; delivery/replay có audit; external side effects có giới hạn rõ; báo cáo lợi ích và chi phí, không chỉ demo chạy được.

**Rủi ro/rollback:** duplicate effects và tồn đọng messages. Dừng consumer ngoài, xác minh watermark/inbox rồi bật consumer monolith cùng event IDs; không chạy hai consumer gửi mail không dedup. Không xóa outbox chưa xử lý.

**Điều kiện hoàn thành:** ADR ghi số liệu và lựa chọn tiếp tục/quay lại; monolith vẫn vận hành độc lập, không tạo dependency production ngoài phạm vi duyệt.

## 3. Definition of Done chung

1. Có phạm vi/ADR được phê duyệt, code và migration đúng phạm vi; các quyết định còn mở không bị tự giả định.
2. Tests có ý nghĩa cho invariant, quyền và failure path xanh trên engine phù hợp; concurrency dùng nhiều connection thực.
3. Không còn regression Critical trong phạm vi; các lỗi ngoài phạm vi được nêu rõ, không gọi hệ thống production-ready quá sớm.
4. API/schema compatibility, data reconciliation và rollback được review; nếu rollback không khả thi thì có pause/roll-forward plan.
5. Tài liệu hiện trạng cập nhật sau triển khai thật; có bằng chứng chạy test/rehearsal, không chỉ checklist đánh dấu.

## 4. Giai đoạn đầu tiên đề nghị phê duyệt

**P0 — Baseline và test cách ly.** Repository hiện có migration drift và PHPUnit chưa chỉ định DB test riêng. Sửa trực tiếp money/inventory trước khi biết schema thực sẽ khó phân biệt lỗi cũ với regression và có nguy cơ tác động dữ liệu đang dùng.

P0 cần gọn, tập trung vào DB/test guard, schema diff, contract và quyết định giá/kho. Sau đó ưu tiên ngay P1 và các bản sửa Critical P2, không dành nhiều vòng để làm đẹp kiến trúc. Nếu hệ thống đang nhận đơn thật, cần kế hoạch containment riêng được phê duyệt cho payment/checkout bị ảnh hưởng; không chờ hoàn thành module hóa mới xử lý rủi ro.
