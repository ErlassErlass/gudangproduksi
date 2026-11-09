# 06 — Business Rules and Invariants

## Inventory invariants
1. Every posted stock movement must have a ledger header and line.
2. No direct balance mutation outside Inventory Posting Service.
3. Posted transactions are immutable. Reverse using a new transaction linked to the original.
4. Posting must be atomic and idempotent.
5. Lock relevant stock dimensions before validating quantity.
6. Available = eligible on-hand − active reservations.
7. Quarantine, damaged, missing, and in-transit stock are not available for normal picking.
8. No negative available stock by default.
9. Serialized unit quantity is one; one active location/status at a time.
10. Batch-controlled items must retain batch identity through issue/receipt/transfer where required.

## Reservation rules
- Reservation reduces available quantity but does not reduce on-hand.
- Reservation must be released on cancellation/expiry and consumed on issue.
- A serial cannot be reserved for overlapping rental periods.
- Do not double reserve the same quantity/serial.

## BOM rules
- One active BOM version per product for a given effective date.
- Released production order retains immutable BOM and stage snapshots.
- BOM alternatives require explicit approval when configured.
- BOM quantity and UOM conversion must be resolved before MRP explosion.
- BOM cycles are prohibited; validate graph before activation.

## Production rules
- Production plan is not a production order until approved/released.
- Material issue uses actual inventory valuation at posting time.
- Actual consumption may differ from BOM standard; record variance reason.
- Returned unused material posts back to inventory and reverses associated consumption cost.
- Material used on rejected/scrapped units remains production material cost.
- WIP is inventory at a defined stage/location/status, not an unexplained difference.
- Closing requires stage reconciliation, no unresolved material request, and mandatory QC pass.

## Procurement rules
- Receipt quantity cannot exceed outstanding PO quantity unless an authorized over-receipt tolerance exists.
- Partial receipts are allowed.
- Accepted goods post to available stock; rejected goods go to quarantine/return disposition.
- Preserve PO price and actual receipt price snapshots; never overwrite historical cost.

## Vendor/GAP rules
- Dispatch moves stock to external vendor location, not out of Erlass ownership.
- Vendor order, shipment, and vendor receipt are separate documents.
- Sent = completed + outstanding + damaged + other explicitly dispositioned quantity; enforce a reconciliation model without double counting.
- Only physically received quantity can be posted back from vendor.
- Received stock is quarantined until QC disposition.
- GAP service fee is excluded from production material costing in phase 1.

## Rental rules
- Rental dispatch requires reserved/available serialized unit(s).
- Capture expected composition at dispatch time.
- Return must compare expected vs actual contents.
- Missing/damaged components create discrepancy and QC records.
- Returned unit is not available until QC disposition.
- Generate handover/return confirmation documents.

## Cost rules
- Quantity-tracked materials use moving weighted average initially.
- Unit cost on issue is a snapshot and cannot be recalculated retroactively.
- Production material cost = sum of net actual consumed issue cost.
- Cost per good unit = total material cost / accepted good output.
- Do not include labor, machine, electricity, overhead, depreciation, or GAP service fee.

## Document lifecycle
- Draft: editable.
- Submitted: locked from ordinary edits; returned to draft only through explicit reject/revision flow.
- Approved: eligible for next lifecycle action.
- Posted: immutable.
- Reversed: original remains visible; reversal references original.
- Cancelled: only before posting, unless a reversal/compensating flow is used.
