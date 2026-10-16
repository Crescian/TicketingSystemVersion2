<?php

namespace App\Support;

use App\Jobs\SendExpoPush;
use App\Models\MobileNotification;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

// Notifies staff on the mobile app: records an inbox row (GET /mobile/inbox)
// and pushes to their registered phones. Fed by PushStaffMailNotification
// (every staff email) and by new requester chat messages. Wrapped in rescue()
// so a notification problem can never break the action or mail behind it.
class StaffNotifier
{
    /** @param Collection<int, User>|array<int, User> $users */
    public static function notify(Collection|array $users, string $type, string $title, ?string $body, ?string $ticketId = null, ?string $concernId = null): void
    {
        rescue(function () use ($users, $type, $title, $body, $ticketId, $concernId) {
            $userIds = collect($users)->pluck('id')->unique()->values();
            if ($userIds->isEmpty()) {
                return;
            }

            // One insert for all recipients, one lookup for who has a phone.
            $now = now();
            MobileNotification::insert($userIds->map(fn ($id) => [
                'id' => (string) Str::uuid(),
                'user_id' => $id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'ticket_id' => $ticketId,
                'concern_id' => $concernId,
                'created_at' => $now,
            ])->all());

            $withDevices = UserDevice::whereIn('user_id', $userIds)->distinct()->pluck('user_id');
            foreach ($withDevices as $userId) {
                SendExpoPush::dispatch($userId, $title, (string) $body, array_filter([
                    'type' => $type,
                    'ticket_id' => $ticketId,
                    'concern_id' => $concernId,
                    'audience' => 'staff',
                ]));
            }
        }, report: true);
    }
}
