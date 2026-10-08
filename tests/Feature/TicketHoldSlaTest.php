<?php

namespace Tests\Feature;

use App\Support\BusinessClock;
use App\Support\TicketHold;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

// Time spent On Hold must not count against the SLA: on resume, the business
// minutes that were left at pause time are laid out again from the resume moment.
// Only the holidays table is needed (BusinessClock looks it up), so it's built
// here directly rather than running the Postgres-specific migrations.
class TicketHoldSlaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('holidays', function ($table) {
            $table->id();
            $table->date('date');
            $table->boolean('is_active')->default(true);
        });
    }

    private function manila(string $time): Carbon
    {
        return Carbon::parse($time, 'Asia/Manila');
    }

    private function assertSameMoment(string $expected, Carbon $actual): void
    {
        $this->assertSame(
            $this->manila($expected)->format('Y-m-d H:i'),
            $actual->copy()->setTimezone('Asia/Manila')->format('Y-m-d H:i'),
        );
    }

    public function test_hold_time_is_added_back_to_the_deadline(): void
    {
        // The screenshot case: started 8:48 AM, 5h SLA → due 2:48 PM (lunch skipped).
        $dueAt = BusinessClock::addBusinessMinutes($this->manila('2026-10-07 08:48'), 300);
        $this->assertSameMoment('2026-10-07 14:48', $dueAt);

        // Paused 12:09 PM (already inside lunch, so 108 business minutes remain),
        // resumed 2:35 PM → 2:35 PM + 108m = 4:23 PM.
        $newDueAt = TicketHold::resumedDueAt(
            $this->manila('2026-10-07 12:09'), $dueAt, $this->manila('2026-10-07 14:35'),
        );

        $this->assertSameMoment('2026-10-07 16:23', $newDueAt);
    }

    public function test_already_breached_ticket_keeps_its_deadline(): void
    {
        $dueAt = $this->manila('2026-10-07 10:00');

        $newDueAt = TicketHold::resumedDueAt(
            $this->manila('2026-10-07 11:00'), $dueAt, $this->manila('2026-10-07 14:00'),
        );

        $this->assertTrue($newDueAt->eq($dueAt));
    }

    public function test_overnight_hold_only_carries_remaining_business_minutes(): void
    {
        // Started Wed 3:00 PM, 5h SLA → 2h Wed + 3h Thu → due Thu 11:00 AM.
        $dueAt = BusinessClock::addBusinessMinutes($this->manila('2026-10-07 15:00'), 300);
        $this->assertSameMoment('2026-10-08 11:00', $dueAt);

        // Paused Wed 4:30 PM with 30m + 180m = 210m left; resumed Thu 9:00 AM →
        // 180m to noon, lunch, 30m → Thu 1:30 PM.
        $newDueAt = TicketHold::resumedDueAt(
            $this->manila('2026-10-07 16:30'), $dueAt, $this->manila('2026-10-08 09:00'),
        );

        $this->assertSameMoment('2026-10-08 13:30', $newDueAt);
    }

    public function test_business_minutes_spanning_skips_weekends_and_holidays(): void
    {
        DB::table('holidays')->insert(['date' => '2026-10-12', 'is_active' => true]); // Monday

        // Fri 4:00 PM → Tue 9:00 AM: 60m Friday + 60m Tuesday.
        $this->assertSame(120, BusinessClock::businessMinutesSpanning(
            $this->manila('2026-10-09 16:00'), $this->manila('2026-10-13 09:00'),
        ));

        $this->assertSame(0, BusinessClock::businessMinutesSpanning(
            $this->manila('2026-10-09 16:00'), $this->manila('2026-10-09 15:00'),
        ));
    }
}
