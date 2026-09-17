# 🧠 ErlassGudangApp — Dokumentasi Logika Bisnis

Dokumen ini menjelaskan alur logika bisnis, arsitektur data, dan keputusan desain pada sistem ErlassGudangApp.

---

## 1. Alur Autentikasi

### 1.1 Login via NIK & Password

```
User memasukkan NIK Karyawan & Password pada form login
        │
        ▼
POST /api/auth/login { nik: "admin", password: "password" }
        │
        ▼
AuthController::login()
  → Cari user di DB WHERE nik = input
  → Verifikasi hash password (Hash::check)
  → Jika cocok: buat Sanctum PersonalAccessToken
  → Return: { token, user: { id, name, email, nik, role } }
        │
        ▼
Frontend menyimpan ke localStorage:
  gs_token  = "1|abc123..."
  gs_user   = { id, name, email, nik, role }
        │
        ▼
Semua API call selanjutnya menggunakan header:
  Authorization: Bearer 1|abc123...
```

> [!NOTE]
> Akun default yang diseed adalah `admin` (admin, NIK: `admin`, sandi: `password`) dan `petugas` (petugas, NIK: `12345`, sandi: `password`). Akun `webmaster` tidak terdaftar pada aplikasi GudangScan karena hanya diperuntukkan bagi aplikasi utama (`webapperlass`).

### 1.3 QueryTokenMiddleware (Download Bypass)

**Masalah**: Saat user klik link download PDF/Excel yang membuka tab baru, browser tidak mengirim header `Authorization`. Sanctum menolak request → error "Route [login] not defined".

**Solusi**: `QueryTokenMiddleware` terdaftar sebagai **global middleware** di `bootstrap/app.php`:

```
Request masuk dengan ?token=1|abc123...
        │
        ▼
QueryTokenMiddleware::handle()
  → Cek apakah ada query param 'token'
  → Jika ada DAN belum ada header Authorization:
      → Set header: Authorization: Bearer 1|abc123...
  → Lanjutkan ke middleware berikutnya (Sanctum)
```

Ini memungkinkan URL seperti:
```
/api/export/pdf/5?tahun=2026&token=1|abc123...
```

### 1.3 Logout

```
POST /api/auth/logout
  → Hapus current Sanctum token dari DB
  → Frontend hapus gs_token & gs_user dari localStorage
  → Redirect ke login screen
```

---

## 2. Logika Perhitungan Stok

### 2.1 Stok Real-Time (Computed, bukan stored)

**Keputusan desain**: Stok **TIDAK** disimpan sebagai kolom di tabel `items`. Stok dihitung secara **real-time** dari tabel `transactions`:

```
Stok Barang X = SUM(qty WHERE tipe='masuk') - SUM(qty WHERE tipe='keluar')
```

**Implementasi** di `Item.php`:

```php
// app/Models/Item.php

public function getStokAttribute(): int
{
    return $this->getStok();
}

public function getStok(string $gudang = null): int
{
    return $this->getTotalMasuk($gudang) - $this->getTotalKeluar($gudang);
}

public function getTotalMasuk(string $gudang = null): int
{
    $query = $this->transactions()->where('tipe', 'masuk');
    if ($gudang && $gudang !== 'Semua') {
        $query->where('lokasi', $gudang);
    }
    return (int) $query->sum('qty');
}
```

**Kenapa computed?**
- ✅ Tidak pernah inkonsisten (no race condition)
- ✅ Stok per gudang otomatis terkalkulasi dari filter `lokasi`
- ✅ Audit trail lengkap — setiap unit bisa ditelusuri
- ⚠️ Performa: cocok untuk volume < 100.000 transaksi. Untuk skala lebih besar, pertimbangkan materialized view.

### 2.2 Filter Multi-Gudang

Setiap transaksi memiliki field `lokasi` yang menyimpan nama gudang:
- `Gudang Utama`
- `Gudang Raw Material`
- `Gudang Work in Process`

Saat filter gudang aktif:
```
Stok di "Gudang Utama" = 
  SUM(qty WHERE tipe='masuk' AND lokasi='Gudang Utama')
  - SUM(qty WHERE tipe='keluar' AND lokasi='Gudang Utama')
```

Saat filter "Semua Gudang":
```
Stok Total = SUM(semua masuk) - SUM(semua keluar)
```

### 2.3 Status Stok

| Status | Kondisi | Warna UI |
|--------|---------|----------|
| **Normal** | stok ≥ `min_stok` (default: 5) | 🟢 Hijau |
| **Menipis** | 0 < stok < `min_stok` | 🟡 Amber |
| **Kosong** | stok ≤ 0 | 🔴 Merah |

---

## 3. Kartu Stok (Running Balance)

### 3.1 Konsep

Kartu Stok adalah laporan periodik per barang per tahun yang menampilkan **saldo berjalan** (running balance):

