# Architecture Decision Records

## ADR-020 — Reporting prototype outbox/inbox P8, giữ monolith mặc định (2026-10-30)

- Trạng thái: đã kiểm chứng local/sandbox trên nhánh `experiment/p8-catalog-reporting`; không triển khai service production. Chủ dự án yêu cầu thực hiện P8; không tự suy ra quyền đổi DB/reader production hoặc push.
- Chọn Reporting catalog cards thay Notifications để kiểm chứng tách process/DB mà không tạo SMTP duplicate window hoặc tác động tiền/tồn. Không tách Ordering–Inventory. Prototype dùng PHP/MySQL/file spool hiện có, không broker/dependency mới.
- Catalog thêm port/no-op; SaveProduct/save SKU ghi snapshot qua port cuối business transaction. Adapter thử nghiệm opt-in, testing + fresh DB allowlist + producer marker + transaction bắt buộc; default không cần P8 schema. Composition root experiment nằm ngoài Modules; Domain/Application không import transport hoặc framework.
- Producer outbox + monotonic per-product stream version cùng transaction product/SKUs. Relay publish retained file trước acknowledge DB; retry/replay giữ UUID và có audit. Consumer riêng dùng inbox unique ID + stream version, canonical payload hash và projection/audit cùng DB commit; full snapshot mới thắng, cũ stale, unsupported/identity conflict quarantine.
- Chọn full snapshot vì Reporting không cần áp tuần tự mọi delta; version gap được phép. Quy tắc này không được tái dùng cho inventory/financial ledger. Guarantee một effect projection theo event identity trong DB không phải exactly-once transport/email.
- Hai logical schemas và credential riêng trên cùng MySQL local; consumer không boot Laravel/đọc env/quyền nguồn. Shared filesystem/code/vendor còn coupling, chưa chứng minh deploy/network/host failure độc lập. Migrations SQL versioned ngoài default Laravel path; không áp vào nguồn, không tự backfill dữ liệu thật.
- Số liệu cùng 20 cards/1.000 products × 3 SKU × 5 kho: read 2→1 query, p95 6,497→1,227 ms; write ORM 3→9 query, tổng MySQL statements 5→13, p95 7,338→15,747 ms. 1.030 event bắt kịp/inbox đầy đủ, 1.000 cards; catch-up batch local khoảng 13,716 giây. Không dùng số liệu này làm production SLO hoặc so với API home đầy đủ.
- Quyết định: giữ Modular Monolith mặc định; giữ prototype cho học tập. Precomputed read model có thể nằm nội bộ monolith; read gain hiện chưa bù đủ complexity để chốt service production riêng. Cần workload đồng thời, ownership/deployment/transport/retention và feed archive/raw updates trước quyết định tiếp.
- Rollback: production reader chưa chuyển; dừng external consumer, giữ inbox/version watermark/event IDs và pending outbox; in-process fallback dùng cùng consumer DB/inbox. Không chạy consumer side effect không dedup, không purge theo published_at hoặc restore nguồn. [P8_STATUS](../roadmap/P8_STATUS.md), [benchmark](p8-performance.json), [verification](p8-verification.json), [runbook](../runbooks/P8_EXPERIMENT.md).

## ADR-019 — Hiệu năng có số liệu và recovery sandbox P7 (2026-10-30)

