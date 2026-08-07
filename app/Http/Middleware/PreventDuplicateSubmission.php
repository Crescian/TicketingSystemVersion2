<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Blocks an identical repeat submission (double-click, slow-network retry) from the
 * same user for a short window. This is not a general Idempotency-Key system — it
 * closes the race where two rapid clicks both pass a controller's status guard
 * (e.g. "only New Request tickets can be acknowledged") before either write commits.
 */
class PreventDuplicateSubmission
{
    public function handle(Request $request, Closure $next, int $seconds = 10)
    {
        $key = 'dupe-guard:' . sha1(implode('|', [
            Auth::id() ?? $request->ip(),
            $request->method(),
            $request->path(),
            json_encode($request->except(['_token'])),
        ]));

        if (! Cache::add($key, true, $seconds)) {
            $message = 'This request is already being processed. Please wait a moment and try again.';

            return $request->expectsJson()
                ? response()->json(['message' => $message], 409)
                : back()->with('error', $message);
        }

        $response = $next($request);

        // A validation failure isn't an in-flight duplicate — let the user fix and resubmit right away.
        if ($response->getStatusCode() === 302 && $request->session()->get('errors')?->any()) {
            Cache::forget($key);
        }

        return $response;
    }
}
