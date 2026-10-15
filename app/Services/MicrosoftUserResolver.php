<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;

// Maps a Microsoft (Azure AD) identity to a local account — shared by the web
// SSO callback (App\Http\Controllers\Auth\MicrosoftController) and the mobile
// token exchange (App\Http\Controllers\Api\AuthController::microsoft()).
// Matches on microsoft_id first, then email (linking the Microsoft id on first
// use); unknown identities are auto-created as Employees, same as the web.
class MicrosoftUserResolver
{
    public function findOrCreate(SocialiteUser $azureUser): User
    {
        $user = User::where('microsoft_id', $azureUser->getId())
            ->orWhere('email', $azureUser->getEmail())
            ->first();

        if ($user) {
            if (!$user->microsoft_id) {
                $user->microsoft_id = $azureUser->getId();
                $user->save();
            }

            Log::info('Microsoft SSO: matched existing user', [
                'user_id' => $user->id,
                'email' => $user->email,
                'role' => $user->role?->role_name,
            ]);

            return $user;
        }

        $defaultRoleId = Role::where('role_name', 'Employee')->value('id');

        $user = User::create([
            'name' => $azureUser->getName() ?: $azureUser->getEmail(),
            'email' => $azureUser->getEmail(),
            'password' => Hash::make(Str::random(40)),
            'microsoft_id' => $azureUser->getId(),
            'role_id' => $defaultRoleId,
            'position' => 'Employee',
            'active' => true,
        ]);
        $user->email_verified_at = now();
        $user->save();

        Log::info('Microsoft SSO: auto-created new user', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        return $user;
    }
}
