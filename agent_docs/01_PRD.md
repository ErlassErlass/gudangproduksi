# 01 — Product Requirements Document (PRD)

## 1. Product vision
Membangun sistem operasional terpadu yang menghubungkan perencanaan kebutuhan, produksi bertahap, persediaan, gudang, vendor processing, rental, QC, dan distribusi dalam satu alur yang terukur dan dapat diaudit.

## 2. Problem statement
Spreadsheet saat ini memuat master produk, komponen, modul, box, lokasi, peminjaman, QC, dan katalog program. Data tersebut belum menjadi sistem transaksi terintegrasi. Risiko utama adalah saldo stok tidak konsisten, WIP tidak terlacak, pengadaan tidak terhubung dengan kebutuhan, barang vendor tidak terlihat, serta selisih pengembalian tidak terdokumentasi secara konsisten.

## 3. Goals / outcomes
- Setiap item persediaan memiliki identitas, satuan, metode tracking, dan lokasi.
- Setiap transaksi stok menghasilkan ledger entry yang immutable.
- Production plan dapat menghasilkan kebutuhan material melalui BOM explosion/MRP.
- Setiap Production Order menyimpan snapshot BOM yang dipakai saat dirilis.
- Tahap produksi MLK dapat melacak Component → WIP Module → WIP Box → Finished Good.
- Partial receipt, partial production, reject, scrap, dan outstanding dapat dicatat terpisah.
- Rental/loan dapat melacak unit dan kelengkapan komponen saat keluar dan kembali.
- Cost material dihitung dari actual posted issue dan nilai unit cost saat issue.
- Semua laporan dapat difilter menurut tanggal, produk, lokasi, dokumen, PIC, dan status.

## 4. Non-goals fase awal
- Payroll, time tracking, labor costing.
- Machine/electricity/overhead/depreciation costing.
- Vendor self-service portal.
- Full accounting/general ledger, tax, invoicing, accounts payable.
- E-commerce storefront.
- Advanced demand forecasting/AI optimization.
- Multi-company consolidation.
- Automatic purchasing without human approval.

## 5. Functional requirements

### FR-01 Authentication and administration
- Login/logout, user management, role and permission management.
- Sanctum session/token handling.
- Account activation/deactivation.
- Audit log for login and privileged actions where feasible.

### FR-02 Master data
- Product, category, product type, physical type, fulfillment type.
- Component, module, box, finished good, project, service, program.
- UOM and conversion, warehouse, location hierarchy, customer, supplier/vendor, employee/PIC.
- Product code uniqueness; barcode/QR identifiers.
- Effective-dated prices and status history.
- Import/export with validation and error report.

### FR-03 Product classification
Each catalog item must be described across independent dimensions:
- `product_type`: PRODUCT, SERVICE, PROGRAM.
- `physical_type`: PHYSICAL, DIGITAL, NON_INVENTORY.
- `fulfillment_type`: SELL, RENTAL, INTERNAL, MIXED.
- `tracking_method`: QUANTITY, SERIALIZED, BATCH, NONE.
A PROGRAM may contain service and physical components using `product_components`. Do not use a single overloaded type field to represent all dimensions.

### FR-04 BOM management
- Standard MLK BOM template: components → WIP module → WIP box → finished good.
- Flexible multi-level BOM for other physical products, projects, and subassemblies.
- Versioning, effective dates, alternatives, UOM, scrap allowance, sequence.
- Draft/review/active/retired lifecycle.
- BOM snapshot copied into production order on release.

### FR-05 Procurement
- Purchase requisition (PR), approval, purchase order (PO), supplier, expected date.
- Partial goods receipt.
- Actual received quantity and received price captured at receipt.
- Incoming QC accept/reject/quarantine.
- Receipt posts inventory only for accepted quantity.
- MRP recommendation does not automatically create PO.

### FR-06 Production planning and MRP
- Create production plan by product, target quantity, due date, customer/project, priority.
- Calculate gross requirements from BOM explosion.
- Calculate net requirement using available stock and confirmed incoming, accounting for reservations and safety stock.
- Display shortages and suggested purchase quantities.
- Estimate material budget using current reference cost; label as estimate.
- Submit and approve plan; create production order from approved plan.

