{{-- Header for the IT Support Specialists sidebar roster, with an (i) toggle
     explaining what each row's numbers mean. Uses Bootstrap's data-attribute
     collapse (no JS init needed, unlike tooltips/popovers, and works on touch). --}}
<div class="sidebar-head d-flex align-items-center justify-content-between">
    <span>IT Support Specialists</span>
    <button type="button" class="btn btn-link p-0 border-0 lh-1"
            style="color:inherit;font-size:14px"
            data-bs-toggle="collapse" data-bs-target="#specialistAvailHelp"
            aria-expanded="false" aria-controls="specialistAvailHelp"
            title="What do these numbers mean?">
        <i class="bi bi-info-circle"></i>
        <span class="visually-hidden">What do these numbers mean?</span>
    </button>
</div>
<div class="collapse" id="specialistAvailHelp">
    <div class="p-3" style="font-size:12px;color:var(--tm);border-bottom:1px solid var(--bd);line-height:1.5">
        <div class="mb-2">
            <strong style="color:var(--gd)">Open tickets</strong> are tickets assigned to the specialist
            that they still have work to do on:
        </div>
        <ul class="mb-2 ps-3">
            <li><strong>In progress</strong> — working on the service request or service report</li>
            <li><strong>Queued</strong> — assigned but not started yet</li>
            <li><strong>On hold</strong> — paused</li>
        </ul>
        <div class="mb-2">
            Not counted: tickets waiting for supervisor review, requestor confirmation,
            escalated, closed or cancelled.
        </div>
        <div>
            <strong style="color:var(--gd)">Free time</strong> is the part of today's
            8:00 AM – 5:00 PM schedule not yet booked by tickets. The bar shows how much of
            the day is still open.
        </div>
    </div>
</div>