- Trạng thái: áp dụng trong phạm vi P7 local/sandbox được duyệt; chưa kết luận CI remote hoặc production đã vận hành.
- Workload/ngưỡng được duyệt: 1.000 products × 3 SKU × 5 kho; CSV 100.000 dòng, detail ≤8 query, home ≤5, CSV ≤64 MiB/subprocess và chunk 1.000. p95 chẩn đoán, chưa là production SLO.
- Query Object/read adapter bên ngoài Modules gom OR search trong warehouse/status; scalar joins + keyset/high-water mark CSV thay load toàn bộ. CSV không hứa snapshot tài chính khi có concurrent writes; header/route giữ nguyên, text công thức được neutralize.
- Product detail tái sử dụng tập stock chung cho totals/warehouses. Correlated SUM bị loại sau EXPLAIN cho thấy quét inventory trong subquery; dùng index FK/PK hiện có, không thêm index khi chưa có bằng chứng.
- Display cache dùng scoped memoization, generation key và observer sau outer commit; tx bypass shared cache. Bulk reorder banner gọi invalidation rõ ràng. Không cache giá/tồn quyết định checkout hoặc đưa cache vào Domain/Application. Request/job kế tiếp nạp generation mới; build cũ chỉ ghi generation cũ, TTL giới hạn orphan keys.
- Database queue after_commit, retry/timeout/backoff hữu hạn; worker crash/retry và scheduler expiry lặp đã kiểm chứng bằng subprocess thật trên DB allowlisted. Task Windows thật giữ Disabled. Không hứa email exactly-once hoặc timeout pcntl trên Windows; outbox/inbox chỉ xét P8 riêng.
- Structured logs bỏ payload/SQL bindings, tóm tắt Throwable; Telescope opt-in/watchers off mặc định và active-admin middleware cả local. Không purge dữ liệu cũ hoặc tự áp retention chưa duyệt.
- CI service cùng MySQL engine trên runner, install lockfiles; không Docker local/dependencies mới. Workflow SHA pin, quyền read-only, schema/race/recovery/budget checks bật, không continue-on-error. Actions remote/branch protection chưa xác minh khi chưa push/chạy workflow.
- Rehearsal backup/restore 56 bảng tổng hợp + FK anti-join/balance; config/routes cache và rollback cache trên runtime riêng. Không migration P7 hoặc ghi DB nghiệp vụ. [Số liệu](p7-performance-after.json), [trạng thái](../roadmap/P7_STATUS.md), [runbook](../runbooks/P7_OPERATIONS.md).

## ADR-018 — Lifecycle P6 và archive an toàn (2026-10-30)

- Chủ dự án duyệt thu đủ COD có mã đối soát; refund request/kết quả thủ công, không gọi provider; hàng trả chỉ bán lại sau kiểm tra đạt. Coupon một mã/đơn, reserve ở checkout, redeem online paid/COD confirmed, release pending cancel/expiry. Cho phép migration additive sau sandbox + backup/restore/rehearsal, giữ legacy mơ hồ.
- Ordering điều phối AfterSales và CouponRedemptions trong local transaction với order lock; Payments/Inventory/Pricing sở hữu ledger riêng, không thêm dependency ngược về Ordering. Không dùng distributed saga hoặc callback email để quyết định tiền/kho.
- Refund dùng hạn mức collected có chứng cứ và requested+settled; trạng thái thu tiền trên đơn giữ nguyên, ledger mô tả hoàn. Return có proof committed và cap theo item; receipt damaged nằm ngoài pool sellable. Các action bền vững có operation/payload hash, unique references và events; history không bị sửa khi retry.
- Coupon khóa row và dùng current locking reads sau khi lock kho để chống snapshot stale của MySQL. Assignment job theo revision, không xóa cấp cũ; delivery pending riêng để phục hồi trước dispatch, không hứa exactly-once SMTP. Quy tắc VND dương P2 giữ nguyên, chưa hỗ trợ free order/promotion stacking.
- SaveProduct/SaveAccount là adapter persistence admin, giữ UploadedFile/Laravel ở Infrastructure. Atomic aggregate, child scope, file mới cleanup khi thất bại, ảnh cũ sau commit và kiểm references. Archive bảo toàn order relations/snapshots; chặn force-delete cả category vì cascade legacy.
- Không gỡ compatibility bridges chỉ để chuyển import tests: còn scripts maintenance và provider E2E chưa xác minh. Owner/điều kiện review tiếp được bổ sung trong registry P5. Không bật VNPay, thêm package, triển khai P7/P8 hoặc tự commit/push.
- Chi tiết matrix/schema/HTTP: [contract P6](p6-lifecycle-contract.md); [runbook](../runbooks/P6_OPERATIONS.md); [trạng thái kiểm chứng](../roadmap/P6_STATUS.md). Các ghi chú P0–P5 bên dưới là lịch sử, không ghi đè bằng quyết định mới lên dữ liệu cũ.

