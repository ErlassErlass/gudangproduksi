<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request)
    {
        $query = Item::with('category');

        if ($request->has('produk') && $request->produk !== 'Semua') {
            $query->where('produk', $request->produk);
        }

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                  ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->map(function ($item) {
            return [
                'id'             => $item->id,
                'category_id'    => $item->category_id,
                'category'       => $item->category,
                'kode'           => $item->kode,
                'nama'           => $item->nama,
                'satuan'         => $item->satuan,
                'jenis_barang'   => $item->jenis_barang,
                'upc_barcode'    => $item->upc_barcode,
                'produk'         => $item->produk,
                'komponen'       => $item->komponen,
                'lokasi_default' => $item->lokasi_default,
                'min_stok'       => $item->min_stok,
                'stok'           => $item->stok,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $items,
        ]);
    }

    /**
     * Store a newly created item in storage. Only for admins.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah barang.',
            ], 403);
        }

        $validated = $request->validate([
            'kode'           => 'required|string|max:30|unique:items,kode',
            'nama'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'jenis_barang'   => 'nullable|string|in:INV,SVC',
            'upc_barcode'    => 'nullable|string|max:100',
            'satuan'         => 'required|string|max:20',
            'produk'         => 'nullable|string|max:50',
            'komponen'       => 'nullable|string|max:50',
            'lokasi_default' => 'nullable|string|max:50',
            'min_stok'       => 'nullable|integer|min:0',
            'deskripsi'      => 'nullable|string',
        ]);

        $item = Item::create($validated);

        // Load category to return
        $item->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil ditambahkan',
            'data'    => $item,
        ], 201);
    }

    /**
     * Display the specified item.
     */
    public function show(Item $item)
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $item->id,
                'category_id'    => $item->category_id,
                'category'       => $item->category,
                'kode'           => $item->kode,
                'nama'           => $item->nama,
                'satuan'         => $item->satuan,
                'jenis_barang'   => $item->jenis_barang,
                'upc_barcode'    => $item->upc_barcode,
                'produk'         => $item->produk,
                'komponen'       => $item->komponen,
                'lokasi_default' => $item->lokasi_default,
                'min_stok'       => $item->min_stok,
                'deskripsi'      => $item->deskripsi,
                'stok'           => $item->stok,
            ],
        ]);
    }

    /**
     * Update the specified item in storage. Only for admins.
     */
    public function update(Request $request, Item $item)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah barang.',
            ], 403);
        }

        $validated = $request->validate([
            'kode'           => 'required|string|max:30|unique:items,kode,' . $item->id,
            'nama'           => 'required|string|max:255',
            'category_id'    => 'nullable|exists:categories,id',
            'jenis_barang'   => 'nullable|string|in:INV,SVC',
            'upc_barcode'    => 'nullable|string|max:100',
            'satuan'         => 'required|string|max:20',
            'produk'         => 'nullable|string|max:50',
            'komponen'       => 'nullable|string|max:50',
            'lokasi_default' => 'nullable|string|max:50',
            'min_stok'       => 'nullable|integer|min:0',
            'deskripsi'      => 'nullable|string',
        ]);

        $item->update($validated);
        $item->load('category');

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil diupdate',
            'data'    => $item,
        ]);
    }

    /**
     * Remove the specified item from storage. Only for admins.
     */
    public function destroy(Request $request, Item $item)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus barang.',
            ], 403);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang berhasil dihapus',
        ]);
    }
}
