{{--
    Auto-opens when a Classify & Assign / Reassign / Takeover action would push a
    ticket past the specialist's remaining business hours today (App\Services\TicketScheduler).
    Resubmits the original form fields (echoed back as hidden inputs) plus the
    supervisor's schedule_decision — no client-side scheduling logic, the server
    computation is the single source of truth.
--}}
@if(session('scheduleConflict'))
    @php($conflict = session('scheduleConflict'))
    <div class="modal fade" id="scheduleConflictModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Scheduling Conflict</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>
                        Assigning ticket <strong>#{{ $conflict['ticket_number'] }}</strong> to
                        <strong>{{ $conflict['technician_name'] }}</strong> would run until
                        <strong>{{ $conflict['proposed_end'] }}</strong>, past today's
                        <strong>{{ $conflict['day_end'] }}</strong> cutoff
                        ({{ $conflict['overtime_minutes'] }} minute(s) over).
                    </p>
                    <p class="mb-0">Choose how to proceed:</p>
                </div>
                <div class="modal-footer flex-column align-items-stretch gap-2">
                    <form method="POST" action="{{ $conflict['action_url'] }}">
                        @csrf
                        @foreach($conflict['extra_fields'] as $key => $value)
                            @foreach(\Illuminate\Support\Arr::wrap($value) as $v)
                                <input type="hidden" name="{{ is_array($value) ? $key . '[]' : $key }}" value="{{ $v }}">
                            @endforeach
                        @endforeach
                        <button type="submit" name="schedule_decision" value="overtime" class="btn btn-warning w-100">
                            Assign as Overtime
                        </button>
                    </form>
                    <form method="POST" action="{{ $conflict['action_url'] }}">
                        @csrf
                        @foreach($conflict['extra_fields'] as $key => $value)
                            @foreach(\Illuminate\Support\Arr::wrap($value) as $v)
                                <input type="hidden" name="{{ is_array($value) ? $key . '[]' : $key }}" value="{{ $v }}">
                            @endforeach
                        @endforeach
                        <button type="submit" name="schedule_decision" value="next_day" class="btn btn-primary w-100">
                            Move to Next Working Day
                        </button>
                    </form>
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(function () {
            new bootstrap.Modal('#scheduleConflictModal').show();
        });
    </script>
@endif
