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
| POST | `/auth/login` | ❌ | — | Login via PIN atau Email |
| POST | `/auth/logout` | ✅ | Semua | Logout & revoke token |
| GET | `/items` | ✅ | Semua | List semua master barang |
| POST | `/items` | ✅ | 🔒 Admin | Tambah barang baru |
| GET | `/items/{id}` | ✅ | Semua | Detail satu barang |
| PUT | `/items/{id}` | ✅ | 🔒 Admin | Update barang |
| DELETE | `/items/{id}` | ✅ | 🔒 Admin | Hapus barang (soft delete) |
| GET | `/stock` | ✅ | Semua | Status stok semua barang |
| GET | `/stock/{item_id}/card` | ✅ | Semua | Kartu stok per barang |
| GET | `/transactions` | ✅ | Semua | Riwayat transaksi |
| GET | `/transactions/stats` | ✅ | Semua | Statistik ringkasan |
| POST | `/transactions` | ✅ | Semua | Simpan transaksi (single/bulk) |
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
| GET | `/assets` | ✅ | Semua | List unit asset terserialisasi |
| POST | `/assets` | ✅ | Semua | Daftarkan unit asset baru |
| POST | `/assets/mutate` | ✅ | Semua | Pindah lokasi asset |
| POST | `/assets/rent-out` | ✅ | Semua | Sewa keluar asset ke customer |
| POST | `/assets/rent-return` | ✅ | Semua | Terima kembali asset dari sewa |
| POST | `/assets/{id}/status` | ✅ | Semua | Update kondisi fisik asset |
| GET | `/assets/{id}/card` | ✅ | Semua | Riwayat transaksi satu unit asset |
| GET | `/export/excel` | ✅ | Semua | Download Excel |
| GET | `/export/pdf/{item_id}` | ✅ | Semua | Download PDF Kartu Stok |

> 🔒 = Hanya role `admin` (untuk endpoint Items CRUD). Endpoint Master Data lainnya (locations, vendors, customers) dapat diakses oleh semua role yang terautentikasi.

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
      "kode": "MSJ01",
      "nama": "Modul Scratch Jilid 01",
      "satuan": "pcs",
      "produk": "Modul",
      "komponen": null,
      "lokasi_default": null,
      "min_stok": 5,
      "deskripsi": null,
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
    "kode": "MSJ01",
    "nama": "Modul Scratch Jilid 01",
    "satuan": "pcs",
    "produk": "Modul",
    "komponen": null,
    "lokasi_default": null,
    "min_stok": 5,
    "deskripsi": null,
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
        "kode": "MSJ01",
        "nama": "Modul Scratch Jilid 01",
        "satuan": "pcs",
        "produk": "Modul",
        "komponen": null,
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
    "item": { "id": 1, "kode": "MSJ01", "nama": "..." },
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
        "kode": "MSJ01",
        "nama": "Modul Scratch Jilid 01"
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
  "kode": "MSJ01",
  "tipe": "masuk",
  "qty": 10,
  "transaction_date": "2026-06-29",
  "lokasi": "Gudang Utama",
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
  "message": "Stok tidak cukup untuk MSJ01. Tersedia: 3"
}
```

#### Mode Bulk (Offline Sync)

**Request**:
```json
{
  "transactions": [
    { "kode": "MSJ01", "tipe": "masuk", "qty": 5, "lokasi": "Gudang Utama", "client_id": "GS-111" },
    { "kode": "MSJ02", "tipe": "keluar", "qty": 2, "lokasi": "Gudang Raw Material", "client_id": "GS-222" }
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

## 12. Error Responses

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
