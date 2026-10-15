<?php

namespace Tests\Feature\Api\Mobile;

use App\Jobs\SendExpoPush;
use App\Jobs\SendTicketChatDigest;
use App\Models\TicketMessage;
use App\Models\UserDevice;
use App\Support\TicketHold;
use App\Support\TicketStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;

class MobileChatAndPushTest extends MobileApiTestCase
{
    public function test_chat_send_read_and_unread_counts(): void
    {
        $employee = $this->employee();
        $helpdesk = $this->userWithRole('Helpdesk');
        $ticket = $this->ticketFor($employee);
        TicketMessage::create(['ticket_id' => $ticket->id, 'sender_id' => $helpdesk->id, 'message' => 'Is it plugged in?', 'is_read' => false, 'created_at' => now()]);
        $headers = $this->tokenFor($employee);

        $this->getJson('/api/mobile/messages/unread', $headers)->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath("by_ticket.{$ticket->id}", 1);
        $this->getJson('/api/mobile/tickets', $headers)->assertJsonPath('data.0.unread_messages', 1);

        $this->getJson("/api/mobile/tickets/{$ticket->id}/messages", $headers)->assertOk()
            ->assertJsonPath('data.0.sender.role', 'Helpdesk')
            ->assertJsonPath('data.0.is_me', false);

        $this->getJson('/api/mobile/messages/unread', $headers)->assertJsonPath('total', 0);

        $this->postJson("/api/mobile/tickets/{$ticket->id}/messages", [], $headers)->assertStatus(422);
        $this->postJson("/api/mobile/tickets/{$ticket->id}/messages", ['message' => 'Yes it is'], $headers)
            ->assertCreated()
            ->assertJsonPath('data.is_me', true)
            ->assertJsonPath('data.sender.initials', 'JC');
    }

    public function test_device_registration_moves_token_to_new_owner(): void
    {
        $first = $this->employee();
        $second = $this->userWithRole('Employee');

        $this->postJson('/api/mobile/devices', ['expo_push_token' => 'not-a-token'], $this->tokenFor($first))->assertStatus(422);
        $this->postJson('/api/mobile/devices', ['expo_push_token' => 'ExponentPushToken[abc]', 'platform' => 'android'], $this->tokenFor($first))->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/mobile/devices', ['expo_push_token' => 'ExponentPushToken[abc]'], $this->tokenFor($second))->assertCreated();

        $this->assertDatabaseCount('user_devices', 1);
        $this->assertSame($second->id, UserDevice::first()->user_id);
    }

    public function test_status_change_and_staff_message_push_to_requestor_device(): void
    {
        // The chat-digest email job re-dispatches itself while a chat is active;
        // on the sync test queue its delay is ignored, so it's faked here too.
        Bus::fake([SendExpoPush::class, SendTicketChatDigest::class]);
        $employee = $this->employee();
        $helpdesk = $this->userWithRole('Helpdesk');
        $ticket = $this->ticketFor($employee);
        UserDevice::create(['user_id' => $employee->id, 'expo_push_token' => 'ExponentPushToken[abc]']);

        Auth::login($helpdesk);
        $ticket->update(['status' => TicketStatus::ASSIGNED]);

        Bus::assertDispatched(SendExpoPush::class, fn ($job) => $job->userId === $employee->id
            && $job->title === 'Request scheduled'
            && $job->data['ticket_id'] === $ticket->id);

        app(\App\Services\TicketChatService::class)->send($ticket, $helpdesk, 'On my way', []);
        Bus::assertDispatched(SendExpoPush::class, fn ($job) => $job->data['type'] === 'new_message'
            && $job->body === 'Helpdesk User: On my way');

        $ticket->update(['status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST]);
        Bus::assertDispatched(SendExpoPush::class, fn ($job) => $job->title === 'Work has started');

        TicketHold::pause($ticket->fresh(), 'Waiting for spare part');
        Bus::assertDispatched(SendExpoPush::class, fn ($job) => $job->data['type'] === 'on_hold');
    }

    public function test_no_push_without_device_or_for_own_actions(): void
    {
        Bus::fake([SendExpoPush::class]);
        $employee = $this->employee();
        $withDevice = $this->ticketFor($employee, ['status' => TicketStatus::REQUESTOR_CONFIRMATION]);
        $otherEmployee = $this->userWithRole('Employee');
        $noDevice = $this->ticketFor($otherEmployee);

        $noDevice->update(['status' => TicketStatus::ASSIGNED]);

        UserDevice::create(['user_id' => $employee->id, 'expo_push_token' => 'ExponentPushToken[abc]']);
        // The requestor closing their own ticket shouldn't push to themselves.
        $this->patchJson("/api/mobile/tickets/{$withDevice->id}/confirm", [], $this->tokenFor($employee))->assertOk();
        // Nor their own chat message.
        app(\App\Services\TicketChatService::class)->send($withDevice, $employee, 'Thanks', []);

        Bus::assertNotDispatched(SendExpoPush::class);
    }

    public function test_push_job_sends_to_expo_and_drops_dead_tokens(): void
    {
        $employee = $this->employee();
        UserDevice::create(['user_id' => $employee->id, 'expo_push_token' => 'ExponentPushToken[live]']);
        UserDevice::create(['user_id' => $employee->id, 'expo_push_token' => 'ExponentPushToken[dead]']);

        Http::fake([SendExpoPush::ENDPOINT => Http::response(['data' => [
            ['status' => 'ok', 'id' => 'r1'],
            ['status' => 'error', 'message' => 'not registered', 'details' => ['error' => 'DeviceNotRegistered']],
        ]])]);

        (new SendExpoPush($employee->id, 'Title', 'Body', ['type' => 'status_change']))->handle();

        Http::assertSent(fn ($request) => count($request->data()) === 2 && $request[0]['title'] === 'Title');
        $this->assertSame(['ExponentPushToken[live]'], UserDevice::pluck('expo_push_token')->all());
    }

    public function test_notification_feed_lists_it_activity_since(): void
    {
        $employee = $this->employee();
        $helpdesk = $this->userWithRole('Helpdesk');
        $ticket = $this->ticketFor($employee);
        \App\Models\TicketStatusHistories::create(['ticket_id' => $ticket->id, 'new_status' => TicketStatus::FOR_ACKNOWLEDGMENT, 'changed_by' => $employee->id, 'notes' => 'Ticket submitted by employee.', 'changed_at' => now()->subMinute()]);
        \App\Models\TicketStatusHistories::create(['ticket_id' => $ticket->id, 'new_status' => TicketStatus::FOR_CLASSIFICATION, 'changed_by' => $helpdesk->id, 'notes' => 'Acknowledged by Helpdesk.', 'changed_at' => now()]);
        TicketMessage::create(['ticket_id' => $ticket->id, 'sender_id' => $helpdesk->id, 'message' => 'Hello', 'is_read' => false, 'created_at' => now()]);

        $response = $this->getJson('/api/mobile/notifications', $this->tokenFor($employee))->assertOk()
            ->assertJsonCount(2, 'notifications');

        $this->assertEqualsCanonicalizing(['status_change', 'new_message'], array_column($response->json('notifications'), 'type'));

        $this->getJson('/api/mobile/notifications?since=' . urlencode($response->json('server_time')), $this->tokenFor($employee))
            ->assertJsonCount(0, 'notifications');
    }
}
