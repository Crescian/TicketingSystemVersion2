<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\MicrosoftUserResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class MicrosoftController extends Controller
{
    public function __construct(private MicrosoftUserResolver $users)
    {
    }

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

        $user = $this->users->findOrCreate($azureUser);

        if (!$user->active) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your account is deactivated. Please contact admin.',
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectByRole($user);
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
            'Developer' => redirect()->route('developer.dashboard'),
            default => redirect('/login'),
        };
    }
}
