<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $remember = $request->boolean('remember');

        if (!Auth::attempt($request->only('email', 'password'), $remember)) {
            return back()->withErrors([
                'email' => 'The provided credentials are incorrect.',
            ])->onlyInput('email');
        }

        if (!Auth::user()->active) {
            Auth::logout();
            return back()->withErrors([
                'email' => 'Your account is deactivated. Please contact admin.',
            ]);
        }

        $request->session()->regenerate();

        // Redirect based on role
        return $this->redirectByRole();
    }

    // Role-based redirect
    private function redirectByRole()
    {
        $role = Auth::user()->role?->role_name;

        return match ($role) {
            'Employee' => redirect()->route('employee.tickets.index'),
            'Helpdesk' => redirect()->route('helpdesk.dashboard'),
            'IT Technician' => redirect()->route('technician.dashboard'),
            'IT Admin' => redirect()->route('admin.dashboard'),
            'Executive' => redirect()->route('executive.dashboard'),
            default => redirect('/login'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}