## ADR-017 — Module boundaries P5, giữ transaction P4 (2026-10-29)

- Chủ dự án yêu cầu triển khai tiếp P5. Phạm vi là các use case P2–P4 đã có tests: checkout/quote, stock protocol, payment, order transition/expiry; không rewrite CRUD, không triển khai P6 hoặc đổi database/API/dependencies.
- Sáu module có public Contracts và readonly DTO. Models giữ app/Models để tương thích; chỉ Infrastructure của owner import model. Domain/Application dùng ports, ActorContext và Clock. ModuleServiceProvider là composition root; SharedKernel không chứa service nghiệp vụ.
- Dependency DAG: Ordering → Identity/Pricing/Inventory/Payments; Pricing → Catalog; Inventory → Identity; các module dùng SharedKernel. Không Payments/Inventory → Ordering import. Inventory → Identity là thay đổi có lý do so với proposal ban đầu vì stock admin writer phải kiểm tra account tường minh.
- PaymentLedger sở hữu attempt facts/locks, kiểm lại signature/reference/merchant tại write boundary, trả PaymentResult. Ordering khóa order rồi xử lý result để ghi order/retain hold trong cùng transaction. Snapshot supplier đồng bộ giữ order → attempt → reservation mà không import module ngược. Không thêm event bus/outbox/network trong khóa.
- Expiry đổi Order/History nên orchestration thuộc Ordering; Inventory chỉ ghi reservation/stock. Legacy backfill coordinator đọc LegacyOrders DTO rồi gọi InventoryMaintenance, không query Order từ Inventory. Không chạy migration/backfill ứng dụng trong P5.
- Giữ Eloquent compatibility adapters cho tests/tools/admin verbs, một implementation duy nhất. Owner, caller và cổng gỡ P6 được ghi tại [adapter registry](p5-module-boundaries.md); Reporting read-only và legacy CRUD có ngoại lệ rõ, không cho module mới gọi ngược adapter.
- Pattern dùng có mục đích: Ports and Adapters, Application Services, immutable DTO, transaction Unit of Work và compatibility adapter; không generic repository, class state cho từng status hoặc domain aggregate song song với Eloquent cho mọi CRUD.
- [P5_STATUS](../roadmap/P5_STATUS.md) ghi tests/query budgets/rollback. Query overhead các entry tương thích tối đa một read; import/IPN không tăng. Thời gian synthetic local không phải production benchmark; load/SLO thuộc P7. Các số dư/hash P4 được giữ nguyên theo [read-only evidence](p5-runtime-verification.json).

## ADR-016 — Inventory protocol và cutover P4 (2026-10-29)

