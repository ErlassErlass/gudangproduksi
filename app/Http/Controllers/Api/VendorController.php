<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index()
    {
        $vendors = Vendor::all();
        return response()->json([
            'success' => true,
            'data' => $vendors,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'kontak' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:50',
            'alamat' => 'nullable|string',
        ]);

        $vendor = Vendor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil ditambahkan',
            'data' => $vendor,
        ], 201);
    }

    public function destroy($id)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Vendor berhasil dihapus',
        ]);
    }
}
