# 📡 ErlassGudangApp — API Reference

Base URL: `https://gudang.erlass.institute/api`

Semua endpoint yang di-protect membutuhkan header:
```
Authorization: Bearer {token}
```

Atau untuk download file di tab baru, gunakan query parameter:
```
?token={token}
```

---

## 📋 Daftar Endpoint

| Method | Endpoint | Auth | Role | Deskripsi |
|--------|----------|------|------|-----------|
| POST | `/auth/login` | ❌ | — | Login via PIN, NIK, atau Email |
| POST | `/auth/logout` | ✅ | Semua | Logout & revoke token |
| GET | `/items` | ✅ | Semua | List semua master barang |
| POST | `/items` | ✅ | 🔒 Admin | Tambah barang baru |
| GET | `/items/{id}` | ✅ | Semua | Detail satu barang |
| PUT | `/items/{id}` | ✅ | 🔒 Admin | Update barang |
| DELETE | `/items/{id}` | ✅ | 🔒 Admin | Hapus barang (soft delete) |
| GET | `/stock` | ✅ | Semua | Status stok semua barang |
| GET | `/stock/{item_id}/card` | ✅ | Semua | Kartu stok per barang |
| GET | `/transactions` | ✅ | Semua | Riwayat transaksi |
| GET | `/transactions/stats` | ✅ | Semua | Statistik ringkasan transaksi |
| POST | `/transactions` | ✅ | Semua | Simpan transaksi (single/bulk offline) |
| GET | `/locations` | ✅ | Semua | List semua lokasi |
| POST | `/locations` | ✅ | Semua | Tambah lokasi baru |
| DELETE | `/locations/{id}` | ✅ | Semua | Hapus lokasi |
| GET | `/vendors` | ✅ | Semua | List semua vendor |
| POST | `/vendors` | ✅ | Semua | Tambah vendor baru |
| DELETE | `/vendors/{id}` | ✅ | Semua | Hapus vendor |
| GET | `/customers` | ✅ | Semua | List semua customer |
| POST | `/customers` | ✅ | Semua | Tambah customer baru |
| DELETE | `/customers/{id}` | ✅ | Semua | Hapus customer |
| GET | `/categories` | ✅ | Semua | List semua kategori |
| POST | `/categories` | ✅ | Semua | Tambah kategori baru |
| PUT | `/categories/{id}` | ✅ | Semua | Update kategori |
| DELETE | `/categories/{id}` | ✅ | Semua | Hapus kategori |
| GET | `/users` | ✅ | 🔒 Admin | List semua pengguna |
| POST | `/users` | ✅ | 🔒 Admin | Tambah pengguna baru |
| PUT | `/users/{id}` | ✅ | 🔒 Admin | Update pengguna |
| DELETE | `/users/{id}` | ✅ | 🔒 Admin | Hapus pengguna |
| POST | `/users/import` | ✅ | 🔒 Admin | Impor massal akun pengguna |
| GET | `/assets` | ✅ | Semua | List unit asset terserialisasi |
| POST | `/assets` | ✅ | Semua | Daftarkan unit asset baru |
| POST | `/assets/mutate` | ✅ | Semua | Pindah lokasi asset |
| POST | `/assets/rent-out` | ✅ | Semua | Sewa keluar asset ke customer |
| POST | `/assets/rent-return` | ✅ | Semua | Terima kembali asset dari sewa |
| POST | `/assets/{id}/status` | ✅ | Semua | Update kondisi fisik asset |
| GET | `/assets/{id}/card` | ✅ | Semua | Riwayat transaksi satu unit asset |
| GET | `/mikms/dashboard` | ✅ | Semua | Statistik ringkas metrik MIKMS |
| GET | `/mikms/modules` | ✅ | Semua | Master modul MIKMS & komponen BOM |
| GET | `/mikms/boxes` | ✅ | Semua | List master boks kit |
| POST | `/mikms/boxes` | ✅ | Semua | Daftarkan boks kit baru |
| POST | `/mikms/productions` | ✅ | Semua | Form perakitan modul (auto potong raw) |
| POST | `/mikms/qc-logs` | ✅ | Semua | Form verifikasi QC kelayakan modul |
| POST | `/mikms/shipments` | ✅ | Semua | Form pengiriman boks ke sekolah |
| POST | `/mikms/returns` | ✅ | Semua | Form pengembalian boks dari sekolah |
| POST | `/mikms/repairs` | ✅ | Semua | Form perbaikan modul/komponen |
| POST | `/mikms/stock-opnames` | ✅ | Semua | Form pencatatan stock opname MIKMS |
| GET | `/mikms/logs` | ✅ | Semua | Riwayat transaksi dan aktivitas MIKMS |
| GET | `/mikms/package-simulate` | ✅ | Semua | Simulasi pesanan paket (Cascading BOM) |
| GET | `/mikms/package-orders` | ✅ | Semua | List riwayat pesanan paket kit |
| POST | `/mikms/package-orders` | ✅ | Semua | Eksekusi pesanan paket (deduksi bertingkat) |
| GET | `/mikms/module-stocks` | ✅ | Semua | Stok modul jadi siap pakai |
| POST | `/mikms/module-stocks/adjust` | ✅ | Semua | Penyesuaian stok modul jadi manual |
| GET | `/mikms/programs` | ✅ | Semua | List Master Program Kit (BOM Packages) |
| POST | `/mikms/programs` | ✅ | Semua | Tambah Master Program Kit baru |
| PUT | `/mikms/programs/{id}` | ✅ | Semua | Perbarui Master Program Kit |
| DELETE | `/mikms/programs/{id}` | ✅ | Semua | Hapus / nonaktifkan Program Kit |
| GET | `/mikms/export/excel` | ✅ | Semua | Download Excel seluruh data MIKMS |
| GET | `/export/excel` | ✅ | Semua | Download Excel transaksi reguler |
| GET | `/export/pdf/{item_id}` | ✅ | Semua | Download PDF Kartu Stok |

