# 13 — Data Import and Workbook Mapping Guide

## Source workbook
`RUNDOWN PEMBUATAN WEB ERLASS INVENTORY.xlsx`

The workbook includes:
- Operational production rundown.
- Legacy product catalog and 2027 product catalog.
- Production execution codes.
- Component master and allocation use.
- Module and module-component BOM.
- Box and module composition.
- Warehouse building/floor/room/rack/bin.
- Barcode examples.
- Rental/loan tracking and customer information.
- QC workflow and feature list.
- User roles and feature access.
- Additional requirements for component/module/box/finished good stock cards and BOM.

## Recommended normalization
| Spreadsheet concept | Target entity |
|---|---|
| Product / old product / new produk 2027 | `products`, `product_categories`, `product_prices`, `product_status_histories` |
| Jenis Program | `program_types` and product classification |
| Jenjang / Kelas / Guru / Umum | `education_levels`, `class_levels`, audience relations |
| SD/SMP/SMA-K/Guru/Umum columns | normalized audience/education relations, not booleans |
| Komponen | `products` with component category and tracking method |
| Modul | `products` + BOM |
| Box | `products` + BOM; serialize when used as rental asset |
| Detail Komponen + Qty Komponen | `bom_items` |
| Detail Modul per Box | `bom_items` / subassembly links |
| Lokasi Gedung/Lantai/Ruangan/Rak | `warehouses`, `locations` hierarchy |
| Barcode | `product_barcodes`, `product_serials`, location barcode table if required |
| Peminjaman | `rental_orders`, `rental_order_items`, `rental_returns` |
| QC | `quality_inspections`, checklist, discrepancy |
| Customer / Sekolah | `customers` |
| Vendor | `vendors` |
| Data akun / role | `users`, `roles`, `permissions` |

## Import process
1. Upload workbook to import staging.
2. Parse each sheet and detect header rows; do not assume first row is always header.
3. Normalize whitespace, dates, numeric values, and status labels.
4. Map legacy codes and preserve source sheet/row reference.
5. Run duplicate detection (code, name, barcode, serial).
6. Validate foreign references (category, UOM, BOM component, location).
7. Produce preview and row-level error report.
8. Require authorized confirmation.
9. Import master data.
10. Import BOM and opening stock only from approved opening balance source.
11. Reconcile imported opening balance to signed/approved stock opname.
12. Preserve import batch ID and source row for audit.

## Important cautions
- Some workbook sheets/rows are legacy or incomplete.
- Some catalog rows represent services while others represent physical goods or mixed programs.
- Product category and fulfillment type are different concepts.
- Do not infer a missing price or status.
- Do not assume spreadsheet sample data is actual live stock.
- Validate the sample barcode convention before generating permanent labels.
