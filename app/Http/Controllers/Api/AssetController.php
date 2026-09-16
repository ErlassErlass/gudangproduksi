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

        $assets = $query->get();

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
                'message' => 'Unit Asset berhasil didaftarkan',
                'data'    => $asset->load(['item', 'location']),
            ], 201);
        });
    }

    public function updateStatus(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:good,not_good,lengkap,tidak_lengkap',
        ]);

        $asset->status = $validated['status'];
        $asset->save();

        return response()->json([
            'success' => true,
            'message' => 'Status kondisi asset berhasil diperbarui',
            'data'    => $asset->load(['item', 'location']),
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

        // [FIX BUG-02] Validasi seluruh unit SEBELUM masuk transaksi DB
        // agar tidak ada partial commit jika satu unit di tengah batch tidak valid.
        $assets = collect($validated['asset_ids'])->map(function ($assetId) {
            $asset = Asset::findOrFail($assetId);
            if ($asset->is_rented) {
                abort(422, "Unit dengan Serial Number {$asset->serial_number} sedang dalam status disewa.");
            }
            return $asset;
        });

        return DB::transaction(function () use ($validated, $request, $assets) {
            $customer = DB::table('customers')->where('id', $validated['customer_id'])->first();
            $rentedAssets = [];

            foreach ($assets as $asset) {
                $asset->is_rented = true;
                $asset->save();

                Transaction::create([
                    'item_id'          => $asset->item_id,
                    'asset_id'         => $asset->id,
                    'customer_id'      => $validated['customer_id'],
                    'tipe'             => 'keluar',
                    'tipe_detail'      => 'sewa_keluar',
                    'qty'              => 1,
                    'transaction_date' => now(),
                    'no_dokumen'       => $validated['no_dokumen'] ?? null,
                    'penerima'         => $customer->nama,
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
     * agar rollback benar-benar terjadi jika ada unit yang tidak valid.
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

        // [FIX BUG-03] Validasi seluruh unit SEBELUM masuk transaksi DB
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
                $asset->status     = $item['status'];
                $asset->location_id= $item['location_id'];
                $asset->save();

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
                    'catatan'          => $validated['catatan'] ?? 'Pengembalian barang sewa',
                    'client_id'        => (string) Str::uuid(),
                ]);

                $returnedAssets[] = $asset->fresh(['item', 'location']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Transaksi pengembalian sewa berhasil diproses',
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
