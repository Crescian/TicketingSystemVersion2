<?php

namespace App\Jobs;

use App\Models\UserDevice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// Sends one push notification to every registered device of one user through
// Expo's push service. Queued so a slow/unreachable Expo never delays the web
// request that triggered it. Tokens Expo reports as DeviceNotRegistered (app
// uninstalled, token rotated) are deleted so they aren't retried forever.
class SendExpoPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const ENDPOINT = 'https://exp.host/--/api/v2/push/send';

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $userId,
        public string $title,
        public string $body,
        public array $data = [],
    ) {
    }

    public function handle(): void
    {
        $tokens = UserDevice::where('user_id', $this->userId)->pluck('expo_push_token');

        if ($tokens->isEmpty()) {
            return;
        }

        $messages = $tokens->map(fn ($token) => [
            'to' => $token,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
            'sound' => 'default',
        ])->values()->all();

        $response = Http::acceptJson()->timeout(10)->post(self::ENDPOINT, $messages);

        if ($response->failed()) {
            // Expo outage / rate limit — let the queue retry.
            $response->throw();
        }

        // Tickets come back in the same order as the messages sent.
        foreach ($response->json('data', []) as $i => $ticket) {
            if (($ticket['status'] ?? null) === 'error'
                && ($ticket['details']['error'] ?? null) === 'DeviceNotRegistered') {
                UserDevice::where('expo_push_token', $messages[$i]['to'])->delete();
            } elseif (($ticket['status'] ?? null) === 'error') {
                Log::warning('Expo push rejected', ['user_id' => $this->userId, 'ticket' => $ticket]);
            }
        }
    }
}
