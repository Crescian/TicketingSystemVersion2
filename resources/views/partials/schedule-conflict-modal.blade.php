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
                        Assigning support request <strong>#{{ $conflict['ticket_number'] }}</strong> to
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
        // Plain DOMContentLoaded, not jQuery's $(fn) — this partial is included
        // inside @section('content'), which the layout renders *before* the
        // jQuery/Bootstrap <script src> tags near the end of <body>. $(...) would
        // throw "$ is not defined" the instant the parser reaches this tag; the
        // event listener itself only needs `document` (always available), and its
        // callback doesn't run until DOMContentLoaded — by which point every
        // earlier synchronous <script src>, jQuery/Bootstrap included, has already
        // executed.
        document.addEventListener('DOMContentLoaded', function () {
            new bootstrap.Modal(document.getElementById('scheduleConflictModal')).show();
        });
    </script>
@endif
