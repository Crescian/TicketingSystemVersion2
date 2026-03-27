<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        return view('profile');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user = Auth::user();

        // Check current password is correct
        if (! Hash::check($request->current_password, $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Your current password is incorrect.'])
                ->with('error', 'Your current password is incorrect.');
        }

        // Check new password is not the same as current
        if (Hash::check($request->password, $user->password)) {
            return back()
                ->with('error', 'Your new password cannot be the same as your current password.');
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return back()->with('success', '✅ Password updated successfully! Please keep it safe.');
    }
}
