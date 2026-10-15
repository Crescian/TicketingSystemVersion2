@extends('layouts.app')

@section('title', 'Report to Developer — LGICT')

@include('developer.partials.nav')

@section('hero-title')
    <h1>REPORT TO <em>DEVELOPER</em></h1>
@endsection
@section('hero-subtitle', 'Send system issues, data/database changes, connectivity problems or requests straight to the Developer.')

@section('sidebar')
    <div class="sidebar-card mb-3">
        <div class="sidebar-head">Send a Concern</div>
        <form method="POST" action="{{ route('developer-concerns.store') }}" class="p-3 d-flex flex-column gap-2 dc-form">
            @csrf
            <div>
                <label for="dcSubject" class="mb-1">Subject</label>
                <input type="text" name="subject" id="dcSubject" class="form-control form-control-sm" maxlength="255" required value="{{ old('subject') }}">
            </div>
            <div>
                <label for="dcCategory" class="mb-1">Category</label>
                <select name="category" id="dcCategory" class="form-select form-select-sm" required>
                    @foreach($categoryGroups as $group => $categories)
                        <optgroup label="{{ $group }}">
                            @foreach($categories as $category => $hint)
                                <option value="{{ $category }}" data-hint="{{ $hint }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
                <small id="dcCategoryHint" class="d-block mt-1" style="color:var(--tm);font-size:11px"></small>
            </div>
            <div>
                <label for="dcUrgency" class="mb-1">Urgency</label>
                <select name="urgency" id="dcUrgency" class="form-select form-select-sm" required>
                    @foreach($urgencies as $urgency)
                        <option value="{{ $urgency }}" @selected(old('urgency', 'Medium') === $urgency)>{{ $urgency }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="dcDetails" class="mb-1">Details</label>
                <textarea name="details" id="dcDetails" class="form-control form-control-sm" rows="6" maxlength="5000" required
                          placeholder="What happened, where (page / ticket # / PC / location), and steps to reproduce.">{{ old('details') }}</textarea>
            </div>
            <button type="submit" class="dc-btn primary mt-1"><i class="bi bi-send me-1"></i>Send to Developer</button>
        </form>
    </div>
@endsection

@section('content')
    @include('developer.partials.styles')
    @include('developer.partials.flash')

    <h6 class="fw-bold mb-3" style="color:var(--gd)">My Concerns</h6>

    <div class="d-flex flex-column gap-3">
        @forelse($concerns as $concern)
            <div class="ticket-card dc-card urgency-{{ $concern->urgency }}">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="ticket-id">#{{ $concern->reference }}</span>
                        <span class="dc-chip dc-urgency-{{ $concern->urgency }}">{{ $concern->urgency }}</span>
                        <span class="dc-chip" style="background:#f1efe9;color:#555">{{ $concern->category }}</span>
                    </div>
                    <span class="dc-chip dc-status-{{ $concern->status }}">{{ $concern->status }}</span>
                </div>

                <div class="dc-subject">{{ $concern->subject }}</div>
                <div class="dc-details">{{ $concern->details }}</div>

                <div class="dc-meta">
                    <span><i class="bi bi-clock me-1"></i>Sent {{ $concern->created_at->timezone('Asia/Manila')->format('M j, Y g:i A') }}</span>
                    @if($concern->acknowledged_at)
                        <span><i class="bi bi-eye me-1"></i>Acknowledged {{ $concern->acknowledged_at->timezone('Asia/Manila')->format('M j, g:i A') }}</span>
                    @endif
                    @if($concern->closed_at)
                        <span><i class="bi bi-check2-circle me-1"></i>Closed {{ $concern->closed_at->timezone('Asia/Manila')->format('M j, g:i A') }}</span>
                    @endif
                </div>

                @if($concern->closing_note)
                    <div class="dc-note"><strong>Developer's note:</strong> {{ $concern->closing_note }}</div>
                @endif
            </div>
        @empty
            <div class="dc-empty"><i class="bi bi-chat-square-text fs-3 d-block mb-2"></i>You haven't sent any concerns yet.</div>
        @endforelse
    </div>

    <div class="mt-3">{{ $concerns->onEachSide(1)->links('pagination::bootstrap-5') }}</div>
@endsection

@section('scripts')
<script>
    // Show the selected category's "what to include" hint under the dropdown.
    (function () {
        const select = document.getElementById('dcCategory');
        const hint = document.getElementById('dcCategoryHint');
        const update = () => { hint.textContent = select.selectedOptions[0]?.dataset.hint ?? ''; };
        select.addEventListener('change', update);
        update();
    })();
</script>
@endsection
