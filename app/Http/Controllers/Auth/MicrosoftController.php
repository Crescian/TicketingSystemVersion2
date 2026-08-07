<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('azure')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $azureUser = Socialite::driver('azure')->user();
        } catch (\Throwable $e) {
            Log::warning('Microsoft SSO login failed', ['error' => $e->getMessage()]);

            return redirect()->route('login')->withErrors([
                'email' => 'Microsoft sign-in failed. Please try again.',
            ]);
        }

        $user = $this->findOrCreateUser($azureUser);

        if (!$user->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is deactivated. Please contact admin.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
    }

    private function findOrCreateUser(SocialiteUser $azureUser): User
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

    private function redirectByRole(User $user): RedirectResponse
    {
        $role = $user->role?->role_name;

        return match ($role) {
            'Employee' => redirect()->route('employee.tickets.index'),
            'Helpdesk' => redirect()->route('helpdesk.dashboard'),
            'IT Support Specialist' => redirect()->route('technician.dashboard'),
            'Supervisor - Support Specialist' => redirect()->route('supervisor.support.dashboard'),
            'IT Admin' => redirect()->route('admin.dashboard'),
            'Supervisor - IT Admin' => redirect()->route('supervisor.dashboard'),
            'Manager' => redirect()->route('executive.dashboard'),
            default => redirect('/login'),
        };
    }
}
