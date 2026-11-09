# 12 — Implementation Roadmap

## Delivery strategy
Build vertical slices with working tests, not all UI first or all CRUD first. Prioritize inventory correctness before high-level automation.

### Phase 0 — Foundation
- Laravel 12 + React app integration, MySQL.
- Sanctum, RBAC, API response/error conventions.
- Audit, document numbering, attachments, queue, CI.
- Domain boundaries and coding standards.

### Phase 1 — Master Data
- Product classification, categories, UOM.
- Warehouse/location, customer, supplier, vendor, employees.
- Barcode and import staging/validation.
- Seed demo master data.

### Phase 2 — BOM
- Standard MLK template.
- Flexible BOM editor and versioning.
- Stage mapping, cycle validation, BOM explosion.
- Snapshot model for production order.

### Phase 3 — Inventory Engine
- Ledger, balance projection, reservations.
- Quantity/batch/serialized tracking.
- Transfers, adjustments, stock card, opname.
- Concurrency and idempotency tests.
**Gate:** no downstream module may post stock before this phase passes.

### Phase 4 — Procurement
- PR/PO, supplier pricing, partial GRN.
- Incoming QC and receipt posting.
- Purchase price history.

### Phase 5 — Production Planning / MRP
- Production plan, MRP runs, shortage report.
- Approval and production order creation.
- Material budget estimate.

### Phase 6 — Production Execution
- Work orders and stages.
- Material request/issue/unused return.
- Production results, WIP, reject, scrap, reconciliation.

### Phase 7 — QC and GAP Vendor
- QC templates, inspection, discrepancy.
- Vendor order, dispatch, partial receipt, vendor outstanding.
- External vendor location handling.

### Phase 8 — Rental and Distribution
- Rental/loan availability, reservation, dispatch, return.
- Unit-level composition and QC.
- Shipment/DO and proof of delivery.

### Phase 9 — Material Cost and Reporting
- Moving weighted average.
- Production cost summaries and actual-vs-standard.
- Reports and exports.

### Phase 10 — Hardening and deployment
- Full regression, permission audit, load tests.
- Backup/restore, monitoring, queue worker, scheduler.
- UAT, migration dry-run, production release.

## Indicative schedule
A single full-stack developer assisted by coding agents may require roughly 12–16 weeks for a disciplined MVP, depending on data cleanup, document templates, UX complexity, and test coverage. This is a planning estimate, not a commitment.

## Release gates
- Gate A: inventory ledger correctness.
- Gate B: procurement receipt and stock availability.
- Gate C: production order snapshot and execution reconciliation.
- Gate D: vendor and rental traceability.
- Gate E: cost traceability and report reconciliation.
- Gate F: security, backup, UAT, and deployment readiness.