### FR-07 Production execution
- Production order → work orders/stages → material request → material issue → production result.
- Track target, issued, consumed, returned-unused, good output, reject, scrap, rework, and WIP.
- Record PIC, timestamps, notes, attachments, and QC.
- Stage output becomes next stage input.
- Completion requires reconciled quantities and required QC passed.

### FR-08 Inventory
- Centralized stock ledger.
- Quantity, serialized, and batch tracking.
- Stock balance by product/location/batch/serial/status.
- Reservation and release.
- Transfers, adjustments, returns, scrap, stock opname.
- Prevent negative available stock by default.
- Transaction posting must be atomic and idempotent.

### FR-09 Warehouse
- Hierarchical location: site/building → floor → room → rack → bin.
- Barcode scan for receive, put-away, pick, transfer, dispatch, and opname.
- Stock card per item and location.
- Warehouse dashboard for on-hand, reserved, available, quarantine, damaged, in-transit, and at-vendor.

### FR-10 Quality control
- Inspection types: incoming, production stage, finished goods, rental return, vendor return, discrepancy.
- Configurable checklist by product/BOM.
- Record pass/fail/conditional, damage, missing component, photos, inspector, timestamp.
- Failed goods move to quarantine/damaged/repair status.
- Rental return QC compares expected kit contents against actual returned contents.
- Generate compensation/claim document using configured component reference price; do not automatically post a financial receivable in phase 1.

### FR-11 GAP/vendor processing
- Erlass staff creates vendor order and shipment.
- Dispatch moves owned stock to external vendor location / AT_VENDOR.
- Staff records completed, outstanding, damaged, and returned quantities.
- Partial receipt supported.
- Received goods undergo QC before returning to available stock.
- Vendor order is separate from shipment and receipt documents.

### FR-12 Rental and loan
- Rental/loan order with customer/school, project, GL/sales PIC, expected dispatch/return date.
- Reserve serialized box/unit and its component composition.
- Dispatch, return, inspection, missing/damaged component, repair, and closeout.
- Generate handover and return receipt documents.
- Search/filter by customer, PIC, school, product, date, and status.

### FR-13 Distribution
- Pick/pack/dispatch against approved sales, project, internal, or rental demand.
- Delivery order (DO)/shipment document.
- Partial dispatch and delivery status.
- Stock remains in transit until delivery confirmation where required.

### FR-14 Material cost
- Purchase price history and actual goods receipt cost.
- Moving weighted average for quantity-tracked materials as initial policy.
- Serialized assets retain per-unit acquisition cost/history.
- Production material cost = sum(actual quantity consumed × unit cost at issue).
- Returned unused material reverses/reduces production consumption through linked return transaction.
- Material used on rejected output remains part of consumed material cost; report yield and cost per good unit separately.
- Exclude labor, machine, electricity, overhead, depreciation.
- GAP service/processing fee excluded from initial production costing.

### FR-15 Reporting
- Inventory balance and stock card.
- Inventory aging, stock movement, low-stock indicator (threshold optional per item).
- Production plan vs actual, stage output, WIP, yield, rejection, material variance.
- Procurement lead time and price history.
- Vendor outstanding/aging.
- Rental utilization, overdue, missing/damaged.
- QC defect and return discrepancy.
- Material cost by production order/product/period.
- CSV/XLSX/PDF export according to permission.

## 6. Non-functional requirements
- Authorization enforced server-side on every protected endpoint.
- Database transactions for posting operations.
- Row-level locking for stock mutation.
- Idempotency for retriable posting endpoints.
- Auditability for stock, approval, cost, and master changes.
- Pagination, filtering, and server-side sorting for large lists.
- Validation errors returned consistently.
- Store timestamps in UTC; display in configured Asia/Jakarta timezone.
- Do not expose secrets in frontend or logs.
- File upload validation, private storage for sensitive documents.
- Automated tests for critical business invariants.
- Responsive desktop-first UI; warehouse scanning workflows usable on tablet/mobile.

## 7. Success metrics
- 100% stock-changing actions represented in ledger.
- 0 duplicate postings from retry of same idempotency key.
- 0 negative available stock unless explicit authorized exception policy is later approved.
- 100% released production orders retain BOM snapshot.
- 100% vendor dispatch quantities reconciled to returned/completed/outstanding/damaged categories.
- 100% rental returns have inspection disposition before inventory availability.
- Production cost report traces to posted issue transactions.