> 🔒 = Hanya role `admin` (untuk endpoint Items CRUD & Manajemen User). Endpoint Master Data lainnya (locations, vendors, customers, categories) dan operasional MIKMS dapat diakses oleh semua role yang terautentikasi.

---

## 1. Autentikasi

### POST `/auth/login`

Login menggunakan NIK Karyawan dan Password.

**Request**:
```json
{
  "nik": "12345",
  "password": "password"
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Login berhasil",
  "token": "1|XW9ehyPGcYwoSe51zJYv3vzx64IAk1e0dCsV4kYD5a3a6413",
  "user": {
    "id": 2,
    "name": "Admin Gudang",
    "email": "admin@erlass.institute",
    "nik": "admin",
    "role": "admin"
  }
}
```

**Response 401** (PIN/password salah):
```json
{
  "success": false,
  "message": "PIN salah atau tidak terdaftar"
}
```

---

### POST `/auth/logout`

Revoke current Sanctum token.

**Headers**: `Authorization: Bearer {token}`

**Response 200**:
```json
{
  "success": true,
  "message": "Logout berhasil"
}
```

---

## 2. Master Barang (Items)

### GET `/items`

List semua barang aktif dengan filter opsional.

**Query Parameters**:
| Param | Tipe | Deskripsi |
|-------|------|-----------|
| `produk` | string | Filter kategori produk |
| `search` | string | Cari berdasarkan kode/nama |

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "kode": "CT-001",
      "nama": "Micro:bit V2",
      "satuan": "pcs",
      "produk": "Controller",
      "komponen": "Microcontroller",
      "lokasi_default": "Gudang Raw Material",
      "min_stok": 5,
      "deskripsi": "Papan utama Micro:bit V2",
      "stok": 42
    }
  ]
}
```

> **Catatan**: Field `stok` adalah computed value, dihitung dari sum transaksi.

---

### POST `/items` 🔒 Admin Only

Tambah barang baru ke master data.

**Request**:
```json
{
  "kode": "MSJ10",
  "nama": "Modul Scratch Jilid 10",
  "satuan": "pcs",
  "produk": "Modul",
  "komponen": "Shared",
  "lokasi_default": "Gudang Utama",
  "min_stok": 5,
  "deskripsi": "Modul untuk kelas Scratch tingkat 10"
}
```

**Response 201**:
```json
{
  "success": true,
  "message": "Barang berhasil ditambahkan",
  "data": { "id": 10, "kode": "MSJ10", "..." }
}
```

---

### GET `/items/{id}`

Detail satu barang.

**Response 200**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "kode": "CT-001",
    "nama": "Micro:bit V2",
    "satuan": "pcs",
    "produk": "Controller",
    "komponen": "Microcontroller",
    "lokasi_default": "Gudang Raw Material",
    "min_stok": 5,
    "deskripsi": "Papan utama Micro:bit V2",
    "stok": 42
  }
}
```

---

### PUT `/items/{id}` 🔒 Admin Only

Update data barang. (Format request sama dengan POST)

---

### DELETE `/items/{id}` 🔒 Admin Only

Soft-delete barang. Record tidak dihapus permanen dari database.

**Response 200**:
```json
{
  "success": true,
  "message": "Barang berhasil dihapus"
}
```

---

## 3. Stok

### GET `/stock`

Status stok semua barang, dengan summary.

**Query Parameters**:
| Param | Tipe | Deskripsi |
|-------|------|-----------|
| `gudang` | string | Filter nama gudang (opsional). Jika kosong/`"Semua"` → semua gudang. |

