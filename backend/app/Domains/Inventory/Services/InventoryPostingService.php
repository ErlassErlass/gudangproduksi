<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Enums\ReservationStatus;
use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryReservation;
use App\Domains\Inventory\Models\InventoryTransaction;
use App\Domains\Inventory\Models\InventoryTransactionItem;
use App\Domains\MasterData\Enums\TrackingMethod;
use App\Domains\MasterData\Models\Product;
use App\Domains\MasterData\Models\ProductSerial;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use App\Shared\Idempotency\IdempotencyService;
use App\Shared\Inventory\Contracts\InventoryPostingContract;
use App\Shared\Inventory\DTO\PostInventoryCommand;
use App\Shared\Inventory\DTO\StockMovementLine;
use App\Shared\Numbering\DocumentNumberGenerator;
use App\Shared\Outbox\OutboxEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Concrete implementation of `InventoryPostingContract` (Gate A — Phase 3).
 *
 * Enforces all 10 inventory invariants in `agent_docs/06_BUSINESS_RULES.md` and the contract in
 * `AGENT_INSTRUCTIONS.md §7`:
 *  1. Validate product classification & tracking method (PROGRAM/SERVICE/NON_INVENTORY rejected).
 *  2. Resolve & pre-create balance rows, then lock all affected dimensions in deterministic
 *     `dimension_key` order (`SELECT ... FOR UPDATE`) to prevent deadlocks.
 *  3. Validate available quantity (`on_hand − reserved` for AVAILABLE stock) and reservation rules.
 *  4. Persist immutable ledger header (`inventory_transactions`) and lines (`inventory_transaction_items`).
 *  5. Atomically update balance projections, moving weighted average cost, and serial location/status.
 *  6. Persist idempotency result, audit entry, and transactional outbox event inside one DB transaction.
 */