- Chủ dự án yêu cầu review P0–P3, chạy migration còn thiếu và tiến hành P4; xác nhận một kho active đủ toàn đơn, online TTL 15 phút/COD không tự hết hạn, cách ly legacy thiếu bằng chứng. Quyết định này cập nhật các blocker allocation/TTL/quarantine của ADR trước, không phê duyệt P5/P6.
- Giữ transaction MySQL chung. PlaceOrder điều phối server quote/order/reservation/payment attempt/cart; IdempotentOperation lưu hash payload và kết quả, trả lại effect cũ khi retry. Không gọi network trong khóa.
- Chọn mutex cấp warehouse theo ID tăng dần, sau đó inventory; khóa thô để bảo vệ cả tạo row mới và tất cả stock writer. Current locking read trên reservation child rows tránh snapshot SUM cũ và tránh join khóa parent của đơn khác. Chỉ thu hẹp lock sau benchmark/test chứng minh tương đương.
- StockWriter là writer balance/movement duy nhất; InventoryService giữ vai trò adapter. Confirm giữ tồn; shipping consume identity, cancel/expiry release đúng lines. Transfer là cặp movement cùng operation. Stocktake thấp hơn reserved giữ balance và tạo discrepancy. Chặn API model ghi tồn trực tiếp.
- Legacy backfill chỉ metadata, maintenance và flags dừng ghi. Bằng chứng không đủ hoặc aggregate overallocated cách ly toàn bộ phần mơ hồ; reserved chưa phân bổ vào legacy hold. Không sửa lịch sử/đoán kho. Kết quả thật: 18 backfilled, 27 quarantined, 40 held; fingerprint nghiệp vụ được bảo toàn.
- P2/P3 và hai migration P4 đã chạy sau full backup/restore/rehearsal, tổng 55 migrations. Hai gate inventory/order bật trong `.env` local; VNPay mới giữ tắt vì merchant IPN công khai chưa xác minh. Scheduler Windows mỗi phút chạy trong phiên đăng nhập, chưa phải service độc lập với login.
- Chỉ thêm abstraction phục vụ lỗi cụ thể: application service, allocation/lifecycle policy, narrow stock store, idempotent command và compatibility adapter. Không thêm generic repository, event bus, saga hay microservice.
- [Contract](p4-inventory-contract.md), [rollout evidence](p4-migration-verification.json), [cutover](p4-cutover-report.json), [status/tests/rollback](../roadmap/P4_STATUS.md). Các ADR-014/015 giữ làm lịch sử; các câu chưa rollout trong đó không còn là trạng thái mới nhất.

## ADR-015 — Transition atomic trong P3 (2026-10-29)

- Theo yêu cầu triển khai P3, dùng một OrderTransitionPolicy cho UI/service, giữ graph hiện có; confirm giữ reservation, shipping là admin xác nhận bàn giao và mới trừ tồn vật lý theo Q4 đã chốt.
- OrderService bắt buộc actor active admin tường minh, khóa/đọc lại order trước policy/replay; order_transitions vừa là operation identity vừa là lịch sử. Same key/payload không lặp effect; khác payload conflict. Unique target dựa trên graph acyclic, cần review schema nếu sau này mở vòng trạng thái.
- Thứ tự P3: order → operation/history → inventory tăng dần; phối hợp P2 order → payment attempt. P3 chưa thống nhất mọi inventory writer của P4.
- Chỉ nhận allocation khi movement/items/balance chứng minh được; không chọn kho default hoặc sửa legacy. Dry-run 52 đơn: 18 phù hợp tại thời điểm đọc, 27 cần đối soát, 7 terminal. Reviewer phải duyệt đường đối soát trước rollout.
- Stock methods cần transition context; movement P3 có ULID/actor tường minh, giữ generator cũ tương thích. Không thêm mail order chưa tồn tại; notification tương lai phải sau outer commit.
- Migration history forward-only chỉ chạy trên sandbox; ORDER_TRANSITIONS_ENABLED mặc định false. [Contract](p3-order-transitions.md), [bằng chứng/tests](../roadmap/P3_STATUS.md), [legacy audit](p3-legacy-order-audit.json). Không đánh dấu inventory an toàn hoặc tự triển khai P4.

## ADR-014 — Implementation P2 (2026-10-29)

- Đã triển khai giá bán từ SKU, VND nguyên/giới hạn theo orders schema, FormRequest, CartItemPolicy, active gate và login throttle. Không sửa tiền/giá vốn lịch sử hoặc thêm pricing rules chưa có.
- VNPay qua PaymentGateway/VnPayGateway và PaymentService; attempts/events additive, browser return không ghi tiền, IPN transaction + locks + unique dedup. Online tắt mặc định tới khi review schema/merchant. Chi tiết: [P2 contracts](p2-http-contracts.md), [P2 status](../roadmap/P2_STATUS.md).
- OAuth state/handoff một lần gắn browser proof; giữ password, không auto-link local account chưa verified. Provider-subject identities/authenticated linking chưa triển khai.
- Ngoại lệ tạm thời: PaymentService import Order model để giữ một local transaction. Module boundaries/ports đầy đủ sẽ xử lý khi P5 được duyệt, không kéo refactor đó vào P2.
- Hardening trên đã có code/test. Rollout DB ứng dụng, contract review và merchant end-to-end còn chờ xác nhận; không supersede phạm vi P3/P4 hoặc đánh dấu inventory an toàn.

