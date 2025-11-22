<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::debug('Login attempt', ['email' => $request->email, 'request_data' => $request->all()]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            Log::debug('Login failed: user not found', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials (user not found)'], 401);
        }

        if (!Hash::check($request->password, $user->password)) {
            Log::debug('Login failed: password mismatch', ['email' => $request->email]);
            return response()->json(['message' => 'Invalid credentials (wrong password)'], 401);
        }

        try {
            $token = $user->createToken('api_token')->plainTextToken;
            Log::debug('Token created successfully', ['user_id' => $user->id, 'token' => $token]);

            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token
            ]);
        } catch (\Exception $e) {
            Log::error('Login failed: exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Server error during login',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}
