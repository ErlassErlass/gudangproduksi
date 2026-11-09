# 02 — Scope, Assumptions, and Decision Register

## Confirmed decisions
| Area | Final decision |
|---|---|
| System scope | Production + Warehouse + Inventory + Distribution |
| Stock tracking | Hybrid quantity, serialized, and batch |
| Production items | MLK, modules, projects, and other physical products |
| BOM | Standard MLK template + flexible BOM |
| GAP | Managed by Erlass staff; no external portal initially |
| Costing | Material/component and purchase cost only |
| Excluded cost | Labor, machine, electricity, overhead, depreciation |
| Architecture | Modular Monolith |
| Backend/frontend/database | Laravel 12 / React.js / MySQL |
| API/auth | REST `/api/v1` / Sanctum |

## Operational interpretation from workbook
- MLK flow: Component → WIP Module → WIP Box → Finished Good.
- Components have unique component codes, e.g. `SP.PART.ER.016`.
- Modules have codes such as `M01`; boxes such as `B01`.
- Example location barcode pattern: `POP.L3R3A1.M01`.
- Example production execution code: `{product_code}-{YYMMDD}-{sequence}`, e.g. `1001-261107-01`.
- Example rental/loan code: `PP-{YYMMDD}-{sequence}`.
- Warehouse location hierarchy includes building, floor, room, rack, and rack/bin number.
- Existing rack zoning examples: A for components, B for projects, C for WIP module/box. Treat this as configurable master data, not hardcoded application logic.
- Workbook includes a catalog for 2027 with program type, product name, service/physical indicator, education level/class, audience, channel, yearly status, and price. Normalize this into related tables and effective-dated pricing.

## Assumptions requiring configuration, not hardcoding
1. Document numbering formats and prefixes.
2. Warehouse/site list and location tree.
3. QC checklist per product/category.
4. Approval thresholds and approver roles.
5. Safety stock and reorder rules.
6. UOM conversions.
7. Default currency IDR and timezone Asia/Jakarta.
8. Whether a specific stock item is serialized, batch, or quantity-based.
9. Compensation price basis for missing rental components.
10. Whether rental order represents rental, loan, or both (use `transaction_kind` enum).

## Clarifications not blocking initial implementation
- Exact list of approvers and monetary approval thresholds.
- Final import mapping for every legacy spreadsheet column.
- Exact document template design (DO, MOU, return receipt, claim).
- Whether weighted average is perpetual or periodic. Implement transaction-time moving weighted average, but keep valuation service isolated.
- Whether all customers are schools; schema should allow organization and individual customers.

## Data migration policy
- Preserve legacy codes as `legacy_code` or external reference.
- Do not overwrite identifiers when normalizing category or product classification.
- Detect duplicates, whitespace differences, mixed formats, invalid prices, and inconsistent status labels.
- Import into staging tables first; produce validation report; only then commit to production master data.
- Do not infer missing values from neighboring rows.