```
┌──────────┬──────────────────┬──────┬───────┬──────────┐
│ Tanggal  │ Keterangan       │Masuk │Keluar │Sisa Akhir│
├──────────┼──────────────────┼──────┼───────┼──────────┤
│ 01-Jan-26│ SALDO AWAL 2026  │  —   │  —    │   45     │ ← Opening Balance
│ 05-Jan-26│ Supplier A       │ +10  │  —    │   55     │
│ 12-Jan-26│ Divisi Produksi  │  —   │  -3   │   52     │
│ 20-Jan-26│ Transfer gudang  │ +5   │  —    │   57     │
└──────────┴──────────────────┴──────┴───────┴──────────┘
```

### 3.2 Algoritma Opening Balance

```
Opening Balance Tahun Y = 
  SUM(qty WHERE tipe='masuk' AND transaction_date < 'Y-01-01')
  - SUM(qty WHERE tipe='keluar' AND transaction_date < 'Y-01-01')
```

Jika ada filter gudang, opening balance juga difilter per gudang.

### 3.3 Implementasi (`Item::getKartuStok`)

```php
public function getKartuStok(int $year, string $gudang = null): array
{
    // 1. Hitung opening balance (semua transaksi sebelum tahun ini)
    $openingBalance = $openingMasuk - $openingKeluar;

    // 2. Ambil transaksi tahun ini, urut by tanggal
    $transactions = $this->transactions()
        ->whereYear('transaction_date', $year)
        ->orderBy('transaction_date')
        ->get();

    // 3. Baris pertama = SALDO AWAL
    $rows[] = ['sisa_akhir' => $openingBalance, ...];

    // 4. Loop setiap transaksi, hitung running balance
    foreach ($transactions as $tx) {
        $runningBalance += $masuk - $keluar;
        $rows[] = [..., 'sisa_akhir' => $runningBalance];
    }

    return ['item' => $this, 'year' => $year, 'rows' => $rows];
}
```

---

## 4. Alur Transaksi (Masuk/Keluar)

### 4.1 Flow Scan → Simpan

```
1. User buka halaman SCAN
2. Aktifkan kamera → jsQR decode QR code
3. QR terdeteksi → extract kode barang
   Format QR: "KODE|NAMA" (contoh: "CT-001|Micro:bit V2")
4. Auto-fill form: kode, nama, satuan
5. User pilih mode: MASUK atau KELUAR
6. Isi detail: gudang, qty, sumber/penerima, PO/PRN, dll
7. Submit → simpan ke localStorage (offline-first)
8. Sync ke server via POST /api/transactions
```

### 4.2 Validasi Stok Keluar

Saat tipe = `keluar`, server memeriksa ketersediaan stok:

```php
if ($validated['tipe'] === 'keluar') {
    $stok = $item->stok;
    if ($stok < $validated['qty']) {
        return response()->json([
            'message' => "Stok tidak cukup. Tersedia: {$stok}",
        ], 422);
    }
}
```

### 4.3 De-duplikasi (Offline Sync)

Setiap transaksi dari PWA memiliki `client_id` unik (format: `GS-{timestamp}`):

```
1. Frontend generate: client_id = "GS-1719705600000"
2. Simpan di pending queue (localStorage)
3. Saat online, kirim batch:
   POST /api/transactions { transactions: [...] }
4. Server cek setiap tx:
   → Jika client_id sudah ada di DB → skip (sudah disinkronisasi)
   → Jika belum ada → insert
5. Return: { synced_count, failed: [...] }
```

Ini mencegah duplikasi data saat user menekan sync berulang kali.

---

## 5. Sinkronisasi Offline

### 5.1 Arsitektur Offline Storage

```
localStorage keys:
  gs_token    → Sanctum token
  gs_user     → { id, name, role }
  gs_master   → Array cache master barang
  gs_log      → Array cache transaksi
  gs_pending  → Array antrian transaksi belum ter-sync
```

### 5.2 Flow Sinkronisasi

```
                     ┌───────────────────┐
                     │   User Submit TX  │
                     └────────┬──────────┘
                              │
                     ┌────────▼──────────┐
                     │ Simpan ke pending │
                     │ (localStorage)    │
                     └────────┬──────────┘
                              │
                     ┌────────▼──────────┐
              ┌──NO──┤   Online?         ├──YES──┐
              │      └───────────────────┘       │
              │                                  │
     ┌────────▼──────────┐              ┌────────▼──────────┐
     │ Tampilkan badge   │              │ POST /api/         │
     │ "pending: N"      │              │ transactions       │
     │ Tunggu online     │              │ (bulk sync)        │
     └───────────────────┘              └────────┬──────────┘
                                                 │
                                        ┌────────▼──────────┐
                                        │ Sukses?           │
                                        │ → Clear pending   │
                                        │ → Reload stock    │
                                        │ → Update dashboard│
                                        └───────────────────┘
```

### 5.3 Event Listeners

```javascript
window.addEventListener('online', () => {
    setSyncDot('on');   // Indikator hijau
    syncPending();      // Auto-sync antrian
});

window.addEventListener('offline', () => {
    setSyncDot('off');  // Indikator merah
});
```

---

## 6. Role-Based Access Control (RBAC)

