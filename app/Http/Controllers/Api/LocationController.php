<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index()
    {
        $locations = Location::with('parent')->get();
        return response()->json([
            'success' => true,
            'data' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:locations,id',
            'nama' => 'required|string|max:255',
            'tipe' => 'required|in:gedung,lantai,ruangan,rak',
            'kode' => 'required|string|max:100|unique:locations,kode',
        ]);

        $location = Location::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil ditambahkan',
            'data' => $location->load('parent'),
        ], 201);
    }

    public function destroy($id)
    {
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lokasi berhasil dihapus',
        ]);
    }
}
