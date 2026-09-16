<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\MikmsModule;
use App\Models\MikmsBom;
use App\Models\MikmsBox;
use App\Models\MikmsProduction;
use App\Models\MikmsQcLog;
use App\Models\MikmsShipment;
use App\Models\MikmsReturn;
use App\Models\MikmsRepair;
use App\Models\MikmsStockOpname;

class MikmsController extends Controller
{
    /**
     * Dashboard statistik MIKMS
     */
    public function getDashboard()
    {
        $totalModules = MikmsModule::count();
        $totalBoxes = MikmsBox::count();
        $boxesReady = MikmsBox::where('status', 'READY')->count();
        $boxesOnLoan = MikmsBox::where('status', 'ON_LOAN')->count();
        $boxesRepair = MikmsBox::where('status', 'REPAIR')->count();

        $totalProduced = MikmsProduction::sum('quantity_produced');
        $totalShipments = MikmsShipment::count();
        $totalReturns = MikmsReturn::count();
        $totalRepairs = MikmsRepair::count();

        $recentProductions = MikmsProduction::with('module')->latest()->take(5)->get();
        $recentShipments = MikmsShipment::latest()->take(5)->get();
        $recentReturns = MikmsReturn::latest()->take(5)->get();
        $recentRepairs = MikmsRepair::latest()->take(5)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'stats' => [
                    'total_modules' => $totalModules,
                    'total_boxes' => $totalBoxes,
                    'boxes_ready' => $boxesReady,
                    'boxes_on_loan' => $boxesOnLoan,
                    'boxes_repair' => $boxesRepair,
                    'total_produced' => (int)$totalProduced,
                    'total_shipments' => $totalShipments,
                    'total_returns' => $totalReturns,
                    'total_repairs' => $totalRepairs,
                ],
                'recent_productions' => $recentProductions,
                'recent_shipments' => $recentShipments,
                'recent_returns' => $recentReturns,
                'recent_repairs' => $recentRepairs,
            ]
        ]);
    }

    /**
     * List master modul & BOM
     */
    public function getModules()
    {
        $modules = MikmsModule::with(['boms.item'])->get()->map(function ($mod) {
            $bomList = $mod->boms->map(function ($b) {
                $stok = $b->item ? $b->item->stok : 0;
                return [
                    'id' => $b->id,
                    'item_id' => $b->item_id,
                    'item_code' => $b->item ? $b->item->kode : '',
                    'item_name' => $b->item ? $b->item->nama : '',
                    'item_unit' => $b->item ? $b->item->satuan : 'pcs',
                    'quantity_per_module' => $b->quantity,
                    'box_category' => $b->box_category,
                    'current_stock' => $stok,
                ];
            });

            return [
                'id' => $mod->id,
                'code' => $mod->code,
                'name' => $mod->name,
                'description' => $mod->description,
                'components' => $bomList,
            ];
        });

        return response()->json(['status' => 'success', 'data' => $modules]);
    }

    /**
     * List Master Box Kit
     */
    public function getBoxes(Request $request)
    {
        $query = MikmsBox::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($w) use ($q) {
                $w->where('box_code', 'like', "%$q%")
                  ->orWhere('category', 'like', "%$q%")
                  ->orWhere('program_code', 'like', "%$q%");
            });
        }

        $boxes = $query->orderBy('box_code')->get();
        return response()->json(['status' => 'success', 'data' => $boxes]);
    }

    /**
     * Simpan / Tambah Box Kit
     */
    public function storeBox(Request $request)
    {
        $validated = $request->validate([
            'box_code' => 'required|string|max:50|unique:mikms_boxes,box_code',
            'category' => 'required|string|max:100',
            'program_code' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $box = MikmsBox::create([
            'box_code' => strtoupper($validated['box_code']),
            'category' => $validated['category'],
            'program_code' => $validated['program_code'] ?? null,
            'status' => $validated['status'] ?? 'READY',
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Box Kit berhasil ditambahkan', 'data' => $box]);
    }

    /**
     * Form Produksi Modul (Potong stok otomatis via BOM)
     */
    public function storeProduction(Request $request)
    {
        $validated = $request->validate([
            'production_date' => 'required|date',
            'module_id' => 'required|exists:mikms_modules,id',
            'quantity_produced' => 'required|integer|min:1',
            'produced_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $module = MikmsModule::with('boms.item')->findOrFail($validated['module_id']);
        $qty = $validated['quantity_produced'];

        // Cek stok bahan baku
        $shortage = [];
        foreach ($module->boms as $bom) {
            $needed = $bom->quantity * $qty;
            $currentStock = $bom->item ? $bom->item->stok : 0;
            if ($currentStock < $needed) {
                $shortage[] = "{$bom->item->nama} (kurang " . ($needed - $currentStock) . " {$bom->item->satuan})";
            }
        }

        if (!empty($shortage)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Stok bahan baku tidak mencukupi untuk perakitan: ' . implode(', ', $shortage),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Simpan data produksi
            $production = MikmsProduction::create([
                'production_date' => $validated['production_date'],
                'module_id' => $module->id,
                'quantity_produced' => $qty,
                'produced_by' => $validated['produced_by'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Potong stok komponen otomatis di gudang utama
            $userId = auth()->id() ?? 1;
            foreach ($module->boms as $bom) {
                $needed = $bom->quantity * $qty;
                Transaction::create([
                    'item_id' => $bom->item_id,
                    'user_id' => $userId,
                    'jenis' => 'keluar',
                    'jumlah' => $needed,
                    'keterangan' => "Produksi MIKMS: {$module->code} - {$module->name} x {$qty}",
                    'lokasi' => 'Gudang Utama',
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Berhasil memproduksi {$qty} unit Modul {$module->code} ({$module->name}). Stok komponen otomatis terpotong.",
                'data' => $production
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Form QC Modul
     */
    public function storeQcLog(Request $request)
    {
        $validated = $request->validate([
            'qc_date' => 'required|date',
            'module_id' => 'required|exists:mikms_modules,id',
            'target_box_code' => 'nullable|string|max:50',
            'status_qc' => 'required|in:LOLOS,TIDAK LOLOS',
            'defect_notes' => 'nullable|string',
            'checked_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $qc = MikmsQcLog::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Hasil QC Modul berhasil disimpan (' . $validated['status_qc'] . ')',
            'data' => $qc
        ]);
    }

    /**
     * Form Pengiriman ke Sekolah
     */
    public function storeShipment(Request $request)
    {
        $validated = $request->validate([
            'shipment_date' => 'required|date',
            'box_code' => 'required|string|max:50',
            'program_code' => 'nullable|string|max:50',
            'program_name' => 'nullable|string|max:100',
            'school_name' => 'required|string|max:150',
            'quantity_box' => 'nullable|integer|min:1',
            'shipped_by' => 'required|string|max:100',
            'received_by_school' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $boxCode = strtoupper(trim($validated['box_code']));

        // Update status box jika terdaftar
        $box = MikmsBox::where('box_code', $boxCode)->first();
        if ($box) {
            $box->update(['status' => 'ON_LOAN']);
        }

        $shipment = MikmsShipment::create([
            'shipment_date' => $validated['shipment_date'],
            'box_code' => $boxCode,
            'program_code' => $validated['program_code'] ?? null,
            'program_name' => $validated['program_name'] ?? null,
            'school_name' => $validated['school_name'],
            'quantity_box' => $validated['quantity_box'] ?? 1,
            'shipped_by' => $validated['shipped_by'],
            'received_by_school' => $validated['received_by_school'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pengiriman {$boxCode} ke {$validated['school_name']} berhasil dicatat.",
            'data' => $shipment
        ]);
    }

    /**
     * Form Pengembalian dari Sekolah
     */
    public function storeReturn(Request $request)
    {
        $validated = $request->validate([
            'return_date' => 'required|date',
            'box_code' => 'required|string|max:50',
            'school_name' => 'required|string|max:150',
            'condition' => 'required|in:LENGKAP,RUSAK,HILANG',
            'problematic_item_code' => 'nullable|string|max:50',
            'problematic_item_name' => 'nullable|string|max:150',
            'problematic_quantity' => 'nullable|integer|min:0',
            'received_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $boxCode = strtoupper(trim($validated['box_code']));

        // Update status box
        $box = MikmsBox::where('box_code', $boxCode)->first();
        if ($box) {
            if ($validated['condition'] === 'LENGKAP') {
                $box->update(['status' => 'READY']);
            } elseif ($validated['condition'] === 'RUSAK') {
                $box->update(['status' => 'REPAIR']);
            } else {
                $box->update(['status' => 'DAMAGED']);
            }
        }

        $return = MikmsReturn::create([
            'return_date' => $validated['return_date'],
            'box_code' => $boxCode,
            'school_name' => $validated['school_name'],
            'condition' => $validated['condition'],
            'problematic_item_code' => $validated['problematic_item_code'] ?? null,
            'problematic_item_name' => $validated['problematic_item_name'] ?? null,
            'problematic_quantity' => $validated['problematic_quantity'] ?? 0,
            'received_by' => $validated['received_by'],
            'notes' => $validated['notes'] ?? null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Pengembalian {$boxCode} dicatat (Kondisi: {$validated['condition']}).",
            'data' => $return
        ]);
    }

    /**
     * Form Repair
     */
    public function storeRepair(Request $request)
    {
        $validated = $request->validate([
            'repair_date' => 'required|date',
            'item_code' => 'required|string|max:50',
            'item_name' => 'required|string|max:150',
            'asset_id' => 'nullable|string|max:50',
            'damage_type' => 'required|string|max:150',
            'repair_action' => 'required|string|max:255',
            'quantity' => 'nullable|integer|min:1',
            'repair_result' => 'required|in:BERHASIL,GAGAL',
            'repaired_by' => 'required|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $repair = MikmsRepair::create([
            'repair_date' => $validated['repair_date'],
            'item_code' => $validated['item_code'],
            'item_name' => $validated['item_name'],
            'asset_id' => $validated['asset_id'] ?? null,
            'damage_type' => $validated['damage_type'],
            'repair_action' => $validated['repair_action'],
            'quantity' => $validated['quantity'] ?? 1,
            'repair_result' => $validated['repair_result'],
            'repaired_by' => $validated['repaired_by'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Jika ada Box terkait yang dalam status REPAIR, jika hasil BERHASIL bisa dikembalikan READY
        if (!empty($validated['asset_id'])) {
            $box = MikmsBox::where('box_code', strtoupper($validated['asset_id']))->first();
            if ($box) {
                $box->update(['status' => ($validated['repair_result'] === 'BERHASIL' ? 'READY' : 'DISPOSED')]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Hasil repair dicatat: {$validated['repair_result']}.",
            'data' => $repair
        ]);
    }

    /**
     * Form Stock Opname
     */
    public function storeStockOpname(Request $request)
    {
        $validated = $request->validate([
            'opname_date' => 'required|date',
            'item_code' => 'required|string|max:50',
            'physical_quantity' => 'required|integer|min:0',
            'difference_reason' => 'nullable|string|max:255',
            'counted_by' => 'required|string|max:100',
        ]);

        $item = Item::where('kode', $validated['item_code'])->first();
        $itemName = $item ? $item->nama : $validated['item_code'];
        $sysQty = $item ? $item->stok : 0;
        $physQty = $validated['physical_quantity'];
        $diff = $physQty - $sysQty;

        $opname = MikmsStockOpname::create([
            'opname_date' => $validated['opname_date'],
            'item_code' => $validated['item_code'],
            'item_name' => $itemName,
            'system_quantity' => $sysQty,
            'physical_quantity' => $physQty,
            'difference' => $diff,
            'difference_reason' => $validated['difference_reason'] ?? null,
            'counted_by' => $validated['counted_by'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => "Stock Opname {$validated['item_code']} tersimpan (Fisik: {$physQty}, Selisih: {$diff}).",
            'data' => $opname
        ]);
    }

    /**
     * Ambil seluruh riwayat transaksi MIKMS
     */
    public function getLogs(Request $request)
    {
        $type = $request->query('type', 'productions');

        switch ($type) {
            case 'productions':
                $data = MikmsProduction::with('module')->latest()->paginate(20);
                break;
            case 'qc':
                $data = MikmsQcLog::with('module')->latest()->paginate(20);
                break;
            case 'shipments':
                $data = MikmsShipment::latest()->paginate(20);
                break;
            case 'returns':
                $data = MikmsReturn::latest()->paginate(20);
                break;
            case 'repairs':
                $data = MikmsRepair::latest()->paginate(20);
                break;
            case 'opname':
                $data = MikmsStockOpname::latest()->paginate(20);
                break;
            default:
                $data = [];
        }

        return response()->json(['status' => 'success', 'data' => $data]);
    }
}