### 6.1 Backend Enforcement

CRUD Master Barang (`ItemController`) dilindungi di level controller:

```php
// store(), update(), destroy()
if ($request->user()->role !== 'admin') {
    return response()->json([
        'message' => 'Akses ditolak. Hanya Admin.',
    ], 403);
}
```

**Catatan**: `index()` dan `show()` bisa diakses semua role.

### 6.2 Frontend Enforcement

Menu "Master Barang" dan section admin di sidebar disembunyikan:

```javascript
if (user.role === 'admin') {
    document.querySelectorAll('.admin-section').forEach(e => e.style.display = '');
}
```

Element dengan class `.admin-section` memiliki `style="display:none"` secara default.

---

## 7. Dashboard & Visualisasi

### 7.1 Statistik Cards

Dashboard menampilkan 5 kartu statistik yang dihitung dari data di memori:

| Kartu | Sumber Data |
|-------|-------------|
| Total Item | `master.length` |
| Total Stok Unit | `sum(master[*].stok)` |
| Stok Menipis | `count(master WHERE 0 < stok < 5)` |
| Stok Kosong | `count(master WHERE stok <= 0)` |
| Transaksi Hari Ini | `count(txLog WHERE date = today)` |

### 7.2 Chart: Tren Masuk vs Keluar (30 Hari)

- **Tipe**: Line chart (Chart.js)
- **Data**: Aggregate qty per hari dari `txLog`
- **Dataset**: 2 line — Masuk (hijau) dan Keluar (merah)
- **Rendering**: Client-side dari data yang sudah di-load

### 7.3 Chart: Distribusi per Gudang

- **Tipe**: Bar chart (Chart.js)
- **Data**: Total masuk & keluar per gudang
- **Label**: Utama, Raw Material, WIP

---

## 8. PWA (Progressive Web App)

### 8.1 Manifest

File `/public/manifest.json` mendefinisikan:
- `display: standalone` — tampil seperti app native
- `theme_color: #3b82f6` — warna status bar
- Icons 192px dan 512px

### 8.2 Service Worker

File `/public/sw.js` menggunakan strategi **network-first**:

```
Fetch request
    │
    ▼
Coba fetch dari network
    │
    ├── Berhasil → Return response + simpan ke cache
    │
    └── Gagal → Ambil dari cache (fallback offline)
```

**Pengecualian**: Request ke `/api/` TIDAK di-cache agar data selalu fresh.

### 8.3 Install Prompt

```javascript
// Browser memicu event 'beforeinstallprompt'
window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;
    // Tampilkan tombol "⬇ Install Aplikasi"
    document.getElementById('pwa-install-btn').style.display = 'inline-flex';
});

// Saat user klik tombol install
function installPWA() {
    deferredPrompt.prompt();  // Tampilkan dialog install browser
}
```

---

## 9. Export Data

### 9.1 PDF Kartu Stok

```
GET /api/export/pdf/{item_id}?tahun=2026&gudang=Gudang+Utama&token=xxx
    │
    ▼
ExportController::exportPdf()
  → Item::getKartuStok(2026, 'Gudang Utama')
  → Pdf::loadView('pdf.kartu-stok', data)
  → $pdf->setPaper('a4', 'portrait')
  → Return: download stream
```

### 9.2 Excel Export

```
GET /api/export/excel?gudang=Gudang+Utama&token=xxx
    │
    ▼
ExportController::exportExcel()
  → new GudangScanExport($gudang)
  → Excel::download(export, filename)
  → Return: download stream
```

---

## 10. QR Code Format

### 10.1 Encoding

QR Code berisi string dengan format:
```
{KODE}|{NAMA}
```
Contoh: `CT-001|Micro:bit V2`

### 10.2 Decoding (Scanner)

```javascript
function onQRDetected(raw) {
    let kode = raw.includes('|') ? raw.split('|')[0] : raw;
    kode = kode.trim().toUpperCase();
    // Cari di master data...
}
```

Jika QR hanya berisi kode tanpa separator `|`, tetap bisa diproses.

---

## 11. Ketahanan Aplikasi & Kompatibilitas Browser (Error Resilience & Compatibility)

Untuk menjamin aplikasi tetap berjalan stabil di lapangan dengan berbagai kondisi jaringan dan perangkat, sistem menerapkan mekanisme pertahanan berikut:

### 11.1 Isolasi Parsing LocalStorage (Safe Parser)
Browser lokal yang memiliki data sesi kedaluwarsa atau korup (misalnya bernilai string `"undefined"`) dapat menyebabkan `JSON.parse` mengalami crash instan.
- **Solusi**: Setiap pemanggilan data dari `localStorage` dibungkus dengan blok `try-catch`.
- **Fallback**: Jika terjadi kegagalan parsing, data otomatis di-fallback ke array kosong `[]` atau `null` alih-alih menghentikan siklus hidup JavaScript utama.

