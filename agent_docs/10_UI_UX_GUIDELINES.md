# 10 — UI/UX Guidelines

## Design principles
- Desktop-first operational dashboard; responsive for tablet/mobile scanning.
- Clear information hierarchy; dense tables for operations, cards for KPIs and status summaries.
- Use consistent status labels, badges, empty states, loading states, and confirmation dialogs.
- Avoid destructive actions; post/reverse actions require explicit confirmation and reason.
- Show document number and status prominently on detail pages.
- Provide global search for document number, product code, serial, barcode, customer, and vendor.

## Main navigation
1. Dashboard
2. Master Data
3. BOM
4. Procurement
5. Production Planning
6. Production Execution
7. Inventory
8. Warehouse
9. Quality Control
10. Vendor / GAP
11. Rental & Loan
12. Distribution
13. Material Cost
14. Reports
15. Administration

## Standard list page
- Page title and breadcrumb.
- Primary action button subject to permission.
- Search and filters.
- Server-side pagination and sorting.
- Table columns relevant to the domain.
- Row actions governed by document status and permission.
- Export respects current filters and user permission.

## Standard document detail
- Header: document number, status, date, owner/PIC.
- Summary fields.
- Line items.
- Related documents/timeline.
- Attachments.
- Approval history.
- Action buttons only for valid state transitions.

## Inventory screens
- Separate On Hand, Reserved, Available, In Transit, At Vendor, Quarantine, Damaged.
- Stock card timeline with opening balance, movement, closing balance.
- Location tree and barcode scan input.
- Serialized items show unit-level status and location.
- Do not visually imply quarantined stock is available.

## Production screens
- Plan overview with target, MRP shortage, budget estimate, approval.
- Production order stage timeline.
- Stage input/output reconciliation.
- Material request and issue.
- Actual vs standard BOM consumption.
- WIP and rejected/scrap output separately.

## Rental screens
- Availability calendar.
- Unit/box selection with serial and kit contents.
- Dispatch checklist.
- Return checklist comparing expected and actual.
- Damage/missing component panel and QC disposition.

## Accessibility and usability
- Keyboard-friendly forms and tables.
- Labels for every input; meaningful validation text.
- Do not rely on color alone for status.
- Barcode field should autofocus where appropriate and support scanner keyboard input.
- Confirmation modal must explain the stock/cost consequence.
