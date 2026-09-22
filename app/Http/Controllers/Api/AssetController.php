<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Transaction;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with(['item', 'location']);

        if ($request->has('tipe') && !empty($request->tipe)) {
            $query->where('tipe_kepemilikan', $request->tipe);
        }

        if ($request->has('status') && !empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('is_rented')) {
            $query->where('is_rented', filter_var($request->is_rented, FILTER_VALIDATE_BOOLEAN));
        }

        $assets = $query->orderBy('id', 'desc')->get()->map(function ($a) {
            $mikms = \App\Models\MikmsUnit::with('product')->where('serial_number', $a->serial_number)->first();
            $needsQc = $mikms ? in_array($mikms->status, ['qc_pending', 'returned', 'repair']) : in_array($a->status, ['not_good', 'tidak_lengkap']);
            $canRentOut = !$a->is_rented && $a->tipe_kepemilikan === 'sewa' && !$needsQc && ($mikms ? $mikms->status === 'in_stock' : in_array($a->status, ['good', 'lengkap']));

            $a->mikms_unit = $mikms ? [
                'id'           => $mikms->id,
                'product_code' => $mikms->product?->code,
                'product_name' => $mikms->product?->name,
                'status'       => $mikms->status,
                'needs_qc'     => $needsQc,
            ] : null;

            $a->needs_qc = $needsQc;
            $a->can_rent_out = $canRentOut;

            return $a;
        });

        return response()->json([
            'success' => true,
            'data' => $assets,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id'          => 'required|exists:items,id',
            'serial_number'    => 'required|string|unique:assets,serial_number',
            'location_id'      => 'required|exists:locations,id',
            'tipe_kepemilikan' => 'required|in:normal,sewa,bekas',
            'status'           => 'required|in:good,not_good,lengkap,tidak_lengkap',
            'vendor_id'        => 'nullable|exists:vendors,id',
            'no_po'            => 'nullable|string|max:50',
            'no_dokumen'       => 'nullable|string|max:50',
            'catatan'          => 'nullable|string',
        ]);

        $item = Item::findOrFail($validated['item_id']);
        if ($validated['tipe_kepemilikan'] === 'sewa') {
            // Aturan Bisnis: Sewa hanya terjadi di Finish Goods (bukan raw material / komponen umum / modul)
            $isRawMaterial = in_array($item->produk, ['Umum', 'Modul'])
                || in_array($item->komponen, ['Shared'])
                || preg_match('/^(CT|OP|SN|MT|PW|CN|MC|CS|TL|PC)-/i', $item->kode);
            if ($isRawMaterial) {
                return response()->json([
                    'success' => false,
                    'message' => "Sewa hanya terjadi di Finish Goods. Item {$item->kode} ({$item->nama}) adalah komponen/raw material dan tidak dapat didaftarkan sebagai unit sewa.",
                ], 422);
            }

            // Validasi: Unit Finish Goods harus bersumber dari unit yang sudah tergenerate di MIKMS
            $mikmsUnit = \App\Models\MikmsUnit::with('product')->where('serial_number', $validated['serial_number'])->first();
            if (!$mikmsUnit) {
                return response()->json([
                    'success' => false,
                    'message' => "Untuk Aset Barang Sewa, nomor seri {$validated['serial_number']} harus bersumber dari unit Finish Goods yang sudah tergenerate di MIKMS.",
                ], 422);
            }

            if (!$mikmsUnit->product?->can_rent) {
                return response()->json([
                    'success' => false,
                    'message' => "Unit {$mikmsUnit->serial_number} berasal dari produk {$mikmsUnit->product?->name} yang tidak diizinkan untuk disewakan.",
                ], 422);
            }
            if ($mikmsUnit->status !== 'in_stock') {
                return response()->json([
                    'success' => false,
                    'message' => "Unit {$mikmsUnit->serial_number} berstatus '{$mikmsUnit->status}'. Hanya unit yang sudah lolos QC ('in_stock') yang dapat didaftarkan ke aset sewa.",
                ], 422);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $asset = Asset::create([
                'item_id'          => $validated['item_id'],
                'serial_number'    => $validated['serial_number'],
                'location_id'      => $validated['location_id'],
                'tipe_kepemilikan' => $validated['tipe_kepemilikan'],
                'status'           => $validated['status'],
                'is_rented'        => false,
            ]);

            // Create ledger entry
            Transaction::create([
                'item_id'          => $validated['item_id'],
                'asset_id'         => $asset->id,
                'vendor_id'        => $validated['vendor_id'] ?? null,
                'location_id'      => $validated['location_id'],
                'tipe'             => 'masuk',
                'tipe_detail'      => 'pembelian',
                'qty'              => 1,
                'transaction_date' => now(),
                'no_po'            => $validated['no_po'] ?? null,
                'no_dokumen'       => $validated['no_dokumen'] ?? null,
                'petugas'          => $request->user()->name,
                'catatan'          => $validated['catatan'] ?? 'Registrasi asset baru',
                'client_id'        => (string) Str::uuid(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Unit Asset berhasil didaftarkan ke list asset barang sewa',
                'data'    => $asset->load(['item', 'location']),
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status'  => 'required|in:good,not_good,lengkap,tidak_lengkap',
            'catatan' => 'nullable|string',
        ]);

        $asset = Asset::findOrFail($id);
        $asset->status = $validated['status'];
        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Status asset berhasil diubah',
            'data'    => $asset,
        ]);
    }

    /**
     * FIX BUG-04: Simpan lokasi lama SEBELUM update, agar audit trail "keluar"
     * mencatat lokasi asal yang benar, bukan lokasi tujuan.
     */
    public function mutateLocation(Request $request)
    {
        $validated = $request->validate([
            'asset_id'    => 'required|exists:assets,id',
            'location_id' => 'required|exists:locations,id',
            'catatan'     => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $asset = Asset::findOrFail($validated['asset_id']);

            // [FIX BUG-04] Simpan location_id lama sebelum diperbarui
            $oldLocationId = $asset->location_id;

            $asset->location_id = $validated['location_id'];
            $asset->save();

            // Transaksi KELUAR: dari lokasi LAMA (oldLocationId)
            Transaction::create([
                'item_id'          => $asset->item_id,
                'asset_id'         => $asset->id,
                'location_id'      => $oldLocationId, // [FIX] lokasi asal yang benar
                'tipe'             => 'keluar',
                'tipe_detail'      => 'mutasi_lokasi',
                'qty'              => 1,
                'transaction_date' => now(),
                'petugas'          => $request->user()->name,
                'catatan'          => 'Mutasi keluar dari lokasi lama. ' . ($validated['catatan'] ?? ''),
                'client_id'        => (string) Str::uuid(),
            ]);

            // Transaksi MASUK: ke lokasi BARU (validated['location_id'])
            Transaction::create([
                'item_id'          => $asset->item_id,
                'asset_id'         => $asset->id,
                'location_id'      => $validated['location_id'], // lokasi tujuan
                'tipe'             => 'masuk',
                'tipe_detail'      => 'mutasi_lokasi',
                'qty'              => 1,
                'transaction_date' => now(),
                'petugas'          => $request->user()->name,
                'catatan'          => 'Mutasi masuk ke lokasi baru. ' . ($validated['catatan'] ?? ''),
                'client_id'        => (string) Str::uuid(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Lokasi asset berhasil dipindahkan',
                'data'    => $asset->load(['item', 'location']),
            ]);
        });
    }

    /**
     * FIX BUG-02: Gunakan throw Exception agar DB::transaction() benar-benar rollback
     * saat validasi gagal di tengah proses batch. Return response JSON dari dalam
     * closure tidak memicu rollback — hanya Exception yang memicunya.
     * Aturan Bisnis: Hanya unit yang ada di list masuk asset barang sewa yang dapat disewakan keluar.
     * Unit yang berstatus Wajib QC Ulang (qc_pending / returned / repair) DIBLOKIR dari sewa keluar.
     */
    public function rentOut(Request $request)
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'asset_ids'      => 'required|array',
            'asset_ids.*'    => 'required|exists:assets,id',
            'no_dokumen'     => 'nullable|string|max:50',
            'keperluan'      => 'nullable|string|max:255',
            'catatan'        => 'nullable|string',
        ]);

        // Validasi seluruh unit SEBELUM masuk transaksi DB
        $assets = collect($validated['asset_ids'])->map(function ($assetId) {
            $asset = Asset::with('item')->findOrFail($assetId);

            // 1. Yang keluar HARUS ada di list masuk asset barang sewa
            if ($asset->tipe_kepemilikan !== 'sewa') {
                abort(422, "Unit {$asset->serial_number} bukan bertipe aset sewa (tipe: {$asset->tipe_kepemilikan}). Hanya unit di list asset barang sewa yang dapat disewakan keluar.");
            }

            // 2. Tidak sedang dalam status disewa
            if ($asset->is_rented) {
                abort(422, "Unit dengan Serial Number {$asset->serial_number} sedang dalam status disewa.");
            }

            // 3. Sewa hanya terjadi di Finish Goods
            $item = $asset->item;
            if ($item) {
                $isRawMaterial = in_array($item->produk, ['Umum', 'Modul'])
                    || in_array($item->komponen, ['Shared'])
                    || preg_match('/^(CT|OP|SN|MT|PW|CN|MC|CS|TL|PC)-/i', $item->kode);
                if ($isRawMaterial) {
                    abort(422, "Sewa hanya terjadi di Finish Goods. Serial {$asset->serial_number} ({$item->nama}) adalah komponen/raw material dan tidak dapat disewakan.");
                }
            }

            // 4. Jika masuk wajib QC ulang: cek apakah unit butuh QC ulang!
            $mikms = \App\Models\MikmsUnit::where('serial_number', $asset->serial_number)->first();
            if ($mikms) {
                if (in_array($mikms->status, ['qc_pending', 'returned', 'repair'])) {
                    abort(422, "Unit {$asset->serial_number} dalam status WAJIB QC ULANG ({$mikms->status}). Harus lolos QC terlebih dahulu sebelum dapat disewakan kembali.");
                }
                if ($mikms->status !== 'in_stock') {
                    abort(422, "Unit {$asset->serial_number} berstatus '{$mikms->status}' dan tidak siap disewakan.");
                }
            } else {
                if (in_array($asset->status, ['not_good', 'tidak_lengkap'])) {
                    abort(422, "Unit {$asset->serial_number} berkondisi {$asset->status}. Wajib melalui perbaikan / QC ulang terlebih dahulu.");
                }
            }

            return ['asset' => $asset, 'mikms' => $mikms];
        });

        return DB::transaction(function () use ($validated, $request, $assets) {
            $customer = DB::table('customers')->where('id', $validated['customer_id'])->first();
            $rentedAssets = [];

            foreach ($assets as $entry) {
                $asset = $entry['asset'];
                $mikms = $entry['mikms'];

                $asset->is_rented = true;
                $asset->save();

                if ($mikms) {
                    $mikms->status = 'dispatched';
                    $mikms->save();
                }

                Transaction::create([
                    'item_id'          => $asset->item_id,
                    'asset_id'         => $asset->id,
                    'customer_id'      => $validated['customer_id'],
                    'tipe'             => 'keluar',
                    'tipe_detail'      => 'sewa_keluar',
                    'qty'              => 1,
                    'transaction_date' => now(),
                    'no_dokumen'       => $validated['no_dokumen'] ?? null,
                    'penerima'         => $customer?->nama ?? 'Customer',
                    'keperluan'        => $validated['keperluan'] ?? 'Sewa barang',
                    'petugas'          => $request->user()->name,
                    'catatan'          => $validated['catatan'] ?? 'Penyewaan asset',
                    'client_id'        => (string) Str::uuid(),
                ]);

                $rentedAssets[] = $asset->fresh(['item', 'location']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi sewa keluar berhasil diproses',
                'data'    => $rentedAssets,
            ]);
        });
    }

    /**
     * FIX BUG-03: Sama dengan BUG-02 — validasi semua unit SEBELUM masuk DB::transaction
     * Aturan Bisnis: Unit yang masuk kembali dari sewa WAJIB QC ULANG.
     * Status mikms_unit diset 'qc_pending' dan status asset diset 'not_good' hingga lulus QC ulang.
     */
    public function rentReturn(Request $request)
    {
        $validated = $request->validate([
            'returns'               => 'required|array',
            'returns.*.asset_id'   => 'required|exists:assets,id',
            'returns.*.status'     => 'required|in:good,not_good,lengkap,tidak_lengkap',
            'returns.*.location_id'=> 'required|exists:locations,id',
            'no_dokumen'            => 'nullable|string|max:50',
            'catatan'               => 'nullable|string',
        ]);

        // Validasi seluruh unit SEBELUM masuk transaksi DB
        $assetReturns = collect($validated['returns'])->map(function ($item) {
            $asset = Asset::findOrFail($item['asset_id']);
            if (!$asset->is_rented) {
                abort(422, "Unit dengan Serial Number {$asset->serial_number} tidak sedang disewa.");
            }
            return ['asset' => $asset, 'meta' => $item];
        });

        return DB::transaction(function () use ($validated, $request, $assetReturns) {
            $returnedAssets = [];

            foreach ($assetReturns as $entry) {
                $asset = $entry['asset'];
                $item  = $entry['meta'];

                $asset->is_rented  = false;
                // Aturan Bisnis: Unit yang masuk kembali WAJIB QC Ulang!
                // Diset not_good agar tidak bisa langsung disewa keluar sebelum lolos QC.
                $asset->status     = 'not_good';
                $asset->location_id= $item['location_id'];
                $asset->save();

                // Sinkronkan ke MikmsUnit jika ada: ubah menjadi 'qc_pending' (Wajib QC Ulang)
                $mikms = \App\Models\MikmsUnit::where('serial_number', $asset->serial_number)->first();
                if ($mikms) {
                    $mikms->status = 'qc_pending';
                    $mikms->save();
                }

                Transaction::create([
                    'item_id'          => $asset->item_id,
                    'asset_id'         => $asset->id,
                    'location_id'      => $item['location_id'],
                    'tipe'             => 'masuk',
                    'tipe_detail'      => 'sewa_kembali',
                    'qty'              => 1,
                    'transaction_date' => now(),
                    'no_dokumen'       => $validated['no_dokumen'] ?? null,
                    'petugas'          => $request->user()->name,
                    'catatan'          => (!empty($validated['catatan']) ? $validated['catatan'] . ' - ' : '') . 'Pengembalian barang sewa (Wajib QC Ulang)',
                    'client_id'        => (string) Str::uuid(),
                ]);

                $freshAsset = $asset->fresh(['item', 'location']);
                $freshAsset->needs_qc = true;
                $returnedAssets[] = $freshAsset;
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pengembalian sewa berhasil diproses. Seluruh unit yang kembali berstatus WAJIB QC ULANG sebelum dapat disewakan lagi.',
                'data'    => $returnedAssets,
            ]);
        });
    }

    public function getAssetCard($id)
    {
        $asset = Asset::with(['item', 'location'])->findOrFail($id);

        $transactions = Transaction::where('asset_id', $id)
            ->with(['vendor', 'customer', 'location'])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'asset'        => $asset,
                'transactions' => $transactions,
            ],
        ]);
    }
}