Ngày lập: 2026-10-28. ADR-001–012 là đề xuất trừ phần quyết định nghiệp vụ hoặc triển khai được ghi rõ. ADR-013 ghi nhận lựa chọn đã áp dụng trong P1 theo chỉ đạo tiếp theo của chủ dự án. Việc tạo tài liệu đơn thuần không cho phép refactor, migration hoặc thay đổi API.

Mỗi quyết định được chốt bằng ngày, người phê duyệt, phạm vi và liên kết PR khi triển khai. Nếu thay đổi quyết định đã chốt, thêm ADR superseding thay vì xóa lịch sử.

**Bằng chứng bổ sung 2026-10-28:** [laravel.sql analysis](database-export-analysis.md) xác minh schema snapshot và migration history, chưa xác minh runtime. Q1 được làm rõ một phần: có dữ liệu lịch sử và bất nhất cần giữ để đối soát; không tự coi dữ liệu được phép xóa. ADR-009 phải phân biệt schema export với khả năng dựng mới từ migration. Các ADR vẫn ở trạng thái đề xuất.

## ADR-001 — Modular Monolith trước Microservices

- Bối cảnh: hiện có một Laravel app, database chung, transaction kho/đơn chưa nhất quán.
- Quyết định đề xuất: giữ một deployment Backend và một DB; chia namespace và quyền sở hữu dữ liệu theo nghiệp vụ.
- Phương án khác: tách order/inventory thành services ngay. Chưa chọn vì tăng distributed failure và compensation khi invariant nội bộ còn sai.
- Hệ quả: transaction ACID nội bộ vẫn dùng được; cần architecture tests để boundary không chỉ là quy ước.
- Áp dụng: P4–P5. Xem lại khi có tải, đội ngũ hoặc nhu cầu deploy độc lập được đo.

## ADR-002 — Clean Architecture có chọn lọc

- Bối cảnh: CheckoutController và InventoryService chứa nhiều quy tắc; CRUD nội dung tương đối đơn giản.
- Quyết định: domain thuần cho tiền, reservation và transition; application use cases dùng ports; Eloquent ở adapters. CRUD ít quy tắc giữ cấu trúc đơn giản.
- Phương án khác: generic repository và domain entity cho mọi bảng. Không chọn vì thêm mapping và abstraction chưa có lợi ích.
- Hệ quả: code cũ được chuyển từng use case; có tạm thời hai vị trí code nhưng chỉ một đường ghi nghiệp vụ đang active.
- Áp dụng: P4–P5; test dependency rules và contracts bắt buộc.

## ADR-003 — Backend là nguồn quyết định giá

- Bối cảnh: checkout dùng giá request; cost_price mang ý nghĩa không nhất quán.
- Quyết định: QuoteService tính giá từ SKU và chính sách phía server; order lưu snapshot tiền/currency; client không quyết định total.
- Cần chốt: cost_price là giá vốn hay giá giảm; đơn vị VND, rounding, phí ship và discount precedence. Không suy đoán khi backfill.
- Phương án khác: tin price_at_add hoặc giá client. Loại vì bị sửa và có thể hết hiệu lực.
- Hệ quả: có thể cần thông báo giá thay đổi; contract phải phân biệt quote và order final. Không tự đổi đơn lịch sử.
- Áp dụng: P0 chốt semantics, P2 bảo vệ checkout, P6 mở rộng coupon.

## ADR-004 — Reservation là nguồn sự thật của phân bổ kho

