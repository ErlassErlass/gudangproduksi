<?php

namespace App\Http\Controllers\Api\V1;

use App\Domains\Inventory\Enums\StockStatus;
use App\Domains\Inventory\Models\InventoryBalance;
use App\Domains\MasterData\Models\Product;
use App\Domains\Production\Enums\BomVersionStatus;
use App\Domains\Production\Models\BomVersion;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Shared\Audit\AuditLog;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function summary(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        if (! $user->hasPermission('dashboard.view')) {
            throw new AuthorizationException;
        }

        $availRow = InventoryBalance::query()
            ->selectRaw('COALESCE(SUM(on_hand_qty), 0) as on_hand, COALESCE(SUM(reserved_qty), 0) as reserved')
            ->where('stock_status', StockStatus::AVAILABLE->value)
            ->first();

        $availableStock = bcsub((string) ($availRow->on_hand ?? '0'), (string) ($availRow->reserved ?? '0'), 4);

        $atVendorStock = (string) InventoryBalance::query()
            ->where('stock_status', StockStatus::AT_VENDOR->value)
            ->sum('on_hand_qty');

        $recent = AuditLog::query()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'module' => $log->module(),
                'user' => $log->user?->name,
                'created_at' => $log->created_at->toIso8601String(),
            ]);

        return response()->json([
            'data' => [
                'generated_at' => now()->toIso8601String(),
                'metrics' => [
                    [
                        'key' => 'products',
                        'label' => 'Total Jenis Barang',
                        'value' => Product::query()->count(),
                        'hint' => 'Komponen, modul, box, FG & program',
                    ],
                    [
                        'key' => 'available_stock',
                        'label' => 'Total Unit Stok Tersedia',
                        'value' => (float) $availableStock,
                        'hint' => 'On-hand dikurangi reservasi aktif',
                    ],
                    [
                        'key' => 'production_plans',
                        'label' => 'Rencana Produksi Aktif',
                        'value' => \Illuminate\Support\Facades\DB::table('production_plans')->whereIn('status', ['DRAFT', 'APPROVED', 'IN_PROGRESS'])->count(),
                        'hint' => 'Target produksi terjadwal',
                    ],
                    [
                        'key' => 'at_vendor_stock',
                        'label' => 'Stok di Vendor / GAP',
                        'value' => (float) $atVendorStock,
                        'hint' => 'Persediaan milik Erlass di lokasi eksternal',
                    ],
                ],
                'recent_activities' => $recent,
            ],
        ]);
    }
}
