# 00 — Project Brief

## 1. Ringkasan
Erlass PWMS adalah aplikasi internal untuk mengendalikan siklus produk fisik Erlass dari master data, perencanaan produksi, pengadaan, perakitan, penyimpanan, QC, peminjaman/penyewaan, hingga distribusi. Sistem mengonsolidasikan data yang sebelumnya tersebar di spreadsheet dan proses manual menjadi alur dokumen yang dapat ditelusuri.

## 2. Masalah yang diselesaikan
- Data komponen, modul, box, finished goods, lokasi, dan transaksi stok perlu satu sumber data.
- Perhitungan kebutuhan komponen untuk produksi harus diturunkan dari BOM dan stok aktual.
- WIP perlu dilacak per tahap agar jumlah yang selesai, rusak, tertunda, dan sedang diproses tidak tercampur.
- Pengiriman ke GAP/vendor harus tetap terlihat sebagai persediaan milik Erlass di lokasi eksternal.
- Barang sewa/pinjaman harus dapat ditelusuri sampai tingkat unit/serial dan kelengkapan komponennya.
- QC pengembalian harus mencatat kerusakan, kehilangan, selisih, dan dokumen ganti rugi.
- Biaya material produksi harus dihitung dari konsumsi aktual, bukan hanya estimasi BOM.
- Riwayat transaksi, persetujuan, dan perubahan data harus dapat diaudit.

## 3. Sasaran bisnis
1. Meningkatkan akurasi stok dan visibilitas lokasi.
2. Mengurangi pembelian mendadak melalui MRP.
3. Menyediakan traceability dari komponen hingga finished good.
4. Mengontrol barang yang berada di vendor, dipinjam, disewa, atau dalam perjalanan.
5. Menyediakan laporan produksi, stok, QC, distribusi, rental, dan biaya material.
6. Mengurangi ketergantungan pada pencarian manual di spreadsheet/chat.

## 4. Pengguna utama
- Management
- Production Manager / Kepala Produksi
- Production Staff
- Procurement Staff
- Warehouse Manager / PIC Gudang
- Warehouse Staff
- QC Inspector
- Supporting / Sales / Marketing
- Rental Staff
- Finance (akses laporan biaya sesuai hak)
- Auditor
- Super Admin

## 5. Platform dan teknologi
- Backend: Laravel 12 (PHP).
- Frontend: React.js.
- Database: MySQL 8+ dengan InnoDB.
- API: REST JSON, prefix `/api/v1`.
- Authentication: Laravel Sanctum.
- Queue: Laravel Queue; database queue untuk deployment sederhana, dapat ditingkatkan ke Redis.
- File: local/S3-compatible storage melalui abstraction Laravel Filesystem.
- Deployment: Linux VPS; konfigurasi melalui environment variables.

## 6. Prinsip produk
- Modular Monolith, bukan microservices pada fase awal.
- Inventory ledger adalah sumber kebenaran pergerakan stok.
- Dokumen posted tidak dapat diedit atau dihapus; koreksi menggunakan reversal.
- Semua proses kritis memiliki status dan approval.
- Data master dipisahkan dari transaksi.
- Satu produk dapat menjadi bagian dari program/bundle yang berisi jasa dan barang fisik.
- Audit trail untuk perubahan dan tindakan sensitif.
