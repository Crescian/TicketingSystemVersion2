<?php

namespace App\Support;

use App\Jobs\SendExpoPush;
use App\Models\MobileNotification;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Support\Collection;

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
            foreach (collect($users)->unique('id') as $user) {
                MobileNotification::create([
                    'user_id' => $user->id,
                    'type' => $type,
                    'title' => $title,
                    'body' => $body,
                    'ticket_id' => $ticketId,
                    'concern_id' => $concernId,
                ]);

                if (UserDevice::where('user_id', $user->id)->exists()) {
                    SendExpoPush::dispatch($user->id, $title, (string) $body, array_filter([
                        'type' => $type,
                        'ticket_id' => $ticketId,
                        'concern_id' => $concernId,
                        'audience' => 'staff',
                    ]));
                }
            }
        }, report: true);
    }
}
