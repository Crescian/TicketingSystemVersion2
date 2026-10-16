<?php

namespace App\Support\Mobile;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Symfony\Component\HttpFoundation\Response;

// Runs an existing web controller method for the mobile API, so staff actions
// and dashboards on the phone follow exactly the web's rules (guards,
// validation, mails, history) without duplicating ~6,600 lines of controller
// code. The web methods answer with back()->with('success'|'error'|
// 'scheduleConflict', …) or a view; this gives them a throwaway in-memory
// session and translates the result to JSON.
final class WebBridge
{
    // Run a web *action*. Returns the JSON answer for the app:
    //   200 {message}                            — flash 'success'
    //   422 {message}                            — flash 'error' (a guard said no)
    //   422 {code: schedule_decision_required,…} — flash 'scheduleConflict'
    // Validation errors (422) and abort(403/404) propagate as exceptions and are
    // rendered as JSON for api/mobile/* (bootstrap/app.php).
    public static function action(Request $request, string $controller, string $method, array $parameters): JsonResponse|Response
    {
        $session = self::useArraySession($request);

        $response = app()->call([app($controller), $method], $parameters);

        if ($response instanceof JsonResponse) {
            return $response;
        }

        if (!$response instanceof RedirectResponse) {
            return response()->json(['message' => 'Done.']);
        }

        if ($conflict = $session->get('scheduleConflict')) {
            return response()->json([
                'message' => "This would run past {$conflict['technician_name']}'s working day.",
                'code' => 'schedule_decision_required',
                'conflict' => [
                    'ticket_number' => $conflict['ticket_number'] ?? null,
                    'technician_name' => $conflict['technician_name'] ?? null,
                    'proposed_end' => $conflict['proposed_end'] ?? null,
                    'day_end' => $conflict['day_end'] ?? null,
                    'overtime_minutes' => $conflict['overtime_minutes'] ?? null,
                ],
            ], 422);
        }

        if ($error = $session->get('error')) {
            return response()->json(['message' => $error], 422);
        }

        // withErrors() without a ValidationException (rare in these controllers).
        if (($errors = $session->get('errors')) && $errors->any()) {
            return response()->json(['message' => $errors->first(), 'errors' => $errors->getBag('default')->toArray()], 422);
        }

        return response()->json(['message' => $session->get('success') ?? 'Done.']);
    }

    // Run a web *page* (dashboard index, calendar…) and return the data it
    // would have rendered, without rendering it.
    public static function viewData(Request $request, string $controller, string $method, array $parameters = []): array
    {
        self::useArraySession($request);

        $view = app()->call([app($controller), $method], $parameters);

        if (!$view instanceof View) {
            throw new \UnexpectedValueException("{$controller}@{$method} did not return a view.");
        }

        return $view->getData();
    }

    // The token API has no session; give the request (and the redirector the
    // controllers use for back()) an in-memory one so flashes can be read back.
    private static function useArraySession(Request $request): Store
    {
        $session = app('session')->driver('array');
        $session->start();
        // The array driver is shared within a process (tests, queue workers);
        // start every bridged call clean so an earlier call's flash can't leak in.
        $session->flush();

        $request->setLaravelSession($session);
        app('redirect')->setSession($session);

        return $session;
    }
}
