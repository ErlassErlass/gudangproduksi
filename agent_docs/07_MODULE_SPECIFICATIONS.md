# 07 — Module Specifications

## M01 Dashboard
- Role-based KPI cards: on-hand, available, reserved, WIP, at vendor, in transit, damaged/quarantine.
- Production plan/order status, low-stock/shortage, overdue rental, vendor outstanding, QC queue.
- Date, warehouse, category, product filters.
- Dashboard values must be computed from domain queries, not duplicated manually maintained totals.

## M02 Master Data
- CRUD for products, categories, UOM, warehouse/location, customers, suppliers, vendors, employees/PIC.
- Product classification dimensions as specified in PRD.
- Catalog year status and effective-dated prices.
- Import preview, validation, duplicate report, commit.
- Product detail page includes BOMs, barcode, stock summary, transaction history.

## M03 BOM Management
- Create standard MLK template and flexible BOM.
- Multi-level tree editor, UOM, quantity, scrap, alternative component.
- Stage mapping and effective dates.
- Validate cycles and active-version overlap.
- BOM cost estimate is indicative only.

## M04 Procurement
- PR creation and approval.
- PO generation from approved PR or manual purchase.
- Supplier price history.
- Partial GRN, incoming QC, accepted/rejected quantities.
- Post accepted quantities through Inventory service.

## M05 Production Planning / MRP
- Plan header and lines; priority, due date, customer/project.
- BOM explosion and MRP run.
- Shortage matrix, incoming supply, reservations, safety stock.
- Suggested procurement quantities and estimated material budget.
- Approval and conversion to production order.

## M06 Production Execution
- Production order release with BOM snapshot.
- Work order by configured stage.
- Material request, approval, issue, unused return.
- Record output: good, reject, scrap, WIP, rework.
- Stage reconciliation and timeline.
- Close only after required QC and reconciliation.

## M07 Inventory
- Stock balance by item/location/status/batch/serial.
- Stock card and transaction search.
- Reservation, transfer, adjustment, return, scrap, opname.
- Centralized posting service.
- Serial and batch traceability.

## M08 Warehouse
- Hierarchical location management.
- Barcode label generation and scan workflow.
- Put-away, picking, transfer, dispatch.
- Rack/bin stock view and print labels.
- Configurable location zones; do not hardcode A/B/C rack semantics.

## M09 Quality Control
- Inspection templates/checklists by product/category and inspection type.
- Incoming, in-process, finished goods, rental return, vendor receipt.
- Pass/fail/conditional disposition.
- Defect, missing, damage, photo evidence.
- Compensation estimate document for rental loss; no accounting receivable posting in phase 1.

## M10 Vendor Management
- Vendor master and vendor order.
- Dispatch and external vendor location.
- Processing result and partial return.
- Outstanding/damaged reconciliation.
- QC after receipt.
- No vendor login/portal initially.

## M11 Rental Management
- Rental and loan order.
- Unit availability calendar.
- Serial/box reservation and dispatch.
- Expected kit composition snapshot.
- Return intake, QC, missing/damaged, repair.
- Handover and return documents.
- Filters by GL, sales PIC, customer/school, product, period.

## M12 Distribution
- Demand source, reservation, pick list, shipment/DO.
- Partial dispatch and delivery confirmation.
- In-transit tracking and delivery evidence.
- Support customer, project, internal and rental dispatch.

## M13 Material Cost Management
- Purchase price history and receipt cost.
- Inventory valuation service.
- Actual issue cost by production order/work order.
- Unused material return adjustment.
- Reject/scrap cost visibility.
- Cost per good unit and standard-vs-actual material variance.
- No labor/overhead categories.

## M14 Reporting
- Saved filters and export.
- Stock card, stock balance, stock aging.
- Production plan vs actual, WIP, yield, scrap, variance.
- Procurement price history and vendor performance.
- Rental utilization/overdue/condition.
- QC discrepancy and claim summary.
- Material cost by product/order/period.

## M15 Administration
- User, role, permission.
- Approval policy.
- Numbering configuration.
- Document templates.
- Audit log.
- System settings and reference data.