**Response 200**:
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "kode": "CT-001",
        "nama": "Micro:bit V2",
        "satuan": "pcs",
        "produk": "Controller",
        "komponen": "Microcontroller",
        "masuk": 50,
        "keluar": 8,
        "stok": 42
      }
    ],
    "summary": {
      "total_barang": 25,
      "stok_kosong": 2,
      "stok_menipis": 3
    }
  }
}
```

---

### GET `/stock/{item_id}/card`

Kartu stok (running balance) per barang per tahun.

**Query Parameters**:
| Param | Tipe | Default | Deskripsi |
|-------|------|---------|-----------|
| `tahun` | int | Tahun sekarang | Tahun kartu stok |
| `gudang` | string | Semua | Filter gudang |

**Response 200**:
```json
{
  "success": true,
  "data": {
    "item": { "id": 1, "kode": "CT-001", "nama": "Micro:bit V2" },
    "year": 2026,
    "gudang": "Semua",
    "opening_balance": 45,
    "rows": [
      {
        "tanggal": "2026-01-01",
        "tanggal_formatted": "01-Jan-26",
        "masuk": 0,
        "keluar": 0,
        "sisa_akhir": 45,
        "no_po": "",
        "no_prn": "",
        "job_number": "",
        "transfer_order": "",
        "user_pemasok": "",
        "lokasi": "Semua",
        "pic": "",
        "keterangan": "SALDO AWAL 2026"
      }
    ]
  }
}
```

---

## 4. Transaksi (Stok Reguler)

### GET `/transactions`

Riwayat transaksi dengan filter.

**Query Parameters**:
| Param | Tipe | Default | Deskripsi |
|-------|------|---------|-----------| 
| `tipe` | string | — | Filter: `masuk` atau `keluar` |
| `item_id` | int | — | Filter per barang |
| `start_date` | date | — | Awal rentang tanggal |
| `end_date` | date | — | Akhir rentang tanggal |
| `limit` | int | 100 | Batas jumlah record |

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 42,
      "item_id": 1,
      "tipe": "masuk",
      "tipe_detail": null,
      "qty": 10,
      "transaction_date": "2026-06-29",
      "lokasi": "Gudang Utama",
      "sumber": "Supplier / Vendor",
      "penerima": null,
      "no_po": "PO-001",
      "petugas": "Admin Gudang",
      "item": {
        "id": 1,
        "kode": "CT-001",
        "nama": "Micro:bit V2"
      }
    }
  ]
}
```

---

### POST `/transactions`

Simpan transaksi baru. Mendukung **single** dan **bulk** (untuk offline sync).

#### Mode Single

**Request**:
```json
{
  "kode": "CT-001",
  "tipe": "masuk",
  "qty": 10,
  "transaction_date": "2026-06-29",
  "lokasi": "Gudang Raw Material",
  "sumber": "Supplier / Vendor",
  "no_po": "PO-001",
  "petugas": "Admin Gudang",
  "client_id": "GS-1719705600000"
}
```

**Response 201**:
```json
{
  "success": true,
  "message": "Transaksi berhasil dicatat",
  "data": { "id": 42, "..." }
}
```

**Response 422** (Stok tidak cukup):
```json
{
  "success": false,
  "message": "Stok tidak cukup untuk CT-001. Tersedia: 3"
}
```

#### Mode Bulk (Offline Sync)

**Request**:
```json
{
  "transactions": [
    { "kode": "CT-001", "tipe": "masuk", "qty": 5, "lokasi": "Gudang Raw Material", "client_id": "GS-111" },
    { "kode": "CT-002", "tipe": "keluar", "qty": 2, "lokasi": "Gudang Raw Material", "client_id": "GS-222" }
  ]
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Proses sinkronisasi selesai",
  "synced_count": 2,
  "failed": []
}
```

> **De-duplikasi**: Jika `client_id` sudah ada di database, transaksi akan di-skip secara silent.

---

### GET `/transactions/stats`

**Response 200**:
```json
{
  "success": true,
  "stats": {
    "total_masuk": 1250,
    "total_keluar": 340,
    "transaksi_hari_ini": 5
  }
}
```

---

## 5. Master Lokasi

### GET `/locations`

List semua lokasi beserta relasi parent.

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "parent_id": null,
      "nama": "Gedung A",
      "tipe": "gedung",
      "kode": "GDG-A",
      "parent": null
    },
    {
      "id": 2,
      "parent_id": 1,
      "nama": "Lantai 1",
      "tipe": "lantai",
      "kode": "GDG-A-L1",
      "parent": { "id": 1, "nama": "Gedung A" }
    }
  ]
}
```

---

### POST `/locations`

Tambah lokasi baru.

**Request**:
```json
{
  "nama": "Rak A-1",
  "tipe": "rak",
  "kode": "GDG-A-L1-RK-A1",
  "parent_id": 3
}
```

`tipe` valid: `gedung`, `lantai`, `ruangan`, `rak`

**Response 201**:
```json
{
  "success": true,
  "message": "Lokasi berhasil ditambahkan",
  "data": { "id": 4, "..." }
}
```

---

### DELETE `/locations/{id}`

Hapus lokasi. **Perhatian**: Pastikan tidak ada asset aktif di lokasi ini sebelum menghapus.

---

## 6. Master Vendor

### GET `/vendors`

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama": "PT Supplier Elektronik",
      "kontak": "Budi Santoso",
      "telepon": "081234567890",
      "alamat": "Jl. Industri No. 1"
    }
  ]
}
```

---

### POST `/vendors`

**Request**:
```json
{
  "nama": "PT Supplier Elektronik",
  "kontak": "Budi Santoso",
  "telepon": "081234567890",
  "alamat": "Jl. Industri No. 1"
}
```

---

### DELETE `/vendors/{id}`

Hapus vendor. Transaksi terkait dipertahankan (`vendor_id` menjadi null).

---

## 7. Master Customer

