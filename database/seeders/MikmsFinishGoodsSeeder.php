<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MikmsFinishGoodsSeeder extends Seeder
{
    public function run(): void
    {
        // ── 5 PRODUK FINISH GOOD ──────────────────────────────────────
        $products = [
            [
                'code'        => 'MLK',
                'name'        => 'Microbit Learning Kit',
                'type'        => 'assembled',
                'can_rent'    => true,
                'active'      => true,
                'description' => 'Kit pembelajaran Micro:bit lengkap. Dirakit dari modul-modul MIKMS.',
            ],
            [
                'code'        => 'ROBO',
                'name'        => 'Robotic Explorer',
                'type'        => 'bundle',
                'can_rent'    => true,
                'active'      => true,
                'description' => 'Paket kemasan gabungan 1 MLK + 1 JIMU (Opsi A: Boks kemasan luar tersendiri).',
            ],
            [
                'code'        => 'JIMU',
                'name'        => 'Jimu Trackbot',
                'type'        => 'direct',
                'can_rent'    => true,
                'active'      => true,
                'description' => 'Robot Jimu Trackbot. Masuk langsung dari vendor sebagai finish good.',
            ],
            [
                'code'        => 'ERBO',
                'name'        => 'Erboblox',
                'type'        => 'direct',
                'can_rent'    => true,
                'active'      => true,
                'description' => 'Kit Erboblox. Masuk langsung dari vendor sebagai finish good.',
            ],
            [
                'code'        => 'ALK',
                'name'        => 'Arduino Learning Kit',
                'type'        => 'direct',
                'can_rent'    => true,
                'active'      => true,
                'description' => 'Kit pembelajaran Arduino. Masuk langsung dari vendor sebagai finish good.',
            ],
        ];

        foreach ($products as $product) {
            DB::table('mikms_finish_goods')->updateOrInsert(
                ['code' => $product['code']],
                array_merge($product, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }

        // ── QC CHECKLIST TEMPLATES PER PRODUK ────────────────────────
        $mlkId  = DB::table('mikms_finish_goods')->where('code', 'MLK')->value('id');
        $roboId = DB::table('mikms_finish_goods')->where('code', 'ROBO')->value('id');
        $jimuId = DB::table('mikms_finish_goods')->where('code', 'JIMU')->value('id');
        $erboId = DB::table('mikms_finish_goods')->where('code', 'ERBO')->value('id');
        $alkId  = DB::table('mikms_finish_goods')->where('code', 'ALK')->value('id');

        $templates = [
            // MLK — Microbit Learning Kit
            ['product_id' => $mlkId, 'sort_order' => 1,  'check_item' => 'Power ON — LED indikator Micro:bit menyala normal'],
            ['product_id' => $mlkId, 'sort_order' => 2,  'check_item' => 'Koneksi USB terdeteksi oleh komputer'],
            ['product_id' => $mlkId, 'sort_order' => 3,  'check_item' => 'Upload firmware berhasil (test program blink)'],
            ['product_id' => $mlkId, 'sort_order' => 4,  'check_item' => 'Sensor ultrasonik HC-SR04 merespon jarak (< 30cm)'],
            ['product_id' => $mlkId, 'sort_order' => 5,  'check_item' => 'Modul sensor suhu/kelembaban aktif'],
            ['product_id' => $mlkId, 'sort_order' => 6,  'check_item' => 'Semua kabel dan konektor terpasang rapi, tidak ada yang longgar'],
            ['product_id' => $mlkId, 'sort_order' => 7,  'check_item' => 'Kondisi fisik boks — tidak retak, tidak penyok, label terbaca'],
            ['product_id' => $mlkId, 'sort_order' => 8,  'check_item' => 'Kelengkapan isi boks sesuai daftar komponen (BOM)'],

            // ROBO — Robotic Explorer
            ['product_id' => $roboId, 'sort_order' => 1, 'check_item' => 'Power ON — sistem elektronik aktif normal'],
            ['product_id' => $roboId, 'sort_order' => 2, 'check_item' => 'Motor servo SG90 berputar 180° mulus tanpa hambatan'],
            ['product_id' => $roboId, 'sort_order' => 3, 'check_item' => 'Sensor ultrasonik mendeteksi halangan dan robot berhenti'],
            ['product_id' => $roboId, 'sort_order' => 4, 'check_item' => 'Semua roda berputar sinkron (maju, mundur, belok)'],
            ['product_id' => $roboId, 'sort_order' => 5, 'check_item' => 'Koneksi wireless/remote berfungsi (jika ada)'],
            ['product_id' => $roboId, 'sort_order' => 6, 'check_item' => 'Kondisi rangka — tidak retak, sekrup terpasang semua'],
            ['product_id' => $roboId, 'sort_order' => 7, 'check_item' => 'Kondisi fisik boks — tidak retak, tidak penyok, label terbaca'],
            ['product_id' => $roboId, 'sort_order' => 8, 'check_item' => 'Kelengkapan isi boks sesuai daftar komponen (BOM)'],

            // JIMU — Jimu Trackbot
            ['product_id' => $jimuId, 'sort_order' => 1, 'check_item' => 'Power ON — indikator daya Jimu menyala'],
            ['product_id' => $jimuId, 'sort_order' => 2, 'check_item' => 'Koneksi Bluetooth/app berhasil terhubung'],
            ['product_id' => $jimuId, 'sort_order' => 3, 'check_item' => 'Semua servo Jimu merespon perintah gerak'],
            ['product_id' => $jimuId, 'sort_order' => 4, 'check_item' => 'Track/roda berjalan normal (maju, mundur)'],
            ['product_id' => $jimuId, 'sort_order' => 5, 'check_item' => 'Baterai terisi / charger tersedia'],
            ['product_id' => $jimuId, 'sort_order' => 6, 'check_item' => 'Kondisi fisik unit — tidak retak, semua part terpasang'],
            ['product_id' => $jimuId, 'sort_order' => 7, 'check_item' => 'Kondisi fisik boks — tidak retak, label terbaca, segel vendor utuh'],

            // ERBO — Erboblox
            ['product_id' => $erboId, 'sort_order' => 1, 'check_item' => 'Power ON — sistem Erboblox aktif'],
            ['product_id' => $erboId, 'sort_order' => 2, 'check_item' => 'Modul input/output merespon (sensor atau LED)'],
            ['product_id' => $erboId, 'sort_order' => 3, 'check_item' => 'Koneksi ke software/app berhasil'],
            ['product_id' => $erboId, 'sort_order' => 4, 'check_item' => 'Semua konektor blok terpasang dan tidak longgar'],
            ['product_id' => $erboId, 'sort_order' => 5, 'check_item' => 'Kondisi fisik unit — tidak retak, tidak penyok'],
            ['product_id' => $erboId, 'sort_order' => 6, 'check_item' => 'Kondisi fisik boks — tidak retak, label terbaca, segel vendor utuh'],
            ['product_id' => $erboId, 'sort_order' => 7, 'check_item' => 'Kelengkapan isi boks sesuai manifest vendor'],

            // ALK — Arduino Learning Kit
            ['product_id' => $alkId, 'sort_order' => 1, 'check_item' => 'Arduino Uno terdeteksi sebagai COM port di komputer'],
            ['product_id' => $alkId, 'sort_order' => 2, 'check_item' => 'Upload sketch blink berhasil tanpa error'],
            ['product_id' => $alkId, 'sort_order' => 3, 'check_item' => 'LED onboard (pin 13) berkedip sesuai program'],
            ['product_id' => $alkId, 'sort_order' => 4, 'check_item' => 'Breadboard dan komponen pasif tersedia (resistor, kapasitor)'],
            ['product_id' => $alkId, 'sort_order' => 5, 'check_item' => 'Kabel USB Arduino tersedia dan berfungsi'],
            ['product_id' => $alkId, 'sort_order' => 6, 'check_item' => 'Kondisi fisik unit — tidak retak, header pin lurus'],
            ['product_id' => $alkId, 'sort_order' => 7, 'check_item' => 'Kondisi fisik boks — tidak retak, label terbaca, segel vendor utuh'],
            ['product_id' => $alkId, 'sort_order' => 8, 'check_item' => 'Kelengkapan isi boks sesuai manifest vendor'],
        ];

        // Hapus template lama lalu insert ulang agar idempoten
        $productIds = array_filter([$mlkId, $roboId, $jimuId, $erboId, $alkId]);
        if (!empty($productIds)) {
            DB::table('mikms_qc_checklist_templates')->whereIn('product_id', $productIds)->delete();
        }

        $now = now();
        foreach ($templates as &$t) {
            $t['active']     = true;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
        }

        DB::table('mikms_qc_checklist_templates')->insert($templates);

        // ── SEED INITIAL STOCK UNITS (IN_STOCK & QC PASSED) ───────────
        $productsToSeed = [
            'MLK'  => ['id' => $mlkId,  'prefix' => 'MLK-',  'count' => 5],
            'ROBO' => ['id' => $roboId, 'prefix' => 'ROBO-', 'count' => 3],
            'JIMU' => ['id' => $jimuId, 'prefix' => 'JIMU-', 'count' => 5],
            'ERBO' => ['id' => $erboId, 'prefix' => 'ERBO-', 'count' => 5],
            'ALK'  => ['id' => $alkId,  'prefix' => 'ALK-',  'count' => 5],
        ];

        foreach ($productsToSeed as $code => $cfg) {
            if (!$cfg['id']) continue;
            for ($i = 1; $i <= $cfg['count']; $i++) {
                $serial = sprintf('%s%05d', $cfg['prefix'], $i);
                
                // Pastikan tidak menduplikasi jika unit sudah ada
                $existing = DB::table('mikms_units')->where('serial_number', $serial)->first();
                if (!$existing) {
                    $unitId = DB::table('mikms_units')->insertGetId([
                        'serial_number' => $serial,
                        'product_id'    => $cfg['id'],
                        'status'        => 'in_stock',
                        'source_type'   => $code === 'MLK' ? 'assembly' : ($code === 'ROBO' ? 'bundle' : 'vendor'),
                        'notes'         => 'Initial ready stock Finish Goods',
                        'created_at'    => now()->subDays(2),
                        'updated_at'    => now()->subDays(2),
                    ]);

                    // QC Pass log
                    DB::table('mikms_qc_logs')->insert([
                        'unit_id'            => $unitId,
                        'qc_date'            => now()->subDays(2)->toDateString(),
                        'qc_type'            => 'first_assembly',
                        'status_qc'          => 'PASS',
                        'checklist_results'  => json_encode(['inspeksi_keseluruhan' => 'PASS']),
                        'checked_by'         => 'QC Team Erlass',
                        'notes'              => 'Lolos inspeksi QC standar operasional.',
                        'created_at'         => now()->subDays(2),
                        'updated_at'         => now()->subDays(2),
                    ]);
                }
            }
        }

        $this->command->info('✅ MIKMS: 5 finish goods + QC templates + ready stock units seeded.');
    }
}

