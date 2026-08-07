<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class SSOController extends Controller
{
    public function handleSSO(Request $request)
    {
        $token = $request->query('sso_token');

        if (!$token) {
            abort(401, 'Missing SSO token');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
        ])->post('http://usermgmt.development.com/api/verify-sso', [
            'sso_token' => $token,
        ]);

        if ($response->failed()) {
            abort(401, 'SSO verification failed');
        }

        $userData = $response->json('user');

        if (!$userData || empty($userData['email'])) {
            abort(401, 'Invalid SSO token');
        }

        // ✅ Find existing user by email
        $user = User::where('email', $userData['email'])->first();
 
        if (!$user) {
            abort(401, 'User not found in Ticketing system');
        }

        Auth::login($user);
        $request->session()->regenerate();

        // ✅ Redirect based on role
        $roleName = DB::table('roles')
            ->where('id', $user->role_id)
            ->value('role_name');

        $redirectRoute = match($roleName) {
            'Helpdesk'              => 'helpdesk.dashboard',
            'IT Support Specialist' => 'technician.dashboard',
            'IT Admin'              => 'admin.dashboard',
            'Manager'               => 'executive.dashboard',
            default                 => 'employee.tickets.index',
        };

        return redirect()->route($redirectRoute);
    }
}
