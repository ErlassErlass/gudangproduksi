<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * List semua kategori beserta nama parent-nya.
     */
    public function index()
    {
        $categories = Category::with('parent')->orderBy('nama')->get();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * Tambah kategori baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama'      => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
        ]);

        $category = Category::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil ditambahkan',
            'data'    => $category->load('parent'),
        ], 201);
    }

    /**
     * Update kategori.
     * Validasi mencegah kategori menjadi parent dari dirinya sendiri.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'nama'      => 'required|string|max:255',
            'parent_id' => "nullable|exists:categories,id|not_in:{$id}",
        ]);

        $category->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil diperbarui',
            'data'    => $category->load('parent'),
        ]);
    }

    /**
     * Hapus kategori.
     * Sub-kategori yang menunjuk ke kategori ini akan parent_id-nya jadi null (nullOnDelete).
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori berhasil dihapus',
        ]);
    }
}