final readonly class InventoryPostingService implements InventoryPostingContract
{
    public function __construct(
        private DocumentNumberGenerator $numbering,
        private IdempotencyService $idempotency,
        private AuditLogger $audit,
    ) {}

    public function post(PostInventoryCommand $command): array
    {
        if ($command->lines === []) {
            throw new DomainException('Transaksi inventaris wajib memiliki minimal satu baris item.', 'INVENTORY_EMPTY_LINES', 422);
        }

        $payloadHash = hash('sha256', json_encode([
            'type' => $command->transactionType,
            'src' => [$command->sourceDocumentType, $command->sourceDocumentId],
            'lines' => array_map(fn (StockMovementLine $l) => (array) $l, $command->lines),
        ], JSON_THROW_ON_ERROR));

        $result = DB::transaction(function () use ($command, $payloadHash): array {
            return $this->idempotency->execute(
                $command->idempotencyKey,
                'inventory.post:'.$command->transactionType,
                $payloadHash,
                $command->postedByUserId,
                fn () => [
                    'status' => 201,
                    'body' => $this->executePosting($command),
                ],
            );
        });

        return $result['body'];
    }

    public function reverse(int $inventoryTransactionId, string $idempotencyKey, int $userId, string $reason): array
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Alasan reversal wajib diisi.', 'INVENTORY_REVERSAL_REASON_REQUIRED', 422);
        }

        $payloadHash = hash('sha256', "reverse|{$inventoryTransactionId}|{$reason}");

        $result = DB::transaction(function () use ($inventoryTransactionId, $idempotencyKey, $userId, $reason, $payloadHash): array {
            return $this->idempotency->execute(
                $idempotencyKey,
                'inventory.reverse',
                $payloadHash,
                $userId,
                function () use ($inventoryTransactionId, $idempotencyKey, $userId, $reason): array {
                    /** @var InventoryTransaction $original */
                    $original = InventoryTransaction::query()
                        ->with('items')
                        ->lockForUpdate()
                        ->findOrFail($inventoryTransactionId);

                    if ($original->status === 'REVERSED') {
                        throw new DomainException('Transaksi ini sudah di-reverse sebelumnya.', 'INVENTORY_ALREADY_REVERSED', 409);
                    }
                    if ($original->status !== 'POSTED') {
                        throw new DomainException('Hanya transaksi berstatus POSTED yang dapat di-reverse.', 'INVENTORY_NOT_POSTED', 409);
                    }

                    $mirrorLines = [];
                    foreach ($original->items as $item) {
                        $mirrorLines[] = new StockMovementLine(
                            productId: $item->product_id,
                            quantity: $item->quantity,
                            fromLocationId: $item->to_location_id,
                            toLocationId: $item->from_location_id,
                            batchId: $item->batch_id,
                            productSerialId: $item->product_serial_id,
                            stockStatusFrom: $item->stock_status_to,
                            stockStatusTo: $item->stock_status_from,
                            unitCost: $item->unit_cost,
                        );
                    }

                    $posted = $this->executePosting(new PostInventoryCommand(
                        idempotencyKey: $idempotencyKey.':reversal-ledger',
                        transactionType: 'REVERSAL',
                        sourceDocumentType: 'inventory_transactions',
                        sourceDocumentId: $original->id,
                        postedByUserId: $userId,
                        lines: $mirrorLines,
                        notes: "Reversal {$original->document_no}: {$reason}",
                    ), reversedTransactionId: $original->id);

                    $original->update(['status' => 'REVERSED']);

                    return [
                        'status' => 201,
                        'body' => [
                            ...$posted,
                            'reversed_transaction_id' => $original->id,
                        ],
                    ];
                },
            );
        });

        return $result['body'];
    }

    /**
     * @return array{id: int, document_no: string, status: string}
     */
    private function executePosting(PostInventoryCommand $command, ?int $reversedTransactionId = null): array
    {
        $productIds = array_values(array_unique(array_map(fn (StockMovementLine $l) => $l->productId, $command->lines)));
        /** @var \Illuminate\Database\Eloquent\Collection<int, Product> $products */
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        // Step 1: validate product classification & tracking rules and collect all affected dimension keys.
        $dimensionsToLock = [];
        foreach ($command->lines as $idx => $line) {
            $product = $products->get($line->productId);
            if ($product === null) {
                throw new DomainException("Produk #{$line->productId} tidak ditemukan.", 'INVENTORY_PRODUCT_NOT_FOUND', 422);
            }

            $this->validateLineAgainstProduct($product, $line, $idx);

            if ($line->fromLocationId !== null) {
                $statusFrom = $line->stockStatusFrom ?? StockStatus::AVAILABLE->value;
                $key = InventoryBalance::buildDimensionKey($line->productId, $line->fromLocationId, $line->batchId, $line->productSerialId, $statusFrom);
                $dimensionsToLock[$key] = [$line->productId, $line->fromLocationId, $line->batchId, $line->productSerialId, $statusFrom];
            }

            if ($line->toLocationId !== null) {
                $statusTo = $line->stockStatusTo ?? StockStatus::AVAILABLE->value;
                $key = InventoryBalance::buildDimensionKey($line->productId, $line->toLocationId, $line->batchId, $line->productSerialId, $statusTo);
                $dimensionsToLock[$key] = [$line->productId, $line->toLocationId, $line->batchId, $line->productSerialId, $statusTo];
            }
        }

        // Step 2: sort dimension keys lexicographically and acquire row locks (`SELECT ... FOR UPDATE`).
        ksort($dimensionsToLock);
        /** @var array<string, InventoryBalance> $lockedBalances */
        $lockedBalances = [];
        foreach ($dimensionsToLock as $dimKey => [$prodId, $locId, $batchId, $serialId, $statusCode]) {
            $lockedBalances[$dimKey] = $this->lockOrCreateBalance($dimKey, $prodId, $locId, $batchId, $serialId, $statusCode);
        }

        // Step 3: create ledger header.
        $numberingType = $this->resolveNumberingType($command->transactionType);
        $documentNo = $this->numbering->next($numberingType, $command->transactionDate);

        $header = InventoryTransaction::query()->create([
            'document_no' => $documentNo,
            'transaction_type' => $command->transactionType,
            'status' => 'POSTED',
            'transaction_date' => $command->transactionDate ?? now(),
            'source_document_type' => $command->sourceDocumentType,
            'source_document_id' => $command->sourceDocumentId,
            'idempotency_key' => $command->idempotencyKey,
            'posted_by' => $command->postedByUserId,
            'posted_at' => now(),
            'reversed_transaction_id' => $reversedTransactionId,
            'notes' => $command->notes,
        ]);

        // Step 4: apply each line to balances & write ledger items.
        foreach ($command->lines as $idx => $line) {
            $product = $products->get($line->productId);
            $qty = bcadd($line->quantity, '0', 4);
            $resolvedUnitCost = $line->unitCost !== null ? bcadd($line->unitCost, '0', 6) : null;

            if ($line->fromLocationId !== null) {
                $statusFrom = $line->stockStatusFrom ?? StockStatus::AVAILABLE->value;
                $fromKey = InventoryBalance::buildDimensionKey($line->productId, $line->fromLocationId, $line->batchId, $line->productSerialId, $statusFrom);
                $sourceBalance = $lockedBalances[$fromKey];

                if ($line->consumeReservationId !== null) {
                    $this->consumeReservation($line->consumeReservationId, $sourceBalance, $qty);
                }

                $availableToDraw = $statusFrom === StockStatus::AVAILABLE->value
                    ? bcsub($sourceBalance->on_hand_qty, $sourceBalance->reserved_qty, 4)
                    : $sourceBalance->on_hand_qty;

                if (bccomp($availableToDraw, $qty, 4) < 0) {
                    throw new DomainException(
                        "Stok tersedia untuk produk [{$product->code}] tidak mencukupi (tersedia: {$availableToDraw}, diminta: {$qty}).",
                        'INVENTORY_INSUFFICIENT_STOCK',
                        409,
                        ["items.{$idx}.quantity" => ['Kuantitas yang diminta melebihi stok tersedia.']],
                    );
                }

                if ($resolvedUnitCost === null) {
                    $resolvedUnitCost = $sourceBalance->avg_unit_cost;
                }

                $sourceBalance->on_hand_qty = bcsub($sourceBalance->on_hand_qty, $qty, 4);
                $sourceBalance->row_version = $sourceBalance->row_version + 1;
                $sourceBalance->save();
            }

            if ($line->toLocationId !== null) {
                $statusTo = $line->stockStatusTo ?? StockStatus::AVAILABLE->value;
                $toKey = InventoryBalance::buildDimensionKey($line->productId, $line->toLocationId, $line->batchId, $line->productSerialId, $statusTo);
                $destBalance = $lockedBalances[$toKey];

                if ($product->tracking_method === TrackingMethod::SERIALIZED && bccomp($destBalance->on_hand_qty, '0', 4) > 0) {
                    throw new DomainException(
                        'Unit serial tidak boleh memiliki saldo lebih dari 1 pada satu waktu.',
                        'INVENTORY_SERIAL_ALREADY_ON_HAND',
                        409,
                    );
                }

                $incomingCost = $resolvedUnitCost ?? $destBalance->avg_unit_cost;

                // Moving weighted average on inbound receipt / costed movement (06_BUSINESS_RULES.md #60).
                if ($line->fromLocationId === null && $line->unitCost !== null) {
                    $existingValue = bcmul($destBalance->on_hand_qty, $destBalance->avg_unit_cost, 10);
                    $incomingValue = bcmul($qty, $incomingCost, 10);
                    $newQty = bcadd($destBalance->on_hand_qty, $qty, 4);
                    if (bccomp($newQty, '0', 4) > 0) {
                        $destBalance->avg_unit_cost = bcdiv(bcadd($existingValue, $incomingValue, 10), $newQty, 6);
                    }
                } elseif (bccomp($destBalance->on_hand_qty, '0', 4) === 0 && $incomingCost !== null) {
                    $destBalance->avg_unit_cost = $incomingCost;
                }

                $destBalance->on_hand_qty = bcadd($destBalance->on_hand_qty, $qty, 4);
                $destBalance->row_version = $destBalance->row_version + 1;
                $destBalance->save();
            }

            if ($line->productSerialId !== null) {
                $this->syncSerialState($line, $resolvedUnitCost);
            }

            $totalCost = $resolvedUnitCost !== null ? bcmul($qty, $resolvedUnitCost, 2) : null;

            InventoryTransactionItem::query()->create([
                'inventory_transaction_id' => $header->id,
                'product_id' => $line->productId,
                'from_location_id' => $line->fromLocationId,
                'to_location_id' => $line->toLocationId,
                'batch_id' => $line->batchId,
                'product_serial_id' => $line->productSerialId,
                'stock_status_from' => $line->fromLocationId !== null ? ($line->stockStatusFrom ?? StockStatus::AVAILABLE->value) : null,
                'stock_status_to' => $line->toLocationId !== null ? ($line->stockStatusTo ?? StockStatus::AVAILABLE->value) : null,
                'quantity' => $qty,
                'unit_cost' => $resolvedUnitCost,
                'total_cost' => $totalCost,
            ]);
        }

        // Step 5: audit + outbox inside the same transaction.
        $summary = [
            'id' => $header->id,
            'document_no' => $header->document_no,
            'status' => $header->status,
        ];

        $this->audit->record('inventory.transaction.posted', $header, null, [
            'document_no' => $header->document_no,
            'transaction_type' => $command->transactionType,
            'line_count' => count($command->lines),
        ]);

        OutboxEvent::record('inventory_transactions', $header->id, 'inventory.transaction.posted', $summary);

        return $summary;
    }

    private function validateLineAgainstProduct(Product $product, StockMovementLine $line, int $idx): void
    {
        if (! $product->isStockTracked()) {
            throw new DomainException(
                "Item [{$product->code}] bertipe {$product->product_type->value}/{$product->physical_type->value} dan tidak dapat diposting sebagai stok fisik.",
                'INVENTORY_NON_STOCK_PRODUCT',
                422,
                ["items.{$idx}.product_id" => ['Program atau jasa tidak dapat diposting sebagai mutasi stok fisik.']],
            );
        }

        if ($line->fromLocationId === null && $line->toLocationId === null) {
            throw new DomainException(
                'Setiap baris mutasi wajib memiliki lokasi asal atau lokasi tujuan.',
                'INVENTORY_LOCATION_REQUIRED',
                422,
            );
        }

        if (bccomp($line->quantity, '0', 4) <= 0) {
            throw new DomainException(
                'Kuantitas mutasi harus lebih besar dari nol.',
                'INVENTORY_INVALID_QUANTITY',
                422,
                ["items.{$idx}.quantity" => ['Kuantitas harus lebih besar dari 0.']],
            );
        }

        if ($product->tracking_method === TrackingMethod::SERIALIZED) {
            if ($line->productSerialId === null) {
                throw new DomainException(
                    "Produk [{$product->code}] wajib menyertakan nomor serial.",
                    'INVENTORY_SERIAL_REQUIRED',
                    422,
                    ["items.{$idx}.product_serial_id" => ['Nomor serial wajib diisi untuk produk serialized.']],
                );
            }
            if (bccomp($line->quantity, '1.0000', 4) !== 0) {
                throw new DomainException(
                    'Kuantitas untuk item serialized wajib tepat 1.0000 per baris.',
                    'INVENTORY_SERIAL_QTY_MUST_BE_ONE',
                    422,
                    ["items.{$idx}.quantity" => ['Item serialized wajib berjumlah 1 per baris.']],
                );
            }
        }

        if ($product->tracking_method === TrackingMethod::BATCH && $line->batchId === null) {
            throw new DomainException(
                "Produk [{$product->code}] wajib menyertakan nomor batch.",
                'INVENTORY_BATCH_REQUIRED',
                422,
                ["items.{$idx}.batch_id" => ['Batch wajib diisi untuk produk batch-tracked.']],
            );
        }
    }

    private function lockOrCreateBalance(
        string $dimensionKey,
        int $productId,
        int $locationId,
        ?int $batchId,
        ?int $serialId,
        string $statusCode,
    ): InventoryBalance {
        $existing = InventoryBalance::query()
            ->where('dimension_key', $dimensionKey)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            InventoryBalance::query()->create([
                'dimension_key' => $dimensionKey,
                'product_id' => $productId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
                'product_serial_id' => $serialId,
                'stock_status' => $statusCode,
                'on_hand_qty' => '0.0000',
                'reserved_qty' => '0.0000',
                'avg_unit_cost' => '0.000000',
                'row_version' => 1,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Concurrent transaction inserted the dimension row first; fall through to lock it.
        }

        return InventoryBalance::query()
            ->where('dimension_key', $dimensionKey)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function consumeReservation(int $reservationId, InventoryBalance $balance, string $qty): void
    {
        /** @var InventoryReservation $reservation */
        $reservation = InventoryReservation::query()->lockForUpdate()->findOrFail($reservationId);
        if (! in_array($reservation->status, [ReservationStatus::ACTIVE, ReservationStatus::PARTIAL], true)) {
            throw new DomainException('Reservasi stok sudah tidak aktif.', 'INVENTORY_RESERVATION_INACTIVE', 409);
        }

        $remaining = bcsub($reservation->quantity, $reservation->consumed_qty, 4);
        if (bccomp($qty, $remaining, 4) > 0) {
            throw new DomainException('Kuantitas pengeluaran melebihi sisa reservasi.', 'INVENTORY_RESERVATION_EXCEEDED', 409);
        }

        $newConsumed = bcadd($reservation->consumed_qty, $qty, 4);
        $reservation->consumed_qty = $newConsumed;
        $reservation->status = bccomp($newConsumed, $reservation->quantity, 4) === 0
            ? ReservationStatus::CONSUMED
            : ReservationStatus::PARTIAL;
        $reservation->save();

        $balance->reserved_qty = bcsub($balance->reserved_qty, $qty, 4);
    }

    private function syncSerialState(StockMovementLine $line, ?string $unitCost): void
    {
        /** @var ProductSerial $serial */
        $serial = ProductSerial::query()->lockForUpdate()->findOrFail($line->productSerialId);

        if ($line->toLocationId !== null) {
            $serial->current_location_id = $line->toLocationId;
            $serial->current_status = $line->stockStatusTo ?? StockStatus::AVAILABLE->value;
            if ($serial->acquisition_cost === null && $unitCost !== null) {
                $serial->acquisition_cost = $unitCost;
                $serial->acquired_at = now();
            }
        } elseif ($line->fromLocationId !== null) {
            $serial->current_location_id = null;
            $serial->current_status = 'ISSUED';
        }

        $serial->save();
    }

    private function resolveNumberingType(string $transactionType): string
    {
        return match ($transactionType) {
            'ADJUSTMENT' => 'INVENTORY_ADJUSTMENT',
            default => 'INVENTORY_TRANSFER',
        };
    }
}
