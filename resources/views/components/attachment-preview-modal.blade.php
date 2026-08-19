{{-- Attachment preview modal — clicking an attachment previews it in-page first
     (images inline, PDFs/text via iframe) instead of forcing a download. Falls back
     to "Open in New Tab" for file types the browser can't render inline (Office docs,
     etc). Download stays available as an explicit, separate action.
     Call openAttachmentPreview(id, name, mimeType) from anywhere on the page;
     include this component once per page. Styled with inline CSS vars only (no
     dependency on any one layout's local button classes) so it renders consistently
     whether the page extends layouts.app or layouts.admin. --}}
<div class="modal fade" id="attachmentPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width:820px">
        <div class="modal-content" style="border-radius:20px;overflow:hidden;border:none;height:85vh">
            <div style="background:var(--gd);padding:16px 24px;display:flex;align-items:center;justify-content:space-between;gap:12px">
                <div style="min-width:0">
                    <div style="font-family:'Nunito',sans-serif;font-weight:900;font-size:16px;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        <i class="bi bi-paperclip me-2" style="color:var(--yg)"></i><span id="apModalName">Attachment</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <a id="apModalOpenTab" href="#" target="_blank" rel="noopener"
                       style="background:rgba(255,255,255,.12);color:#fff;font-family:'Nunito',sans-serif;font-weight:700;font-size:12px;padding:7px 14px;border-radius:20px;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
                        <i class="bi bi-box-arrow-up-right"></i>Open in New Tab
                    </a>
                    <a id="apModalDownload" href="#"
                       style="background:rgba(255,255,255,.12);color:#fff;font-family:'Nunito',sans-serif;font-weight:700;font-size:12px;padding:7px 14px;border-radius:20px;text-decoration:none;display:inline-flex;align-items:center;gap:5px">
                        <i class="bi bi-download"></i>Download
                    </a>
                    <button data-bs-dismiss="modal"
                            style="background:rgba(255,255,255,.15);border:none;color:#fff;width:30px;height:30px;border-radius:50%;font-size:15px;cursor:pointer;display:flex;align-items:center;justify-content:center">✕</button>
                </div>
            </div>
            <div class="modal-body p-0" id="apModalBody"
                 style="flex:1;overflow:auto;display:flex;align-items:center;justify-content:center;background:#3a3a3a"></div>
        </div>
    </div>
</div>

<script>
    window.openAttachmentPreview = function (attachmentId, name, mimeType) {
        document.getElementById('apModalName').textContent = name;

        const viewUrl = '/attachments/' + attachmentId + '/view';
        const downloadUrl = '/attachments/' + attachmentId + '/download';
        document.getElementById('apModalOpenTab').href = viewUrl;
        document.getElementById('apModalDownload').href = downloadUrl;

        const body = document.getElementById('apModalBody');
        const imageMimes = ['image/jpeg', 'image/png', 'image/gif'];

        if (mimeType === 'application/pdf') {
            body.style.background = '#3a3a3a';
            body.innerHTML = `<iframe src="${viewUrl}" style="width:100%;height:100%;border:0"></iframe>`;
        } else if (imageMimes.includes(mimeType)) {
            body.style.background = '#3a3a3a';
            body.innerHTML = `<img src="${viewUrl}" alt="" style="max-width:100%;max-height:100%;object-fit:contain">`;
        } else if (mimeType === 'text/plain') {
            body.style.background = '#fff';
            body.innerHTML = `<iframe src="${viewUrl}" style="width:100%;height:100%;border:0;background:#fff"></iframe>`;
        } else {
            body.style.background = 'var(--cr)';
            body.innerHTML = `
                <div style="text-align:center;padding:40px;color:var(--tm)">
                    <i class="bi bi-file-earmark" style="font-size:48px;opacity:.4"></i>
                    <div style="margin-top:12px;font-weight:700;color:var(--gd)">Preview not available for this file type.</div>
                    <div style="font-size:12px;margin-top:4px">Use "Open in New Tab" or "Download" above.</div>
                </div>`;
        }

        new bootstrap.Modal('#attachmentPreviewModal').show();
    };

    // Deferred to DOMContentLoaded (plain JS, not $(fn)) because this component can
    // render inside @section('modals'), which the layout outputs before the jQuery
    // <script src> tag loads near the end of <body>.
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('attachmentPreviewModal')?.addEventListener('hidden.bs.modal', function () {
            document.getElementById('apModalBody').innerHTML = '';
        });
    });
</script>
