# 📦 ErlassGudangApp — Warehouse Management System

ErlassGudangApp adalah aplikasi **Progressive Web App (PWA)** berbasis **Laravel 12 API** (Backend) dan **Single-Page Application** (Frontend) untuk manajemen inventaris multi-gudang. Dibangun untuk **Erlass Institute** dengan desain **desktop-first dashboard** yang modern dan premium.

> **Live**: [https://gudang.erlass.institute](https://gudang.erlass.institute)

---

## 🚀 Fitur Utama

| # | Fitur | Deskripsi |
|---|-------|-----------|
| 1 | **📊 Dashboard Desktop** | Dashboard dengan statistik real-time, grafik tren 30 hari (Chart.js), dan distribusi per gudang |
| 2 | **📷 Scan QR Code** | Pemindaian kode barang via kamera menggunakan `jsQR`, dengan auto-fill form |
| 3 | **🔄 Offline Sync** | Transaksi tersimpan di `localStorage`, otomatis sinkronisasi saat online |
| 4 | **🏢 Multi-Warehouse** | Mendukung 3 gudang: Gudang Utama, Gudang Raw Material, Gudang Work in Process |
| 5 | **📄 Kartu Stok PDF** | Kartu stok periodik per barang per tahun, dengan saldo awal otomatis |
| 6 | **📥 Excel Export** | Ekspor seluruh log transaksi dan stok ke file `.xlsx` |
| 7 | **🖨️ Cetak QR Stiker** | Print label QR (satuan atau antrean massal) langsung dari browser dengan presisi kertas kiri atas |
| 8 | **🔐 Role-Based Access** | Pemisahan hak akses Admin vs Petugas Lapangan |
| 9 | **⬇️ PWA Install** | Tombol install aplikasi untuk akses native-like di desktop/mobile |

---

## 🔑 Hak Akses & Akun Bawaan

Sistem mendukung login menggunakan **PIN 6 digit** (untuk di lapangan/tablet) maupun **Email & Password**. 

| Jabatan | PIN | Email | Password | Menu & Fitur | CRUD Master |
|---------|-----|-------|----------|--------------|-------------|
| **Petugas Lapangan** | `000000` | `gudang@erlass.institute` | `password` | Dashboard, Scan, Stok, Transaksi, Kartu Stok, Print QR | ❌ HTTP 403 |
| **Admin / Back Office** | `123456` | `admin@erlass.institute` | `password` | Semua menu + **Master Barang** | ✅ Tambah, Edit, Hapus |

> [!IMPORTANT]
> Akun **`webmaster@erlass.institute`** hanya tersedia di aplikasi utama (**webapperlass**) dan **tidak terdaftar** secara default di sistem Gudang (GudangScan). Gunakan salah satu akun di atas untuk login ke GudangScan.

---

## 🏗️ Arsitektur Sistem

```
┌────────────────────────────────────────────────────────────┐
│                    BROWSER (PWA)                           │
│  ┌─────────┐  ┌───────────┐  ┌──────────┐  ┌──────────┐  │
│  │Dashboard│  │  Scanner  │  │  Tables  │  │  Charts  │  │
│  │ (stats) │  │  (jsQR)   │  │  (data)  │  │(Chart.js)│  │
│  └────┬────┘  └─────┬─────┘  └────┬─────┘  └────┬─────┘  │
│       │             │             │              │         │
│  ┌────▼─────────────▼─────────────▼──────────────▼─────┐  │
│  │            localStorage (offline queue)              │  │
│  └──────────────────────┬──────────────────────────────┘  │
│                         │ sync                            │
│  ┌──────────────────────▼──────────────────────────────┐  │
│  │           Service Worker (sw.js)                     │  │
│  │     Cache: network-first + offline fallback          │  │
│  └──────────────────────┬──────────────────────────────┘  │
└─────────────────────────┼──────────────────────────────────┘
                          │ HTTPS
┌─────────────────────────▼──────────────────────────────────┐
│                    NGINX + SSL                             │
│              gudang.erlass.institute                       │
└─────────────────────────┬──────────────────────────────────┘
                          │
┌─────────────────────────▼──────────────────────────────────┐
│                   LARAVEL 12 API                           │
│  ┌──────────────────────────────────────────────────────┐  │
│  │ QueryTokenMiddleware (global)                        │  │
│  │   → ?token=xxx → Authorization: Bearer xxx           │  │
│  └──────────────────────┬───────────────────────────────┘  │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │ Sanctum Auth (auth:sanctum middleware)                │  │
│  └──────────────────────┬───────────────────────────────┘  │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │ Controllers: Auth, Item, Transaction, Stock, Export   │  │
│  └──────────────────────┬───────────────────────────────┘  │
│  ┌──────────────────────▼───────────────────────────────┐  │
│  │ Models: User, Item, Transaction                       │  │
│  │   → Item.stok = computed (sum masuk - sum keluar)     │  │
│  │   → Item.getKartuStok(year, gudang) → running balance │  │
│  └──────────────────────┬───────────────────────────────┘  │
└─────────────────────────┼──────────────────────────────────┘
                          │
┌─────────────────────────▼──────────────────────────────────┐
│               MySQL (gudangscan_db)                        │
│  ┌──────┐  ┌───────┐  ┌──────────────┐  ┌──────────────┐  │
│  │users │  │ items │  │ transactions │  │personal_     │  │
│  │      │  │       │  │              │  │access_tokens │  │
│  └──────┘  └───────┘  └──────────────┘  └──────────────┘  │
└────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Skema Database

### Tabel `users`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | int, PK | Auto increment |
| `name` | varchar | Nama petugas |
| `pin` | varchar | PIN 6 digit (plain text match) |
| `role` | enum(`admin`, `petugas`) | Tingkat hak akses |
| `email` | varchar, nullable | Opsional untuk login alternatif |

### Tabel `items`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | int, PK | Auto increment |
| `kode` | varchar, unique | Kode QR barang (misal: `MSJ01`) |
| `nama` | varchar | Nama lengkap barang |
| `satuan` | varchar | pcs, unit, set, box, lembar |
| `produk` | varchar | Kategori produk |
| `komponen` | varchar | Sub-kategori komponen |
| `lokasi_default` | varchar | Lokasi default penempatan |
| `min_stok` | int | Batas minimum sebelum warning |
| `is_active` | boolean | Status aktif (soft delete) |

### Tabel `transactions`
| Kolom | Tipe | Keterangan |
|-------|------|------------|
| `id` | int, PK | Auto increment |
| `item_id` | int, FK→items | Relasi ke barang |
| `tipe` | enum(`masuk`, `keluar`) | Jenis mutasi |
| `qty` | int unsigned | Jumlah unit |
| `transaction_date` | date | Tanggal pencatatan |
| `lokasi` | varchar | Nama gudang |
| `sumber` | varchar | Asal barang (untuk masuk) |
| `penerima` | varchar | Penerima (untuk keluar) |
| `keperluan` | varchar | Tujuan penggunaan |
| `no_dokumen` | varchar | No. Surat Jalan |
| `no_po` | varchar | No. Purchase Order |
| `no_prn` | varchar | No. Purchase Requisition |
| `job_number` | varchar | No. Job Pekerjaan |
| `transfer_order` | varchar | No. Transfer Order |
| `petugas` | varchar | PIC yang input |
| `catatan` | text | Catatan tambahan |
| `client_id` | varchar, unique | De-duplikasi offline sync |

---

## 📂 Struktur File Penting

```
/root/gudangscan/  (symlink → /var/www/gudangscan)
│
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/
│   │   │   ├── AuthController.php      ← Login PIN/Email, Logout
│   │   │   ├── ItemController.php      ← CRUD Master Barang (admin-only)
│   │   │   ├── TransactionController.php ← Log & Sync transaksi
│   │   │   ├── StockController.php     ← Status stok & kartu stok
│   │   │   └── ExportController.php    ← PDF & Excel export
│   │   └── Middleware/
│   │       └── QueryTokenMiddleware.php ← Token bypass untuk download
│   └── Models/
│       ├── Item.php                    ← Computed stok, kartu stok
│       ├── Transaction.php
│       └── User.php
│
├── bootstrap/
│   └── app.php                         ← Global middleware registration
│
├── resources/views/
│   ├── app.blade.php                   ← Frontend SPA (Desktop Dashboard)
│   └── pdf/
│       └── kartu-stok.blade.php        ← Template PDF kartu stok
│
├── routes/
│   ├── api.php                         ← API endpoints
│   └── web.php                         ← Serve SPA
│
├── public/
│   ├── manifest.json                   ← PWA manifest
│   ├── sw.js                           ← Service Worker
│   └── icons/
│       ├── icon-192.png
│       └── icon-512.png
│
└── docs/
    ├── LOGIC.md                        ← Dokumentasi logika bisnis
    ├── API.md                          ← Referensi API endpoints
    └── DATABASE.md                     ← Skema database & tabel relasi
```

---

## ⚡ Perintah Maintenance

```bash
cd /root/gudangscan

# Bersihkan cache
php artisan route:clear && php artisan config:clear && php artisan cache:clear

# Migrasi database
php artisan migrate --force

# Restart web server
systemctl restart nginx
```

---

## 📦 Stack Teknologi

| Layer | Teknologi |
|-------|-----------|
| **Backend** | Laravel 12, PHP 8.3 |
| **Database** | MySQL 8 |
| **Auth** | Laravel Sanctum (Personal Access Token) |
| **Frontend** | Vanilla JS SPA (Single File Blade) |
| **Charts** | Chart.js 4.x |
| **QR Scan** | jsQR |
| **QR Generate** | qrcode.js |
| **PDF** | barryvdh/laravel-dompdf |
| **Excel** | maatwebsite/laravel-excel |
| **Web Server** | Nginx + Let's Encrypt SSL |
| **PWA** | Service Worker + Web App Manifest |

---

## 📚 Dokumentasi Lengkap

| Dokumen | Deskripsi |
|---------|-----------|
| [LOGIC.md](docs/LOGIC.md) | Penjelasan alur logika bisnis, autentikasi, stok, sinkronisasi offline |
| [API.md](docs/API.md) | Referensi lengkap seluruh API endpoint beserta contoh request/response |
