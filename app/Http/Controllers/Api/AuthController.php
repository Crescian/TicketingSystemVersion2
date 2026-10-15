<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\MicrosoftUserResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    // Ability carried by every user login token (password or Microsoft). Never
    // '*' — a wildcard token would also pass the 'org:read' / 'tickets:read'
    // checks meant only for the integration tokens, letting any logged-in user
    // pull the whole staff directory and every ticket.
    public const USER_ABILITY = 'user';

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

        return $this->tokenResponse($user, $request->input('client', 'default'));
    }

    // Mobile "Sign in with Microsoft": the app runs the Microsoft login itself
    // (expo-auth-session) and sends the resulting Microsoft Graph access token
    // here. Graph validates the token when Socialite fetches /me with it; the
    // tenant and client-id claims are then checked so only tokens our own app
    // registration obtained inside our own tenant are accepted.
    public function microsoft(Request $request, MicrosoftUserResolver $users)
    {
        $request->validate([
            'access_token' => 'required|string',
            'client' => 'nullable|string|max:100',
        ]);

        try {
            $azureUser = Socialite::driver('azure')->userFromToken($request->access_token);
        } catch (\Throwable $e) {
            Log::warning('Mobile Microsoft sign-in failed', ['error' => $e->getMessage()]);

            return response()->json(['message' => 'Microsoft sign-in failed. Please try again.'], 401);
        }

        if (!$this->issuedForUs($request->access_token)) {
            Log::warning('Mobile Microsoft sign-in rejected: token from another tenant or app', [
                'email' => $azureUser->getEmail(),
            ]);

            return response()->json(['message' => 'Microsoft sign-in failed. Please try again.'], 401);
        }

        $user = $users->findOrCreate($azureUser)->load('department', 'role');

        if (!$user->active) {
            return response()->json([
                'message' => 'Your account is deactivated. Please contact admin.',
            ], 403);
        }

        return $this->tokenResponse($user, $request->input('client', 'mobile'));
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    // Also unregisters this phone's push token when the app sends it, so a
    // logged-out device stops receiving the previous user's notifications.
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        if ($request->filled('expo_push_token')) {
            UserDevice::where('user_id', $request->user()->id)
                ->where('expo_push_token', $request->expo_push_token)
                ->delete();
        }

        return response()->json(['message' => 'Logged out successfully.']);
    }

    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        UserDevice::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => 'Logged out from all devices.']);
    }

    // Token is named after the consuming system ("client"), e.g. "mobile" or
    // "intranet-portal". Only that system's own prior token is revoked, so one
    // system logging a user in doesn't silently log them out of another — each
    // consuming system keeps its own independent session for the same user.
    private function tokenResponse(User $user, string $client)
    {
        $user->tokens()->where('name', $client)->delete();

        $token = $user->createToken($client, [self::USER_ABILITY])->plainTextToken;

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
                'needs_account_setup' => (bool) $user->needs_account_setup,
                'must_change_password' => (bool) $user->must_change_password,
            ],
        ]);
    }

    // The Graph token's own claims: 'tid' (tenant) and 'appid' (v1 tokens) /
    // 'azp' (v2 tokens) for the app registration that obtained it. Safe to read
    // without verifying the signature because Graph already accepted the token.
    private function issuedForUs(string $accessToken): bool
    {
        $parts = explode('.', $accessToken);
        $claims = count($parts) === 3
            ? json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true)
            : null;

        if (!is_array($claims)) {
            return false;
        }

        $allowedClients = array_filter([
            config('services.azure.mobile_client_id'),
            config('services.azure.client_id'),
        ]);

        return ($claims['tid'] ?? null) === config('services.azure.tenant')
            && in_array($claims['appid'] ?? $claims['azp'] ?? null, $allowedClients, true);
    }
}
