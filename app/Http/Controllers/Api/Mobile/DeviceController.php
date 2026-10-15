<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\Request;

// Expo push token registration. The app calls store() after every login and
// app start (cheap, idempotent); a token already registered to someone else
// moves to the current user, since that phone now belongs to them.
class DeviceController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'expo_push_token' => ['required', 'string', 'max:255', 'regex:/^Expo(nent)?PushToken\[.+\]$/'],
            'platform' => 'nullable|in:ios,android',
        ]);

        UserDevice::updateOrCreate(
            ['expo_push_token' => $request->expo_push_token],
            [
                'user_id' => $request->user()->id,
                'platform' => $request->platform,
                'last_seen_at' => now(),
            ],
        );

        return response()->json(['message' => 'Device registered.'], 201);
    }

    public function destroy(Request $request)
    {
        $request->validate(['expo_push_token' => 'required|string']);

        UserDevice::where('user_id', $request->user()->id)
            ->where('expo_push_token', $request->expo_push_token)
            ->delete();

        return response()->noContent();
    }
}