### 11.2 Penyorotan Navigasi Berbasis ID (ID-Based Sidebar Navigation)
Pendelegasian penyorotan sidebar menu yang bergantung pada objek `event.target` (seperti `event?.target?.closest()`) seringkali diblokir oleh browser di bawah mode ketat (*strict mode*) karena variabel global `event` bersifat *deprecated*.
- **Solusi**: Sistem menggunakan ID statis terstruktur (`sb-link-{page_name}`) untuk menyorot sidebar aktif secara langsung melalui `document.getElementById()`, memastikan kompatibilitas penuh dengan Safari Mobile (iOS) dan browser Firefox.

### 11.3 Tampilan & Gaya Mandiri (Tailwind CDN Fallback)
Visualisasi titik PIN (`pin-dot`) pada layar login rentan tidak memunculkan perubahan visual (titik terisi) apabila pustaka CDN Tailwind CSS lambat dimuat atau diblokir oleh jaringan korporat.
- **Solusi**: Ditambahkan gaya CSS murni (*Vanilla CSS*) khusus `.pin-dot`, `.pin-dot.filled`, dan `.pin-dot.error` langsung di dalam tag `<style>` lokal. Hal ini menjamin feedback visual input PIN tetap berfungsi mulus tanpa ketergantungan pada CDN eksternal.

### 11.4 Alur Navigasi Kembali Mobile & Tombol Menu Dinamis
Pada perangkat mobile dengan laci sidebar tersembunyi, pengguna membutuhkan kejelasan alur kembali ke dashboard tanpa harus selalu membuka sidebar.
- **Solusi**: Diterapkan tombol menu dinamis di topbar kiri mobile. Jika berada di halaman `dashboard`, tombol hamburger (`☰`) ditampilkan untuk membuka sidebar. Jika berada di sub-halaman lain (seperti scan/stok/transaksi), tombol hamburger disembunyikan dan digantikan oleh **Tombol Kembali (⬅️)** untuk mengarahkan pengguna kembali ke Dashboard.

### 11.5 Tombol Instalasi PWA Permanen & Panduan OS
Perangkat iOS (Safari) tidak mendukung pemicu instalasi PWA terprogram (`beforeinstallprompt`), sehingga tombol instalasi biasanya tersembunyi secara permanen di iPhone.
- **Solusi**: Tombol instalasi `📲 Install` ditampilkan secara permanen di topbar. Jika event browser tidak tersedia, tombol mendeteksi User Agent sistem operasi (OS) pengguna. Pengguna iOS Safari diarahkan melalui alert instruksi manual ketuk tombol *Share* lalu *Add to Home Screen*, sedangkan pengguna Android/PC diarahkan ke menu *Settings* browser.

### 11.6 Pencegahan Zoom Otomatis (Double-Tap Zoom) & Highlight Warna Ketukan
Ketukan cepat berulang pada perangkat genggam (seperti saat memasukkan PIN 6 digit secara cepat) dapat secara otomatis memicu pembesaran layar browser (*double-tap zoom*) yang merusak visual PWA.
- **Solusi**: 
  - Tag viewport ditambahkan atribut `maximum-scale=1.0, user-scalable=no, viewport-fit=cover`.
  - Aturan CSS global disuntikkan properti `touch-action: manipulation` pada seluruh elemen klik aktif, memaksa browser mobile melarang *double-tap zoom* tetapi tetap mengizinkan scrolling.
  - Properti `-webkit-tap-highlight-color: transparent` disematkan untuk membuang kedipan kotak biru saat ketukan cepat pada tombol.

### 11.7 Pembaruan Cache PWA Paksa (Cache Invalidation)
Untuk memaksa browser klien membuang salinan file halaman PWA lama dan memuat perbaikan JavaScript terbaru:
- **Solusi**: Versi cache di `/public/sw.js` dinaikkan menjadi `erlassgudangapp-v1`. Browser otomatis mendeteksi perubahan ini saat online, menghapus cache lama, dan mengunduh ulang versi visual terbaru.

### 11.8 Penanganan "Server Error" (500) Pasca Clear Cache View
Ketika menjalankan perintah `php artisan view:clear` di lingkungan produksi dengan OPCache aktif:
- **Masalah**: Berkas Blade lama yang dikompilasi dihapus dari disk, tetapi PHP-FPM masih menyimpan referensi berkas tersebut dalam memori RAM (OPCache). Ini mengakibatkan error `filemtime(): stat failed` (HTTP 500 Server Error).
- **Solusi**: Lakukan restart atau reload pada service PHP-FPM untuk mengosongkan memori OPCache secara paksa setelah melakukan pembersihan cache view:
  ```bash
  systemctl restart php8.3-fpm
  ```

### 11.9 Rencana Input Manual PIC/Petugas Berdasarkan Role (Pengembangan Selanjutnya)
Untuk kebutuhan input data petugas secara lebih fleksibel namun tetap terklasifikasi berdasarkan wewenang:
- **Kebutuhan**:
  - Menyediakan form input teks tambahan untuk kolom `Petugas` (PIC) di halaman Scan Barang masuk/keluar.
  - Label input harus dinamis:
    - Jika user login memiliki role `petugas`, label input adalah **"Petugas"** (misal untuk menginput nama petugas lapangan yang bertugas).
  - Nilai dari input manual ini akan dikirim ke API sebagai parameter `petugas`. Jika dikosongkan, sistem akan otomatis menggunakan nama pengguna yang login (`user.name`) sebagai fallback.