### GET `/customers`

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama": "SDN 01 Pagi",
      "kontak": "Pak Kepala Sekolah",
      "telepon": "021-1234567",
      "alamat": "Jl. Merdeka No. 1"
    }
  ]
}
```

---

### POST `/customers`

**Request**:
```json
{
  "nama": "SDN 01 Pagi",
  "kontak": "Pak Kepala Sekolah",
  "telepon": "021-1234567",
  "alamat": "Jl. Merdeka No. 1"
}
```

---

### DELETE `/customers/{id}`

Hapus customer. Transaksi sewa terkait dipertahankan (`customer_id` menjadi null).

---

## 8. Master Kategori

### GET `/categories`

List semua kategori barang, berurutan sesuai abjad nama.

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nama": "Elektronik",
      "parent_id": null,
      "parent": null
    },
    {
      "id": 2,
      "nama": "Laptop",
      "parent_id": 1,
      "parent": {
        "id": 1,
        "nama": "Elektronik"
      }
    }
  ]
}
```

---

### POST `/categories`

Tambah kategori baru.

**Request**:
```json
{
  "nama": "Laptop",
  "parent_id": 1
}
```

**Response 201**:
```json
{
  "success": true,
  "message": "Kategori berhasil ditambahkan",
  "data": {
    "id": 2,
    "nama": "Laptop",
    "parent_id": 1,
    "parent": {
      "id": 1,
      "nama": "Elektronik"
    }
  }
}
```

---

### PUT `/categories/{id}`

Update nama atau parent kategori. Validasi mencegah kategori menunjuk dirinya sendiri sebagai parent.

**Request**:
```json
{
  "nama": "Laptop HP",
  "parent_id": 1
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Kategori berhasil diperbarui",
  "data": {
    "id": 2,
    "nama": "Laptop HP",
    "parent_id": 1,
    "parent": {
      "id": 1,
      "nama": "Elektronik"
    }
  }
}
```

**Response 422** (Jika parent_id = id itu sendiri):
```json
{
  "message": "The selected parent id is invalid.",
  "errors": {
    "parent_id": [
      "The selected parent id is invalid."
    ]
  }
}
```

---

### DELETE `/categories/{id}`

Hapus kategori. Sub-kategori yang terikat akan diset `parent_id` nya menjadi null (menjadi Kategori Utama).

---

## 9. Asset Terserialisasi (Sewa & Bekas)

Modul ini digunakan untuk melacak aset **per unit** (per serial number) — khususnya untuk barang yang disewakan ke sekolah-sekolah klien.

### GET `/assets`

List semua unit asset dengan filter opsional.

