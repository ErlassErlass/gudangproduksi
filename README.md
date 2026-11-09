# ERLASS PWMS — Production & Warehouse Management System
**PT ERLASS Prokreatif Indonesia**

Monorepo resmi untuk **ERLASS PWMS (Production & Warehouse Management System)** — Sistem Terpadu Manajemen Produksi, Inventaris Gudang, Sirkulasi Sewa Mobile Learning Kit (MLK) Sekolah, Peminjaman Sales, dan Distribusi Terintegrasi.

---

## Daftar Isi
1. [Tentang Sistem](#tentang-sistem)
2. [Arsitektur & Teknologi](#arsitektur--teknologi)
3. [Daftar Peran & Hak Akses (Role & RBAC)](#daftar-peran--hak-akses-role--rbac)
4. [Modul & Fitur Unggulan](#modul--fitur-unggulan)
5. [Panduan Instalasi & Menjalankan](#panduan-instalasi--menjalankan)
6. [Pengujian Otomatis (Automated Testing)](#pengujian-otomatis-automated-testing)
7. [Struktur Direktori Monorepo](#struktur-direktori-monorepo)
8. [SOP & Alur Bisnis Operasional](#sop--alur-bisnis-operasional)

---

## Tentang Sistem
ERLASS PWMS dirancang khusus untuk memenuhi kebutuhan manufaktur perakitan dan distribusi edukatif di lingkungan **ERLASS Prokreatif Indonesia**. Sistem ini mengelola seluruh siklus hidup produk, mulai dari pengadaan bahan baku, perakitan bertingkat (BOM), eksekusi rencana produksi dinamis, pelacakan nomor unit serial aset netral yang dapat disewakan berulang kali lintas sekolah mitra, perhitungan otomatis klaim kerugian komponen hilang saat pengembalian sewa (QC), hingga pencetakan dokumen resmi (DO, Tanda Terima, Surat Jalan, dan Kartu Stok).

Antarmuka sistem telah dioptimalkan secara responsif untuk kenyamanan penggunaan pada perangkat *mobile* (smartphone/HP) oleh staf lapangan maupun layar monitor komputer kantor.

---

## Arsitektur & Teknologi

### Backend
- **Framework**: Laravel 11 / 12 (PHP 8.2+) dengan arsitektur *Domain-Driven Design (Modular Monolith)*.
- **Autentikasi & Keamanan**: Laravel Sanctum (Token-based API authentication), Role-Based Access Control (RBAC), Separation of Duties, dan immutable Audit Logging.
- **Basis Data**: MySQL 8+ (InnoDB Engine) dengan dukungan transaksi ACID penuh.

### Frontend
- **Framework**: React 18 SPA dengan TypeScript dan Vite.
- **Manajemen State & Cache Server**: TanStack React Query v5.
- **Desain & Gaya Tampilan**: Design System kustom ERLASS (CSS Variables, Flexbox/Grid, Lucide Icons, konsistensi PageHeader, Card, StatCard, DataTable, dan StatusBadge).
- **Scanner Kamera Mobile**: Web MediaDevices API (`getUserMedia`) terintegrasi dengan browser native `BarcodeDetector` API untuk pemindaian barcode fisik Finished Good dan nomor unit sewa MLK langsung dari kamera smartphone.

---

## Daftar Peran & Hak Akses (Role & RBAC)

Sistem mengadopsi 4 peran utama sesuai kebutuhan operasional ERLASS Prokreatif Indonesia:

| Role Code | Nama Peran | Deskripsi Wewenang & Tanggung Jawab |
|---|---|---|
| `super_admin` | **Super Admin** | Akses penuh atas seluruh fitur sistem: Manajemen Pengguna & Hak Akses, Audit Log, Master Data & Customer, Template Surat, Pengaturan Dokumen, dan Supervisi Seluruh Modul. |
| `warehouse_pic` | **PIC Gudang** | Bertanggung jawab atas penerimaan pengadaan (PO), lokasi penyimpanan, kartu stok keluar-masuk, pengeluaran bahan produksi, pencetakan barcode aset, penerbitan Surat DO, dan penerimaan kembali sewa. |
| `production_lead` | **Kepala Produksi** | Mengelola Resep BOM bertahap (Modul -> Box -> FG), menyusun Rencana Produksi, kalkulasi MRP Kebutuhan Kotor, dan mengelola tahapan pengerjaan dinamis (0 - 100%). |
| `staff` | **Staff Operasional** | Menangani operasional lapangan: Peminjaman Projek Sales, pengembalian & cetak tanda terima PDF, pemindaian barcode unit via kamera HP, dan verifikasi kelengkapan unit di lapangan. |

### Kredensial Akun Pengujian (Seeder Default):
Password bawaan seluruh akun demo: `password`

- **Super Admin**: `admin@erlass.test`
- **Kepala Produksi**: `production@erlass.test`
- **PIC Gudang**: `warehouse@erlass.test`
- **Staff Operasional**: `staff@erlass.test` / `rental@erlass.test`

---

## Modul & Fitur Unggulan

### 1. Katalog & Produk (Pemisahan Kategori Rapi)
- Pembagian halaman terpisah: **Komponen**, **Modul**, **Box**, dan **Finished Good & Jasa**.
- Informasi kategori/tipe ditampilkan sebagai kolom tersendiri (bukan di samping nama).
- Pembedaan produk fisik vs non-fisik (jasa/program) dengan validasi: produk non-fisik tidak dapat dicetak barcode dan tidak mencatat stok fisik.

### 2. Bill of Materials (BOM) Bertahap
- Resep bertingkat sesuai alur perakitan nyata: **Produksi Modul -> Produksi Box -> Produksi Finished Good**.
- Resep BOM dapat dikelola secara mandiri (tambah, edit, hapus komponen/sub-assembly).
- Tabel BOM menyajikan Kode & Nama Resep, Bahan Komposisi, dan tombol aksi terstruktur.

### 3. Rencana Produksi & Tahapan Dinamis
- **Kalkulasi MRP Kebutuhan Kotor**: Menghitung secara instan seluruh bahan baku yang dibutuhkan untuk target kuantitas rencana produksi tanpa kompleksitas stok berlebih.
- **Tahapan Pengerjaan Dinamis**: Pengerjaan dapat dibagi ke dalam beberapa tahapan persentase fleksibel hingga mencapai 100%.
- **Proteksi Tahapan Selesai**: Tahapan yang telah diselesaikan terkunci secara otomatis dan tidak dapat dihapus.

### 4. Persewaan Finished Good MLK untuk Pihak Sekolah
- **Barcode Unit Fisik Netral Aset**: Format kode unit `1001_01`, `1001_02`, dst., tanpa mencetak nama sekolah permanen di label fisik. Unit dapat dipakai berulang kali lintas sekolah mitra.
- **Penomoran Berkelanjutan Otomatis**: Jika Sekolah A menyewa 50 unit (1001_01 - 1001_50), pesanan sekolah berikutnya otomatis melanjutkan nomor serial berikutnya (1001_51 - 1001_60), dengan opsi memilih nomor unit awal untuk menggunakan kembali unit yang sudah kembali ke gudang.
- **Penerbitan Surat DO Otomatis**: Klik *"Cetak Surat DO"* langsung menerbitkan surat jalan dan menyinkronkan data ke modul Distribusi & Pengiriman.

### 5. Quality Control (QC) & Perhitungan Ganti Rugi Sekolah
- Form checklist kelengkapan pengembalian sewa (kuantitas realisasi vs kuantitas standar).
- **Perhitungan Otomatis Kerugian**: Jika terdapat komponen yang hilang, sistem secara otomatis menghitung selisih dan nilai kerugian finansial yang dibebankan kepada sekolah mitra.
- Pengaturan ganti rugi fleksibel: komponen dapat diatur apakah wajib diganti rugi atau dibebaskan saat hilang.

### 6. Peminjaman Projek Sales
- Relasi bertingkat: **GL Marketing -> Sales -> Sekolah (Customer) -> Produk & Kuantitas**.
- Pencatatan tanggal kembali saat barang diserahkan kembali oleh sales.
- **Cetak Bukti Tanda Terima**: Menghasilkan dokumen cetak tanda terima pengembalian resmi berformat PDF.

### 7. Kartu Stok & Riwayat Keluar Masuk Berdasarkan Rentang Tanggal
- Laporan mutasi barang keluar masuk dapat difilter berdasarkan tanggal awal dan tanggal akhir tertentu untuk kemudahan audit dan rekonsiliasi berkala.

### 8. Pemindai Barcode Terintegrasi Kamera HP & Scanner Fisik
- Menggunakan kamera smartphone (kamera belakang dengan mode fokus otomatis) atau alat scanner USB/Bluetooth.
- Mampu mendeteksi barcode Finished Good maupun nomor unit MLK secara terpadu.
- Menampilkan status unit saat ini, nama sekolah pemegang, riwayat pemakaian sebelumnya, riwayat kelengkapan QC, serta menyediakan form langsung untuk mengubah status unit dan sekolah pemakai.

### 9. Dropdown Profil & Pembaruan Kredensial Mandiri
- Profil di sudut kanan atas berbentuk tombol dropdown (`DS` - Demo Super Admin - Super Admin).
- Fitur **Ubah Email & Password** langsung dari dalam sistem dengan verifikasi kata sandi lama untuk perlindungan akun.
- Tombol tautan langsung ke **Manual Pengguna** sesuai peran aktif.

### 10. Halaman Manual Pengguna Dinamis Sesuai Role
- Halaman panduan operasional (`/user-manual`) yang secara otomatis mendeteksi peran pengguna saat login.
- Menyediakan Standar Operasional Prosedur (SOP) langkah demi langkah, fitur kunci, dan FAQ untuk masing-masing role: Super Admin, PIC Gudang, Kepala Produksi, dan Staff Operasional.

---

## Panduan Instalasi & Menjalankan

### Kebutuhan Sistem
- PHP >= 8.2 dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `xml`.
- Composer >= 2.x
- Node.js >= 18.x dan npm
- MySQL Server >= 8.0

### 1. Instalasi Backend (Laravel)

```bash
# Masuk ke direktori backend
cd backend

# Salin file konfigurasi environment
cp .env.example .env

# Pasang dependensi PHP
composer install

# Generate application key
php artisan key:generate

# Konfigurasikan koneksi basis data di file .env:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=erlass_pwms
# DB_USERNAME=root
# DB_PASSWORD=

# Jalankan migrasi dan seeder awal
php artisan migrate --seed

# Jalankan server API backend
php artisan serve
# Server aktif pada http://127.0.0.1:8000
```

### 2. Instalasi Frontend (React + Vite)

```bash
# Buka terminal baru dan masuk ke direktori frontend
cd frontend

# Pasang dependensi Node.js
npm install

# Salin file konfigurasi environment
cp .env.example .env

# Jalankan server pengembangan Vite
npm run dev
# Aplikasi terbuka pada http://localhost:5173
```

---

## Pengujian Otomatis (Automated Testing)

Seluruh logika bisnis inti, integritas database, RBAC, dan bundling antarmuka diuji secara komprehensif melalui *automated tests*:

### Menjalankan Backend Tests:
```bash
cd backend
php artisan test
```
*Hasil: 31 feature test cases dengan 288 assertions berhasil lulus 100% (PASS).*

### Menjalankan Frontend Build & Typecheck:
```bash
cd frontend
npm run build
```
*Hasil: Kompilasi TypeScript dan Vite bundling sukses tanpa galat (zero errors).*

---

## Struktur Direktori Monorepo

```text
erlass_pwms/
├── agent_docs/                 # Dokumentasi spesifikasi bisnis, arsitektur, dan RBAC
├── backend/                    # Laravel REST API Application
│   ├── app/
│   │   ├── Domains/            # Domain Business Logic (MasterData, Inventory, Production, Rentals)
│   │   ├── Http/Controllers/   # API V1 Controllers (Auth, Operations, MasterData, BOM)
│   │   ├── Models/             # Eloquent Models & Relationships
│   │   └── Shared/             # Shared Utilities, Audit Logger, Idempotency
│   ├── database/
│   │   ├── migrations/         # Skema tabel basis data
│   │   └── seeders/            # Data inisialisasi awal & pengguna demo
│   ├── routes/api.php          # Definisi endpoint REST API V1
│   └── tests/                  # Feature & Unit Test Suites
├── frontend/                   # React TypeScript SPA (Vite)
│   ├── src/
│   │   ├── api/                # HTTP Client Axios & interceptor token
│   │   ├── components/         # Komponen UI inti (Button, Modal, StatCard, FormField, States)
│   │   ├── features/
│   │   │   ├── auth/           # Login, AuthContext, ProfileModal, API Autentikasi
│   │   │   ├── catalog/        # Katalog Komponen, Modul, Box, FG, BarcodeScanModal
│   │   │   ├── bom/            # Manajemen BOM Multi-Level Bertahap
│   │   │   ├── operations/     # Rencana Produksi, Pengadaan PO, Quality Control, Distribusi
│   │   │   ├── rentals/        # Sewa MLK Sekolah, Serial Barcode, Peminjaman Sales
│   │   │   ├── inventory/      # Inventaris Gudang & Cetak Riwayat Keluar Masuk
│   │   │   ├── manual/         # Manual Pengguna Dinamis Sesuai Role
│   │   │   └── administration/ # Manajemen User, Role, Customer, Template Dokumen, Audit Log
│   │   ├── layouts/            # Header, Sidebar, ProfileDropdown, AppLayout
│   │   └── routes/             # Routing navigasi & permission guards
├── CHANGELOG.md
└── README.md
```

---

## SOP & Alur Bisnis Operasional

### 1. Alur Perakitan & Produksi MLK
1. **Penyusunan BOM**: Modul dirakit dari komponen -> Box dirakit dari modul & komponen pelengkap -> Finished Good MLK dirakit dari Box & modul utama.
2. **Rencana Produksi**: Kepala Produksi membuat rencana produksi dengan target unit dan tanggal jatuh tempo.
3. **Kalkulasi MRP**: Sistem menghitung kebutuhan kotor material untuk memastikan ketersediaan bahan di gudang.
4. **Eksekusi Tahapan**: Progress dicatat secara bertahap (0% s/d 100%). Rencana yang rampung (status `COMPLETED`) siap digunakan untuk pemenuhan order sewa sekolah.

### 2. Alur Persewaan MLK Sekolah & Penomoran Unit
1. **Pembuatan Kontrak Sewa**: PIC Gudang memilih rencana produksi yang telah selesai dan memilih sekolah mitra peminjam.
2. **Cetak Barcode Netral**: Sistem membuat stiker barcode serial berurutan (misal: `1001_01` s/d `1001_50`). Stiker ini dapat terus dipakai berulang kali oleh sekolah berbeda di masa mendatang.
3. **Penerbitan Surat DO**: Klik *"Cetak Surat DO"* untuk membuat surat jalan resmi dan mengirim data ke modul Distribusi.
4. **Pengembalian Sewa & QC**: Saat sekolah mengembalikan unit, tim QC melakukan inspeksi kelengkapan. Apabila ada komponen yang kurang, sistem menghitung otomatis total denda ganti rugi sekolah.
5. **Pembaruan Status Unit**: Nomor unit yang telah kembali otomatis berstatus `TERSEDIA DI GUDANG` dan siap disewakan kembali ke sekolah mitra berikutnya.

---

## Lisensi & Hak Cipta
Hak Cipta &copy; 2026 **PT ERLASS Prokreatif Indonesia**. Seluruh hak cipta dilindungi undang-undang.
