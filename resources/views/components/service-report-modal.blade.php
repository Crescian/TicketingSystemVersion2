{{-- Service Report preview modal — embeds the PDF inline (like a payslip preview)
     instead of forcing a download. Call openServiceReportPreview(ticketId, ticketNumber)
     from anywhere on the page; include this component once per page. --}}
<div class="modal fade" id="serviceReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:900px">
        <div class="modal-content" style="height:90vh">
            <div class="modal-header-gd d-flex align-items-center justify-content-between">
                <h5 class="mb-0">Service <em>Report</em> — <em id="srModalRef">#TKT-0000</em></h5>
                <div class="d-flex align-items-center gap-2">
                    <a id="srModalDownload" href="#" class="btn-back-modal" style="padding:6px 16px;text-decoration:none">
                        <i class="bi bi-download me-1"></i>Download
                    </a>
                    <button class="btn-close-w" data-bs-dismiss="modal">✕</button>
                </div>
            </div>
            <div class="modal-body p-0" style="flex:1;overflow:hidden">
                <iframe id="srModalFrame" src="" style="width:100%;height:100%;border:0"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
    window.openServiceReportPreview = function (ticketId, ticketNumber) {
        $('#srModalRef').text('#' + ticketNumber);
        $('#srModalFrame').attr('src', '{{ url("tickets") }}/' + ticketId + '/service-report/preview');
        $('#srModalDownload').attr('href', '{{ url("tickets") }}/' + ticketId + '/service-report');
        new bootstrap.Modal('#serviceReportModal').show();
    };

    // Stop the iframe reloading the PDF (and re-requesting it) once the modal is
    // closed — no point keeping it live in the background. Deferred to
    // DOMContentLoaded (plain JS, not $(fn)) because this component can render
    // inside @section('modals'), which the layout outputs *before* the jQuery
    // <script src> tag loads near the end of <body> — calling $(...) here
    // directly would throw "$ is not defined" at parse time.
    document.addEventListener('DOMContentLoaded', function () {
        $('#serviceReportModal').on('hidden.bs.modal', function () {
            $('#srModalFrame').attr('src', '');
        });
    });
</script>