**Query Parameters**:
| Param | Tipe | Deskripsi |
|-------|------|-----------|
| `tipe` | string | Filter: `normal`, `sewa`, atau `bekas` |
| `status` | string | Filter: `good`, `not_good`, `lengkap`, `tidak_lengkap` |

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "item_id": 5,
      "location_id": 2,
      "serial_number": "LPT-001-HP",
      "tipe_kepemilikan": "sewa",
      "status": "good",
      "is_rented": false,
      "item": { "id": 5, "kode": "LPT-HP", "nama": "Laptop HP" },
      "location": { "id": 2, "nama": "Gudang Utama" }
    }
  ]
}
```

---

### POST `/assets`

Daftarkan unit asset baru. Akan otomatis membuat ledger entry transaksi `masuk` bertipe `pembelian`.

**Request**:
```json
{
  "item_id": 5,
  "serial_number": "LPT-001-HP",
  "location_id": 2,
  "tipe_kepemilikan": "sewa",
  "status": "good",
  "vendor_id": 1,
  "no_po": "PO-2026-001",
  "no_dokumen": "SJ-001",
  "catatan": "Laptop baru dari vendor"
}
```

**Response 201**:
```json
{
  "success": true,
  "message": "Unit Asset berhasil didaftarkan",
  "data": { "id": 1, "serial_number": "LPT-001-HP", "..." }
}
```

---

### POST `/assets/mutate`

Pindah lokasi fisik asset. Akan membuat 2 ledger entry: `keluar` dari lokasi lama + `masuk` ke lokasi baru, keduanya bertipe `mutasi_lokasi`.

**Request**:
```json
{
  "asset_id": 1,
  "location_id": 3,
  "catatan": "Dipindahkan ke ruangan baru"
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Lokasi asset berhasil dipindahkan",
  "data": { "id": 1, "location_id": 3, "..." }
}
```

---

### POST `/assets/rent-out`

Sewa keluar asset ke customer. Dapat memproses **banyak unit sekaligus**. Sistem menolak unit yang `is_rented = true`.

**Request**:
```json
{
  "customer_id": 1,
  "asset_ids": [1, 2, 3],
  "no_dokumen": "SJ-SEWA-001",
  "keperluan": "Program Ekskul Semester 1 2026",
  "catatan": "Laptop untuk SDN 01 Pagi"
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Transaksi sewa keluar berhasil diproses",
  "data": [{ "id": 1, "is_rented": true, "..." }]
}
```

**Response 422** (Unit sedang disewa):
```json
{
  "success": false,
  "message": "Unit dengan Serial Number LPT-001-HP sedang dalam status disewa."
}
```

---

### POST `/assets/rent-return`

Terima kembali asset dari penyewaan. Mendukung **batch return** banyak unit sekaligus. Update kondisi fisik dan lokasi penempatan kembali.

**Request**:
```json
{
  "returns": [
    {
      "asset_id": 1,
      "status": "good",
      "location_id": 2
    },
    {
      "asset_id": 2,
      "status": "not_good",
      "location_id": 2
    }
  ],
  "no_dokumen": "SJ-KEMBALI-001",
  "catatan": "Pengembalian akhir semester"
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "Transaksi pengembalian sewa berhasil diproses",
  "data": [{ "id": 1, "is_rented": false, "status": "good", "..." }]
}
```

---

### POST `/assets/{id}/status`

Update kondisi fisik asset (tanpa memindahkan lokasi).

**Request**:
```json
{
  "status": "not_good"
}
```

`status` valid: `good`, `not_good`, `lengkap`, `tidak_lengkap`

---

### GET `/assets/{id}/card`

Riwayat lengkap transaksi satu unit asset (kartu asset).

**Response 200**:
```json
{
  "success": true,
  "data": {
    "asset": {
      "id": 1,
      "serial_number": "LPT-001-HP",
      "tipe_kepemilikan": "sewa",
      "status": "good",
      "is_rented": false,
      "item": { "kode": "LPT-HP", "nama": "Laptop HP" },
      "location": { "nama": "Gudang Utama" }
    },
    "transactions": [
      {
        "id": 10,
        "tipe": "masuk",
        "tipe_detail": "pembelian",
        "qty": 1,
        "transaction_date": "2026-07-03",
        "vendor": { "nama": "PT Supplier Elektronik" },
        "customer": null,
        "location": { "nama": "Gudang Utama" },
        "petugas": "Admin Gudang"
      },
      {
        "id": 15,
        "tipe": "keluar",
        "tipe_detail": "sewa_keluar",
        "qty": 1,
        "transaction_date": "2026-07-10",
        "customer": { "nama": "SDN 01 Pagi" },
        "petugas": "Admin Gudang"
      }
    ]
  }
}
```

---

## 10. Export

### GET `/export/excel`

Download file Excel berisi seluruh data stok dan transaksi.

**Query Parameters**:
| Param | Tipe | Deskripsi |
|-------|------|-----------|
| `gudang` | string | Filter gudang (opsional) |
| `token` | string | Auth token (untuk download di tab baru) |

**Response**: File download `ErlassGudangApp_YYYY-MM-DD.xlsx`

---

### GET `/export/pdf/{item_id}`

Download PDF Kartu Stok untuk satu barang.

**Query Parameters**:
| Param | Tipe | Default | Deskripsi |
|-------|------|---------|-----------| 
| `tahun` | int | Tahun sekarang | Tahun kartu stok |
| `gudang` | string | — | Filter gudang |
| `token` | string | — | Auth token |

**Response**: File download `KartuStok_{KODE}_{TAHUN}.pdf`

---

## 11. Manajemen Pengguna (Admin)

Seluruh endpoint di bawah ini memerlukan otentikasi Bearer Token dan hanya dapat diakses oleh user dengan role `admin`.

### GET `/users`
Mendapatkan daftar semua pengguna.

**Response 200**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin Gudang",
      "email": null,
      "nik": "admin",
      "role": "admin",
      "created_at": "2026-07-06T03:57:36.000000Z",
      "updated_at": "2026-07-06T03:57:36.000000Z"
    }
  ]
}
```

### POST `/users`
Menambahkan pengguna baru.

**Request**:
```json
{
  "nik": "12347",
  "name": "Budi Santoso",
  "email": "budi@erlass.institute",
  "password": "password123",
  "role": "petugas"
}
```
*Catatan: `email` bersifat opsional (nullable).*

**Response 201**:
```json
{
  "success": true,
  "message": "Pengguna berhasil ditambahkan",
  "data": {
    "nik": "12347",
    "name": "Budi Santoso",
    "email": "budi@erlass.institute",
    "role": "petugas",
    "id": 3,
    "created_at": "2026-07-06T04:24:00.000000Z",
    "updated_at": "2026-07-06T04:24:00.000000Z"
  }
}
```

### PUT `/users/{id}`
Memperbarui data pengguna.

**Request**:
```json
{
  "nik": "12347",
  "name": "Budi Santoso Edit",
  "email": null,
  "password": "passwordbaru",
  "role": "petugas"
}
```
*Catatan: Kosongkan field `password` jika tidak ingin mengubah password.*

**Response 200**:
```json
{
  "success": true,
  "message": "Pengguna berhasil diperbarui",
  "data": {
    "id": 3,
    "nik": "12347",
    "name": "Budi Santoso Edit",
    "email": null,
    "role": "petugas",
    "created_at": "2026-07-06T04:24:00.000000Z",
    "updated_at": "2026-07-06T04:25:00.000000Z"
  }
}
```

### DELETE `/users/{id}`
Menghapus pengguna. Admin tidak dapat menghapus dirinya sendiri.

**Response 200**:
```json
{
  "success": true,
  "message": "Pengguna berhasil dihapus"
}
```

### POST `/users/import`
Impor massal pengguna menggunakan data array (biasanya hasil parsing CSV).

**Request**:
```json
{
  "users": [
    {
      "nik": "12348",
      "name": "Siti Aminah",
      "password": "password456",
      "role": "petugas"
    }
  ]
}
```