- Bối cảnh: checkout chọn inventory đầu tiên, order mặc định warehouse 1.
- Quyết định: reservation item lưu order item, warehouse, SKU, quantity và lifecycle; commit/release luôn dùng allocation này.
- Đề xuất MVP: chọn một kho active đủ toàn bộ đơn. Nếu không đủ, từ chối rõ ràng; chưa tự tách shipment. Schema cho phép nhiều allocation để mở rộng sau.
- Cần chốt: có chấp nhận từ chối đơn khi tổng nhiều kho đủ nhưng từng kho không đủ không? Nếu không, phải bổ sung split allocation/fulfillment scope trước P4.
- Hệ quả: thêm dữ liệu và đối soát; order.warehouse_id cũ không còn là nguồn duy nhất. Backfill mơ hồ phải xử lý thủ công.
- Áp dụng: P4. Không kích hoạt checkout mới trước khi migration/compatibility path đã kiểm chứng.

## ADR-005 — Một protocol khóa cho mọi đường ghi tồn

- Bối cảnh: có lock ở checkout nhưng thiếu ở nhập/xuất/chuyển/kiểm kê; order chưa lock khi transition.
- Quyết định: transaction cùng connection, row locks, thứ tự khóa xác định, unique constraints, kiểm tra invariant sau lock; retry deadlock giới hạn.
- Phương án khác: distributed lock hoặc chỉ DB::transaction. Chưa cần distributed lock; transaction đơn thuần không tránh stale read-modify-write.
- Hệ quả: có lock contention, phải transaction ngắn và kiểm thử nhiều connection. Không gọi network/mail khi giữ lock.
- Áp dụng: P3 cho chuyển trạng thái, P4 thống nhất toàn bộ inventory. Thứ tự chi tiết tại target-architecture.

## ADR-006 — Idempotency bền vững trong database

- Bối cảnh: checkout, callback, confirm/cancel và jobs có thể được gửi lại.
- Quyết định: operation key unique theo actor/use case; lưu payload hash và kết quả. Payment event/stock movement có identity dedup riêng.
- Phương án khác: disable nút UI hoặc cache TTL. Không đủ chống retry mạng, nhiều process hoặc cache mất.
- Hệ quả: cần retention, trạng thái processing/completed/failed, policy request đang xử lý. Cùng key khác payload phải bị từ chối.
- Áp dụng: P2 payment, P3 transitions, P4 checkout/stock, P6 coupon; retention chốt theo khả năng retry/đối soát.

## ADR-007 — Tách trạng thái tiền, đơn và tồn

- Bối cảnh: paid được cập nhật từ browser return; chưa có lifecycle COD/refund/expiry.
- Quyết định: Payments sở hữu verified payment attempts/events; Ordering sở hữu tiến trình fulfillment; Inventory sở hữu reservation. PaymentConfirmed không tự đồng nghĩa giao hàng xong.
- Cần chốt: trừ quantity khi confirm hay khi ship; TTL từng phương thức; thu COD; cancel/refund sau confirm; callback thành công đến muộn.
- Đề xuất: reservation active → committed hoặc released/expired; không có đường quay ngược tự do. Đã ship thì return là movement bù riêng.
- Hệ quả: cần transition table, timestamps và reconciliation queue; không chỉnh paid thủ công ngoài use case có audit.
- Áp dụng: P2 xác thực, P3 state guard, P4 reservation, P6 lifecycle đầy đủ.

## ADR-008 — Payment adapter và callback có xác minh

- Bối cảnh: VNPay logic trong CheckoutController; chưa kiểm tra chữ ký/amount.
- Quyết định: VnPayGateway triển khai PaymentGateway port; callback kiểm tra theo tài liệu chính thức được xác minh khi P2 triển khai. Browser return phục vụ UX, trạng thái cuối do dữ liệu payment đã xác thực.
- Cần xác minh: endpoint notification/IPN được merchant hỗ trợ, canonicalization/hash, retry/ack protocol và phạm vi unique provider transaction. Tài liệu hiện tại không khẳng định các chi tiết chưa kiểm tra.
- Hệ quả: fixtures có chữ ký đúng/sai, fake gateway, payment attempts và replay tests. Không call provider thật trong CI.
- Áp dụng: P2; không rollback sang handler public chưa xác minh.

