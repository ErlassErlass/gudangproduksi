<?php

namespace App\Domains\Inventory\Services;

use App\Domains\Inventory\Enums\ReservationStatus;
use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\Inventory\Models\InventoryReservation;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Reserves and releases stock without reducing `on_hand_qty`
 * (agent_docs/06_BUSINESS_RULES.md "Reservation rules").
 */
final readonly class ReservationService
{
    public function __construct(private AuditLogger $audit) {}

    public function reserve(
        string $sourceType,
        int $sourceId,
        int $productId,
        int $locationId,
        string $quantity,
        ?int $batchId = null,
        ?int $productSerialId = null,
        ?int $userId = null,
    ): InventoryReservation {
        if (bccomp($quantity, '0', 4) <= 0) {
            throw new DomainException('Kuantitas reservasi harus lebih besar dari 0.', 'RESERVATION_INVALID_QTY', 422);
        }

        return DB::transaction(function () use ($sourceType, $sourceId, $productId, $locationId, $quantity, $batchId, $productSerialId, $userId): InventoryReservation {
            $dimKey = InventoryBalance::buildDimensionKey($productId, $locationId, $batchId, $productSerialId, StockStatus::AVAILABLE);

            /** @var InventoryBalance|null $balance */
            $balance = InventoryBalance::query()
                ->where('dimension_key', $dimKey)
                ->lockForUpdate()
                ->first();

            $available = $balance ? $balance->availableQty() : '0.0000';
            if ($balance === null || bccomp($available, $quantity, 4) < 0) {
                throw new DomainException(
                    "Stok tersedia tidak mencukupi untuk reservasi (tersedia: {$available}, diminta: {$quantity}).",
                    'INVENTORY_INSUFFICIENT_STOCK',
                    409,
                );
            }

            $balance->reserved_qty = bcadd($balance->reserved_qty, $quantity, 4);
            $balance->row_version = $balance->row_version + 1;
            $balance->save();

            $reservation = InventoryReservation::query()->create([
                'reservation_no' => 'RSV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'product_id' => $productId,
                'location_id' => $locationId,
                'batch_id' => $batchId,
                'product_serial_id' => $productSerialId,
                'quantity' => bcadd($quantity, '0', 4),
                'consumed_qty' => '0.0000',
                'status' => ReservationStatus::ACTIVE,
                'created_by' => $userId,
            ]);

            $this->audit->record('inventory.reservation.created', $reservation, null, [
                'product_id' => $productId,
                'location_id' => $locationId,
                'quantity' => $reservation->quantity,
            ]);

            return $reservation;
        });
    }

    public function release(int $reservationId): InventoryReservation
    {
        return DB::transaction(function () use ($reservationId): InventoryReservation {
            /** @var InventoryReservation $reservation */
            $reservation = InventoryReservation::query()->lockForUpdate()->findOrFail($reservationId);
            if (! in_array($reservation->status, [ReservationStatus::ACTIVE, ReservationStatus::PARTIAL], true)) {
                throw new DomainException('Reservasi sudah dilepas atau dikonsumsi.', 'RESERVATION_NOT_ACTIVE', 409);
            }

            $remaining = bcsub($reservation->quantity, $reservation->consumed_qty, 4);
            $dimKey = InventoryBalance::buildDimensionKey(
                $reservation->product_id,
                $reservation->location_id,
                $reservation->batch_id,
                $reservation->product_serial_id,
                StockStatus::AVAILABLE,
            );

            $balance = InventoryBalance::query()->where('dimension_key', $dimKey)->lockForUpdate()->firstOrFail();
            $balance->reserved_qty = bcsub($balance->reserved_qty, $remaining, 4);
            $balance->row_version = $balance->row_version + 1;
            $balance->save();

            $reservation->status = ReservationStatus::RELEASED;
            $reservation->save();

            $this->audit->record('inventory.reservation.released', $reservation, null, ['released_qty' => $remaining]);

            return $reservation;
        });
    }
}