**Response 200**:
```json
{
  "success": true,
  "message": "1 pengguna berhasil diimpor.",
  "imported_count": 1,
  "errors": []
}
```
*Catatan: Jika ada NIK yang duplikat, sistem akan melewatkan baris tersebut dan mengirimkan detail kesalahan di array `errors`.*

---

## 12. Modul Lapangan MIKMS & Cascading BOM

Semua endpoint MIKMS memiliki prefix `/api/mikms` dan membutuhkan token autentikasi Sanctum.

### 12.1 GET `/mikms/dashboard`
Mengambil ringkasan metrik statistik operasional boks, perakitan, pengiriman sekolah, retur, dan repair.

**Response 200**:
```json
{
  "status": "success",
  "data": {
    "stats": {
      "total_modules": 10,
      "total_boxes": 34,
      "boxes_ready": 20,
      "boxes_on_loan": 10,
      "boxes_repair": 4,
      "total_produced": 150,
      "total_shipments": 12,
      "total_returns": 8,
      "total_repairs": 3
    },
    "recent_productions": [...],
    "recent_shipments": [...],
    "recent_returns": [...],
    "recent_repairs": [...]
  }
}
```

### 12.2 GET `/mikms/modules`
Daftar seluruh master modul perakitan beserta spesifikasi komponen Bill of Materials (BOM) dan stok komponen saat ini.

