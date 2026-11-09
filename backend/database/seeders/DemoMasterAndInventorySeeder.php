<?php

namespace Database\Seeders;

use App\Domains\MasterData\Enums\FulfillmentType;
use App\Domains\MasterData\Enums\PhysicalType;
use App\Domains\MasterData\Enums\ProductType;
use App\Domains\MasterData\Enums\TrackingMethod;
use App\Domains\MasterData\Models\Customer;
use App\Domains\MasterData\Models\DocumentTemplate;
use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductCategory;
use App\Domains\MasterData\Models\ProductComponent;
use App\Domains\MasterData\Models\ProductSerial;
use App\Domains\MasterData\Models\Supplier;
use App\Domains\MasterData\Models\Uom;
use App\Domains\MasterData\Models\Vendor;
use App\Domains\Production\Enums\BomType;
use App\Domains\Production\Enums\BomVersionStatus;
use App\Domains\Production\Models\BomHeader;
use App\Domains\Production\Models\BomItem;
use App\Domains\Production\Models\BomVersion;
use App\Domains\Production\Models\ProductionStageTemplate;
use App\Domains\Production\Models\ProductionStageTemplateItem;
use App\Domains\Production\Services\BomService;
use App\Domains\Warehouse\Models\Location;
use App\Domains\Warehouse\Models\Warehouse;
use App\Models\User;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use Illuminate\Database\Seeder;

/**
 * Demo master data, BOMs (Standard MLK + Flexible), warehouse hierarchy (including GAP external
 * location), and opening stock posted through `InventoryPostingContract`
 * (agent_docs/11_TESTING_STRATEGY.md "Test data"). Never executed in production.
 */
final class DemoMasterAndInventorySeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $pcs = Uom::query()->updateOrCreate(['code' => 'PCS'], ['name' => 'Pieces', 'dimension' => 'COUNT']);
        $set = Uom::query()->updateOrCreate(['code' => 'SET'], ['name' => 'Set / Kit', 'dimension' => 'COUNT']);
        $boxUom = Uom::query()->updateOrCreate(['code' => 'BOX'], ['name' => 'Box', 'dimension' => 'COUNT']);

        $catComp = ProductCategory::query()->updateOrCreate(['code' => 'COMP'], ['name' => 'Komponen']);
        $catMod = ProductCategory::query()->updateOrCreate(['code' => 'WIP-MOD'], ['name' => 'WIP Modul']);
        $catBox = ProductCategory::query()->updateOrCreate(['code' => 'WIP-BOX'], ['name' => 'WIP Box']);
        $catFg = ProductCategory::query()->updateOrCreate(['code' => 'FG'], ['name' => 'Finished Good']);
        $catSrv = ProductCategory::query()->updateOrCreate(['code' => 'SRV'], ['name' => 'Layanan / Jasa']);
        $catProg = ProductCategory::query()->updateOrCreate(['code' => 'PROG'], ['name' => 'Program & Layanan']);

        // Warehouse & hierarchical locations (02_SCOPE_AND_ASSUMPTIONS.md: POP.L3R3A1.M01).
        $mainWh = Warehouse::query()->updateOrCreate(
            ['code' => 'WH-POP'],
            ['name' => 'Gudang Utama POP', 'warehouse_type' => 'INTERNAL', 'address' => 'Gedung POP Erlass'],
        );
        $gapWh = Warehouse::query()->updateOrCreate(
            ['code' => 'WH-GAP'],
            ['name' => 'Lokasi Eksternal Vendor GAP', 'warehouse_type' => 'EXTERNAL_VENDOR', 'address' => 'Partner GAP'],
        );

        $floor3 = Location::query()->updateOrCreate(
            ['warehouse_id' => $mainWh->id, 'code' => 'POP.L3'],
            ['name' => 'Lantai 3', 'location_type' => 'FLOOR', 'is_external' => false],
        );
        $room3 = Location::query()->updateOrCreate(
            ['warehouse_id' => $mainWh->id, 'code' => 'POP.L3R3'],
            ['parent_id' => $floor3->id, 'name' => 'Ruang 3 Perakitan & Simpan', 'location_type' => 'ROOM', 'is_external' => false],
        );
        $rackA1 = Location::query()->updateOrCreate(
            ['warehouse_id' => $mainWh->id, 'code' => 'POP.L3R3A1'],
            ['parent_id' => $room3->id, 'barcode' => 'POP.L3R3A1', 'name' => 'Ruang 3 Rak A1 (Komponen)', 'location_type' => 'RACK', 'is_external' => false],
        );
        $rackC1 = Location::query()->updateOrCreate(
            ['warehouse_id' => $mainWh->id, 'code' => 'POP.L3R3C1.M01'],
            ['parent_id' => $room3->id, 'barcode' => 'POP.L3R3C1.M01', 'name' => 'Ruang 3 Rak C1 (WIP & FG)', 'location_type' => 'BIN', 'is_external' => false],
        );
        $gapLoc = Location::query()->updateOrCreate(
            ['warehouse_id' => $gapWh->id, 'code' => 'GAP.EXT.01'],
            ['barcode' => 'GAP.EXT.01', 'name' => 'Area Proses Vendor GAP', 'location_type' => 'VENDOR', 'is_external' => true],
        );

        // Partners
        Supplier::query()->updateOrCreate(['code' => 'SUP-001'], ['name' => 'PT Sumber Elektronik Nusantara', 'contact_name' => 'Hendra', 'phone' => '021-5550101']);
        Vendor::query()->updateOrCreate(['code' => 'GAP-001'], ['name' => 'Mitra Perakitan GAP Utama', 'vendor_type' => 'GAP', 'default_location_id' => $gapLoc->id]);
        Customer::query()->updateOrCreate(['code' => 'CUST-001'], [
            'name' => 'SMA Negeri 1 Contoh',
            'sales_name' => 'Andi Wijaya',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMAN 1 Contoh',
            'contact_name' => 'Drs. H. Mulyadi',
            'phone' => '081234567890',
        ]);
        Customer::query()->updateOrCreate(['code' => 'CUST-002'], [
            'name' => 'SMP Bina Talenta',
            'sales_name' => 'Rina Marlina',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMP Bina Talenta Jakarta',
            'contact_name' => 'Ibu Ratna',
            'phone' => '081398765432',
        ]);
        Customer::query()->updateOrCreate(['code' => 'CUST-003'], [
            'name' => 'SMK Kreatif Nusantara',
            'sales_name' => 'Siti Rahma',
            'customer_type' => 'SCHOOL',
            'school_name' => 'SMK Kreatif Nusantara Depok',
            'contact_name' => 'Pak Joko',
            'phone' => '081512341234',
        ]);

        // Products: components, M01/M02 modules, B01 box, MLK finished good, and 1 Program (non-inventory).
        $comp16 = Product::query()->updateOrCreate(['code' => 'SP.PART.ER.016'], [
            'name' => 'Sensor Ultrasonik HC-SR04',
            'category_id' => $catComp->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::INTERNAL,
            'tracking_method' => TrackingMethod::QUANTITY,
            'base_uom_id' => $pcs->id,
            'safety_stock_qty' => '25.0000',
            'reference_purchase_price' => '18500.00',
            'is_purchasable' => true,
        ]);

        $comp17 = Product::query()->updateOrCreate(['code' => 'SP.PART.ER.017'], [
            'name' => 'Board Mikrokontroler EduCore',
            'category_id' => $catComp->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::INTERNAL,
            'tracking_method' => TrackingMethod::QUANTITY,
            'base_uom_id' => $pcs->id,
            'safety_stock_qty' => '20.0000',
            'reference_purchase_price' => '65000.00',
            'is_purchasable' => true,
        ]);

        $comp18 = Product::query()->updateOrCreate(['code' => 'SP.PART.ER.018'], [
            'name' => 'Casing Akrilik Modul Set',
            'category_id' => $catComp->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::INTERNAL,
            'tracking_method' => TrackingMethod::QUANTITY,
            'base_uom_id' => $set->id,
            'safety_stock_qty' => '20.0000',
            'reference_purchase_price' => '22000.00',
            'is_purchasable' => true,
        ]);

        $mod01 = Product::query()->updateOrCreate(['code' => 'M01'], [
            'name' => 'WIP Modul Sensor & Kontrol M01',
            'category_id' => $catMod->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::INTERNAL,
            'tracking_method' => TrackingMethod::QUANTITY,
            'base_uom_id' => $pcs->id,
            'is_manufactured' => true,
            'is_purchasable' => false,
        ]);

        $box01 = Product::query()->updateOrCreate(['code' => 'B01'], [
            'name' => 'WIP Box Kit Pembelajaran B01',
            'category_id' => $catBox->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::MIXED,
            'tracking_method' => TrackingMethod::QUANTITY,
            'base_uom_id' => $boxUom->id,
            'is_manufactured' => true,
            'is_purchasable' => false,
        ]);

        $mlkFg = Product::query()->updateOrCreate(['code' => '1001'], [
            'legacy_code' => 'MLK-STD-01',
            'name' => 'Mobile Learning Kit (MLK) Standar',
            'category_id' => $catFg->id,
            'product_type' => ProductType::PRODUCT,
            'physical_type' => PhysicalType::PHYSICAL,
            'fulfillment_type' => FulfillmentType::MIXED,
            'tracking_method' => TrackingMethod::SERIALIZED,
            'base_uom_id' => $set->id,
            'is_manufactured' => true,
            'is_sellable' => true,
            'is_rentable' => true,
            'is_purchasable' => false,
        ]);

        $srvTrain = Product::query()->updateOrCreate(['code' => 'SRV-TRAIN-01'], [
            'name' => 'Layanan Instruktur Robotika & Workshop',
            'category_id' => $catSrv->id,
            'product_type' => ProductType::SERVICE,
            'physical_type' => PhysicalType::NON_INVENTORY,
            'fulfillment_type' => FulfillmentType::SELL,
            'tracking_method' => TrackingMethod::NONE,
            'base_uom_id' => $set->id,
            'is_sellable' => true,
            'is_purchasable' => false,
        ]);

        $prog = Product::query()->updateOrCreate(['code' => 'PROG-2027-ROBOTIK'], [
            'name' => 'Program Ekstrakurikuler Robotika 2027 (Jasa + Kit)',
            'category_id' => $catProg->id,
            'product_type' => ProductType::PROGRAM,
            'physical_type' => PhysicalType::NON_INVENTORY,
            'fulfillment_type' => FulfillmentType::SELL,
            'tracking_method' => TrackingMethod::NONE,
            'base_uom_id' => $set->id,
            'is_sellable' => true,
            'is_purchasable' => false,
        ]);

        // Program composition: combines Finished Good kit (1001) and Service (SRV-TRAIN-01).
        ProductComponent::query()->updateOrCreate([
            'parent_product_id' => $prog->id,
            'component_product_id' => $mlkFg->id,
        ], [
            'quantity' => '1.0000',
            'uom_id' => $set->id,
            'component_role' => 'FINISHED_GOOD',
        ]);
        ProductComponent::query()->updateOrCreate([
            'parent_product_id' => $prog->id,
            'component_product_id' => $srvTrain->id,
        ], [
            'quantity' => '1.0000',
            'uom_id' => $set->id,
            'component_role' => 'SERVICE',
        ]);

        // Standard MLK Stage Template: Component → WIP Module → WIP Box → Finished Good.
        $mlkTemplate = ProductionStageTemplate::query()->updateOrCreate(
            ['code' => 'TPL-MLK-STD'],
            ['name' => 'Alur Standar MLK (Komponen → Modul → Box → FG)', 'product_scope' => 'MLK'],
        );

        ProductionStageTemplateItem::query()->updateOrCreate(
            ['stage_template_id' => $mlkTemplate->id, 'sequence' => 1],
            ['stage_code' => 'WIP_MODULE', 'name' => 'Perakitan Komponen → WIP Modul', 'output_product_id' => $mod01->id, 'qc_required' => true],
        );
        ProductionStageTemplateItem::query()->updateOrCreate(
            ['stage_template_id' => $mlkTemplate->id, 'sequence' => 2],
            ['stage_code' => 'WIP_BOX', 'name' => 'Integrasi Modul → WIP Box', 'output_product_id' => $box01->id, 'qc_required' => true],
        );
        ProductionStageTemplateItem::query()->updateOrCreate(
            ['stage_template_id' => $mlkTemplate->id, 'sequence' => 3],
            ['stage_code' => 'FINISHED_GOOD', 'name' => 'Finalisasi Box → Finished Good MLK', 'output_product_id' => $mlkFg->id, 'qc_required' => true],
        );

        $admin = User::query()->where('email', 'admin@erlass.test')->first() ?? User::query()->first();
        $bomService = app(BomService::class);

        // Subassembly BOM for M01 and B01 + Top-level Standard MLK BOM for 1001.
        $this->seedBom('BOM-M01', $mod01->id, 'BOM Modul M01', BomType::FLEXIBLE, null, [
            ['component_product_id' => $comp16->id, 'item_type' => 'COMPONENT', 'quantity' => '2.0000', 'uom_id' => $pcs->id, 'scrap_rate' => '0.0000'],
            ['component_product_id' => $comp17->id, 'item_type' => 'COMPONENT', 'quantity' => '1.0000', 'uom_id' => $pcs->id, 'scrap_rate' => '0.0000'],
            ['component_product_id' => $comp18->id, 'item_type' => 'COMPONENT', 'quantity' => '1.0000', 'uom_id' => $set->id, 'scrap_rate' => '0.0500'],
        ], $admin?->id ?? 1, $bomService);

        $this->seedBom('BOM-B01', $box01->id, 'BOM Box B01', BomType::FLEXIBLE, null, [
            ['component_product_id' => $mod01->id, 'item_type' => 'SUBASSEMBLY', 'quantity' => '2.0000', 'uom_id' => $pcs->id, 'scrap_rate' => '0.0000'],
        ], $admin?->id ?? 1, $bomService);

        $this->seedBom('BOM-MLK-1001', $mlkFg->id, 'BOM Standar MLK 1001', BomType::STANDARD_MLK, $mlkTemplate->id, [
            ['component_product_id' => $box01->id, 'item_type' => 'SUBASSEMBLY', 'quantity' => '1.0000', 'uom_id' => $boxUom->id, 'scrap_rate' => '0.0000'],
        ], $admin?->id ?? 1, $bomService);

        // Register 2 demo serials for MLK 1001 and post opening stock idempotently via InventoryPostingContract.
        $serial1 = ProductSerial::query()->updateOrCreate(
            ['serial_number' => 'MLK-1001-SN0001'],
            ['product_id' => $mlkFg->id, 'barcode' => 'MLK-1001-SN0001'],
        );
        $serial2 = ProductSerial::query()->updateOrCreate(
            ['serial_number' => 'MLK-1001-SN0002'],
            ['product_id' => $mlkFg->id, 'barcode' => 'MLK-1001-SN0002'],
        );

        /** @var InventoryPostingContract $inventory */
        $inventory = app(InventoryPostingContract::class);
        $inventory->post(new PostInventoryCommand(
            idempotencyKey: 'demo-seed-opening-balance-v1',
            transactionType: 'OPENING_BALANCE',
            sourceDocumentType: 'demo_seeder',
            sourceDocumentId: 1,
            postedByUserId: $admin?->id ?? 1,
            lines: [
                new StockMovementLine(productId: $comp16->id, quantity: '120.0000', toLocationId: $rackA1->id, unitCost: '18500.000000'),
                new StockMovementLine(productId: $comp17->id, quantity: '60.0000', toLocationId: $rackA1->id, unitCost: '65000.000000'),
                new StockMovementLine(productId: $comp18->id, quantity: '45.0000', toLocationId: $rackA1->id, unitCost: '22000.000000'),
                new StockMovementLine(productId: $mlkFg->id, quantity: '1.0000', toLocationId: $rackC1->id, productSerialId: $serial1->id, unitCost: '250000.000000'),
                new StockMovementLine(productId: $mlkFg->id, quantity: '1.0000', toLocationId: $rackC1->id, productSerialId: $serial2->id, unitCost: '250000.000000'),
            ],
            notes: 'Saldo awal demo ERLASS PWMS (idempotent)',
        ));

        // Letter / document templates
        DocumentTemplate::query()->updateOrCreate(['code' => 'TPL-SJ-01'], [
            'name' => 'Surat Jalan Pengiriman Barang & Rental',
            'document_type' => 'SURAT_JALAN',
            'template_body' => "SURAT JALAN PENGIRIMAN\nNo: {{document_no}}\nTanggal: {{date}}\nTujuan: {{customer_name}}\n\nDaftar Unit & Serial:\n{{items_table}}\n\nPetunjuk: Periksa kelengkapan komponen & modul sebelum menandatangani.",
            'version' => 1,
            'is_active' => true,
        ]);

        DocumentTemplate::query()->updateOrCreate(['code' => 'TPL-BAST-01'], [
            'name' => 'Berita Acara Serah Terima (BAST)',
            'document_type' => 'BAST',
            'template_body' => "BERITA ACARA SERAH TERIMA (BAST)\nNo: {{document_no}}\nTanggal: {{date}}\n\nTelah diserahterimakan kit Mobile Learning Kit (MLK) dari Erlass kepada {{customer_name}} dalam kondisi baik.",
            'version' => 1,
            'is_active' => true,
        ]);

        DocumentTemplate::query()->updateOrCreate(['code' => 'TPL-SPK-01'], [
            'name' => 'Surat Perintah Kerja (SPK) Produksi',
            'document_type' => 'SPK_PRODUKSI',
            'template_body' => "SURAT PERINTAH KERJA (SPK)\nNo. SPK: {{order_no}}\nTarget Perakitan: {{product_name}}\nJumlah Target: {{target_qty}} unit\n\nIkuti alur tahapan perakitan dan inspeksi QC sesuai SOP Erlass.",
            'version' => 1,
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function seedBom(
        string $code,
        int $productId,
        string $name,
        BomType $type,
        ?int $stageTemplateId,
        array $items,
        int $approverId,
        BomService $bomService,
    ): void {
        $header = BomHeader::query()->updateOrCreate(
            ['code' => $code],
            ['product_id' => $productId, 'name' => $name, 'bom_type' => $type, 'stage_template_id' => $stageTemplateId, 'status' => 'ACTIVE'],
        );

        $version = BomVersion::query()->firstOrCreate(
            ['bom_header_id' => $header->id, 'version_no' => 1],
            ['status' => BomVersionStatus::DRAFT],
        );

        if ($version->items()->count() === 0) {
            foreach ($items as $idx => $item) {
                BomItem::query()->create([
                    'bom_version_id' => $version->id,
                    'component_product_id' => $item['component_product_id'],
                    'item_type' => $item['item_type'],
                    'quantity' => $item['quantity'],
                    'uom_id' => $item['uom_id'],
                    'scrap_rate' => $item['scrap_rate'],
                    'sequence' => $idx + 1,
                ]);
            }
        }

        if ($version->status !== BomVersionStatus::ACTIVE) {
            $bomService->activateVersion($version, $approverId);
        }
    }
}
