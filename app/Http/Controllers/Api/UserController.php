<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display a listing of the users. Only for admin.
     */
    public function index(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat melihat pengguna.',
            ], 403);
        }

        $users = User::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    /**
     * Store a newly created user in storage. Only for admin.
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menambah pengguna.',
            ], 403);
        }

        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:users,nik',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:4',
            'role' => 'required|string|in:admin,petugas',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil ditambahkan',
            'data' => $user,
        ], 201);
    }

    /**
     * Update the specified user in storage. Only for admin.
     */
    public function update(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengubah pengguna.',
            ], 403);
        }

        $validated = $request->validate([
            'nik' => 'required|string|max:50|unique:users,nik,' . $user->id,
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:4',
            'role' => 'required|string|in:admin,petugas',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil diperbarui',
            'data' => $user,
        ]);
    }

    /**
     * Remove the specified user from storage. Only for admin.
     */
    public function destroy(Request $request, User $user)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat menghapus pengguna.',
            ], 403);
        }

        // Prevent self deletion
        if ($request->user()->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak dapat menghapus akun Anda sendiri.',
            ], 400);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pengguna berhasil dihapus',
        ]);
    }

    /**
     * Bulk import users. Only for admin.
     */
    public function import(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Admin yang dapat mengimpor pengguna.',
            ], 403);
        }

        $request->validate([
            'users' => 'required|array',
            'users.*.nik' => 'required|string|max:50',
            'users.*.name' => 'required|string|max:255',
            'users.*.password' => 'required|string',
            'users.*.role' => 'nullable|string|in:admin,petugas',
        ]);

        $imported = [];
        $errors = [];

        foreach ($request->users as $index => $u) {
            $nik = trim($u['nik']);
            
            // Check uniqueness
            if (User::where('nik', $nik)->exists()) {
                $errors[] = "Baris " . ($index + 1) . ": NIK {$nik} sudah terdaftar.";
                continue;
            }

            $user = User::create([
                'nik' => $nik,
                'name' => trim($u['name']),
                'password' => Hash::make($u['password']),
                'role' => $u['role'] ?? 'petugas',
            ]);

            $imported[] = $user;
        }

        if (count($errors) > 0 && count($imported) === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimpor pengguna. Semua NIK sudah terdaftar.',
                'errors' => $errors,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => count($imported) . ' pengguna berhasil diimpor.',
            'imported_count' => count($imported),
            'errors' => $errors,
        ]);
    }
}