### 11.10 Antrean Cetak & Layout Print QR Code Presisi

Untuk mempermudah pekerjaan operasional di lapangan yang memerlukan pencetakan banyak label stiker QR code untuk barang berbeda dalam satu waktu, sistem menerapkan antrean cetak:
* **Antrean Cetak (Print Queue)**: Pengguna dapat mencari barang, menentukan jumlah salinan, lalu memasukkannya ke dalam antrean cetak (`printQueue` array). Tabel antrean menyediakan tombol increment/decrement (`+`/`-`) untuk mengubah jumlah salinan stiker per barang secara dinamis.
* **Pratinjau Gabungan**: Area pratinjau (`#pr-preview`) secara otomatis menggabungkan seluruh item yang ada di antrean beserta jumlah salinannya untuk memberikan representasi visual sebelum pencetakan dilakukan.
* **Layout Cetak Presisi Kiri Atas**: Untuk menghindari stiker terpotong atau memiliki margin default browser yang merusak tata letak stiker:
  - Gaya CSS `@media print` meniadakan `margin` dan `padding` pada `body` (diatur ke `0`).
  - Elemen `#print-area` dipaksa menggunakan `justify-content: flex-start !important` dan `align-items: flex-start !important` agar stiker tersusun rapat dari sudut **paling kiri atas** kertas.
  - Seluruh struktur layout visual utama (`#app-layout`, `#login-screen`, dan `.toast`) disembunyikan sepenuhnya (`display: none !important`) selama proses pencetakan agar printer hanya memproses stiker QR code.

---

## 12. Arsitektur Dual-Mode Inventaris

Sistem ErlassGudangApp mengelola **dua mode inventaris** yang berbeda secara fundamental:

### 12.1 Mode 1: Stok Reguler (Aggregate / Bulk)

Digunakan untuk barang habis pakai atau barang yang dilacak secara agregat (berdasarkan jumlah total), bukan per unit.

```
Contoh: Modul Scratch, Kabel HDMI, Tinta Printer

items
  └─ transactions (banyak)
       ├─ tipe: masuk (dengan lokasi, sumber, PO)
       └─ tipe: keluar (dengan lokasi, penerima, keperluan)

Stok = SUM(masuk) - SUM(keluar)  ← computed, tidak disimpan
```

**Alur operasional**: Scan QR → Form Masuk/Keluar → POST /api/transactions

### 12.2 Mode 2: Asset Terserialisasi (Per-Unit / Serialized)

Digunakan untuk aset bernilai tinggi yang perlu dilacak secara individual per unit, khususnya **barang sewa** ke sekolah klien.

```
Contoh: Laptop, Tablet, Kit Microbit

items
  └─ assets (banyak unit, masing-masing punya serial number)
       └─ transactions (ledger audit trail per unit)
            ├─ tipe_detail: pembelian   ← saat asset didaftarkan
            ├─ tipe_detail: sewa_keluar ← saat dikirim ke sekolah
            ├─ tipe_detail: sewa_kembali← saat dikembalikan
            └─ tipe_detail: mutasi_lokasi← saat dipindah antar gudang
```

**Alur operasional**: Daftar Asset → Sewa Keluar → Sewa Kembali

### 12.3 Perbedaan Utama

| Aspek | Stok Reguler | Asset Terserialisasi |
|-------|-------------|---------------------|
| Unit pelacakan | Agregat qty | Per serial number |
| Field kunci | `item_id`, `lokasi` (string) | `asset_id`, `location_id` (FK) |
| Status sewa | Tidak ada | `is_rented` flag |
| Kondisi fisik | Tidak dilacak | `status` (good/not_good/dst) |
| Use case | Modul, kabel, ATK | Laptop, tablet, kit robot |

---

## 13. Logika Modul Asset Terserialisasi

### 13.1 Siklus Hidup Asset

```
[Registrasi] → [Di Gudang] ←→ [Disewa Keluar] → [Dikembalikan]
     │                                                  │
     ▼                                                  ▼
 Transaction:                                      Transaction:
 tipe=masuk                                        tipe=masuk
 tipe_detail=pembelian                             tipe_detail=sewa_kembali
 is_rented=false                                   is_rented=false
```

### 13.2 Validasi Sewa Keluar (rentOut)

```php
// Cek setiap unit yang akan disewakan:
if ($asset->is_rented) {
    return error("Unit {SN} sedang disewa");
}

// Jika lolos validasi:
$asset->is_rented = true;
$asset->save();
Transaction::create(['tipe_detail' => 'sewa_keluar', ...]);
```

Seluruh operasi dibungkus `DB::transaction()` untuk memastikan atomisitas — jika satu unit gagal, semua rollback.

### 13.3 Validasi Pengembalian (rentReturn)

