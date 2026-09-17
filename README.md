# 📦 ErlassGudangApp — Warehouse Management System (v3.5)

ErlassGudangApp adalah sistem manajemen gudang dan inventaris cerdas berbasis **Progressive Web App (PWA)** dengan backend **Laravel 12 API** dan frontend **Single-Page Application (SPA)** responsif berkinerja tinggi. 

Dirancang khusus untuk **Erlass Institute**, aplikasi ini mengintegrasikan seluruh siklus hidup barang mulai dari barang mentah (*raw material*), perakitan modul (*Work in Process / MIKMS*), pesanan paket kit bertingkat (*Cascading BOM*), sirkulasi peminjaman/sewa ke sekolah (*Serialized Asset Tracking*), hingga pencatatan kartu stok dan pelaporan audit.

> 🌐 **Aplikasi Live**: [https://gudang.erlass.institute](https://gudang.erlass.institute)

---

## 🧭 Alur Kerja Terpadu (Unified Pipeline)

Aplikasi menerapkan arsitektur navigasi **Unified Pipeline** yang menyelaraskan alur kerja tim lapangan dan administrasi gudang dalam satu garis aktivitas logis:

```
[Barang Masuk] ──▶ [Produksi & Modul] ──▶ [Pesanan & Keluar] ──▶ [Sirkulasi / Sewa] ──▶ [Stok & Laporan]
  Scan & Input      Perakitan BOM (M01-M10)   Paket Kit (Cascading)   Boks Kit di Sekolah       Katalog Stok Terpadu
  Supplier / Vendor QC Modul & Boks           Surat Jalan Kirim       Retur & Repair            Kartu Stok & Opname
```

---

## 🚀 Fitur Utama

| Modul / Fitur | Deskripsi Teknis |
|:---|:---|
| **📊 Dashboard Terpadu** | Ringkasan statistik real-time gudang reguler, widget live MIKMS (Total Boks, Modul Jadi Siap, Pesanan Bulan Ini, Unit di Lapangan), grafik mutasi 30 hari (Chart.js), dan shortcut aksi cepat mobile. |
| **📷 Scan QR & Barcode** | Pemindaian kode barang otomatis via kamera web/mobile menggunakan engine `jsQR` berpresisi tinggi dengan deteksi auto-fill instan ke formulir transaksi. |
| **⚙️ Perakitan Modul MIKMS (BOM)** | Pengelolaan perakitan modul elektronik (M01 Controller, M02 LED, M03 Motion, M07 Connection, dsb) berbasis standar Bill of Materials (BOM) dengan pengurangan otomatis stok komponen bahan mentah. |
| **📦 Cascading Multi-Level BOM** | Otomasi pesanan paket kit utuh (misal: 5 Microbit Learning Kit / MLK): sistem memprioritaskan pengurangan stok modul jadi yang siap pakai (`mikms_module_stocks`), dan secara otomatis mem-breakdown modul yang belum dirakit ke komponen dasar (*raw materials*). |
| **✅ Quality Control (QC)** | Pencatatan verifikasi kelayakan modul pasca-produksi sebelum dimasukkan ke dalam boks kit siap distribusi sekolah. |
| **🚚 Distribusi & Sirkulasi Sekolah** | Surat jalan pengiriman boks kit ke sekolah/mitra (`mikms_shipments`), pencatatan pengembalian/retur (`mikms_returns`), serta pencatatan boks kit bermasalah ke antrean reparasi (`mikms_repairs`). |
| **🔄 Modul Sewa & Unit Bekas** | Pelacakan unit asset terserialisasi (*Serialized Asset Tracking*) dengan riwayat mutasi antar-lokasi, peminjaman/sewa keluar (*rent-out*), pengembalian (*rent-return*), dan kartu riwayat unit individu. |
| **🏢 Multi-Warehouse Management** | Manajemen multi-gudang (Gudang Utama, Gudang Bahan Baku / Raw Material, Gudang Modul Jadi / WIP, Gudang Sewa & Lapangan). |
| **📄 Kartu Stok Terpadu & Audit** | Rekap kartu stok running-balance per item per gudang per tahun, pencatatan stock opname fisik, serta ekspor laporan PDF (`barryvdh/laravel-dompdf`) dan Excel (`maatwebsite/laravel-excel`). |
| **🖨️ Cetak QR Stiker Presisi** | Generator label QR stiker (satuan atau antrean cetak massal) siap cetak langsung dari browser dengan format tata letak kiri-atas ramah printer thermal/kertas stiker. |
| **📶 Offline Sync & PWA** | Menggunakan Web Service Worker (`sw.js`) dan antrean transaksi lokal (`localStorage`) untuk memungkinkan pencatatan mutasi di area minim sinyal dengan sinkronisasi otomatis saat online kembali. |

---

## 🔑 Hak Akses & Akun Bawaan

Sistem mendukung autentikasi menggunakan **NIK Karyawan & Kata Sandi** serta opsi cepat **PIN 6 digit**.

| Peran (Role) | NIK | Kata Sandi | PIN | Cakupan Hak Akses |
|:---|:---|:---|:---|:---|
| **Petugas Lapangan** | `12345` | `password` | `000000` | Dashboard, Scan Masuk, Perakitan Modul, QC, Pesan Paket, Pengiriman Sekolah, Retur, Repair, Opname, Stok, Transaksi, Cetak QR |
| **Administrator** | `admin` | `password` | `123456` | Seluruh fitur operasional lapangan + **Manajemen Master** (Barang, Lokasi, Vendor, Customer, Kategori, User Management) |

> [!NOTE]
> Akun `webmaster@erlass.institute` diperuntukkan khusus bagi aplikasi portal utama (`webapperlass`) dan tidak digunakan di ErlassGudangApp.

---

## 🏗️ Arsitektur Sistem

```
┌──────────────────────────────────────────────────────────────────────────┐
│                      CLIENT LAYER (Browser PWA / SPA)                    │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────┐  ┌───────────┐  │
│  │   Dashboard   │  │   QR Scanner  │  │  Unified Nav  │  │  Offline  │  │
│  │(Charts & Stats│  │    (jsQR)     │  │ (goPage pipe) │  │Queue Local│  │
│  └───────┬───────┘  └───────┬───────┘  └───────┬───────┘  └─────┬─────┘  │
│          └──────────────────┼───────────────────┘               │        │
│                             ▼                                   ▼        │
│                    Service Worker (sw.js) ◀────────── Sync Handler       │
└─────────────────────────────┼────────────────────────────────────────────┘
                              │ HTTPS / REST JSON
┌─────────────────────────────▼────────────────────────────────────────────┐
│                       SERVER LAYER (NGINX + SSL)                         │
│                    gudang.erlass.institute                               │
└─────────────────────────────┼────────────────────────────────────────────┘
                              │
┌─────────────────────────────▼────────────────────────────────────────────┐
│                    LARAVEL 12 CORE ENGINE                                │
│  ┌────────────────────────────────────────────────────────────────────┐  │
│  │ QueryTokenMiddleware (Global Header / ?token= injector)            │  │
│  │ Laravel Sanctum (Bearer Token Authorization)                       │  │
│  └──────────────────────────────────┬─────────────────────────────────┘  │
│                                     ▼                                    │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ API CONTROLLERS:                                                │  │
│  │ • AuthController        • ItemController      • StockController │  │
│  │ • TransactionController • AssetController     • ExportController│  │
│  │ • MikmsController       • LocationController  • UserController  │  │
│  │ • VendorController      • CustomerController  • CategoryContr.  │  │
│  └──────────────────────────────────┬─────────────────────────────────┘  │
│                                     ▼                                    │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │ BUSINESS SERVICES & ENGINES:                                    │  │
│  │ • Cascading BOM Calculator & Auto-Deduction Engine              │  │
│  │ • Running Balance Kartu Stok Generator                          │  │
│  │ • Module Stock Transition & Serialized Asset Lifecycle State    │  │
│  └──────────────────────────────────┬─────────────────────────────────┘  │
└─────────────────────────────────────┼────────────────────────────────────┘
                                      │ PDO MySQL
┌─────────────────────────────────────▼────────────────────────────────────┐
│                    DATABASE LAYER (MySQL 8 - gudangscan_db)              │
│  ┌─────────────────┐  ┌──────────────────┐  ┌─────────────────────────┐  │
│  │ items           │  │ transactions     │  │ assets (serialized)     │  │
│  │ users           │  │ categories       │  │ locations / vendors     │  │
│  ├─────────────────┴──┴──────────────────┴──┴─────────────────────────┤  │
│  │ MIKMS SUITE:                                                       │  │
│  │ • mikms_modules      • mikms_bom             • mikms_boxes         │  │
│  │ • mikms_productions  • mikms_qc_logs         • mikms_shipments     │  │
│  │ • mikms_returns      • mikms_repairs         • mikms_stock_opnames │  │
│  │ • mikms_module_stocks• mikms_module_stock_logs                     │  │
│  │ • mikms_package_orders (Cascading multi-level deduction log)       │  │
│  └────────────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────────────┘
```

---

## 🗂️ Struktur Direktori Proyek

```
/var/www/gudangscan/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php          ← Login NIK/PIN, Logout
│   │   │   ├── ItemController.php          ← CRUD Master Barang (Admin)
│   │   │   ├── TransactionController.php   ← Transaksi masuk/keluar & sinkronisasi
│   │   │   ├── StockController.php         ← Status stok & riwayat kartu stok
│   │   │   ├── AssetController.php         ← Unit asset sewa, mutasi, rent out/in
│   │   │   ├── MikmsController.php         ← MIKMS, BOM, QC, Cascading BOM, Opname
│   │   │   ├── MikmsExportController.php   ← Ekspor Excel khusus MIKMS
│   │   │   ├── LocationController.php      ← Master Lokasi penyimpanan
│   │   │   ├── VendorController.php        ← Master Vendor / Supplier
│   │   │   ├── CustomerController.php      ← Master Customer / Sekolah
│   │   │   ├── CategoryController.php      ← Master Kategori Barang
│   │   │   ├── UserController.php          ← CRUD Pengguna & Import Akun
│   │   │   └── ExportController.php        ← PDF kartu stok & Ekspor Excel umum
│   │   └── Middleware/
│   │       └── QueryTokenMiddleware.php    ← Bypass token auth untuk download file
│   └── Models/
│       ├── Item.php                        ← Relasi item, computed stock, kartu stok
│       ├── Transaction.php                 ← Log transaksi barang umum
│       ├── Asset.php                       ← Asset unit terserialisasi
│       ├── MikmsModule.php                 ← Master modul perakitan (M01-M10)
│       ├── MikmsBom.php                    ← Bill of Materials modul
│       ├── MikmsBox.php                    ← Master boks kit Micro:bit
│       ├── MikmsProduction.php             ← Riwayat perakitan modul
│       ├── MikmsQcLog.php                  ← Riwayat kontrol kualitas (QC)
│       ├── MikmsShipment.php                ← Riwayat pengiriman boks ke sekolah
│       ├── MikmsReturn.php                  ← Riwayat retur boks & komponen rusak
│       ├── MikmsRepair.php                  ← Riwayat perbaikan barang/komponen
│       ├── MikmsStockOpname.php            ← Pencatatan fisik stock opname MIKMS
│       ├── MikmsModuleStock.php            ← Saldo stok modul jadi siap pakai
│       ├── MikmsModuleStockLog.php         ← Mutasi penambahan/pengurangan modul jadi
│       ├── MikmsPackageOrder.php           ← Pesanan paket kit (Cascading BOM)
│       └── User.php                        ← Model pengguna dengan NIK & Role
│
├── database/
│   └── migrations/                         ← Migrasi database lengkap
│
├── resources/views/
│   ├── app.blade.php                       ← Frontend SPA tunggal (Unified Pipeline UI)
│   └── pdf/
│       └── kartu-stok.blade.php            ← Template cetak PDF kartu stok
│
├── routes/
│   ├── api.php                             ← Definisi REST API lengkap
│   └── web.php                             ← Route view utama SPA
│
├── public/
│   ├── manifest.json                       ← Konfigurasi instalasi PWA
│   ├── sw.js                               ← Service Worker offline caching
│   └── icons/                              ← Ikon aplikasi PWA
│
└── docs/
    ├── LOGIC.md                            ← Dokumentasi logika bisnis & kalkulasi BOM
    ├── API.md                              ← Spesifikasi lengkap REST API & payload
    └── DATABASE.md                         ← ERD, tabel relasi, dan kamus data
```

---

## ⚡ Panduan Operasional & Maintenance Server

```bash
# Berpindah ke direktori proyek
cd /var/www/gudangscan

# 1. Bersihkan & re-cache konfigurasi, route, dan view
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Jalankan migrasi database
php artisan migrate --force

# 3. Cek status service web server & PHP-FPM
systemctl restart nginx
systemctl status php8.3-fpm
```

---

## 🛠️ Stack Teknologi

| Komponen | Teknologi yang Digunakan |
|:---|:---|
| **Backend Framework** | Laravel 12 (PHP 8.3) |
| **Autentikasi** | Laravel Sanctum (Personal Access Token) |
| **Database** | MySQL 8.0 (InnoDB Engine, UTF8MB4) |
| **Frontend Framework** | Single File SPA (Blade + Vanilla JavaScript ES6+) |
| **Styling & CSS** | Tailwind CSS + DaisyUI |
| **Pemindaian QR** | jsQR |
| **Generator QR Stiker** | qrcode.js |
| **Grafik Dashboard** | Chart.js 4.x |
| **Ekspor Dokumen** | `barryvdh/laravel-dompdf` & `maatwebsite/laravel-excel` |
| **Web Server & SSL** | NGINX + Let's Encrypt SSL (HTTP/2) |
| **PWA Engine** | Service Worker Cache-First / Network-Fallback |

---

## 📖 Dokumentasi Teknis Lanjutan

Untuk membaca dokumentasi arsitektur dan spesifikasi API secara mendalam:

1. [**LOGIC.md**](docs/LOGIC.md) — Alur logika bisnis, aturan deduksi stok Cascading BOM, state machine boks kit, dan lifecycle perakitan.
2. [**API.md**](docs/API.md) — Katalog lengkap REST API endpoints, parameter query, struktur request JSON, dan format response.
3. [**DATABASE.md**](docs/DATABASE.md) — Entity Relationship Diagram (ERD), kamus data setiap tabel, dan constraint integritas data.
