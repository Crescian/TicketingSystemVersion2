<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'client' => 'nullable|string|max:100',
        ]);

        $user = User::with('department', 'role')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->active) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact admin.',
            ], 403);
        }

        // Token is named after the consuming system ("client"), e.g. "helpdesk-mobile"
        // or "intranet-portal". Only that system's own prior token is revoked, so one
        // system logging a user in doesn't silently log them out of another — each
        // consuming system keeps its own independent session for the same user.
        $client = $request->input('client', 'default');
        $user->tokens()->where('name', $client)->delete();

        $token = $user->createToken($client)->plainTextToken;

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'position' => $user->position,
                'active' => $user->active,
                'role_id' => $user->role_id,
                'role' => $user->role?->role_name,
                'department_id' => $user->department_id,
                'department' => $user->department?->department_name,
            ],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out from all devices.']);
    }
}