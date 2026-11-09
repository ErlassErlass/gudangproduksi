<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\MasterData\Models\Product;
use App\Domains\Warehouse\Models\Location;
use App\Domains\Production\Enums\BomType;
use App\Domains\Production\Enums\BomVersionStatus;
use App\Domains\Production\Models\BomHeader;
use App\Domains\Production\Models\BomItem;
use App\Domains\Production\Models\BomVersion;
use App\Domains\Production\Models\ProductionStageTemplate;
use App\Domains\Production\Services\BomService;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Phase 2 BOM endpoints (`/boms`, `/boms/{id}/versions`, `/bom-versions/{id}/submit|approve|activate|explosion`).
 */
final class BomController extends Controller
{
    public function __construct(
        private readonly BomService $bomService,
        private readonly AuditLogger $audit,
        private readonly InventoryPostingContract $inventoryPosting,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'bom.view');

        $boms = BomHeader::query()
            ->with(['product:id,code,name', 'stageTemplate:id,code,name', 'versions' => fn ($q) => $q->withCount('items')])
            ->orderBy('code')
            ->get();

        return response()->json(['data' => $boms]);
    }

    public function show(Request $request, BomHeader $bom): JsonResponse
    {
        $this->requirePermission($request, 'bom.view');
        $bom->load([
            'product:id,code,name,base_uom_id',
            'stageTemplate.items.outputProduct:id,code,name',
            'versions.items.component:id,code,name,reference_purchase_price',
            'versions.items.uom:id,code,name',
        ]);

        return response()->json(['data' => $bom]);
    }

    public function stageTemplates(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'bom.view');

        return response()->json([
            'data' => ProductionStageTemplate::query()->with('items')->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('bom_headers', 'code')],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'name' => ['required', 'string', 'max:160'],
            'bom_type' => ['required', new Enum(BomType::class)],
            'stage_template_id' => ['nullable', 'integer', Rule::exists('production_stage_templates', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.component_product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.item_type' => ['sometimes', 'string', Rule::in(['COMPONENT', 'SUBASSEMBLY'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'items.*.scrap_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $bom = DB::transaction(function () use ($data): BomHeader {
            $header = BomHeader::query()->create([
                'code' => $data['code'],
                'product_id' => $data['product_id'],
                'name' => $data['name'],
                'bom_type' => $data['bom_type'],
                'stage_template_id' => $data['stage_template_id'] ?? null,
                'status' => 'ACTIVE',
            ]);

            $version = BomVersion::query()->create([
                'bom_header_id' => $header->id,
                'version_no' => 1,
                'status' => BomVersionStatus::DRAFT,
            ]);

            $this->persistVersionItems($version, $data['items']);
            $this->bomService->assertNoCycle($header->product_id, $version);

            $this->audit->record('bom.created', $header, null, ['code' => $header->code, 'version_no' => 1]);

            return $header->load(['product:id,code,name', 'versions.items.component:id,code,name', 'versions.items.uom:id,code,name']);
        });

        return response()->json(['data' => $bom], 201);
    }

    public function storeVersion(Request $request, BomHeader $bom): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        $data = $request->validate([
            'effective_from' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.component_product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.item_type' => ['sometimes', 'string', Rule::in(['COMPONENT', 'SUBASSEMBLY'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'items.*.scrap_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $version = DB::transaction(function () use ($bom, $data): BomVersion {
            $nextNo = ((int) $bom->versions()->max('version_no')) + 1;
            $version = BomVersion::query()->create([
                'bom_header_id' => $bom->id,
                'version_no' => $nextNo,
                'status' => BomVersionStatus::DRAFT,
                'effective_from' => $data['effective_from'] ?? null,
            ]);

            $this->persistVersionItems($version, $data['items']);
            $this->bomService->assertNoCycle($bom->product_id, $version);

            $this->audit->record('bom.version.created', $version, null, [
                'bom_header_id' => $bom->id,
                'version_no' => $nextNo,
            ]);

            return $version->load(['items.component:id,code,name', 'items.uom:id,code,name']);
        });

        return response()->json(['data' => $version], 201);
    }

    public function submitVersion(Request $request, BomVersion $bomVersion): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');
        if ($bomVersion->status !== BomVersionStatus::DRAFT) {
            throw new DomainException('Hanya versi BOM berstatus DRAFT yang dapat diajukan.', 'BOM_INVALID_STATE', 409);
        }

        $bomVersion->loadMissing('header');
        $this->bomService->assertNoCycle($bomVersion->header->product_id, $bomVersion);
        $bomVersion->update(['status' => BomVersionStatus::REVIEW]);
        $this->audit->record('bom.version.submitted', $bomVersion);

        return response()->json(['data' => $bomVersion]);
    }

    public function activateVersion(Request $request, BomVersion $bomVersion): JsonResponse
    {
        $this->requirePermission($request, 'bom.approve');
        if (! in_array($bomVersion->status, [BomVersionStatus::DRAFT, BomVersionStatus::REVIEW], true)) {
            throw new DomainException('Versi BOM ini tidak dapat diaktifkan dari status saat ini.', 'BOM_INVALID_STATE', 409);
        }

        /** @var User $user */
        $user = $request->user();
        $activated = $this->bomService->activateVersion($bomVersion, $user->id);

        return response()->json(['data' => $activated]);
    }

    public function explosion(Request $request, BomVersion $bomVersion): JsonResponse
    {
        $this->requirePermission($request, 'bom.view');
        $qty = (string) $request->query('quantity', '1.0000');
        if (! is_numeric($qty) || bccomp($qty, '0', 4) <= 0) {
            throw new DomainException('Kuantitas explosion harus lebih besar dari 0.', 'BOM_INVALID_QTY', 422);
        }

        $lines = $this->bomService->explode($bomVersion, $qty);
        $totalBudget = array_reduce(
            $lines,
            fn (string $carry, array $line) => $line['estimated_total_cost'] !== null ? bcadd($carry, $line['estimated_total_cost'], 2) : $carry,
            '0.00',
        );

        return response()->json([
            'data' => [
                'bom_version_id' => $bomVersion->id,
                'target_qty' => bcadd($qty, '0', 4),
                'estimated_material_budget' => $totalBudget,
                'requirements' => $lines,
            ],
        ]);
    }

    public function byProduct(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'bom.view');

        // As output product (recipe to produce this product)
        $outputBom = BomHeader::query()
            ->with([
                'product:id,code,name,base_uom_id',
                'stageTemplate.items.outputProduct:id,code,name',
                'activeVersion.items.component:id,code,name,base_uom_id,reference_purchase_price',
                'activeVersion.items.uom:id,code,name',
                'versions.items.component:id,code,name',
                'versions.items.uom:id,code,name',
            ])
            ->where('product_id', $product->id)
            ->first();

        // Where this product is consumed as an input component for the next stage
        $usedInBoms = BomItem::query()
            ->with([
                'bomVersion.header.product:id,code,name',
                'bomVersion.header.stageTemplate:id,code,name',
                'uom:id,code,name',
            ])
            ->where('component_product_id', $product->id)
            ->whereHas('bomVersion', fn ($v) => $v->where('status', BomVersionStatus::ACTIVE->value))
            ->get()
            ->map(fn (BomItem $item) => [
                'id' => $item->id,
                'parent_bom_header_id' => $item->bomVersion->bom_header_id,
                'parent_bom_code' => $item->bomVersion->header->code,
                'target_product' => $item->bomVersion->header->product,
                'stage_name' => $item->bomVersion->header->stageTemplate?->name ?? 'Tahap Lanjutan',
                'quantity' => $item->quantity,
                'uom' => $item->uom,
                'scrap_rate' => $item->scrap_rate,
            ]);

        return response()->json([
            'data' => [
                'product' => $product->only(['id', 'code', 'name']),
                'output_bom' => $outputBom,
                'used_in_next_stages' => $usedInBoms,
            ],
        ]);
    }

    public function quickSave(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'code' => ['nullable', 'string', 'max:64'],
            'name' => ['nullable', 'string', 'max:160'],
            'bom_type' => ['nullable', new Enum(BomType::class)],
            'stage_template_id' => ['nullable', 'integer', Rule::exists('production_stage_templates', 'id')],
            'items' => ['required', 'array', 'min:1'],
            'items.*.component_product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.item_type' => ['sometimes', 'string', Rule::in(['COMPONENT', 'SUBASSEMBLY'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'items.*.scrap_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $bom = DB::transaction(function () use ($data, $request): BomHeader {
            /** @var User $user */
            $user = $request->user();

            $header = BomHeader::query()->where('product_id', $data['product_id'])->first();
            if (! $header) {
                $product = Product::query()->findOrFail($data['product_id']);
                $header = BomHeader::query()->create([
                    'code' => $data['code'] ?? ('BOM-' . $product->code),
                    'product_id' => $data['product_id'],
                    'name' => $data['name'] ?? ('BOM ' . $product->name),
                    'bom_type' => $data['bom_type'] ?? BomType::STANDARD_MLK->value,
                    'stage_template_id' => $data['stage_template_id'] ?? null,
                    'status' => 'ACTIVE',
                ]);
            } else {
                if (isset($data['name'])) {
                    $header->update(['name' => $data['name']]);
                }
                if (isset($data['stage_template_id'])) {
                    $header->update(['stage_template_id' => $data['stage_template_id']]);
                }
            }

            $nextNo = ((int) $header->versions()->max('version_no')) + 1;
            $version = BomVersion::query()->create([
                'bom_header_id' => $header->id,
                'version_no' => $nextNo,
                'status' => BomVersionStatus::DRAFT,
                'effective_from' => now()->toDateString(),
            ]);

            $this->persistVersionItems($version, $data['items']);
            $this->bomService->assertNoCycle($header->product_id, $version);
            $this->bomService->activateVersion($version, $user->id);

            $this->audit->record('bom.quick_saved', $header, null, [
                'bom_header_id' => $header->id,
                'version_no' => $nextNo,
                'item_count' => count($data['items']),
            ]);

            return $header->fresh([
                'product:id,code,name,base_uom_id',
                'activeVersion.items.component:id,code,name',
                'activeVersion.items.uom:id,code,name',
            ]);
        });

        return response()->json(['data' => $bom]);
    }

    public function update(Request $request, BomHeader $bom): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'bom_type' => ['sometimes', new Enum(BomType::class)],
            'status' => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE', 'ARCHIVED'])],
            'stage_template_id' => ['nullable', 'integer', Rule::exists('production_stage_templates', 'id')],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.component_product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.item_type' => ['sometimes', 'string', Rule::in(['COMPONENT', 'SUBASSEMBLY'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'items.*.scrap_rate' => ['sometimes', 'numeric', 'min:0', 'max:1'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $original = $bom->getOriginal();
        $bom->update(array_filter([
            'name' => $data['name'] ?? null,
            'bom_type' => $data['bom_type'] ?? null,
            'status' => $data['status'] ?? null,
            'stage_template_id' => array_key_exists('stage_template_id', $data) ? $data['stage_template_id'] : null,
        ]));

        if (! empty($data['items'])) {
            $user = $request->user();
            $nextNo = ((int) $bom->versions()->max('version_no')) + 1;
            $version = BomVersion::query()->create([
                'bom_header_id' => $bom->id,
                'version_no' => $nextNo,
                'status' => BomVersionStatus::DRAFT,
                'effective_from' => now()->toDateString(),
            ]);

            $this->persistVersionItems($version, $data['items']);
            $this->bomService->assertNoCycle($bom->product_id, $version);
            $this->bomService->activateVersion($version, $user->id);
        }

        $this->audit->recordChanges('bom.updated', $bom, $original);

        return response()->json(['data' => $bom->fresh(['product:id,code,name', 'versions.items.component:id,code,name', 'versions.items.uom:id,code,name'])]);
    }

    public function destroy(Request $request, BomHeader $bom): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        // Check if referenced in production orders
        $isReferenced = DB::table('production_orders')
            ->whereIn('bom_version_id', $bom->versions()->pluck('id'))
            ->exists();

        if ($isReferenced) {
            throw new DomainException(
                'Resep BOM ini sudah digunakan dalam perintah produksi dan tidak dapat dihapus.',
                'BOM_IN_USE',
                409
            );
        }

        DB::transaction(function () use ($bom) {
            foreach ($bom->versions as $version) {
                $version->items()->delete();
                $version->delete();
            }
            $bom->delete();
        });

        $this->audit->record('bom.deleted', $bom);

        return response()->json(['message' => 'Resep BOM berhasil dihapus.']);
    }

    public function assembleStage(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'bom.manage');

        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'source_location_id' => ['sometimes', 'nullable', 'integer', Rule::exists('locations', 'id')],
            'destination_location_id' => ['sometimes', 'nullable', 'integer', Rule::exists('locations', 'id')],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        // Resolve default location if not provided
        $defaultLocation = Location::query()->where('is_active', true)->first();
        $sourceLocId = (int) ($data['source_location_id'] ?? $defaultLocation?->id);
        $destLocId = (int) ($data['destination_location_id'] ?? $defaultLocation?->id);

        if (! $sourceLocId || ! $destLocId) {
            throw new DomainException('Lokasi gudang fisik tidak ditemukan untuk perakitan.', 'LOCATION_NOT_FOUND', 422);
        }

        $product = Product::query()->findOrFail($data['product_id']);
        if (! $product->isStockTracked()) {
            throw new DomainException(
                "Produk [{$product->code}] bukan barang fisik yang dapat dilacak stok perakitannya.",
                'PRODUCT_NOT_STOCK_TRACKED',
                422
            );
        }

        $activeVersion = $this->bomService->findActiveVersionForProduct($product->id);
        if (! $activeVersion) {
            throw new DomainException(
                "Belum ada versi BOM aktif untuk produk [{$product->code}]. Konfigurasi resep BOM terlebih dahulu.",
                'BOM_NO_ACTIVE_VERSION',
                422
            );
        }

        $activeVersion->loadMissing(['items.component', 'items.uom']);
        $lines = [];
        $qtyStr = bcadd((string) $data['quantity'], '0', 4);

        // Outflow lines: Consume input components from previous stage
        foreach ($activeVersion->items as $item) {
            $scrapMultiplier = bcadd('1.0000', (string) ($item->scrap_rate ?? '0'), 4);
            $requiredPerUnit = bcmul((string) $item->quantity, $scrapMultiplier, 4);
            $totalNeeded = bcmul($requiredPerUnit, $qtyStr, 4);

            $lines[] = new StockMovementLine(
                productId: $item->component_product_id,
                quantity: $totalNeeded,
                fromLocationId: $sourceLocId,
                stockStatusFrom: StockStatus::AVAILABLE->value,
            );
        }

        // Inflow line: Deposit assembled product at this stage
        $lines[] = new StockMovementLine(
            productId: $product->id,
            quantity: $qtyStr,
            toLocationId: $destLocId,
            stockStatusTo: StockStatus::AVAILABLE->value,
        );

        $idempotencyKey = $request->header('Idempotency-Key') ?? ('ASM-' . $product->code . '-' . Str::uuid());

        $cmd = new PostInventoryCommand(
            idempotencyKey: $idempotencyKey,
            transactionType: 'PRODUCTION_OUTPUT',
            sourceDocumentType: 'BOM_STAGE_ASSEMBLY',
            sourceDocumentId: $activeVersion->id,
            postedByUserId: $user->id,
            lines: $lines,
            notes: $data['notes'] ?? ("Perakitan BOM {$product->code} sebanyak {$qtyStr} unit"),
        );

        $tx = $this->inventoryPosting->post($cmd);

        $this->audit->record('bom.stage_assembled', $product, null, [
            'bom_version_id' => $activeVersion->id,
            'quantity' => $qtyStr,
            'transaction_id' => $tx['id'],
            'transaction_number' => $tx['document_no'],
        ]);

        return response()->json([
            'message' => "Berhasil merakit {$qtyStr} unit [{$product->code}]. Stok telah bertambah!",
            'data' => [
                'transaction_id' => $tx['id'],
                'transaction_number' => $tx['document_no'],
                'product_id' => $product->id,
                'assembled_qty' => $qtyStr,
            ],
        ]);
    }

    /** @param list<array<string, mixed>> $items */
    private function persistVersionItems(BomVersion $version, array $items): void
    {
        foreach ($items as $idx => $item) {
            BomItem::query()->create([
                'bom_version_id' => $version->id,
                'component_product_id' => $item['component_product_id'],
                'item_type' => $item['item_type'] ?? 'COMPONENT',
                'quantity' => $item['quantity'],
                'uom_id' => $item['uom_id'],
                'scrap_rate' => $item['scrap_rate'] ?? '0.0000',
                'sequence' => $idx + 1,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    private function requirePermission(Request $request, string $code): void
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasPermission($code)) {
            throw new AuthorizationException;
        }
    }
}
