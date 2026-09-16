<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Item;
use App\Models\Location;
use App\Models\Vendor;
use App\Models\Customer;
use App\Models\Asset;
use App\Models\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Default User
        User::updateOrCreate(
            ['email' => 'gudang@erlass.institute'],
            [
                'name' => 'Petugas Gudang',
                'password' => Hash::make('password'),
                'pin' => '000000',
                'nik' => '12345',
                'role' => 'petugas',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@erlass.institute'],
            [
                'name' => 'Admin Gudang',
                'password' => Hash::make('password'),
                'pin' => '123456',
                'nik' => 'admin',
                'role' => 'admin',
            ]
        );

        $defaultItems = [
            // ── PRODUK UNTUK PENJUALAN (EPI) ──────────────────────
            ['kode'=>'MSJ01', 'nama'=>'Modul Scratch Jilid 1', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ02', 'nama'=>'Modul Scratch Jilid 2', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ03', 'nama'=>'Modul Scratch Jilid 3', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ04', 'nama'=>'Modul Scratch Jilid 4', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ05', 'nama'=>'Modul Scratch Jilid 5', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ06', 'nama'=>'Modul Scratch Jilid 6', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ07', 'nama'=>'Modul Project Scratch', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Scratch'],
            ['kode'=>'MSJ08', 'nama'=>'Modul Project Pictoblox AI', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul AI'],
            ['kode'=>'MSJ09', 'nama'=>'Modul Python', 'satuan'=>'pcs', 'produk'=>'Modul', 'komponen'=>'Modul Python'],
            ['kode'=>'MB01',  'nama'=>'Microbit Beginner', 'satuan'=>'pcs', 'produk'=>'Microbit Learning Kit', 'komponen'=>'Beginner Micro'],
            ['kode'=>'MLK01', 'nama'=>'Microbit Learning Kit', 'satuan'=>'pcs', 'produk'=>'Microbit Learning Kit', 'komponen'=>'Full Kit'],
            ['kode'=>'RE01',  'nama'=>'Robotic Explorer', 'satuan'=>'unit', 'produk'=>'Robotic Explorer', 'komponen'=>'Jimu'],
            ['kode'=>'RJ01',  'nama'=>'Robotik Jimu', 'satuan'=>'unit', 'produk'=>'Robotic Explorer', 'komponen'=>'Jimu'],
            ['kode'=>'ALK01', 'nama'=>'Arduino Learning Kit', 'satuan'=>'pcs', 'produk'=>'Arduino Learning Kit', 'komponen'=>'Arduino Kit'],

            // ── PRODUK UNTUK SEWA (SW) ───────────────────────────
            ['kode'=>'SMB01',  'nama'=>'Sewa Microbit Beginner', 'satuan'=>'unit', 'produk'=>'Sewa', 'komponen'=>'Microbit'],
            ['kode'=>'SMLK01', 'nama'=>'Sewa Microbit Learning Kit', 'satuan'=>'unit', 'produk'=>'Sewa', 'komponen'=>'Microbit'],
            ['kode'=>'SRE01',  'nama'=>'Sewa Robotik Explorer', 'satuan'=>'unit', 'produk'=>'Sewa', 'komponen'=>'Jimu'],
            ['kode'=>'SRJ01',  'nama'=>'Sewa Robotik Jimu', 'satuan'=>'unit', 'produk'=>'Sewa', 'komponen'=>'Jimu'],
            ['kode'=>'SALK01', 'nama'=>'Sewa Arduino Learning Kit', 'satuan'=>'unit', 'produk'=>'Sewa', 'komponen'=>'Arduino'],

            // ── PRODUK UNTUK MARKETING (M) ───────────────────────
            ['kode'=>'MMB01',  'nama'=>'Microbit Beginner (Marketing)', 'satuan'=>'pcs', 'produk'=>'Marketing', 'komponen'=>'Microbit'],
            ['kode'=>'MMLK01', 'nama'=>'Microbit Learning Kit (Marketing)', 'satuan'=>'pcs', 'produk'=>'Marketing', 'komponen'=>'Microbit'],
            ['kode'=>'MRE01',  'nama'=>'Robotic Explorer (Marketing)', 'satuan'=>'unit', 'produk'=>'Marketing', 'komponen'=>'Jimu'],
            ['kode'=>'MRJ01',  'nama'=>'Robotik Jimu (Marketing)', 'satuan'=>'unit', 'produk'=>'Marketing', 'komponen'=>'Jimu'],
            ['kode'=>'MALK01', 'nama'=>'Arduino Learning Kit (Marketing)', 'satuan'=>'pcs', 'produk'=>'Marketing', 'komponen'=>'Arduino'],

            // ── ITEM UMUM / SHARED ───────────────────────────────
            ['kode'=>'ARD-001','nama'=>'Arduino Uno R3 Atmega328P', 'satuan'=>'pcs', 'produk'=>'Umum', 'komponen'=>'Shared'],
            ['kode'=>'SRV-001','nama'=>'Motor Servo SG90', 'satuan'=>'pcs', 'produk'=>'Umum', 'komponen'=>'Shared'],
            ['kode'=>'SNS-001','nama'=>'Sensor Ultrasonic HC-SR04', 'satuan'=>'pcs', 'produk'=>'Umum', 'komponen'=>'Shared'],
            ['kode'=>'LED-R01','nama'=>'LED 5mm Merah', 'satuan'=>'pcs', 'produk'=>'Umum', 'komponen'=>'Shared'],
            ['kode'=>'KBL-MM1','nama'=>'Kabel Jumper Male-Male', 'satuan'=>'pcs', 'produk'=>'Umum', 'komponen'=>'Shared'],
        ];

        foreach ($defaultItems as $itemData) {
            Item::updateOrCreate(
                ['kode' => $itemData['kode']],
                [
                    'nama' => $itemData['nama'],
                    'satuan' => $itemData['satuan'],
                    'produk' => $itemData['produk'],
                    'komponen' => $itemData['komponen'],
                    'min_stok' => 5,
                    'is_active' => true,
                ]
            );
        }

        // ── MASTER LOKASI BERTINGKAT ───────────────────────────
        $gedung = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA'],
            ['nama' => 'Gedung Utama', 'tipe' => 'gedung']
        );

        $lantai1 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L1'],
            ['nama' => 'Lantai 1', 'tipe' => 'lantai', 'parent_id' => $gedung->id]
        );

        $gudangUtama = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L1-UTAMA'],
            ['nama' => 'Gudang Utama', 'tipe' => 'ruangan', 'parent_id' => $lantai1->id]
        );

        $rakA1 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L1-UTAMA-RA01'],
            ['nama' => 'Rak A-01', 'tipe' => 'rak', 'parent_id' => $gudangUtama->id]
        );

        $rakA2 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L1-UTAMA-RA02'],
            ['nama' => 'Rak A-02', 'tipe' => 'rak', 'parent_id' => $gudangUtama->id]
        );

        $lantai2 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L2'],
            ['nama' => 'Lantai 2', 'tipe' => 'lantai', 'parent_id' => $gedung->id]
        );

        $gudangSewa = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L2-SEWA'],
            ['nama' => 'Gudang Sewa', 'tipe' => 'ruangan', 'parent_id' => $lantai2->id]
        );

        $rakB1 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L2-SEWA-RB01'],
            ['nama' => 'Rak B-01', 'tipe' => 'rak', 'parent_id' => $gudangSewa->id]
        );

        $rakB2 = Location::updateOrCreate(
            ['kode' => 'GD-UTAMA-L2-SEWA-RB02'],
            ['nama' => 'Rak B-02', 'tipe' => 'rak', 'parent_id' => $gudangSewa->id]
        );

        // ── VENDORS & CUSTOMERS ────────────────────────────────
        $vendor1 = Vendor::updateOrCreate(
            ['nama' => 'PT. Robotic Indonesia'],
            ['kontak' => 'Budi', 'telepon' => '08123456789', 'alamat' => 'Jl. Teknologi No. 12, Jakarta']
        );

        $vendor2 = Vendor::updateOrCreate(
            ['nama' => 'CV. Media Edukasi'],
            ['kontak' => 'Rian', 'telepon' => '08987654321', 'alamat' => 'Jl. Pendidikan No. 45, Bandung']
        );

        $customer1 = Customer::updateOrCreate(
            ['nama' => 'SMP Negeri 1 Jakarta'],
            ['kontak' => 'Ani', 'telepon' => '08111111111', 'alamat' => 'Jl. Merdeka No. 1, Jakarta']
        );

        $customer2 = Customer::updateOrCreate(
            ['nama' => 'SMA Negeri 70 Jakarta'],
            ['kontak' => 'Joko', 'telepon' => '08222222222', 'alamat' => 'Jl. Bulungan No. 8, Jakarta']
        );

        // ── SEED ASSETS (Untuk barang sewa SMB01) ───────────────
        $itemSewa = Item::where('kode', 'SMB01')->first();
        if ($itemSewa) {
            // Asset 1: Ready in Rak B-01
            $asset1 = Asset::updateOrCreate(
                ['serial_number' => 'SEWA-MB-001'],
                [
                    'item_id' => $itemSewa->id,
                    'location_id' => $rakB1->id,
                    'tipe_kepemilikan' => 'sewa',
                    'status' => 'lengkap',
                    'is_rented' => false,
                ]
            );

            // Asset 2: Rented out by SMP Negeri 1 Jakarta
            $asset2 = Asset::updateOrCreate(
                ['serial_number' => 'SEWA-MB-002'],
                [
                    'item_id' => $itemSewa->id,
                    'location_id' => $rakB1->id,
                    'tipe_kepemilikan' => 'sewa',
                    'status' => 'lengkap',
                    'is_rented' => true,
                ]
            );

            // Asset 3: Broken/Not Good in Rak B-02
            $asset3 = Asset::updateOrCreate(
                ['serial_number' => 'SEWA-MB-003'],
                [
                    'item_id' => $itemSewa->id,
                    'location_id' => $rakB2->id,
                    'tipe_kepemilikan' => 'sewa',
                    'status' => 'tidak_lengkap',
                    'is_rented' => false,
                ]
            );

            // Add Transactions for these assets to reflect in card and totals
            // Transaction 1: Masuk (Pembelian dari Vendor)
            Transaction::updateOrCreate(
                ['client_id' => 'seed-tx-in-001'],
                [
                    'item_id' => $itemSewa->id,
                    'asset_id' => $asset1->id,
                    'vendor_id' => $vendor1->id,
                    'location_id' => $rakB1->id,
                    'tipe' => 'masuk',
                    'tipe_detail' => 'pembelian',
                    'qty' => 1,
                    'transaction_date' => now()->subDays(10),
                    'no_dokumen' => 'SJ-8801',
                    'no_po' => 'PO-2026-001',
                    'lokasi' => 'Gudang Sewa',
                    'petugas' => 'Admin Gudang',
                    'catatan' => 'Penerimaan unit sewa baru',
                ]
            );

            Transaction::updateOrCreate(
                ['client_id' => 'seed-tx-in-002'],
                [
                    'item_id' => $itemSewa->id,
                    'asset_id' => $asset2->id,
                    'vendor_id' => $vendor1->id,
                    'location_id' => $rakB1->id,
                    'tipe' => 'masuk',
                    'tipe_detail' => 'pembelian',
                    'qty' => 1,
                    'transaction_date' => now()->subDays(10),
                    'no_dokumen' => 'SJ-8801',
                    'no_po' => 'PO-2026-001',
                    'lokasi' => 'Gudang Sewa',
                    'petugas' => 'Admin Gudang',
                    'catatan' => 'Penerimaan unit sewa baru',
                ]
            );

            Transaction::updateOrCreate(
                ['client_id' => 'seed-tx-in-003'],
                [
                    'item_id' => $itemSewa->id,
                    'asset_id' => $asset3->id,
                    'vendor_id' => $vendor1->id,
                    'location_id' => $rakB2->id,
                    'tipe' => 'masuk',
                    'tipe_detail' => 'pembelian',
                    'qty' => 1,
                    'transaction_date' => now()->subDays(10),
                    'no_dokumen' => 'SJ-8801',
                    'no_po' => 'PO-2026-001',
                    'lokasi' => 'Gudang Sewa',
                    'petugas' => 'Admin Gudang',
                    'catatan' => 'Penerimaan unit sewa baru',
                ]
            );

            // Transaction 2: Sewa Keluar (Asset 2 rented to SMP Negeri 1 Jakarta)
            Transaction::updateOrCreate(
                ['client_id' => 'seed-tx-out-001'],
                [
                    'item_id' => $itemSewa->id,
                    'asset_id' => $asset2->id,
                    'customer_id' => $customer1->id,
                    'tipe' => 'keluar',
                    'tipe_detail' => 'sewa_keluar',
                    'qty' => 1,
                    'transaction_date' => now()->subDays(2),
                    'no_dokumen' => 'SPK-2026-04',
                    'lokasi' => 'Gudang Sewa',
                    'penerima' => 'SMP Negeri 1 Jakarta',
                    'keperluan' => 'Sewa Kegiatan Workshop',
                    'petugas' => 'Admin Gudang',
                    'catatan' => 'Penyewaan unit ke SMPN 1',
                ]
            );
        }
    }
}