```php
if (!$asset->is_rented) {
    return error("Unit {SN} tidak sedang disewa");
}

// Update kondisi dan lokasi baru:
$asset->is_rented = false;
$asset->status = $item['status'];   // Kondisi saat kembali
$asset->location_id = $item['location_id'];
$asset->save();
Transaction::create(['tipe_detail' => 'sewa_kembali', ...]);
```

### 13.4 Mutasi Lokasi (mutateLocation)

Memindahkan asset antar gudang/lokasi. Membuat 2 record transaksi untuk menjaga keseimbangan ledger:

```
keluar (tipe_detail=mutasi_lokasi) dari lokasi_lama
masuk  (tipe_detail=mutasi_lokasi) ke   lokasi_baru
```

---

## 14. Master Data Hierarki Lokasi

### 14.1 Struktur Tree

Tabel `locations` mendukung hierarki berjenjang menggunakan self-referential `parent_id`:

```
Gedung A (tipe: gedung, parent: null)
  ├── Lantai 1 (tipe: lantai, parent: Gedung A)
  │     ├── Ruang Gudang 1 (tipe: ruangan, parent: Lantai 1)
  │     │     ├── Rak A (tipe: rak, parent: Ruang Gudang 1)
  │     │     └── Rak B (tipe: rak, parent: Ruang Gudang 1)
  │     └── Ruang Server (tipe: ruangan, parent: Lantai 1)
  └── Lantai 2 (tipe: lantai, parent: Gedung A)
```

### 14.2 Kode Lokasi

Setiap lokasi memiliki `kode` unik untuk referensi cepat:
- Format bebas, disarankan mengikuti hierarki: `GDG-A-L1-RK-A1`
- Digunakan sebagai identifier dalam form dan laporan

### 14.3 Relasi ke Asset & Transaksi

- `assets.location_id` → lokasi **saat ini** dari unit asset
- `transactions.location_id` → lokasi yang **dicatat** saat transaksi berlangsung (audit trail)
- `transactions.lokasi` → **legacy string field** yang digunakan oleh fitur Scan Barang reguler (offline-first)

---

## 15. Master Kategori Hierarkis

### 15.1 Hierarki Self-Referential

Sama halnya dengan `locations`, tabel `categories` diimplementasikan secara berjenjang menggunakan self-referential `parent_id`:
- Kategori dengan `parent_id = null` adalah **Kategori Utama**.
- Kategori dengan `parent_id` menunjuk ke ID kategori lain adalah **Sub Kategori**.

### 15.2 Validasi Relasi Melingkar (Circular Dependency)
Saat melakukan pembaruan (update) kategori, sistem memvalidasi bahwa `parent_id` tidak boleh sama dengan `id` kategori itu sendiri untuk menghindari relasi melingkar tak berujung (circular loop).

```php
$validated = $request->validate([
    'nama'      => 'required|string|max:255',
    'parent_id' => "nullable|exists:categories,id|not_in:{$id}",
]);
```

### 15.3 Kebijakan Penghapusan
Ketika sebuah kategori induk dihapus, sub-kategori di bawahnya tidak ikut terhapus, melainkan diset `parent_id = null` (NullOnDelete) sehingga otomatis naik tingkat menjadi **Kategori Utama**.

---

## 16. Logika Warning Buffer Stock

Sistem ErlassGudangApp menerapkan mekanisme peringatan dinamis (*Warning Buffer Stock*) berdasarkan batas minimum stok (`min_stok`) yang didefinisikan per-item.

### 16.1 Pengambilan Data Real-Time
Backend (`StockController::index()`) menghitung sisa stok dinamis untuk setiap item:

$$\text{Sisa Stok} = \text{Total Masuk} - \text{Total Keluar}$$

Hasil ini kemudian dievaluasi terhadap nilai `min_stok` item tersebut untuk memproduksi flag status:

```php
'is_warning' => ($stok > 0 && $stok <= $item->min_stok),
'is_empty'   => ($stok <= 0)
```

### 16.2 Ringkasan Stok (Stock Summary)
Peringatan stok diakumulasikan secara real-time pada respons API untuk konsumsi widget dashboard:
- **Stok Kosong** (`stok_kosong`): Jumlah item dengan `is_empty = true`.
- **Stok Menipis** (`stok_menipis`): Jumlah item dengan `is_warning = true` (stok di bawah batas minimumnya).

### 16.3 Representasi Visual di UI
Berdasarkan flag status dari API, UI menerapkan perubahan gaya visual baris tabel:
- `is_empty = true` $\rightarrow$ background merah transparan (`bg-error/5`) + teks merah + badge merah **`HABIS`**.
- `is_warning = true` $\rightarrow$ background kuning transparan (`bg-warning/5`) + teks kuning + badge kuning **`⚠ MENIPIS`**.
- Normal $\rightarrow$ teks hijau.

---

## 17. Logika Modul Lapangan MIKMS (Micro:bit Interactive Kit)

Modul MIKMS dirancang untuk mengelola kit edukasi Micro:bit lapangan yang terdiri dari boks kit, modul rakitan (M01-M10), dan komponen elektronik pendukung.