## ADR-009 — Expand–migrate–contract cho schema

- Bối cảnh: migrations không khớp code; chưa biết schema và lịch sử thực tế.
- Quyết định: xác minh baseline trước; thay đổi additive, backfill có checkpoint, kiểm chứng rồi chuyển readers/writers; drop cột chỉ sau cửa sổ tương thích và backup restore drill.
- Phương án khác: sửa migration cũ hoặc migrate:fresh trên DB đang dùng. Không được chọn cho dữ liệu cần giữ.
- Hệ quả: fresh install và upgrade cần hai kịch bản test. Có thể cần baseline mới tùy kết quả P0; cách xử lý chưa chốt.
- Áp dụng: P0–P1 và mọi migration tiếp theo. Rollback ứng dụng không đồng nghĩa rollback dữ liệu kinh doanh.

## ADR-010 — Bảo toàn lịch sử, audit và quyền truy cập

- Bối cảnh: cascade có thể xóa order/items; cart thiếu ownership; API bỏ qua account status.
- Quyết định: policies/active-account gate; archive hoặc anonymize thay vì cascade lịch sử giao dịch; movements append-only và sửa sai bằng bút toán bù có actor/reason.
- Cần chốt: retention dữ liệu cá nhân/giao dịch và quyền quản trị kho; chưa coi đây là tư vấn pháp lý hoặc policy đã được duyệt.
- Hệ quả: ngăn hard delete có thể thay UX admin; cần báo dependency thay vì lỗi DB thô.
- Áp dụng: P2 quyền; P4 movements; P6 archive/retention.

## ADR-011 — Queue sau commit, outbox khi cần độ tin cậy delivery

- Bối cảnh: coupon job insert có thể lặp; dispatch trước commit.
- Quyết định: after-commit và idempotent jobs trước; khi cần event delivery chịu được crash giữa commit/enqueue, thêm outbox cùng transaction và inbox dedup consumer.
- Phương án khác: thêm message broker ngay. Không giải quyết atomicity DB/broker nếu thiếu outbox; chưa có lợi ích đủ rõ.
- Hệ quả: at-least-once, không hứa exactly-once; phải test crash/replay, retry và retention.
- Áp dụng: P6 jobs; P8 outbox/inbox nếu thí nghiệm service được phê duyệt.

## ADR-012 — Kiểm chứng trên engine thật, tối ưu theo số liệu

- Bối cảnh: test hiện chỉ ExampleTest; SQLite mặc định không chứng minh hành vi MySQL locks; chưa có benchmark.
- Quyết định: unit thuần + MySQL integration cách ly + concurrency nhiều connection; chốt workload/SLO sau baseline, chỉ thêm index/cache khi đo có lợi.
- Hệ quả: test guard phải chặn nhầm database đang dùng; CI cần quyền DB giới hạn. Chưa bắt buộc Docker, có thể dùng DB test local hiện có.
- Áp dụng: P0 nền tảng, P4 concurrency, P7 hiệu năng/CI.

## Các quyết định phải chốt trước triển khai phụ thuộc

### Quyết định nghiệp vụ được chủ dự án xác nhận trong P0

Ngày 2026-10-28, chủ dự án xác nhận trực tiếp:

- `cost_price` là **giá vốn**, không phải giá bán sau giảm. Q2 đã chốt ý nghĩa trường; đơn vị tiền/rounding, phí và discount precedence vẫn cần xác nhận riêng trước các thay đổi tương ứng.
- Tồn vật lý giảm khi **xuất/giao cho vận chuyển**, không giảm tại confirm. Q4 đã chốt thời điểm; điều kiện chứng minh bàn giao, xử lý hủy trước/sau bàn giao và return vẫn cần thiết kế ở P3/P4/P6.