**Response 200**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "code": "M01",
      "name": "Controller Kit",
      "description": "Micro:bit V2 Controller dan kabel power/koneksi",
      "components": [
        {
          "id": 1,
          "item_id": 10,
          "item_code": "CT-001",
          "item_name": "Micro:bit V2",
          "item_unit": "Pcs",
          "quantity_per_module": 1,
          "box_category": "BOX 1 – Beginner Kit",
          "current_stock": 25
        }
      ]
    }
  ]
}
```

### 12.3 GET `/mikms/boxes` & POST `/mikms/boxes`
Mengelola master boks kit Micro:bit.

**Filter Query GET**: `status` (READY, ON_LOAN, REPAIR, DAMAGED), `search` (kode/kategori/program).

**POST Request (Tambah Boks)**:
```json
{
  "box_code": "BOX-MLK-001",
  "category": "BOX 1 – Beginner Kit",
  "program_code": "MLK",
  "status": "READY",
  "notes": "Kit pemula lengkap"
}
```

**Response 200**:
```json
{
  "status": "success",
  "message": "Box Kit berhasil ditambahkan",
  "data": {
    "id": 1,
    "box_code": "BOX-MLK-001",
    "category": "BOX 1 – Beginner Kit",
    "program_code": "MLK",
    "status": "READY",
    "notes": "Kit pemula lengkap"
  }
}
```

### 12.4 POST `/mikms/productions`
Mencatat hasil perakitan modul. **Sistem secara otomatis memeriksa stok dan memotong komponen bahan mentah di tabel transaksi umum (`transactions`).**

**Request**:
```json
{
  "production_date": "2026-09-17",
  "module_id": 1,
  "quantity_produced": 5,
  "produced_by": "Ahmad Petugas",
  "notes": "Batch perakitan pagi"
}
```

**Response 200**:
```json
{
  "status": "success",
  "message": "Berhasil memproduksi 5 unit Modul M01 (Controller Kit). Stok komponen otomatis terpotong.",
  "data": {
    "id": 12,
    "production_date": "2026-09-17",
    "module_id": 1,
    "quantity_produced": 5,
    "produced_by": "Ahmad Petugas"
  }
}
```

*Jika salah satu komponen bahan baku kurang, server mengembalikan 422 dengan rincian nama item dan jumlah kekurangannya.*

### 12.5 POST `/mikms/qc-logs`
Mencatat hasil pemeriksaan kendali mutu (Quality Control) modul atau boks kit.

**Request**:
```json
{
  "qc_date": "2026-09-17",
  "module_id": 1,
  "target_box_code": "BOX-MLK-001",
  "status_qc": "LOLOS",
  "defect_notes": null,
  "checked_by": "Budi QC",
  "notes": "Semua pin berfungsi normal"
}
```

### 12.6 POST `/mikms/shipments`
Mencatat surat jalan pengiriman boks kit ke sekolah. Boks yang tercatat otomatis diubah statusnya menjadi `ON_LOAN`.

**Request**:
```json
{
  "shipment_date": "2026-09-17",
  "box_code": "BOX-MLK-001",
  "program_code": "MLK",
  "program_name": "Microbit Learning Kit",
  "school_name": "SMP Negeri 1 Jakarta",
  "quantity_box": 1,
  "shipped_by": "Doni Logistik",
  "received_by_school": "Pak Guru Joko",
  "notes": "Pengiriman tahap 1"
}
```

### 12.7 POST `/mikms/returns`
Mencatat pengembalian boks kit dari sekolah. Jika kondisi `LENGKAP` boks kembali `READY`; jika `RUSAK` status boks berubah menjadi `REPAIR`; jika `HILANG` status berubah menjadi `DAMAGED`.

**Request**:
```json
{
  "return_date": "2026-09-17",
  "box_code": "BOX-MLK-001",
  "school_name": "SMP Negeri 1 Jakarta",
  "condition": "RUSAK",
  "problematic_item_code": "OP-001",
  "problematic_item_name": "LED Merah",
  "problematic_quantity": 2,
  "received_by": "Ahmad Petugas",
  "notes": "LED kaki patah"
}
```

### 12.8 POST `/mikms/repairs`
Mencatat tindakan perbaikan barang/komponen boks. Jika `repair_result` adalah `BERHASIL`, status boks terkait otomatis dipulihkan menjadi `READY`.

**Request**:
```json
{
  "repair_date": "2026-09-17",
  "item_code": "OP-001",
  "item_name": "LED Merah",
  "asset_id": "BOX-MLK-001",
  "damage_type": "Kaki komponen patah",
  "repair_action": "Solder ulang dan penggantian 1 pcs LED",
  "quantity": 1,
  "repair_result": "BERHASIL",
  "repaired_by": "Teknisi Rudi",
  "notes": "Selesai ditest normal"
}
```

### 12.9 POST `/mikms/stock-opnames`
Mencatat hasil pemeriksaan fisik inventaris lapangan dan menghitung selisih antara stok sistem dan fisik.

**Request**:
```json
{
  "opname_date": "2026-09-17",
  "item_code": "CT-001",
  "physical_quantity": 24,
  "difference_reason": "1 pcs tertinggal di lab",
  "counted_by": "Ahmad Petugas"
}
```

### 12.10 GET `/mikms/logs`
Mengambil data riwayat aktivitas MIKMS dengan pagination (20 baris per halaman).
**Query param `type`**: `productions`, `qc`, `shipments`, `returns`, `repairs`, `opname`, `package_orders`.

---

### 12.11 Smart Cascading BOM Engine

Engine ini menangani pesanan paket kit utuh (misal: 5 Microbit Learning Kit / MLK). Sistem memprioritaskan penggunaan stok modul jadi yang sudah dirakit di rak (`mikms_module_stocks`), dan secara otomatis mem-breakdown modul yang belum dirakit ke komponen dasar (*raw materials*).

#### GET `/mikms/package-simulate`
Simulasi (dry-run / preview) kebutuhan modul dan bahan baku tanpa mengubah data di database.

**Query Parameter**:
- `program_code`: `MLK` atau `ROBOTIC` (wajib)
- `package_qty`: Jumlah paket yang dipesan (integer 1-100, wajib)

**Response 200**:
```json
{
  "status": "success",
  "data": {
    "program_code": "MLK",
    "program_name": "Microbit Learning Kit",
    "package_qty": 5,
    "total_components_per_package": 95,
    "total_modules_from_stock": 14,
    "total_modules_to_assemble": 21,
    "can_fulfill": true,
    "summary": "5 paket Microbit Learning Kit. 14 modul diambil dari rak. 21 modul perlu dirakit dari bahan baku. ✅ Dapat dipenuhi.",
    "modules_breakdown": [
      {
        "module_id": 1,
        "code": "M01",
        "name": "Controller Kit",
        "needed": 5,
        "ready_stock": 2,
        "from_stock": 2,
        "to_assemble": 3
      }
    ],
    "raw_materials_needed": [
      {
        "item_id": 10,
        "code": "CT-001",
        "name": "Micro:bit V2",
        "unit": "Pcs",
        "needed": 3,
        "available": 10,
        "sufficient": true
      }
    ],
    "shortages": []
  }
}
```

#### POST `/mikms/package-orders`
Mengeksekusi pesanan paket kit secara atomik dalam satu transaksi database:
1. Mengunci dan memotong stok modul siap pakai di `mikms_module_stocks` (Tier 1).
2. Memeriksa kecukupan stok bahan baku untuk sisa modul yang harus dirakit.
3. Mencatat transaksi keluar bahan baku di `transactions` dan log produksi otomatis di `mikms_productions` (Tier 2).
4. Menyimpan data pesanan dan audit trail log deduksi di `mikms_package_orders`.

**Request**:
```json
{
  "order_date": "2026-09-17",
  "program_code": "MLK",
  "package_qty": 5,
  "customer_name": "SMA Negeri 8 Jakarta",
  "customer_id": 2,
  "ordered_by": "Ahmad Petugas",
  "notes": "Pesanan semester ganjil"
}
```

**Response 200**:
```json
{
  "status": "success",
  "message": "Pesanan 5 paket Microbit Learning Kit berhasil diproses. Stok telah dipotong.",
  "data": {
    "id": 1,
    "order_date": "2026-09-17",
    "program_code": "MLK",
    "program_name": "Microbit Learning Kit",
    "package_qty": 5,
    "status": "COMPLETED",
    "deduction_log": {
      "program": "MLK",
      "package_qty": 5,
      "modules": [...],
      "raw_materials_deducted": [...]
    }
  }
}
```

#### GET `/mikms/package-orders`
List riwayat pesanan paket kit dengan pagination.

---

### 12.12 Manajemen Stok Modul Jadi

#### GET `/mikms/module-stocks`
Mengambil daftar stok modul jadi (M01-M10) yang siap pakai di rak.

**Response 200**:
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "module_id": 1,
      "module_code": "M01",
      "module_name": "Controller Kit",
      "stock_ready": 8,
      "updated_at": "2026-09-17 03:45:00"
    }
  ]
}
```

#### POST `/mikms/module-stocks/adjust`
Penyesuaian (koreksi/opname) manual terhadap stok modul siap pakai.