### 17.1 Pemetaan Modul & Komponen Bill of Materials (BOM)
Setiap modul rakitan (misal: `M01 Controller Kit`, `M02 LED Kit`, `M03 Motion Kit`, dsb) didefinisikan dalam tabel `mikms_modules` dengan relasi detail ke komponen dasar (`items`) melalui tabel perantara `mikms_bom`:

```
mikms_modules (M01)
  ├── mikms_bom (qty: 1) ──▶ items (CT-001 Micro:bit V2)
  ├── mikms_bom (qty: 1) ──▶ items (CT-003 Kabel Micro USB)
  └── mikms_bom (qty: 1) ──▶ items (CT-002 Kabel Micro Type-C)
```

### 17.2 Perakitan Modul & Otomasi Pemotongan Bahan Baku
Saat petugas menginput hasil perakitan di form produksi (`POST /api/mikms/productions`):
1. Sistem menghitung kebutuhan seluruh komponen dasar:
   $$\text{Kebutuhan Komponen } i = \text{BOM Qty } i \times \text{Kuantitas Modul yang Dirakit}$$
2. Sistem memvalidasi saldo stok dinamis setiap item di tabel `transactions`. Jika ada item yang tidak mencukupi, operasi dibatalkan dan API mengembalikan pesan kesalahan HTTP 422 dengan rincian nama item dan kekurangan kuantitasnya.
3. Jika seluruh bahan mencukupi, dalam satu transaksi database (`DB::transaction`):
   - Record produksi disimpan di `mikms_productions`.
   - Record transaksi pengeluaran stok (`tipe = 'keluar'`) otomatis dibuat di tabel `transactions` untuk setiap komponen dengan catatan otomatis *"Produksi MIKMS: [Kode Modul] x [Qty]"*.

### 17.3 Quality Control (QC) & Siklus Status Boks
Pasca perakitan atau pasca penerimaan dari perbaikan, modul dan boks kit wajib melalui pemeriksaan QC (`POST /api/mikms/qc-logs`):
- `status_qc = 'LOLOS'` $\rightarrow$ modul siap dimasukkan ke boks kit untuk didistribusikan.
- `status_qc = 'TIDAK LOLOS'` $\rightarrow$ dicatat keterangan cacat (`defect_notes`) dan dialihkan ke antrean perbaikan (*repair*).

### 17.4 Distribusi Sekolah, Retur, dan Pemulihan Repair
Status boks kit fisik (`mikms_boxes.status`) mengikuti *finite state machine*:

```
           ┌──────────────────────┐
           │        READY         │◀─────────────────┐
           └──────────┬───────────┘                  │
                      │ Surat Jalan                  │
                      │ (POST /mikms/shipments)      │
                      ▼                              │
           ┌──────────────────────┐                  │ Repair BERHASIL
           │       ON_LOAN        │                  │ (POST /mikms/repairs)
           └──────────┬───────────┘                  │
                      │ Pengembalian                 │
                      │ (POST /mikms/returns)        │
         ┌────────────┴────────────┐                 │
         │ Kondisi:                │ Kondisi:        │
         │ LENGKAP                 │ RUSAK           │
         ▼                         ▼                 │
      [READY]             ┌─────────────────┐        │
                          │     REPAIR      │────────┘
                          └────────┬────────┘
                                   │ Repair GAGAL
                                   ▼
                          ┌─────────────────┐
                          │ DAMAGED/DISPOSED│
                          └─────────────────┘
```

---

## 18. Smart Cascading Multi-Level BOM Engine

### 18.1 Konsep Deduksi Bertingkat (Cascading BOM)
Dalam operasional harian, pesanan dari sekolah masuk dalam bentuk **paket kit utuh** (misal: 5 paket Microbit Learning Kit / MLK). Satu paket MLK membutuhkan 7 modul (M01 sampai M07).

Sistem menerapkan prinsip efisiensi gudang dua tingkat (*Two-Tier Allocation*):
- **Tingkat 1 (Gudang WIP / Rak Modul Jadi)**: Gunakan stok modul jadi yang sudah dirakit sebelumnya (`mikms_module_stocks`).
- **Tingkat 2 (Gudang Bahan Mentah / Raw Material)**: Untuk sisa modul yang belum tersedia di rak, sistem secara otomatis mem-breakdown modul ke daftar komponen bahan mentahnya (komponen BOM) dan memotong stok bahan mentah tersebut secara otomatis.

### 18.2 Algoritma Simulasi (`packageSimulate`)
Sebelum melakukan pesanan nyata, petugas dapat melihat tinjauan simulasi (`GET /api/mikms/package-simulate`):
1. Menentukan daftar modul yang wajib ada untuk program yang dipilih (MLK atau ROBOTIC).
2. Membandingkan kebutuhan dengan saldo `stock_ready` di `mikms_module_stocks`:
   $$\text{from\_stock} = \min(\text{stock\_ready}, \text{package\_qty})$$
   $$\text{to\_assemble} = \text{package\_qty} - \text{from\_stock}$$
