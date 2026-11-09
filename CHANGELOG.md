# Changelog

## [1.1.0] — 2026-11-09 (Katalog Produk Mandiri, Program Edukasi & Rencana Produksi Fleksibel)

### Added
- **Pemisahan Halaman Katalog & Produk Mandiri** — Halaman tersendiri untuk:
  - **Komponen** (`/catalog/components`)
  - **WIP Modul** (`/catalog/modules`)
  - **WIP Box** (`/catalog/boxes`)
  - **Finished Good & Jasa** (`/catalog/finished-goods`)
  - **Program Edukasi** (`/catalog/programs`)
  Masing-masing halaman dilengkapi metrik kesehatan stok, modal rincian saldo fisik per lokasi/serial/batch (`StockDetailModal`), dan formulir pembuatan/perubahan data barang (`ProductFormModal`).
- **Manajemen Program Edukasi (`product_components`)** — Model [`ProductComponent`](file:///d:/project/erlass_pwms/backend/app/Domains/MasterData/Models/ProductComponent.php) dan endpoint `/api/v1/products/{product}/components` untuk mengelola paket program sebagai gabungan dari produk service dan finished good (atau salah satunya). Modal interaktif `ProgramBundleModal` memungkinkan penambahan dan pengubahan isi paket sewaktu-waktu.
- **Fitur BOM Tahap Selanjutnya di Setiap Halaman** — Tombol modal `BomStageModal` di setiap halaman katalog yang terhubung ke endpoint `/api/v1/boms/by-product/{product}` dan `/api/v1/boms/quick-save` untuk melihat alur konsumsi ke tahap berikutnya dan memperbarui resep BOM sewaktu-waktu dengan aktivasi otomatis.
- **Rencana Produksi dengan Kuantitas Manual Dinamis** — Modal pembuatan rencana produksi pada `ProductionPlanningPage` mendukung penambahan multi-produk target perakitan dengan kuantitas manual berapapun serta rilis Production Order per item.

### Changed
- **Pembersihan Dashboard** — Menghapus metrik "Versi BOM Aktif" dari [`DashboardController.php`](file:///d:/project/erlass_pwms/backend/app/Http/Controllers/Api/V1/DashboardController.php) dan dashboard frontend, digantikan dengan metrik "Rencana Produksi Aktif".
- **Penghapusan Halaman Khusus Vendor** — Menghapus menu khusus `/vendor-management` dari navigasi utama (`navigation.ts` dan `router.tsx`) sesuai instruksi bahwa data vendor tidak perlu ditampilkan pada halaman khusus.
- **Pengujian Otomatis** — Menambahkan 3 pengujian fitur baru di [`InventoryEngineAndBomTest.php`](file:///d:/project/erlass_pwms/backend/tests/Feature/InventoryEngineAndBomTest.php) (total 21 test, 149 assertions, 100% lulus).

## [1.0.0] — 2026-11-09 (Phases 1–9 — Full PWMS Implementation)

### Added
- **Phase 1 (Master Data & Warehouse)** — Migrations, models, and `/api/v1/master-data` & `/api/v1/warehouses` endpoints for UOMs, Product Categories, Products (`product_type`, `physical_type`, `fulfillment_type`, `tracking_method`), Barcodes, Serials, Batches, Customers, Suppliers, Vendors, Warehouses, and Locations, plus `DemoMasterAndInventorySeeder`.
- **Phase 2 (Multi-Level BOM & Stage Templates)** — `BomService` with DAG cycle detection, single active version enforcement, and multi-level BOM explosion (`MLK` -> `Sub-Assembly` -> `Raw Material`), plus `/api/v1/boms` endpoints and React `BomPage`.
- **Phase 3 (Core Inventory Engine — Gate A)** — `InventoryPostingService` (sole implementation of `InventoryPostingContract` with lexicographical `dimension_key` sorting, `SELECT ... FOR UPDATE` row locking, non-physical item rejection, negative stock prevention, and immutable reversal transactions), `ReservationService`, and `StockAdjustmentService` (enforcing maker-checker separation of duties).
- **Phase 4 (Procurement & Receiving — Gate B)** — `ProcurementService` for Purchase Orders, partial/full Goods Receipts (GRN), incoming QC split (`AVAILABLE` vs `QUARANTINE`), and `purchase_cost_histories` tracking.
- **Phase 5 & 6 (Production Planning / MRP & Execution — Gate C)** — `ProductionWorkflowService` supporting MRP netting (`max(0, gross - (available - reserved) - incoming_po)`), Production Order locking of `bom_version_id`, Material Issue, Unused Material Return, 6-stage Work Order quantity reconciliation (`input = good + waste + rework`), Finished Goods receipt, and material-only `ProductionCostSummary`.
- **Phase 7 (GAP Vendor Processing & Quality Control — Gate D)** — `VendorProcessingService` for dispatching items to GAP/external vendors (`AT_VENDOR`), partial/full vendor receipts with good/damaged/missing splits, and vendor reconciliation queries.
- **Phase 8 (Rental / Loan & Distribution — Gate D)** — `RentalService` with serial overlap prevention, dispatch (`RENTED` / `LOANED`), mandatory return to `QUARANTINE`, post-rental QC inspection auto-generating `QualityDiscrepancy` on damage/shortage, and outbound `Shipment` dispatch.
- **Phase 9 (Frontend Operational Modules & Verification — Gate E)** — Complete React pages across all 15 sidebar menus (`MasterDataPage`, `BomPage`, `InventoryPage`, `WarehousePage`, `ProcurementPage`, `ProductionPlanningPage`, `ProductionExecutionPage`, `VendorManagementPage`, `QualityControlPage`, `RentalPage`, `DistributionPage`, `MaterialCostPage`, `ReportsPage`) and end-to-end feature tests (`InventoryEngineAndBomTest`, `EndToEndBusinessFlowsTest`).

## [0.1.0] — 2026-11-09 (Phase 0 — Foundation)

### Added
- **Backend (`backend/`)** — Laravel 12 Modular Monolith skeleton (`/api/v1`), Laravel Sanctum token authentication, `EnsureUserIsActive` and `RequireIdempotencyKey` middleware, consistent JSON error envelope (`ApiExceptionRenderer` / `DomainException`).
- **RBAC** — `roles`, `permissions`, `role_user`, `permission_role` migrations, `RbacSeeder` with the 12 roles and granular permission catalog from `09_RBAC_MATRIX.md`, `UserPolicy`, `RolePolicy`, `AuditLogPolicy`, and `DemoUserSeeder` (non-production only).
- **Shared foundation services** — `AuditLogger` (with automatic secret redaction), row-locked `DocumentNumberGenerator` supporting `{prefix}`, `{date}`, `{seq}`, and context tokens (`{product_code}`), `IdempotencyService`, `OutboxEvent`, private `Attachment` storage, and `InventoryPostingContract` interface + DTOs.
- **API v1 endpoints** — `/auth/login`, `/auth/logout`, `/auth/me`, `/dashboard/summary`, `/users`, `/roles`, `/permissions`, `/audit-logs`.
- **Frontend (`frontend/`)** — React 18 + TypeScript + Vite SPA implementing the design tokens and component system from `14_DESIGN_BRIEF.txt`.
- **CI** — `.github/workflows/ci.yml` running PHPUnit, TypeScript check, Vitest, and production build.