Đây là phê duyệt về ý nghĩa nghiệp vụ trong phạm vi P0, không phải phê duyệt triển khai P1–P8. Code hiện tại dùng cost_price cho giá khách hàng và trừ quantity khi confirm vẫn được giữ để characterization; cần thay ở đúng giai đoạn sau.

| Mã | Câu hỏi | Mặc định đề xuất, không phải phê duyệt | Chặn |
| --- | --- | --- | --- |
| Q1 | Có DB/dữ liệu cần giữ? Schema thực ra sao? | Luôn coi dữ liệu cần giữ đến khi xác minh | P1 |
| Q2 | cost_price nghĩa gì và đơn vị tiền? | Đã xác nhận là giá vốn; đơn vị/rounding/phí còn mở | P2 giá và P6 |
| Q3 | Một kho/đơn hay chia kho? | MVP một kho đủ toàn đơn | P4 allocation |
| Q4 | Trừ tồn khi confirm hay ship? | Đã xác nhận khi xuất/giao cho vận chuyển; chi tiết bàn giao còn mở | P3/P4 lifecycle |
| Q5 | TTL, callback muộn, COD/refund/return? | Callback muộn vào đối soát, không tự tái giữ tồn | P4 expiry/P6 |
| Q6 | Quyền theo kho và retention? | Admin hiện tại + ownership trước; không xóa lịch sử | P6 quyền chi tiết |

Tham chiếu: [hiện trạng](current-architecture.md), [mục tiêu](target-architecture.md), [roadmap](../roadmap/UPGRADE_PLAN.md).

## ADR-013 — Laravel schema baseline và forward repair cho P1

- Trạng thái: **Đã áp dụng trong phạm vi P1, 2026-10-28**, sau yêu cầu dùng `.env` hoàn tất P0 và triển khai P1. Không đồng nghĩa chấp thuận P2–P8.
- Bối cảnh: migration history có cả hai bộ create kho và các file promotions cũ không còn trong thư mục ứng dụng. Sửa lịch sử sẽ làm database đang dùng và database dựng mới có lịch sử khác nhau khó kiểm chứng.
- Quyết định: giữ nguyên toàn bộ migration cũ. Commit `database/schema/mysql-schema.sql` theo cơ chế schema state có sẵn của Laravel; chỉ chứa DDL 40 bảng và 50 migration metadata rows, không business data, không USE/CREATE DATABASE và không counter AUTO_INCREMENT từ dữ liệu thật. Fresh migrate nạp baseline rồi chạy repair mới.
- Upgrade: database đã có migration history không nạp baseline; chỉ chạy `2026_10_28_000001_reconcile_application_schema`. Preflight trước DDL, map code rỗng theo ID, giữ code không rỗng, expand/normalize status, xử lý thumbnail và movement type, thêm unique thiết yếu. DDL MySQL không atomic nên repair có thể chạy lại; down từ chối thu hẹp/mất dữ liệu.
- Mã tài khoản mới: User creating hook cấp UUID 32 ký tự theo format admin đang dùng nếu thiếu code; không ghi đè code đã cấp. Unique DB là hàng rào cuối.
- Phương án không chọn: rewrite/xóa migrations lịch sử hoặc replay schema v1 bằng các mapping tồn không có đủ bằng chứng. Schema trước bộ kho mới sẽ fail preflight để yêu cầu mapping riêng, không tự đoán reserved/change.
- Kiểm chứng: native migrate trên DB trống; upgrade schema hiện tại và các dạng drift được hỗ trợ; fail-fast duplicate code; backup/restore synthetic; full backup/restore và upgrade bản sao dữ liệu thật; sau áp dụng DB ứng dụng giữ nguyên business fingerprints.
- Hệ quả: MySQL client cần có trong PATH cho fresh migrate; rollback an toàn là forward repair hoặc restore trên target cách ly đã kiểm chứng. Không dùng migrate:rollback để giả vờ đảo ngược mappings. Xem [hướng dẫn schema](../../database/schema/README.md) và [P1_STATUS](../roadmap/P1_STATUS.md).
