# Erlass Production & Warehouse Management System (PWMS)
**Dokumentasi baseline untuk AI coding agent — v1.0**

## Tujuan paket
Paket ini menjadi sumber kebenaran fungsional dan teknis untuk membangun PWMS Erlass. Agent harus membaca dokumen sesuai urutan sebelum menulis kode.

## Urutan baca wajib
1. `00_PROJECT_BRIEF.md`
2. `01_PRD.md`
3. `02_SCOPE_AND_ASSUMPTIONS.md`
4. `03_BUSINESS_PROCESS.md`
5. `04_SYSTEM_ARCHITECTURE.md`
6. `05_DATABASE_SCHEMA.md`
7. `06_BUSINESS_RULES.md`
8. `07_MODULE_SPECIFICATIONS.md`
9. `08_API_SPECIFICATION.md`
10. `09_RBAC_MATRIX.md`
11. `10_UI_UX_GUIDELINES.md`
12. `11_TESTING_STRATEGY.md`
13. `12_IMPLEMENTATION_ROADMAP.md`
14. `AGENT_INSTRUCTIONS.md`

## Keputusan bisnis yang sudah dikunci
- Nama: **Erlass Production & Warehouse Management System (PWMS)**.
- Ruang lingkup: Production + Warehouse + Inventory + Distribution terintegrasi.
- Teknologi: Laravel 12, React.js, MySQL/InnoDB, REST API `/api/v1`, Laravel Sanctum.
- Arsitektur: Modular Monolith.
- Tracking stok hybrid: quantity, serialized, dan batch sesuai karakter barang.
- BOM hybrid: template standar MLK dan BOM fleksibel untuk produk fisik lainnya.
- GAP vendor dikelola internal Erlass; tidak ada portal vendor eksternal pada fase awal.
- Costing awal hanya biaya material/komponen dan harga pembelian. Tidak menghitung tenaga kerja, mesin, listrik, overhead, atau depresiasi.
- Rental harus melacak unit/box dan komponen secara terperinci.
- Perubahan stok wajib melalui inventory ledger/service; tidak boleh mengubah saldo secara langsung dari controller.

## Catatan penggunaan workbook sumber
Workbook `RUNDOWN PEMBUATAN WEB ERLASS INVENTORY.xlsx` merupakan sumber kebutuhan operasional. Isinya mencakup alur rencana produksi, proses MLK (Komponen → WIP Modul → WIP Box → Finished Good), master komponen/modul/box/produk, lokasi gudang bertingkat, barcode, peminjaman, QC, customer, role, dan katalog produk 2027. Workbook juga berisi data historis/legacy yang perlu dibersihkan sebelum diimpor. Jangan menganggap setiap baris spreadsheet sebagai data final yang siap dimasukkan tanpa validasi.

## Aturan prioritas
Jika terdapat konflik:
1. Keputusan bisnis yang secara eksplisit dikonfirmasi pemilik proyek di dokumen ini.
2. Business rules dan PRD.
3. Spesifikasi modul/API/database.
4. Workbook lama sebagai referensi operasional.
Agent wajib mencatat konflik dan meminta klarifikasi hanya jika konflik mengubah proses atau struktur data secara material. Jangan mengarang kebijakan.

## Definition of Ready untuk coding
- Semua keputusan P0 pada `02_SCOPE_AND_ASSUMPTIONS.md` dipahami.
- Skema database dirancang sebelum implementasi fitur lintas modul.
- Status dan transisi dokumen ditetapkan sebagai enum/constant terpusat.
- Test untuk inventory ledger, reservation, BOM snapshot, partial receipt, QC, rental return, dan cost calculation dibuat sejak awal.
