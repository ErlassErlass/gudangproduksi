<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Enums\TransactionType;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\MasterData\Enums\FulfillmentType;
use App\Domains\MasterData\Enums\PhysicalType;
use App\Domains\MasterData\Enums\ProductType;
use App\Domains\MasterData\Enums\TrackingMethod;
use App\Domains\MasterData\Models\Customer;
use App\Domains\MasterData\Models\DocumentTemplate;
use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductBarcode;
use App\Domains\MasterData\Models\ProductBarcodePrint;
use App\Domains\MasterData\Models\ProductCategory;
use App\Domains\MasterData\Models\ProductComponent;
use App\Domains\MasterData\Models\Supplier;
use App\Domains\MasterData\Models\Uom;
use App\Domains\MasterData\Models\Vendor;
use App\Domains\Warehouse\Models\Location;
use App\Domains\Warehouse\Models\Warehouse;
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
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

/**
 * Phase 1 Master Data endpoints (`/products`, `/categories`, `/uoms`, `/warehouses`,
 * `/locations`, `/customers`, `/suppliers`, `/vendors`) following `08_API_SPECIFICATION.md`.
 */
final class MasterDataController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly InventoryPostingContract $inventory,
    ) {}

    public function products(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        $paginator = Product::query()
            ->with([
                'category:id,code,name',
                'baseUom:id,code,name',
                'barcodes',
                'components.componentProduct:id,code,name,product_type,physical_type,base_uom_id',
                'components.uom:id,code,name',
            ])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('legacy_code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('category_id'), fn ($q) => $q->where('category_id', (int) $request->query('category_id')))
            ->when($request->filled('category_code'), function ($q) use ($request) {
                $codes = array_filter(array_map('trim', explode(',', (string) $request->query('category_code'))));
                $q->whereHas('category', fn ($c) => $c->whereIn('code', $codes));
            })
            ->when($request->filled('item_group'), function ($q) use ($request) {
                $group = (string) $request->query('item_group');
                match ($group) {
                    'component' => $q->whereHas('category', fn ($c) => $c->where('code', 'COMP')),
                    'module' => $q->whereHas('category', fn ($c) => $c->where('code', 'WIP-MOD')),
                    'box' => $q->whereHas('category', fn ($c) => $c->where('code', 'WIP-BOX')),
                    'finished_good' => $q->where(function ($sub) {
                        $sub->whereHas('category', fn ($c) => $c->where('code', 'FG'))
                            ->orWhere('product_type', ProductType::SERVICE->value);
                    }),
                    'program' => $q->where(function ($sub) {
                        $sub->where('product_type', ProductType::PROGRAM->value)
                            ->orWhereHas('category', fn ($c) => $c->where('code', 'PROG'));
                    }),
                    default => null,
                };
            })
            ->when($request->filled('product_type'), fn ($q) => $q->where('product_type', (string) $request->query('product_type')))
            ->when($request->filled('tracking_method'), fn ($q) => $q->where('tracking_method', (string) $request->query('tracking_method')))
            ->orderBy('code')
            ->paginate($perPage);

        // Attach live stock summary per product from inventory_balances.
        $productIds = collect($paginator->items())->pluck('id')->all();
        $stockByProduct = InventoryBalance::query()
            ->selectRaw('product_id, stock_status, SUM(on_hand_qty) as on_hand, SUM(reserved_qty) as reserved')
            ->whereIn('product_id', $productIds)
            ->groupBy('product_id', 'stock_status')
            ->get()
            ->groupBy('product_id');

        $data = collect($paginator->items())->map(function (Product $p) use ($stockByProduct) {
            $rows = $stockByProduct->get($p->id, collect());
            $availableRow = $rows->firstWhere('stock_status', \App\Domains\Inventory\Enums\StockStatus::AVAILABLE);
            $onHand = $availableRow ? bcadd((string) $availableRow->on_hand, '0', 4) : '0.0000';
            $reserved = $availableRow ? bcadd((string) $availableRow->reserved, '0', 4) : '0.0000';
            $available = bcsub($onHand, $reserved, 4);

            return [
                ...$p->toArray(),
                'available_qty' => $available,
                'on_hand_qty' => $onHand,
                'stock_health' => $this->resolveStockHealth($p, $available),
            ];
        });

        return response()->json([
            'data' => $data,
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function showProduct(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');
        $product->load([
            'category',
            'baseUom',
            'barcodes',
            'serials.currentLocation',
            'batches',
            'components.componentProduct:id,code,name,product_type,physical_type,base_uom_id',
            'components.uom:id,code,name',
        ]);

        return response()->json(['data' => $product]);
    }

    public function productStock(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, ['master_data.view', 'inventory.view']);

        $balances = InventoryBalance::query()
            ->with(['location.warehouse', 'batch', 'serial'])
            ->where('product_id', $product->id)
            ->orderBy('location_id')
            ->get()
            ->map(fn (InventoryBalance $b) => [
                'id' => $b->id,
                'location' => $b->location ? ['id' => $b->location->id, 'code' => $b->location->code, 'name' => $b->location->name] : null,
                'warehouse' => $b->location?->warehouse ? ['id' => $b->location->warehouse->id, 'code' => $b->location->warehouse->code, 'name' => $b->location->warehouse->name] : null,
                'batch_number' => $b->batch?->batch_number,
                'serial_number' => $b->serial?->serial_number,
                'stock_status' => $b->stock_status->value,
                'on_hand_qty' => $b->on_hand_qty,
                'reserved_qty' => $b->reserved_qty,
                'available_qty' => $b->availableQty(),
                'avg_unit_cost' => $b->avg_unit_cost,
            ]);

        return response()->json(['data' => $balances]);
    }

    public function storeProduct(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $validated = $this->validateProduct($request);
        $components = $request->input('components');

        $product = DB::transaction(function () use ($validated, $components): Product {
            $prod = Product::query()->create($validated);
            if (is_array($components)) {
                $this->saveComponents($prod, $components);
            }

            return $prod;
        });

        $product->load(['category', 'baseUom', 'components.componentProduct', 'components.uom']);
        $this->audit->record('master_data.product.created', $product, null, $product->toArray());

        return response()->json(['data' => $product], 201);
    }

    public function updateProduct(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $original = $product->getOriginal();
        $validated = $this->validateProduct($request, $product->id);
        $components = $request->input('components');

        DB::transaction(function () use ($product, $validated, $components): void {
            $product->update($validated);
            if (is_array($components)) {
                $this->saveComponents($product, $components);
            }
        });

        $this->audit->recordChanges('master_data.product.updated', $product, $original);

        return response()->json([
            'data' => $product->fresh([
                'category',
                'baseUom',
                'components.componentProduct:id,code,name,product_type,physical_type,base_uom_id',
                'components.uom:id,code,name',
            ]),
        ]);
    }

    public function destroyProduct(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        $hasLedger = DB::table('inventory_transactions')
            ->join('inventory_transaction_items', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->where('inventory_transaction_items.product_id', $product->id)
            ->exists();

        if ($hasLedger) {
            throw new DomainException(
                "Item [{$product->code}] memiliki riwayat mutasi buku besar inventaris dan tidak dapat dihapus. Anda dapat mengubah statusnya menjadi INACTIVE.",
                'PRODUCT_HAS_LEDGER',
                409
            );
        }

        DB::transaction(function () use ($product): void {
            ProductComponent::query()->where('parent_product_id', $product->id)->delete();
            ProductComponent::query()->where('component_product_id', $product->id)->delete();
            $product->delete();
        });

        $this->audit->record('master_data.product.deleted', $product, $product->toArray(), null);

        return response()->json(['message' => "Item [{$product->code}] berhasil dihapus."]);
    }

    public function generateBarcode(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        if ($product->physical_type !== PhysicalType::PHYSICAL || ! $product->isStockTracked()) {
            throw new DomainException(
                "Item [{$product->code}] bertipe non-fisik / layanan dan tidak dapat dibuat barcodenya.",
                'NON_PHYSICAL_BARCODE_NOT_ALLOWED',
                422
            );
        }

        $primary = $product->barcodes()->where('is_primary', true)->first();
        if (! $primary) {
            $code = '899' . str_pad((string) $product->id, 9, '0', STR_PAD_LEFT);
            $primary = ProductBarcode::query()->create([
                'product_id' => $product->id,
                'barcode' => $code,
                'barcode_type' => 'CODE128',
                'is_primary' => true,
            ]);
            $this->audit->record('master_data.product.barcode_generated', $product, null, [
                'barcode' => $code,
            ]);
        }

        return response()->json([
            'data' => [
                'product_id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'barcode' => $primary->barcode,
                'barcode_type' => $primary->barcode_type,
            ],
        ]);
    }

    /**
     * Cetak stiker barcode Finished Good baru untuk pengeluaran barang.
     * Mengurangi stok fisik Finished Good sebanyak kuantitas stiker yang dicetak.
     */
    public function printBarcodes(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $quantity = (int) $validated['quantity'];

        if ($product->physical_type !== PhysicalType::PHYSICAL || ! $product->isStockTracked()) {
            throw new DomainException(
                "Hanya produk fisik dengan pelacakan stok yang dapat dicetak barcode pengeluarannya.",
                'PRODUCT_NOT_STOCK_TRACKED',
                422
            );
        }

        // Resolusi lokasi fisik
        $locationId = $validated['location_id'] ?? null;
        if (! $locationId) {
            $defaultLocation = Location::query()->where('is_active', true)->first();
            $locationId = $defaultLocation?->id;
        }

        if (! $locationId) {
            throw new DomainException('Lokasi gudang fisik tidak ditemukan untuk pengeluaran stok.', 'LOCATION_NOT_FOUND', 422);
        }

        // Cek stok fisik yang tersedia di lokasi
        $balance = InventoryBalance::query()
            ->where('product_id', $product->id)
            ->where('location_id', $locationId)
            ->where('stock_status', StockStatus::AVAILABLE->value)
            ->first();

        $availableStock = $balance ? (float) bcsub((string) $balance->on_hand_qty, (string) $balance->reserved_qty, 4) : 0.0;
        if ($availableStock < $quantity) {
            throw new DomainException(
                "Stok fisik Finished Good tidak mencukupi untuk pengeluaran barcode. Tersedia: {$availableStock}, diminta: {$quantity}.",
                'INSUFFICIENT_STOCK_FOR_BARCODE',
                422,
                ['quantity' => ["Stok fisik hanya tersedia {$availableStock} unit."]]
            );
        }

        // Dapatkan atau buat barcode primer
        $primary = $product->barcodes()->where('is_primary', true)->first();
        if (! $primary) {
            $code = '899' . str_pad((string) $product->id, 9, '0', STR_PAD_LEFT);
            $primary = ProductBarcode::query()->create([
                'product_id' => $product->id,
                'barcode' => $code,
                'barcode_type' => 'CODE128',
                'is_primary' => true,
            ]);
        }

        // Generate batch number unik: BCP-YYYYMMDD-XXXX
        $dateStr = now()->format('Ymd');
        $batchCount = ProductBarcodePrint::query()->whereDate('created_at', now()->toDateString())->count() + 1;
        $batchNo = sprintf('BCP-%s-%04d', $dateStr, $batchCount);

        /** @var User $user */
        $user = $request->user();

        // Lakukan pengeluaran stok fisik Finished Good (mengurangi stok) via InventoryPostingContract
        $idempotencyKey = 'BARCODE-PRINT-' . $batchNo . '-' . uniqid();
        $movementLine = new StockMovementLine(
            productId: $product->id,
            quantity: bcadd((string) $quantity, '0', 4),
            fromLocationId: (int) $locationId,
            stockStatusFrom: StockStatus::AVAILABLE->value,
        );

        $postedTx = $this->inventory->post(new PostInventoryCommand(
            idempotencyKey: $idempotencyKey,
            transactionType: TransactionType::DISTRIBUTION_DISPATCH->value,
            sourceDocumentType: 'barcode_print_batch',
            sourceDocumentId: 0,
            postedByUserId: $user->id,
            lines: [$movementLine],
            notes: "Pengeluaran stok cetak barcode Finished Good [{$product->code}] ({$quantity} stiker) - Batch {$batchNo}",
        ));

        // Catat riwayat pencetakan barcode
        $barcodePrint = ProductBarcodePrint::query()->create([
            'product_id' => $product->id,
            'user_id' => $user->id,
            'batch_no' => $batchNo,
            'barcode' => $primary->barcode,
            'quantity' => $quantity,
            'printed_at' => now(),
            'reprint_count' => 0,
            'last_reprinted_at' => null,
            'inventory_transaction_id' => $postedTx['id'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->audit->record('master_data.product.barcode_printed', $product, null, [
            'batch_no' => $batchNo,
            'barcode' => $primary->barcode,
            'quantity' => $quantity,
            'inventory_transaction_id' => $postedTx['id'],
        ]);

        return response()->json([
            'data' => [
                'id' => $barcodePrint->id,
                'batch_no' => $barcodePrint->batch_no,
                'barcode' => $barcodePrint->barcode,
                'quantity' => $barcodePrint->quantity,
                'printed_at' => $barcodePrint->printed_at->toIso8601String(),
                'reprint_count' => $barcodePrint->reprint_count,
                'last_reprinted_at' => null,
                'product' => [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                ],
            ],
            'message' => "Stok berhasil dikurangi {$quantity} unit dan {$quantity} stiker barcode siap dicetak.",
        ], 201);
    }

    /**
     * Cetak ulang (reprint) batch barcode yang sudah pernah dicetak sebelumnya.
     * Tidak mengurangi stok fisik Finished Good.
     */
    public function reprintBarcode(Request $request, ProductBarcodePrint $barcodePrint): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        // Cetak ulang TIDAK mengurangi stok
        $barcodePrint->increment('reprint_count');
        $barcodePrint->update(['last_reprinted_at' => now()]);

        $barcodePrint->load(['product.category', 'user:id,name']);

        $this->audit->record('master_data.product.barcode_reprinted', $barcodePrint->product, null, [
            'batch_no' => $barcodePrint->batch_no,
            'reprint_count' => $barcodePrint->reprint_count,
            'last_reprinted_at' => $barcodePrint->last_reprinted_at->toIso8601String(),
        ]);

        return response()->json([
            'data' => [
                'id' => $barcodePrint->id,
                'batch_no' => $barcodePrint->batch_no,
                'barcode' => $barcodePrint->barcode,
                'quantity' => $barcodePrint->quantity,
                'printed_at' => $barcodePrint->printed_at->toIso8601String(),
                'reprint_count' => $barcodePrint->reprint_count,
                'last_reprinted_at' => $barcodePrint->last_reprinted_at?->toIso8601String(),
                'product' => [
                    'id' => $barcodePrint->product->id,
                    'code' => $barcodePrint->product->code,
                    'name' => $barcodePrint->product->name,
                ],
            ],
            'message' => 'Cetak ulang berhasil dicatat (tidak mengurangi stok).',
        ]);
    }

    /**
     * Riwayat pencetakan barcode produk Finished Good.
     */
    public function barcodePrints(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        $prints = ProductBarcodePrint::query()
            ->with('user:id,name')
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->get();

        $totalBatches = $prints->count();
        $totalQuantity = $prints->sum('quantity');
        $lastPrinted = $prints->first()?->printed_at?->toIso8601String();
        $lastReprinted = $prints->whereNotNull('last_reprinted_at')->sortByDesc('last_reprinted_at')->first()?->last_reprinted_at?->toIso8601String();

        return response()->json([
            'data' => $prints,
            'summary' => [
                'total_batches' => $totalBatches,
                'total_quantity' => $totalQuantity,
                'last_printed_at' => $lastPrinted,
                'last_reprinted_at' => $lastReprinted,
            ],
        ]);
    }

    /**
     * Scan barcode untuk mendeteksi produk Finished Good.
     */
    public function scanBarcode(Request $request, string $barcode): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        $barcode = trim($barcode);

        // Cari via ProductBarcode atau fallback Product code
        $productBarcode = ProductBarcode::query()->where('barcode', $barcode)->first();
        $product = $productBarcode?->product;

        if (! $product) {
            $product = Product::query()->where('code', $barcode)->first();
        }

        if (! $product) {
            throw new DomainException("Barcode atau kode produk [{$barcode}] tidak ditemukan.", 'BARCODE_NOT_FOUND', 404);
        }

        $product->loadMissing(['category', 'baseUom', 'barcodes']);

        $stockSummary = DB::table('inventory_balances')
            ->where('product_id', $product->id)
            ->selectRaw("
                COALESCE(SUM(on_hand_qty), 0) as total_on_hand,
                COALESCE(SUM(CASE WHEN stock_status = 'AVAILABLE' THEN on_hand_qty - reserved_qty ELSE 0 END), 0) as available_qty
            ")
            ->first();

        $latestPrint = ProductBarcodePrint::query()
            ->where('product_id', $product->id)
            ->orderByDesc('id')
            ->first();

        $totalPrints = ProductBarcodePrint::query()->where('product_id', $product->id)->count();

        return response()->json([
            'data' => [
                'id' => $product->id,
                'code' => $product->code,
                'name' => $product->name,
                'description' => $product->description,
                'category' => $product->category ? [
                    'id' => $product->category->id,
                    'code' => $product->category->code,
                    'name' => $product->category->name,
                ] : null,
                'base_uom' => $product->baseUom ? [
                    'id' => $product->baseUom->id,
                    'code' => $product->baseUom->code,
                    'name' => $product->baseUom->name,
                ] : null,
                'barcode' => $productBarcode?->barcode ?? $product->barcodes->first()?->barcode ?? $product->code,
                'available_qty' => number_format((float) ($stockSummary->available_qty ?? 0), 4, '.', ''),
                'total_on_hand' => number_format((float) ($stockSummary->total_on_hand ?? 0), 4, '.', ''),
                'last_printed_at' => $latestPrint?->printed_at?->toIso8601String(),
                'total_print_batches' => $totalPrints,
            ],
        ]);
    }

    public function syncComponents(Request $request, Product $product): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        $data = $request->validate([
            'components' => ['present', 'array'],
            'components.*.component_product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'components.*.quantity' => ['required', 'numeric', 'gt:0'],
            'components.*.uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'components.*.component_role' => ['sometimes', 'string', 'max:48'],
        ]);

        DB::transaction(function () use ($product, $data): void {
            $this->saveComponents($product, $data['components']);
        });

        $this->audit->record('master_data.product.components_synced', $product, null, [
            'count' => count($data['components']),
        ]);

        return response()->json([
            'data' => $product->fresh([
                'category',
                'baseUom',
                'components.componentProduct:id,code,name,product_type,physical_type,base_uom_id',
                'components.uom:id,code,name',
            ]),
        ]);
    }

    /** @param list<array<string, mixed>> $components */
    private function saveComponents(Product $product, array $components): void
    {
        ProductComponent::query()->where('parent_product_id', $product->id)->delete();
        foreach ($components as $item) {
            ProductComponent::query()->create([
                'parent_product_id' => $product->id,
                'component_product_id' => $item['component_product_id'],
                'quantity' => $item['quantity'],
                'uom_id' => $item['uom_id'],
                'component_role' => $item['component_role'] ?? 'MEMBER',
            ]);
        }
    }

    public function categories(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        return response()->json([
            'data' => ProductCategory::query()->with('parent:id,code,name')->orderBy('code')->get(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'code' => ['required', 'string', 'max:48', Rule::unique('product_categories', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $cat = ProductCategory::query()->create($data);
        $this->audit->record('master_data.category.created', $cat, null, $cat->toArray());

        return response()->json(['data' => $cat], 201);
    }

    public function uoms(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        return response()->json(['data' => Uom::query()->orderBy('code')->get()]);
    }

    public function storeUom(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:24', Rule::unique('uoms', 'code')],
            'name' => ['required', 'string', 'max:80'],
            'dimension' => ['sometimes', 'string', 'max:32'],
        ]);
        $uom = Uom::query()->create($data);
        $this->audit->record('master_data.uom.created', $uom, null, $uom->toArray());

        return response()->json(['data' => $uom], 201);
    }

    public function warehouses(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['master_data.view', 'warehouse.view']);

        return response()->json([
            'data' => Warehouse::query()->withCount('locations')->orderBy('code')->get(),
        ]);
    }

    public function storeWarehouse(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:32', Rule::unique('warehouses', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'warehouse_type' => ['required', 'string', Rule::in(['INTERNAL', 'EXTERNAL_VENDOR', 'CUSTOMER'])],
            'address' => ['nullable', 'string'],
        ]);
        $wh = Warehouse::query()->create($data);
        $this->audit->record('master_data.warehouse.created', $wh, null, $wh->toArray());

        return response()->json(['data' => $wh], 201);
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'warehouse_type' => ['required', 'string', Rule::in(['INTERNAL', 'EXTERNAL_VENDOR', 'CUSTOMER'])],
            'address' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $original = $warehouse->getOriginal();
        $warehouse->update($data);
        $this->audit->recordChanges('master_data.warehouse.updated', $warehouse, $original);

        return response()->json(['data' => $warehouse]);
    }

    public function destroyWarehouse(Request $request, Warehouse $warehouse): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        if ($warehouse->locations()->exists()) {
            throw new DomainException(
                'Gedung / gudang ini masih memiliki data lantai/ruangan/rak dan tidak dapat dihapus.',
                'WAREHOUSE_HAS_LOCATIONS',
                409
            );
        }

        $warehouse->delete();
        $this->audit->record('master_data.warehouse.deleted', $warehouse);

        return response()->json(['message' => 'Gedung berhasil dihapus.']);
    }

    public function locations(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['master_data.view', 'warehouse.view', 'inventory.view']);

        $locations = Location::query()
            ->with(['warehouse:id,code,name', 'parent:id,code,name'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', (int) $request->query('warehouse_id')))
            ->when($request->filled('location_type'), fn ($q) => $q->where('location_type', (string) $request->query('location_type')))
            ->orderBy('warehouse_id')
            ->orderBy('code')
            ->get();

        return response()->json(['data' => $locations]);
    }

    public function storeLocation(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'warehouse_id' => ['required', 'integer', Rule::exists('warehouses', 'id')],
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'code' => ['required', 'string', 'max:64'],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('locations', 'barcode')],
            'name' => ['required', 'string', 'max:120'],
            'location_type' => ['required', 'string', Rule::in(['BUILDING', 'FLOOR', 'ROOM', 'RACK', 'BIN', 'VENDOR', 'TRANSIT', 'QUARANTINE'])],
            'is_external' => ['sometimes', 'boolean'],
        ]);
        $loc = Location::query()->create($data);
        $this->audit->record('master_data.location.created', $loc, null, $loc->toArray());

        return response()->json(['data' => $loc->load(['warehouse:id,code,name', 'parent:id,code,name'])], 201);
    }

    public function updateLocation(Request $request, Location $location): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'name' => ['sometimes', 'string', 'max:120'],
            'barcode' => ['nullable', 'string', 'max:120', Rule::unique('locations', 'barcode')->ignore($location->id)],
            'location_type' => ['sometimes', 'string', Rule::in(['BUILDING', 'FLOOR', 'ROOM', 'RACK', 'BIN', 'VENDOR', 'TRANSIT', 'QUARANTINE'])],
            'is_external' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            if ($data['parent_id'] === $location->id) {
                throw new DomainException('Lokasi tidak dapat menjadi induk bagi dirinya sendiri.', 'INVALID_PARENT', 422);
            }
            $parent = Location::findOrFail($data['parent_id']);
            $data['warehouse_id'] = $parent->warehouse_id;
        }

        $original = $location->getOriginal();
        $location->update($data);
        $this->audit->recordChanges('master_data.location.updated', $location, $original);

        return response()->json(['data' => $location->load(['warehouse:id,code,name', 'parent:id,code,name'])]);
    }

    public function destroyLocation(Request $request, Location $location): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        if (Location::query()->where('parent_id', $location->id)->exists()) {
            throw new DomainException(
                'Lokasi ini masih menjadi induk dari lokasi lain (ruangan/rak) dan tidak dapat dihapus.',
                'LOCATION_HAS_CHILDREN',
                409
            );
        }

        $hasStock = DB::table('inventory_balances')->where('location_id', $location->id)->where('on_hand_qty', '>', 0)->exists();
        if ($hasStock) {
            throw new DomainException(
                'Lokasi ini masih memiliki saldo stok fisik on-hand dan tidak dapat dihapus.',
                'LOCATION_HAS_STOCK',
                409
            );
        }

        $location->delete();
        $this->audit->record('master_data.location.deleted', $location);

        return response()->json(['message' => 'Data lokasi berhasil dihapus.']);
    }

    public function customers(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        return response()->json(['data' => Customer::query()->orderBy('code')->get()]);
    }

    public function storeCustomer(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:48', Rule::unique('customers', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'sales_name' => ['nullable', 'string', 'max:120'],
            'customer_type' => ['required', 'string', Rule::in(['SCHOOL', 'ORGANIZATION', 'INDIVIDUAL'])],
            'school_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:48'],
            'email' => ['nullable', 'email', 'max:160'],
        ]);
        $customer = Customer::query()->create($data);
        $this->audit->record('master_data.customer.created', $customer, null, $customer->toArray());

        return response()->json(['data' => $customer], 201);
    }

    public function updateCustomer(Request $request, Customer $customer): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'sales_name' => ['nullable', 'string', 'max:120'],
            'customer_type' => ['required', 'string', Rule::in(['SCHOOL', 'ORGANIZATION', 'INDIVIDUAL'])],
            'school_name' => ['nullable', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:48'],
            'email' => ['nullable', 'email', 'max:160'],
        ]);
        $original = $customer->getOriginal();
        $customer->update($data);
        $this->audit->recordChanges('master_data.customer.updated', $customer, $original);

        return response()->json(['data' => $customer]);
    }

    public function destroyCustomer(Request $request, Customer $customer): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');

        $hasRental = DB::table('rental_orders')->where('customer_id', $customer->id)->exists();
        if ($hasRental) {
            throw new DomainException(
                'Customer ini memiliki riwayat transaksi rental/peminjaman dan tidak dapat dihapus.',
                'CUSTOMER_HAS_TRANSACTIONS',
                409
            );
        }

        $customer->delete();
        $this->audit->record('master_data.customer.deleted', $customer);

        return response()->json(['message' => 'Customer berhasil dihapus.']);
    }

    // --- Template Surat (Document Templates) ---

    public function documentTemplates(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        return response()->json([
            'data' => DocumentTemplate::query()->orderBy('name')->get(),
        ]);
    }

    public function storeDocumentTemplate(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('document_templates', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'document_type' => ['required', 'string', Rule::in(['SURAT_JALAN', 'BAST', 'PERJANJIAN_SEWA', 'KLAIM_KERUSAKAN', 'SPK_PRODUKSI', 'PURCHASE_ORDER'])],
            'template_body' => ['required', 'string'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $tpl = DocumentTemplate::query()->create($data);
        $this->audit->record('master_data.document_template.created', $tpl, null, $tpl->toArray());

        return response()->json(['data' => $tpl], 201);
    }

    public function updateDocumentTemplate(Request $request, DocumentTemplate $template): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'document_type' => ['required', 'string', Rule::in(['SURAT_JALAN', 'BAST', 'PERJANJIAN_SEWA', 'KLAIM_KERUSAKAN', 'SPK_PRODUKSI', 'PURCHASE_ORDER'])],
            'template_body' => ['required', 'string'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $original = $template->getOriginal();
        $template->update($data);
        $this->audit->recordChanges('master_data.document_template.updated', $template, $original);

        return response()->json(['data' => $template]);
    }

    public function destroyDocumentTemplate(Request $request, DocumentTemplate $template): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $template->delete();
        $this->audit->record('master_data.document_template.deleted', $template);

        return response()->json(['message' => 'Template surat berhasil dihapus.']);
    }

    public function suppliers(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.view');

        return response()->json(['data' => Supplier::query()->orderBy('code')->get()]);
    }

    public function storeSupplier(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:48', Rule::unique('suppliers', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:48'],
            'email' => ['nullable', 'email', 'max:160'],
        ]);
        $supplier = Supplier::query()->create($data);
        $this->audit->record('master_data.supplier.created', $supplier, null, $supplier->toArray());

        return response()->json(['data' => $supplier], 201);
    }

    public function vendors(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['master_data.view', 'vendor.view']);

        return response()->json([
            'data' => Vendor::query()->with('defaultLocation:id,code,name')->orderBy('code')->get(),
        ]);
    }

    public function storeVendor(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'master_data.manage');
        $data = $request->validate([
            'code' => ['required', 'string', 'max:48', Rule::unique('vendors', 'code')],
            'name' => ['required', 'string', 'max:160'],
            'vendor_type' => ['sometimes', 'string', 'max:32'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:48'],
            'default_location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
        ]);
        $vendor = Vendor::query()->create($data);
        $this->audit->record('master_data.vendor.created', $vendor, null, $vendor->toArray());

        return response()->json(['data' => $vendor], 201);
    }

    /** @return array<string, mixed> */
    private function validateProduct(Request $request, ?int $ignoreId = null): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:64', Rule::unique('products', 'code')->ignore($ignoreId)],
            'legacy_code' => ['nullable', 'string', 'max:64'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'product_type' => ['required', new Enum(ProductType::class)],
            'physical_type' => ['required', new Enum(PhysicalType::class)],
            'fulfillment_type' => ['required', new Enum(FulfillmentType::class)],
            'tracking_method' => ['required', new Enum(TrackingMethod::class)],
            'base_uom_id' => ['required', 'integer', Rule::exists('uoms', 'id')],
            'safety_stock_qty' => ['sometimes', 'numeric', 'min:0'],
            'reference_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'is_manufactured' => ['sometimes', 'boolean'],
            'is_purchasable' => ['sometimes', 'boolean'],
            'is_sellable' => ['sometimes', 'boolean'],
            'is_rentable' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        // Classification guard (01_PRD.md FR-03 & AGENT_INSTRUCTIONS.md §8):
        // SERVICE and PROGRAM items must use tracking_method=NONE and physical_type!=PHYSICAL.
        if (in_array($validated['product_type'], [ProductType::SERVICE->value, ProductType::PROGRAM->value], true)
            && $validated['tracking_method'] !== TrackingMethod::NONE->value
        ) {
            throw new DomainException(
                'Item bertipe SERVICE atau PROGRAM wajib menggunakan tracking_method = NONE.',
                'PRODUCT_INVALID_CLASSIFICATION',
                422,
                ['tracking_method' => ['SERVICE dan PROGRAM tidak dilacak sebagai stok fisik langsung; gunakan NONE.']],
            );
        }

        return $validated;
    }

    private function resolveStockHealth(Product $p, string $available): string
    {
        if (! $p->isStockTracked()) {
            return 'NON_STOCK';
        }
        if (bccomp($available, '0', 4) <= 0) {
            return 'OUT_OF_STOCK';
        }
        if (bccomp($p->safety_stock_qty ?? '0', '0', 4) > 0 && bccomp($available, $p->safety_stock_qty, 4) <= 0) {
            return 'LOW_STOCK';
        }

        return 'SAFE';
    }

    /** @param string|list<string> $codes */
    private function requirePermission(Request $request, string|array $codes): void
    {
        /** @var User $user */
        $user = $request->user();
        foreach ((array) $codes as $code) {
            if ($user->hasPermission($code)) {
                return;
            }
        }
        throw new AuthorizationException;
    }
}
