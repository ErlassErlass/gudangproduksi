<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Authenticate user using NIK and Password.
     */
    public function login(Request $request)
    {
        $request->validate([
            'nik'      => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('nik', $request->nik)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'NIK atau Password salah',
            ], 401);
        }

        // Generate Token
        $token = $user->createToken('gudangscan-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token'   => $token,
            'user'    => [
                'id'    => $user->id,
                'name'  => $user->name,
                'email' => $user->email,
                'nik'   => $user->nik,
                'role'  => $user->role,
            ],
        ]);
    }

    /**
     * Log the user out (Revoke token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }
}
