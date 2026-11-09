<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Actions\StockAdjustmentService;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryTransaction;
use App\Domains\Inventory\Models\InventoryTransactionItem;
use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Services\ReservationService;
use App\Domains\MasterData\Models\Product;
use App\Http\Controllers\Controller;
use App\Models\User;
use Carbon\Carbon;
use App\Shared\Exceptions\DomainException;
use App\Shared\Idempotency\IdempotencyService;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Phase 3 Inventory & Warehouse endpoints (`/inventory/balances`, `/inventory/transactions`,
 * `/inventory/stock-card`, `/inventory/transfers`, `/inventory/adjustments`,
 * `/inventory/transactions/{id}/reverse`).
 *
 * Notice that this controller NEVER writes to `inventory_balances` or `inventory_transactions`
 * directly — every stock mutation goes through `InventoryPostingContract`.
 */
final class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryPostingContract $inventory,
        private readonly StockAdjustmentService $adjustments,
        private readonly ReservationService $reservations,
    ) {}

    public function balances(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['inventory.view', 'warehouse.view']);

        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $paginator = InventoryBalance::query()
            ->with(['product.baseUom', 'location.warehouse', 'batch', 'serial'])
            ->when($search !== '', function ($q) use ($search) {
                $q->whereHas('product', fn ($p) => $p->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($request->filled('warehouse_id'), fn ($q) => $q->whereHas('location', fn ($l) => $l->where('warehouse_id', (int) $request->query('warehouse_id'))))
            ->when($request->filled('location_id'), fn ($q) => $q->where('location_id', (int) $request->query('location_id')))
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', (int) $request->query('product_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('stock_status', (string) $request->query('status')))
            ->orderBy('product_id')
            ->orderBy('location_id')
            ->paginate($perPage);

        // Status bucket summary for the warehouse KPI header (10_UI_UX_GUIDELINES.md "Inventory screens").
        $bucketTotals = InventoryBalance::query()
            ->selectRaw('stock_status, SUM(on_hand_qty) as on_hand, SUM(reserved_qty) as reserved')
            ->groupBy('stock_status')
            ->get()
            ->keyBy(fn ($r) => $r->stock_status->value);

        $availRow = $bucketTotals->get('AVAILABLE');
        $availOnHand = $availRow ? bcadd((string) $availRow->on_hand, '0', 4) : '0.0000';
        $availReserved = $availRow ? bcadd((string) $availRow->reserved, '0', 4) : '0.0000';

        $items = collect($paginator->items())->map(fn (InventoryBalance $b) => [
            'id' => $b->id,
            'product' => [
                'id' => $b->product->id,
                'code' => $b->product->code,
                'name' => $b->product->name,
                'uom' => $b->product->baseUom?->code,
                'tracking_method' => $b->product->tracking_method->value,
            ],
            'warehouse' => $b->location?->warehouse ? [
                'id' => $b->location->warehouse->id,
                'code' => $b->location->warehouse->code,
                'name' => $b->location->warehouse->name,
            ] : null,
            'location' => $b->location ? [
                'id' => $b->location->id,
                'code' => $b->location->code,
                'name' => $b->location->name,
                'is_external' => $b->location->is_external,
            ] : null,
            'batch_number' => $b->batch?->batch_number,
            'serial_number' => $b->serial?->serial_number,
            'stock_status' => $b->stock_status->value,
            'on_hand_qty' => $b->on_hand_qty,
            'reserved_qty' => $b->reserved_qty,
            'available_qty' => $b->availableQty(),
            'avg_unit_cost' => $b->avg_unit_cost,
        ]);

        return response()->json([
            'data' => $items,
            'summary' => [
                'on_hand_available_bucket' => $availOnHand,
                'reserved' => $availReserved,
                'available' => bcsub($availOnHand, $availReserved, 4),
                'quarantine' => $bucketTotals->has('QUARANTINE') ? bcadd((string) $bucketTotals->get('QUARANTINE')->on_hand, '0', 4) : '0.0000',
                'damaged' => $bucketTotals->has('DAMAGED') ? bcadd((string) $bucketTotals->get('DAMAGED')->on_hand, '0', 4) : '0.0000',
                'at_vendor' => $bucketTotals->has('AT_VENDOR') ? bcadd((string) $bucketTotals->get('AT_VENDOR')->on_hand, '0', 4) : '0.0000',
                'in_transit' => $bucketTotals->has('IN_TRANSIT') ? bcadd((string) $bucketTotals->get('IN_TRANSIT')->on_hand, '0', 4) : '0.0000',
            ],
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

    public function transactions(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'inventory.view');

        $search = trim((string) $request->query('search', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $tx = InventoryTransaction::query()
            ->with(['poster:id,name', 'items.product:id,code,name', 'items.fromLocation:id,code,name', 'items.toLocation:id,code,name'])
            ->when($search !== '', fn ($q) => $q->where('document_no', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%"))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->query('status')))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'data' => $tx->items(),
            'links' => [
                'first' => $tx->url(1),
                'last' => $tx->url($tx->lastPage()),
                'prev' => $tx->previousPageUrl(),
                'next' => $tx->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $tx->currentPage(),
                'from' => $tx->firstItem(),
                'last_page' => $tx->lastPage(),
                'per_page' => $tx->perPage(),
                'to' => $tx->lastItem(),
                'total' => $tx->total(),
            ],
        ]);
    }

    public function stockCard(Request $request): JsonResponse
    {
        $this->requirePermission($request, ['inventory.view', 'warehouse.view']);

        $validated = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date'],
        ]);

        $productId = (int) $validated['product_id'];
        $locationId = isset($validated['location_id']) ? (int) $validated['location_id'] : null;
        $fromDate = isset($validated['from_date']) ? Carbon::parse($validated['from_date'])->startOfDay() : null;
        $toDate = isset($validated['to_date']) ? Carbon::parse($validated['to_date'])->endOfDay() : null;

        $product = Product::query()->with('baseUom')->findOrFail($productId);

        $lines = InventoryTransactionItem::query()
            ->with(['transaction.poster:id,name', 'fromLocation:id,code,name', 'toLocation:id,code,name'])
            ->where('product_id', $productId)
            ->when($locationId !== null, function ($q) use ($locationId) {
                $q->where(fn ($inner) => $inner->where('from_location_id', $locationId)->orWhere('to_location_id', $locationId));
            })
            ->join('inventory_transactions', 'inventory_transactions.id', '=', 'inventory_transaction_items.inventory_transaction_id')
            ->orderBy('inventory_transactions.transaction_date')
            ->orderBy('inventory_transaction_items.id')
            ->select('inventory_transaction_items.*')
            ->get();

        $running = '0.0000';
        $periodOpening = '0.0000';
        $totalIn = '0.0000';
        $totalOut = '0.0000';
        $movements = [];

        foreach ($lines as $line) {
            $txDate = Carbon::parse($line->transaction->transaction_date);

            $in = ($locationId === null ? $line->to_location_id !== null : $line->to_location_id === $locationId);
            $out = ($locationId === null ? $line->from_location_id !== null : $line->from_location_id === $locationId);

            // A transfer across two locations when viewing all locations has net 0 change across the company.
            $delta = '0.0000';
            $qtyIn = '0.0000';
            $qtyOut = '0.0000';

            if ($in && ! $out) {
                $delta = $line->quantity;
                $qtyIn = $line->quantity;
            } elseif ($out && ! $in) {
                $delta = bcmul($line->quantity, '-1', 4);
                $qtyOut = $line->quantity;
            }

            $opening = $running;
            $running = bcadd($running, $delta, 4);

            // If transaction occurred before the requested start date, it contributes to period opening balance
            if ($fromDate && $txDate->lt($fromDate)) {
                $periodOpening = $running;
                continue;
            }

            // If transaction occurred after the requested end date, ignore it
            if ($toDate && $txDate->gt($toDate)) {
                continue;
            }

            $totalIn = bcadd($totalIn, $qtyIn, 4);
            $totalOut = bcadd($totalOut, $qtyOut, 4);

            $movements[] = [
                'id' => $line->id,
                'document_no' => $line->transaction->document_no,
                'transaction_type' => $line->transaction->transaction_type->value,
                'transaction_date' => $line->transaction->transaction_date->toIso8601String(),
                'from_location' => $line->fromLocation?->code,
                'to_location' => $line->toLocation?->code,
                'quantity' => $line->quantity,
                'in_qty' => $qtyIn,
                'out_qty' => $qtyOut,
                'delta' => $delta,
                'opening_balance' => $opening,
                'closing_balance' => $running,
                'unit_cost' => $line->unit_cost,
                'notes' => $line->transaction->notes,
                'posted_by' => $line->transaction->poster?->name,
            ];
        }

        return response()->json([
            'data' => [
                'product' => [
                    'id' => $product->id,
                    'code' => $product->code,
                    'name' => $product->name,
                    'uom' => $product->baseUom?->code,
                ],
                'product_id' => $productId,
                'location_id' => $locationId,
                'from_date' => $fromDate?->toDateString(),
                'to_date' => $toDate?->toDateString(),
                'period_opening_balance' => $periodOpening,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'closing_balance' => $running,
                'movements' => $movements,
            ],
        ]);
    }

    /**
     * Post an inventory transfer (`08_API_SPECIFICATION.md §3`).
     * Requires `Idempotency-Key` header.
     */
    public function transfer(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'inventory.transaction.post');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'source_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'destination_location_id' => ['required', 'integer', 'different:source_location_id', Rule::exists('locations', 'id')],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.batch_id' => ['nullable', 'integer', Rule::exists('product_batches', 'id')],
            'items.*.product_serial_id' => ['nullable', 'integer', Rule::exists('product_serials', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();
        $lines = array_map(
            fn (array $item) => new StockMovementLine(
                productId: (int) $item['product_id'],
                quantity: (string) $item['quantity'],
                fromLocationId: (int) $data['source_location_id'],
                toLocationId: (int) $data['destination_location_id'],
                batchId: isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                productSerialId: isset($item['product_serial_id']) ? (int) $item['product_serial_id'] : null,
            ),
            $data['items'],
        );

        $posted = $this->inventory->post(new PostInventoryCommand(
            idempotencyKey: $idempotencyKey,
            transactionType: 'TRANSFER',
            sourceDocumentType: 'manual_transfer',
            sourceDocumentId: 0,
            postedByUserId: $user->id,
            lines: $lines,
            notes: $data['notes'] ?? null,
        ));

        return response()->json(['data' => $posted], 201);
    }

    /**
     * Post an inbound opening balance / direct receipt into a location (requires `Idempotency-Key`).
     */
    public function receiveOpening(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'inventory.transaction.post');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'destination_location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'notes' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'items.*.batch_id' => ['nullable', 'integer', Rule::exists('product_batches', 'id')],
            'items.*.product_serial_id' => ['nullable', 'integer', Rule::exists('product_serials', 'id')],
        ]);

        /** @var User $user */
        $user = $request->user();
        $lines = array_map(
            fn (array $item) => new StockMovementLine(
                productId: (int) $item['product_id'],
                quantity: (string) $item['quantity'],
                toLocationId: (int) $data['destination_location_id'],
                batchId: isset($item['batch_id']) ? (int) $item['batch_id'] : null,
                productSerialId: isset($item['product_serial_id']) ? (int) $item['product_serial_id'] : null,
                unitCost: (string) $item['unit_cost'],
            ),
            $data['items'],
        );

        $posted = $this->inventory->post(new PostInventoryCommand(
            idempotencyKey: $idempotencyKey,
            transactionType: 'OPENING_BALANCE',
            sourceDocumentType: 'opening_balance',
            sourceDocumentId: 0,
            postedByUserId: $user->id,
            lines: $lines,
            notes: $data['notes'] ?? 'Saldo awal / penerimaan stok',
        ));

        return response()->json(['data' => $posted], 201);
    }

    public function adjustments(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'inventory.view');

        return response()->json([
            'data' => StockAdjustment::query()
                ->with(['location:id,code,name', 'creator:id,name', 'approver:id,name', 'items.product:id,code,name'])
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function storeAdjustment(Request $request): JsonResponse
    {
        $this->requirePermission($request, 'inventory.transaction.post');

        $data = $request->validate([
            'location_id' => ['required', 'integer', Rule::exists('locations', 'id')],
            'reason' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'items.*.direction' => ['required', 'string', Rule::in(['IN', 'OUT'])],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.batch_id' => ['nullable', 'integer', Rule::exists('product_batches', 'id')],
            'items.*.product_serial_id' => ['nullable', 'integer', Rule::exists('product_serials', 'id')],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $adjustment = $this->adjustments->create($data, $user);

        return response()->json(['data' => $adjustment], 201);
    }

    public function approveAdjustment(Request $request, StockAdjustment $stockAdjustment): JsonResponse
    {
        $this->requirePermission($request, 'inventory.adjustment.approve');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        /** @var User $user */
        $user = $request->user();
        $approved = $this->adjustments->approveAndPost($stockAdjustment, $user, $idempotencyKey);

        return response()->json(['data' => $approved]);
    }

    public function reverse(Request $request, InventoryTransaction $inventoryTransaction): JsonResponse
    {
        $this->requirePermission($request, 'inventory.transaction.reverse');
        $idempotencyKey = $this->requireIdempotencyHeader($request);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $result = $this->inventory->reverse($inventoryTransaction->id, $idempotencyKey, $user->id, $data['reason']);

        return response()->json(['data' => $result], 201);
    }

    private function requireIdempotencyHeader(Request $request): string
    {
        $key = trim((string) $request->header(IdempotencyService::HEADER));
        if ($key === '' || strlen($key) > 128) {
            throw new DomainException(
                'Header Idempotency-Key wajib disertakan untuk endpoint posting.',
                'IDEMPOTENCY_KEY_REQUIRED',
                422,
                [IdempotencyService::HEADER => ['Header Idempotency-Key wajib diisi (maks. 128 karakter).']],
            );
        }

        return $key;
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
