# 11 — Testing Strategy and Acceptance Criteria

## Test layers
1. Unit tests: calculations, state transitions, BOM explosion, UOM conversion, cost calculation.
2. Feature/API tests: validation, authorization, lifecycle endpoints, response contracts.
3. Database tests: constraints, transactions, row locking behavior where practical.
4. Integration tests: procurement → receipt → inventory; production → issue → output; rental → return → QC.
5. UI tests: critical operational flows, permission-based visibility, validation.
6. Regression tests: each bug fixed receives a regression test.

## Critical scenarios
- Concurrent issue attempts cannot overdraw available stock.
- Repeated POST with same idempotency key does not double-post.
- Reversal restores correct quantity and links to original transaction.
- Reservation reduces available but not on-hand.
- Serial item cannot be reserved by overlapping rental orders.
- BOM update after production release does not alter order snapshot.
- BOM cycle is rejected.
- Partial PO receipt leaves correct outstanding quantity.
- Rejected incoming quantity is not available stock.
- Vendor dispatch moves stock to external location/AT_VENDOR.
- Partial vendor receipt does not clear outstanding quantity.
- Rental return cannot become available before QC.
- Missing/damaged rental components create discrepancy records.
- Unused material return reduces net production consumption cost.
- Reject/scrap material remains included in material cost.
- Cost per good unit handles zero accepted output safely.
- User without permission cannot approve or post by manipulating API request.

## Acceptance criteria (baseline)
- AC-001: MLK uses standard stage/BOM template.
- AC-002: Other physical products can use configurable flexible BOM.
- AC-003: BOM version changes do not alter released production order snapshots.
- AC-004: Dispatch of 50 units to GAP moves 50 to external vendor location.
- AC-005: Receipt of 45/50 records 45 physically received.
- AC-006: Remaining 5 are reconciled into outstanding/damaged/other documented disposition, not silently cleared.
- AC-007: Damaged goods are not included in available stock.
- AC-008: Supplier price changes preserve historical PO/receipt cost.
- AC-009: Production material cost derives from actual posted inventory issues.
- AC-010: Material consumed on reject/scrap is visible in production cost.
- AC-011: Cost report includes material/purchase cost only.
- AC-012: No labor/overhead cost is implicitly added.
- AC-013: Rental/loan serial cannot be double-booked.
- AC-014: Rental return checklist compares expected and actual composition.
- AC-015: Inventory posting is transactional and idempotent.
- AC-016: Every stock adjustment is auditable and approval-controlled.

## Test data
Create seeders for:
- Admin and representative operational roles.
- Warehouse/location tree (including external GAP location).
- UOMs and sample products.
- Sample components, M01–M07 modules, B01–B03 boxes, MLK finished good.
- Standard MLK BOM and one flexible BOM.
- Supplier, vendor, customer/school.
- Example rental serial units and QC checklist.
Keep seed data clearly marked as demo and never mix with production imports.
