<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Item;
use App\Models\MikmsModule;
use App\Models\MikmsBom;
use App\Models\MikmsBox;

class MikmsInitialDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Kategori MIKMS
        $categories = [
            'Controller' => 'Microcontroller dan perlengkapan kabel data',
            'Output Device' => 'LED, Display, dan aktuator output visual',
            'Sensor' => 'Sensor ultrasonik dan modul input sensorik',
            'Motion' => 'Servo motor dan penggerak mekanis',
            'Power' => 'Baterai dan wadah sumber daya listrik',
            'Connection' => 'Kabel jumper, alligator, breadboard, konektor',
            'Mechanical' => 'Bricks, lego, separator, plate mekanik',
            'Consumable' => 'Bahan habis pakai praktek (karton, spidol, lakban)',
            'Tools' => 'Peralatan kerja (gunting, cutter, meteran, lem tembak)',
            'Packaging' => 'Box kemasan, boks kit, lembar panduan',
        ];

        $categoryModels = [];
        foreach ($categories as $name => $desc) {
            $cat = Category::firstOrCreate(['nama' => $name]);
            $categoryModels[$name] = $cat->id;
        }

        // 2. Seed 41 Master Komponen (Sheet 1 Legenda)
        $itemsData = [
            ['code' => 'CT-001', 'name' => 'Micro:bit V2', 'category' => 'Controller', 'unit' => 'pcs'],
            ['code' => 'CT-002', 'name' => 'Kabel Micro USB to Type C', 'category' => 'Controller', 'unit' => 'pcs'],
            ['code' => 'CT-003', 'name' => 'Micro USB Cable Data Kabel', 'category' => 'Controller', 'unit' => 'pcs'],
            ['code' => 'OP-001', 'name' => 'LED Merah', 'category' => 'Output Device', 'unit' => 'pcs'],
            ['code' => 'OP-002', 'name' => 'LED Kuning', 'category' => 'Output Device', 'unit' => 'pcs'],
            ['code' => 'OP-003', 'name' => 'LED Hijau', 'category' => 'Output Device', 'unit' => 'pcs'],
            ['code' => 'SN-001', 'name' => 'Sensor Ultrasonik HC-SR04', 'category' => 'Sensor', 'unit' => 'pcs'],
            ['code' => 'MT-001', 'name' => 'Servo SG90 180°', 'category' => 'Motion', 'unit' => 'pcs'],
            ['code' => 'MT-002', 'name' => 'Servo SG90 360°', 'category' => 'Motion', 'unit' => 'pcs'],
            ['code' => 'MT-003', 'name' => 'Servo MG996R', 'category' => 'Motion', 'unit' => 'pcs'],
            ['code' => 'PW-001', 'name' => 'Baterai AAA', 'category' => 'Power', 'unit' => 'pcs'],
            ['code' => 'PW-002', 'name' => 'Battery Holder 4xAAA', 'category' => 'Power', 'unit' => 'pcs'],
            ['code' => 'CN-001', 'name' => 'Breadboard Mini (170 TP)', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-002', 'name' => 'Kabel Jumper Male to Male', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-003', 'name' => 'Kabel Jumper Female to Female', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-004', 'name' => 'Kabel Jumper Male to Female', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-005', 'name' => 'Kabel Aligator', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-006', 'name' => 'Resistor 100 Ohm', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'CN-007', 'name' => 'Blok Konektor (CH-2 Push Connector)', 'category' => 'Connection', 'unit' => 'pcs'],
            ['code' => 'MC-001', 'name' => 'Brick', 'category' => 'Mechanical', 'unit' => 'pcs'],
            ['code' => 'MC-002', 'name' => 'Brick Papan', 'category' => 'Mechanical', 'unit' => 'pcs'],
            ['code' => 'MC-003', 'name' => 'Brick Separator', 'category' => 'Mechanical', 'unit' => 'pcs'],
            ['code' => 'MC-004', 'name' => 'Lego', 'category' => 'Mechanical', 'unit' => 'set'],
            ['code' => 'MC-005', 'name' => 'Base Plate Lego', 'category' => 'Mechanical', 'unit' => 'pcs'],
            ['code' => 'CS-001', 'name' => 'Karton / Kertas Karton', 'category' => 'Consumable', 'unit' => 'lembar'],
            ['code' => 'CS-002', 'name' => 'Kardus', 'category' => 'Consumable', 'unit' => 'pcs'],
            ['code' => 'CS-003', 'name' => 'Lakban', 'category' => 'Consumable', 'unit' => 'roll'],
            ['code' => 'CS-004', 'name' => 'Double Tape', 'category' => 'Consumable', 'unit' => 'roll'],
            ['code' => 'CS-005', 'name' => 'Stik Es Krim', 'category' => 'Consumable', 'unit' => 'pcs'],
            ['code' => 'CS-006', 'name' => 'Spidol', 'category' => 'Consumable', 'unit' => 'pcs'],
            ['code' => 'CS-007', 'name' => 'Kertas Warna', 'category' => 'Consumable', 'unit' => 'lembar'],
            ['code' => 'TL-001', 'name' => 'Gunting', 'category' => 'Tools', 'unit' => 'pcs'],
            ['code' => 'TL-002', 'name' => 'Cutter', 'category' => 'Tools', 'unit' => 'pcs'],
            ['code' => 'TL-003', 'name' => 'Penggaris / Meteran', 'category' => 'Tools', 'unit' => 'pcs'],
            ['code' => 'TL-004', 'name' => 'Lem Tembak', 'category' => 'Tools', 'unit' => 'pcs'],
            ['code' => 'PC-001', 'name' => 'Box Packaging', 'category' => 'Packaging', 'unit' => 'pcs'],
            ['code' => 'PC-002', 'name' => 'Box Beginner Micro', 'category' => 'Packaging', 'unit' => 'pcs'],
            ['code' => 'PC-003', 'name' => 'Box Supporting Equipment', 'category' => 'Packaging', 'unit' => 'pcs'],
            ['code' => 'PC-004', 'name' => 'Box Wires', 'category' => 'Packaging', 'unit' => 'pcs'],
            ['code' => 'PC-005', 'name' => 'Box Bricks', 'category' => 'Packaging', 'unit' => 'pcs'],
            ['code' => 'PC-006', 'name' => 'Lembar Panduan', 'category' => 'Packaging', 'unit' => 'lembar'],
        ];

        $itemMap = [];
        foreach ($itemsData as $it) {
            $catId = $categoryModels[$it['category']] ?? null;
            $item = Item::updateOrCreate(
                ['kode' => $it['code']],
                [
                    'nama' => $it['name'],
                    'category_id' => $catId,
                    'satuan' => $it['unit'],
                    'min_stok' => 5,
                    'is_active' => true,
                ]
            );
            $itemMap[$it['code']] = $item->id;
        }

        // 3. Seed Master Modul MIKMS (Sheet 1 Legenda B)
        $modulesData = [
            ['code' => 'M01', 'name' => 'Controller Kit', 'desc' => 'Micro:bit V2 dan kabel data'],
            ['code' => 'M02', 'name' => 'LED Kit', 'desc' => 'LED Merah, Hijau, Kuning & Resistor'],
            ['code' => 'M03', 'name' => 'Motion Kit', 'desc' => 'Servo SG90 180°, 360°, MG996R'],
            ['code' => 'M04', 'name' => 'Sensor Kit', 'desc' => 'Sensor Ultrasonik HC-SR04'],
            ['code' => 'M05', 'name' => 'Power Kit', 'desc' => 'Battery Holder & Baterai AAA'],
            ['code' => 'M06', 'name' => 'Mechanical Kit', 'desc' => 'Lego, Separator, dan Base Plate'],
            ['code' => 'M07', 'name' => 'Connection Kit', 'desc' => 'Breadboard Mini, Push Connector, Kabel Alligator & Jumper'],
            ['code' => 'M08', 'name' => 'Jimu Trackbot', 'desc' => 'Robotik Kit Jimu Trackbot (Finish Good)'],
            ['code' => 'M09', 'name' => 'Erboblox', 'desc' => 'Erboblox Kit (Finish Good)'],
            ['code' => 'M10', 'name' => 'Arduino Learning Kit', 'desc' => 'Arduino Kit Assembly (Finish Good)'],
            ['code' => 'M11', 'name' => 'Sub-Assembly Kit', 'desc' => 'Sub-Assembly & Third-Party Kit Tambahan'],
        ];

        $modMap = [];
        foreach ($modulesData as $m) {
            $mod = MikmsModule::updateOrCreate(
                ['code' => $m['code']],
                ['name' => $m['name'], 'description' => $m['desc']]
            );
            $modMap[$m['code']] = $mod->id;
        }

        // 4. Seed Bill of Materials (BOM) (Sheet 2 List Komponen)
        $bomData = [
            // M01 - Controller Kit (BOX 1)
            ['mod' => 'M01', 'item' => 'CT-001', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 1],
            ['mod' => 'M01', 'item' => 'CT-003', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 1],
            ['mod' => 'M01', 'item' => 'CT-002', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 1],

            // M02 - LED Kit (BOX 1)
            ['mod' => 'M02', 'item' => 'OP-001', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 4],
            ['mod' => 'M02', 'item' => 'OP-003', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 4],
            ['mod' => 'M02', 'item' => 'OP-002', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 4],
            ['mod' => 'M02', 'item' => 'CN-006', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 12],

            // M07 - Connection Kit (BOX 1)
            ['mod' => 'M07', 'item' => 'CN-001', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 1],
            ['mod' => 'M07', 'item' => 'CN-007', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 2],
            ['mod' => 'M07', 'item' => 'CN-005', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 10],
            ['mod' => 'M07', 'item' => 'CN-002', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 20],
            ['mod' => 'M07', 'item' => 'CN-004', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 10],
            ['mod' => 'M07', 'item' => 'CN-003', 'box' => 'BOX 1 – Beginner Kit', 'qty' => 10],

            // M03 - Motion Kit (BOX 2)
            ['mod' => 'M03', 'item' => 'MT-001', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 2],
            ['mod' => 'M03', 'item' => 'MT-002', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 2],
            ['mod' => 'M03', 'item' => 'MT-003', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 1],

            // M04 - Sensor Kit (BOX 2)
            ['mod' => 'M04', 'item' => 'SN-001', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 1],

            // M05 - Power Kit (BOX 2)
            ['mod' => 'M05', 'item' => 'PW-002', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 1],
            ['mod' => 'M05', 'item' => 'PW-001', 'box' => 'BOX 2 – Supporting Equipment', 'qty' => 4],

            // M06 - Mechanical Kit (BOX 3)
            ['mod' => 'M06', 'item' => 'MC-004', 'box' => 'BOX 3 – Bricks', 'qty' => 1],
            ['mod' => 'M06', 'item' => 'MC-003', 'box' => 'BOX 3 – Bricks', 'qty' => 1],
            ['mod' => 'M06', 'item' => 'MC-005', 'box' => 'BOX 3 – Bricks', 'qty' => 2],
        ];

        foreach ($bomData as $b) {
            $mId = $modMap[$b['mod']] ?? null;
            $itId = $itemMap[$b['item']] ?? null;
            if ($mId && $itId) {
                MikmsBom::updateOrCreate(
                    ['module_id' => $mId, 'item_id' => $itId],
                    ['box_category' => $b['box'], 'quantity' => $b['qty']]
                );
            }
        }

        // 5. Seed Sampel Box Kit
        $boxes = [
            ['box_code' => 'BOX-001', 'category' => 'BOX 1 – Beginner Kit', 'program_code' => 'MB-BEG', 'status' => 'READY'],
            ['box_code' => 'BOX-002', 'category' => 'BOX 2 – Supporting Equipment', 'program_code' => 'MB-BEG', 'status' => 'READY'],
            ['box_code' => 'BOX-003', 'category' => 'BOX 3 – Bricks', 'program_code' => 'MB-BEG', 'status' => 'READY'],
            ['box_code' => 'BOX-004', 'category' => 'BOX 1 – Beginner Kit', 'program_code' => 'MB-ADV', 'status' => 'READY'],
            ['box_code' => 'BOX-005', 'category' => 'BOX 2 – Supporting Equipment', 'program_code' => 'MB-ADV', 'status' => 'READY'],
            ['box_code' => 'BOX-006', 'category' => 'BOX 3 – Bricks', 'program_code' => 'MB-ADV', 'status' => 'READY'],
        ];

        foreach ($boxes as $bx) {
            MikmsBox::updateOrCreate(['box_code' => $bx['box_code']], $bx);
        }
    }
}
