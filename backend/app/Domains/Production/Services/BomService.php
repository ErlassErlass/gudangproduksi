<?php

namespace App\Domains\Production\Services;

use App\Domains\Production\Enums\BomVersionStatus;
use App\Domains\Production\Models\BomHeader;
use App\Domains\Production\Models\BomItem;
use App\Domains\Production\Models\BomVersion;
use App\Shared\Audit\AuditLogger;
use App\Shared\Exceptions\DomainException;
use Illuminate\Support\Facades\DB;

/**
 * BOM cycle validation, active-version lifecycle, multi-level BOM explosion, and immutable
 * snapshot generation for Production Orders (agent_docs/01_PRD.md FR-04, 06_BUSINESS_RULES.md "BOM rules").
 */
final readonly class BomService
{
    public function __construct(private AuditLogger $audit) {}

    /**
     * Activate a BOM version after verifying:
     *  - it contains at least one line item,
     *  - the multi-level BOM graph contains no cycles (AC-001/AC-002/06_BUSINESS_RULES.md),
     *  - any previously ACTIVE version on the same product is retired so only one active BOM
     *    exists per product.
     */
    public function activateVersion(BomVersion $version, int $approverUserId): BomVersion
    {
        return DB::transaction(function () use ($version, $approverUserId): BomVersion {
            $version->loadMissing(['header.product', 'items.component.baseUom', 'items.uom']);

            if ($version->items->isEmpty()) {
                throw new DomainException('Versi BOM tidak dapat diaktifkan tanpa daftar komponen.', 'BOM_EMPTY_ITEMS', 422);
            }

            $rootProductId = $version->header->product_id;
            $this->assertNoCycle($rootProductId, $version);

            // Retire other active versions across all headers for the same product.
            BomVersion::query()
                ->whereHas('header', fn ($q) => $q->where('product_id', $rootProductId))
                ->where('status', BomVersionStatus::ACTIVE->value)
                ->where('id', '!=', $version->id)
                ->update(['status' => BomVersionStatus::RETIRED->value]);

            $version->update([
                'status' => BomVersionStatus::ACTIVE,
                'effective_from' => $version->effective_from ?? now()->toDateString(),
                'approved_by' => $approverUserId,
                'approved_at' => now(),
            ]);

            $this->audit->record('bom.version.activated', $version, null, [
                'bom_header_id' => $version->bom_header_id,
                'version_no' => $version->version_no,
                'product_id' => $rootProductId,
            ]);

            return $version->fresh(['header.product', 'items.component', 'items.uom']) ?? $version;
        });
    }

    /**
     * Multi-level BOM explosion for a target parent quantity.
     * Applies scrap allowance (`qty * (1 + scrap_rate)`) and recurses into subassemblies that have
     * an active BOM version.
     *
     * @return list<array{
     *   product_id: int,
     *   product_code: string,
     *   product_name: string,
     *   item_type: string,
     *   uom_code: string,
     *   gross_qty: string,
     *   estimated_unit_cost: string|null,
     *   estimated_total_cost: string|null,
     *   level: int
     * }>
     */
    public function explode(BomVersion $version, string $targetQty = '1.0000'): array
    {
        $version->loadMissing(['header.product', 'items.component', 'items.uom']);
        $this->assertNoCycle($version->header->product_id, $version);

        $aggregated = [];
        $this->walkExplosion($version, bcadd($targetQty, '0', 4), 1, [$version->header->product_id], $aggregated);

        return array_values($aggregated);
    }

    /**
     * Immutable JSON snapshot copied onto a Production Order on release (AC-003).
     * Subsequent edits or new BOM versions never alter this snapshot.
     *
     * @return array<string, mixed>
     */
    public function buildOrderSnapshot(BomVersion $version, string $targetQty): array
    {
        $version->loadMissing(['header.product', 'header.stageTemplate.items', 'items.component', 'items.uom']);

        return [
            'captured_at' => now()->toIso8601String(),
            'bom_header_id' => $version->bom_header_id,
            'bom_code' => $version->header->code,
            'bom_type' => $version->header->bom_type->value,
            'version_id' => $version->id,
            'version_no' => $version->version_no,
            'product_id' => $version->header->product_id,
            'product_code' => $version->header->product->code,
            'target_qty' => bcadd($targetQty, '0', 4),
            'stages' => $version->header->stageTemplate?->items->map(fn ($s) => [
                'sequence' => $s->sequence,
                'stage_code' => $s->stage_code,
                'name' => $s->name,
                'output_product_id' => $s->output_product_id,
                'qc_required' => $s->qc_required,
            ])->all() ?? [],
            'requirements' => $this->explode($version, $targetQty),
        ];
    }

    /**
     * Depth-first cycle detector across multi-level BOMs.
     *
     * @param  list<int>  $ancestorProductIds
     */
    public function assertNoCycle(int $rootProductId, BomVersion $version, array $ancestorProductIds = []): void
    {
        $path = [...$ancestorProductIds, $rootProductId];
        $version->loadMissing('items');

        foreach ($version->items as $item) {
            $componentId = (int) $item->component_product_id;
            if (in_array($componentId, $path, true)) {
                throw new DomainException(
                    "Siklus BOM terdeteksi: produk #{$componentId} mereferensikan dirinya sendiri di rantai BOM.",
                    'BOM_CYCLE_DETECTED',
                    422,
                );
            }

            $childActiveVersion = $this->findActiveVersionForProduct($componentId);
            if ($childActiveVersion !== null) {
                $this->assertNoCycle($componentId, $childActiveVersion, $path);
            }
        }
    }

    public function findActiveVersionForProduct(int $productId): ?BomVersion
    {
        return BomVersion::query()
            ->where('status', BomVersionStatus::ACTIVE->value)
            ->whereHas('header', fn ($q) => $q->where('product_id', $productId))
            ->with(['header.product', 'items.component', 'items.uom'])
            ->first();
    }

    /**
     * @param  list<int>  $visitedProductIds
     * @param  array<int, array<string, mixed>>  $aggregated
     */
    private function walkExplosion(
        BomVersion $version,
        string $multiplierQty,
        int $level,
        array $visitedProductIds,
        array &$aggregated,
    ): void {
        foreach ($version->items as $item) {
            /** @var BomItem $item */
            $componentId = (int) $item->component_product_id;
            if (in_array($componentId, $visitedProductIds, true)) {
                throw new DomainException('Siklus BOM terdeteksi saat kalkulasi explosion.', 'BOM_CYCLE_DETECTED', 422);
            }

            $scrapFactor = bcadd('1', $item->scrap_rate ?? '0', 6);
            $baseRequired = bcmul($item->quantity, $multiplierQty, 6);
            $grossQty = bcmul($baseRequired, $scrapFactor, 4);

            $childVersion = $item->item_type === 'SUBASSEMBLY'
                ? $this->findActiveVersionForProduct($componentId)
                : null;

            if ($childVersion !== null) {
                $this->walkExplosion($childVersion, $grossQty, $level + 1, [...$visitedProductIds, $componentId], $aggregated);

                continue;
            }

            $component = $item->component;
            $refPrice = $component->reference_purchase_price;

            if (! isset($aggregated[$componentId])) {
                $aggregated[$componentId] = [
                    'product_id' => $componentId,
                    'product_code' => $component->code,
                    'product_name' => $component->name,
                    'item_type' => $item->item_type,
                    'uom_code' => $item->uom->code,
                    'gross_qty' => '0.0000',
                    'estimated_unit_cost' => $refPrice,
                    'estimated_total_cost' => null,
                    'level' => $level,
                ];
            }

            $newGross = bcadd($aggregated[$componentId]['gross_qty'], $grossQty, 4);
            $aggregated[$componentId]['gross_qty'] = $newGross;
            $aggregated[$componentId]['estimated_total_cost'] = $refPrice !== null
                ? bcmul($newGross, $refPrice, 2)
                : null;
        }
    }
}