3. Untuk modul dengan $\text{to\_assemble} > 0$, sistem menghitung akumulasi seluruh komponen mentah yang dibutuhkan dan membandingkannya dengan stok riil di gudang.
4. Mengembalikan ringkasan status `can_fulfill = true/false` beserta rincian kekurangan jika ada.

### 18.3 Eksekusi Pesanan Paket (`storePackageOrder`)
Dijalankan secara atomik (`DB::beginTransaction()`):
1. **Kunci Baris**: `MikmsModuleStock::where(...)->lockForUpdate()` mencegah *race condition* stok ganda.
2. **Potong Modul Rak**: Untuk modul yang diambil dari rak, kurangi `stock_ready` dan catat audit log di `mikms_module_stock_logs` dengan tipe `package_out`.
3. **Validasi & Potong Bahan Mentah**: Untuk modul yang harus dirakit:
   - Validasi ketersediaan stok seluruh komponen bahan mentah.
   - Buat transaksi keluar (`tipe = 'keluar'`) pada tabel `transactions`.
   - Catat auto-produksi pada `mikms_productions` sebagai jejak riwayat perakitan.
4. **Simpan Pesanan**: Catat transaksi pesanan di `mikms_package_orders` lengkap dengan snapshot rincian pemotongan format JSON pada kolom `deduction_log`.

### 18.4 Resolusi Dinamis Master Program Kit
Daftar modul penyusun setiap program kit **sepenuhnya dinamis** dan dikelola melalui tabel `mikms_programs`.
- Saat fungsi `getModulesForProgram($programCode)` dijalankan, sistem mengambil definisi modul langsung dari database (`MikmsProgram::where('code', $programCode)->value('modules')`).
- Estimasi pcs komponen (`calculateTotalPcs()`) dihitung otomatis dengan menjumlahkan seluruh `quantity_per_module` dari komponen BOM yang terkait dengan modul-modul terpilih.
- Antarmuka formulir pesanan paket secara otomatis memuat daftar program aktif via `/api/mikms/programs?active_only=1` sehingga setiap penambahan program kit baru (misal: STEAM, IoT, AI Kit) langsung siap disimulasikan dan dipesan tanpa perubahan kode.

---

## 19. Arsitektur Navigasi Terpadu (Unified Pipeline)

### 19.1 Filosofi Reorganisasi Navigasi
Sebelumnya, navigasi memisahkan menu reguler, sewa, dan modul MIKMS ke dalam grup terpisah. Pada versi 3.5, antarmuka disatukan menjadi **1 alur kerja terpadu berbasis aktivitas gudang**:
1. **Overview**: Dashboard Terpadu & metrik ringkas.
2. **Barang Masuk**: Scan QR & formulir penerimaan barang dari vendor.
3. **Produksi & Modul**: Perakitan modul BOM, stok modul jadi, QC, dan spesifikasi BOM.
4. **Pesanan & Keluar**: Pesanan paket kit bertingkat (Cascading BOM) dan surat jalan pengiriman ke sekolah.
5. **Sirkulasi & Inventori**: Manajemen boks kit, retur, repair, unit asset terserialisasi, dan sewa.
6. **Stok & Laporan**: Katalog stok agregat, kartu stok terpadu, riwayat transaksi, dan stock opname.
7. **Tools & Administrasi**: Cetak QR label, SOP alur kerja interaktif, dan master data.

### 19.2 Dispatcher `goPage(name, mikmsTab)`
Fungsi navigasi utama di frontend (`app.blade.php`) mendukung parameter tab opsional:
```javascript
function goPage(name, mikmsTab) {
  // 1. Tampilkan kontainer halaman
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.getElementById('pg-' + name).classList.add('active');

  // 2. Highlight sidebar spesifik (sb-link-{name} atau sb-link-mk-{mikmsTab})
  document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active', ...));
  const linkId = (name === 'mikms' && mikmsTab) ? 'sb-link-mk-' + mikmsTab : 'sb-link-' + name;
  const activeEl = document.getElementById(linkId);
  if (activeEl) activeEl.classList.add('active', ...);

  // 3. Sinkronisasi judul halaman di header
  document.getElementById('tb-page-title').textContent = pageTitle;

  // 4. Dispatch instan ke tab MIKMS jika ada
  if (name === 'mikms') {
    if (mikmsTab) {
      currentMikmsTab = mikmsTab;
      switchMikmsTab(mikmsTab);
    }
    loadMikmsPage();
  }
}
```

### 19.3 Sinkronisasi Status Aktif Dua Arah
Saat pengguna berpindah tab dari dalam halaman MIKMS melalui tombol tab bar horizontal (`switchMikmsTab(tab)`):
- Sidebar link yang bersesuaian (`#sb-link-mk-{tab}`) otomatis aktif dan disorot dengan warna primer.
- Judul topbar diperbarui mengikuti nama sub-aktivitas tab aktif.
- Tombol tab aktif di-scroll secara halus ke posisi tengah layar pada perangkat bergerak (mobile).
