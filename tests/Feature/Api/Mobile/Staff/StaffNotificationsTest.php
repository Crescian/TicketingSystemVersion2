<?php

namespace Tests\Feature\Api\Mobile\Staff;

use App\Jobs\SendExpoPush;
use App\Jobs\SendTicketChatDigest;
use App\Mail\TicketAssignedMail;
use App\Mail\TicketSubmittedMail;
use App\Models\MobileNotification;
use App\Models\UserDevice;
use App\Services\RequestorTicketService;
use App\Services\TicketChatService;
use App\Support\TicketStatus;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Mime\Email;
use Tests\Feature\Staff\StaffTestCase;

// Staff push/inbox: every staff email becomes a mobile notification
// (PushStaffMailNotification), requester chat messages reach the assignee,
// and mobile activity counts as "online".
class StaffNotificationsTest extends StaffTestCase
{
    private function mailSent(string $mailable, string $to, array $data): void
    {
        event(new MessageSending((new Email())->to($to)->subject('x'), ['__laravel_mailable' => $mailable] + $data));
    }

    public function test_staff_email_becomes_inbox_item_and_push(): void
    {
        Bus::fake([SendExpoPush::class]);
        $tech = $this->staff('IT Support Specialist');
        UserDevice::create(['user_id' => $tech->id, 'expo_push_token' => 'ExponentPushToken[tech]']);
        $ticket = $this->staffTicket(['status' => TicketStatus::ASSIGNED, 'pending_role' => null, 'assigned_to' => $tech->id]);

        $this->mailSent(TicketAssignedMail::class, $tech->email, ['ticket' => $ticket, 'action' => 'You have been assigned a ticket.']);

        $this->assertDatabaseHas('mobile_notifications', ['user_id' => $tech->id, 'type' => 'action_needed', 'ticket_id' => $ticket->id, 'title' => "Action needed · #{$ticket->ticket_number}"]);
        Bus::assertDispatched(SendExpoPush::class, fn ($job) => $job->userId === $tech->id && $job->data['ticket_id'] === $ticket->id && $job->data['audience'] === 'staff');
    }

    public function test_requester_emails_are_ignored(): void
    {
        $employee = $this->employee();
        $ticket = $this->staffTicket(['users_id' => $employee->id]);

        $this->mailSent(TicketSubmittedMail::class, $employee->email, ['ticket' => $ticket]);

        $this->assertDatabaseCount('mobile_notifications', 0);
    }

    public function test_requester_chat_message_reaches_assignee_or_helpdesk(): void
    {
        Bus::fake([SendExpoPush::class, SendTicketChatDigest::class]);
        $employee = $this->employee();
        $tech = $this->staff('IT Support Specialist');
        $helpdesk = $this->staff('Helpdesk');
        $assigned = $this->staffTicket(['users_id' => $employee->id, 'status' => TicketStatus::IN_PROGRESS_SERVICE_REQUEST, 'pending_role' => null, 'assigned_to' => $tech->id]);
        $unassigned = $this->staffTicket(['users_id' => $employee->id]);
        $chat = app(TicketChatService::class);

        $chat->send($assigned, $employee, 'Still broken', []);
        $chat->send($unassigned, $employee, 'Hello?', []);
        $chat->send($assigned, $tech, 'On my way', []); // staff reply: no staff notification

        $this->assertSame(1, MobileNotification::where('user_id', $tech->id)->count());
        $this->assertSame('Juan Dela Cruz: Still broken', MobileNotification::where('user_id', $tech->id)->value('body'));
        $this->assertSame(1, MobileNotification::where('user_id', $helpdesk->id)->where('ticket_id', $unassigned->id)->count());
    }

    public function test_inbox_lists_and_marks_read(): void
    {
        $tech = $this->staff('IT Support Specialist');
        $other = $this->staff('Helpdesk');
        MobileNotification::create(['user_id' => $tech->id, 'type' => 'action_needed', 'title' => 'Mine']);
        MobileNotification::create(['user_id' => $other->id, 'type' => 'action_needed', 'title' => 'Not mine']);
        $headers = $this->tokenFor($tech);

        $this->getJson('/api/mobile/inbox', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'notifications')
            ->assertJsonPath('notifications.0.title', 'Mine')
            ->assertJsonPath('unread', 1);

        $this->postJson('/api/mobile/inbox/read', [], $headers)->assertNoContent();
        $this->getJson('/api/mobile/inbox', $headers)->assertJsonPath('unread', 0);
    }

    public function test_mobile_token_activity_counts_as_online(): void
    {
        $helpdesk = $this->staff('Helpdesk');
        $helpdesk->createToken('mobile', ['user']);
        DB::table('personal_access_tokens')->update(['last_used_at' => now()->subMinute()]);

        $member = app(RequestorTicketService::class)->itTeamStatus()->firstWhere('id', $helpdesk->id);

        $this->assertTrue($member->online);
    }
}