**Request**:
```json
{
  "module_id": 1,
  "adjustment": 3,
  "reason": "Ditemukan 3 unit modul M01 siap pakai pasca bongkar lab",
  "adjusted_by": "Admin Gudang"
}
```

---

### 12.13 GET `/mikms/export/excel`
Mengunduh workbook Excel terstruktur yang berisi seluruh data operasional MIKMS (Multi-sheet: Modules, Boxes, Productions, QC, Shipments, Returns, Repairs, Opname).

---

### 12.14 Master Program Kit (BOM Packages)
Manajemen master program kit pembelajaran/pelatihan yang menyusun paket modul MIKMS secara dinamis.

#### GET `/mikms/programs`
List seluruh program kit yang terdaftar.

**Query Parameter**:
- `active_only` (optional, boolean): `1` untuk hanya menampilkan program yang berstatus aktif (digunakan pada dropdown form Pesan Paket).

**Response (200 OK)**:
```json
{
  "status": "success",
  "success": true,
  "data": [
    {
      "id": 1,
      "code": "MLK",
      "name": "Microbit Learning Kit",
      "description": "Kit pembelajaran dasar micro:bit mencakup Controller, LED, Motion, Sensor, Power, Mech, Conn",
      "modules": ["M01", "M02", "M03", "M04", "M05", "M06", "M07"],
      "total_pcs": 95,
      "is_active": true,
      "created_at": "2026-09-17T05:00:00.000000Z",
      "updated_at": "2026-09-17T05:00:00.000000Z"
    },
    {
      "id": 2,
      "code": "ROBOTIC",
      "name": "Robotic Explorer Kit",
      "description": "Kit eksplorasi robotik mencakup Modul MIKMS ditambah Robotik Jimu",
      "modules": ["M01", "M02", "M07", "M08"],
      "total_pcs": 81,
      "is_active": true,
      "created_at": "2026-09-17T05:00:00.000000Z",
      "updated_at": "2026-09-17T05:00:00.000000Z"
    }
  ]
}
```

#### POST `/mikms/programs`
Mendaftarkan Program Kit baru beserta modul-modul MIKMS penyusunnya.

**Request Body**:
```json
{
  "code": "STEAM_ADV",
  "name": "STEAM Advanced Robot Kit",
  "description": "Paket lanjutan integrasi servo dan sensor",
  "modules": ["M01", "M03", "M04", "M07"],
  "is_active": true
}
```

**Response (201 Created)**:
```json
{
  "status": "success",
  "success": true,
  "message": "Program Kit STEAM Advanced Robot Kit (STEAM_ADV) berhasil ditambahkan.",
  "data": {
    "id": 3,
    "code": "STEAM_ADV",
    "name": "STEAM Advanced Robot Kit",
    "description": "Paket lanjutan integrasi servo dan sensor",
    "modules": ["M01", "M03", "M04", "M07"],
    "total_pcs": 62,
    "is_active": true,
    "created_at": "2026-09-17T06:00:00.000000Z",
    "updated_at": "2026-09-17T06:00:00.000000Z"
  }
}
```

#### PUT `/mikms/programs/{id}`
Memperbarui informasi nama, deskripsi, daftar modul penyusun, atau status aktif Program Kit.

**Request Body**:
```json
{
  "name": "STEAM Advanced Robot Kit V2",
  "description": "Kurikulum diperbarui dengan modul baterai",
  "modules": ["M01", "M03", "M04", "M05", "M07"],
  "is_active": true
}
```

**Response (200 OK)**:
```json
{
  "status": "success",
  "success": true,
  "message": "Program Kit STEAM_ADV berhasil diperbarui.",
  "data": {
    "id": 3,
    "code": "STEAM_ADV",
    "name": "STEAM Advanced Robot Kit V2",
    "description": "Kurikulum diperbarui dengan modul baterai",
    "modules": ["M01", "M03", "M04", "M05", "M07"],
    "total_pcs": 67,
    "is_active": true,
    "created_at": "2026-09-17T06:00:00.000000Z",
    "updated_at": "2026-09-17T06:05:00.000000Z"
  }
}
```

#### DELETE `/mikms/programs/{id}`
Menghapus Program Kit. Jika program telah memiliki riwayat transaksi (pesanan paket di `mikms_package_orders` atau pengiriman sekolah di `mikms_shipments`), sistem secara aman mengubah status menjadi nonaktif (`is_active = false`) agar integritas audit transaksi masa lalu tetap terjaga. Jika belum ada transaksi, data dihapus permanen.

**Response (200 OK)**:
```json
{
  "status": "success",
  "success": true,
  "message": "Program STEAM_ADV berhasil dihapus permanen."
}
```

---

## 13. Error Responses

Semua error menggunakan format konsisten:

### 401 Unauthorized
```json
{ "message": "Unauthenticated." }
```

### 403 Forbidden
```json
{
  "success": false,
  "message": "Akses ditolak. Hanya Admin yang dapat [aksi]."
}
```

### 404 Not Found
```json
{ "message": "No query results for model [App\\Models\\Item]." }
```

### 422 Validation Error
```json
{
  "message": "The kode field is required.",
  "errors": {
    "kode": ["The kode field is required."]
  }
}
```

### 500 Server Error
```json
{ "message": "Server Error" }
```
