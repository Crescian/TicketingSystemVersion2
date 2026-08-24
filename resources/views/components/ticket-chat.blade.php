@props(['ticket'])

@php
    $user = Auth::user();
    $roleName = $user->role?->role_name;
    $roleSlug = match ($roleName) {
        'IT Admin' => 'admin',
        'IT Support Specialist' => 'tech',
        'Helpdesk' => 'helpdesk',
        default => 'employee',
    };
@endphp

<div class="chat-widget" id="chatWidget">

    {{-- Chat Header --}}
    <div class="chat-header" onclick="toggleChat()">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-chat-dots-fill" style="color:var(--yg)"></i>
            <span class="font-brand fw-900 text-white fw-bold" style="font-size:13px">
                Messages — #{{ $ticket->ticket_number }}
            </span>
            <span class="chat-unread-badge d-none" id="chatUnreadBadge">0</span>
        </div>
        <i class="bi bi-chevron-up chat-chevron" id="chatChevron"></i>
    </div>

    {{-- Chat Body --}}
    <div class="chat-body" id="chatBody">

        {{-- Requestor / subject / concern — so replies never land on the wrong ticket --}}
        <div style="padding:10px 16px;background:var(--ygl);border-bottom:1.5px solid var(--bd);font-size:12px">
            <div style="font-weight:800;color:var(--gd)">
                <i class="bi bi-person-fill me-1"></i>{{ $ticket->user->name ?? 'Unknown' }}
            </div>
            <div style="font-weight:700;color:var(--tm);margin-top:2px">{{ $ticket->subject }}</div>
            <div style="color:var(--tm);margin-top:2px;max-height:54px;overflow-y:auto">{{ $ticket->concern }}</div>
        </div>

        {{-- Messages area --}}
        <div class="chat-messages" id="chatMessages">
            <div class="chat-loading">
                <div class="spinner-border spinner-border-sm me-2"></div>
                Loading messages…
            </div>
        </div>

        {{-- Input area --}}
        <div class="chat-input-wrap">
            <div class="chat-role-pill role-{{ $roleSlug }}">
                {{ $roleName }}
            </div>
            <div id="chatPendingAttachments" class="chat-pending-attachments"></div>
            <div class="chat-input-row">
                <input type="file" id="chatFileInput" accept=".jpg,.jpeg,.png,.gif,.pdf" multiple hidden
                    onchange="handleChatFilePick(event)">
                <button type="button" class="chat-attach-btn" title="Attach image or PDF"
                    onclick="document.getElementById('chatFileInput').click()">
                    <i class="bi bi-paperclip"></i>
                </button>
                <textarea id="chatInput" placeholder="Type a message…" rows="1"
                    onkeydown="handleChatKey(event)"></textarea>
                <button class="chat-send-btn" onclick="sendMessage()">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>

    </div>
</div>

<style>
    /* ── Chat Widget ── */
    .chat-widget {
        position: fixed;
        bottom: 0;
        right: 28px;
        width: 380px;
        border-radius: 16px 16px 0 0;
        overflow: hidden;
        box-shadow: 0 -4px 32px rgba(0, 0, 0, .15);
        z-index: 1000;
        border: 1.5px solid var(--bd);
        border-bottom: none;
    }

    /* Mobile — a fixed 380px box pinned to `right: 28px` overflows off-screen
       on phone widths instead of centering. Stretch it edge-to-edge (minus
       equal side margins) so it reads as centered instead. */
    @media (max-width: 576px) {
        .chat-widget {
            left: 12px;
            right: 12px;
            width: auto;
        }
    }

    /* Header */
    .chat-header {
        background: var(--gd);
        padding: 12px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        cursor: pointer;
        user-select: none;
        transition: background .2s;
    }

    .chat-header:hover {
        background: var(--gm);
    }

    .chat-chevron {
        color: rgba(255, 255, 255, .6);
        font-size: 12px;
        transition: transform .3s;
    }

    .chat-widget.collapsed .chat-chevron {
        transform: rotate(180deg);
    }

    .chat-unread-badge {
        background: #e24b4a;
        color: #fff;
        font-size: 10px;
        font-weight: 900;
        border-radius: 20px;
        padding: 1px 7px;
        font-family: 'Nunito', sans-serif;
        min-width: 20px;
        text-align: center;
    }

    /* Body */
    .chat-body {
        background: #fff;
        display: flex;
        flex-direction: column;
        height: 420px;
        transition: height .3s ease;
    }

    .chat-widget.collapsed .chat-body {
        height: 0;
        overflow: hidden;
    }

    /* Messages */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scroll-behavior: smooth;
    }

    .chat-loading {
        text-align: center;
        color: var(--tm);
        font-size: 13px;
        padding: 20px;
    }

    /* Message bubbles */
    .msg-wrap {
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .msg-wrap.me {
        flex-direction: row-reverse;
    }

    .msg-av {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Nunito', sans-serif;
        font-weight: 900;
        font-size: 10px;
        flex-shrink: 0;
    }

    .av-employee {
        background: var(--ygl);
        color: var(--gd);
    }

    .av-helpdesk {
        background: #d4f0d4;
        color: var(--gm);
    }

    .av-tech {
        background: #fff4cc;
        color: #7a5a00;
    }

    .av-admin {
        background: var(--rdl);
        color: var(--rd);
    }

    .av-executive {
        background: #e8e0ff;
        color: #4a1a8a;
    }

    .msg-content {
        max-width: 75%;
    }

    .msg-sender {
        font-size: 10px;
        font-weight: 700;
        color: var(--tm);
        margin-bottom: 3px;
    }

    .me .msg-sender {
        text-align: right;
    }

    .msg-bubble {
        padding: 9px 13px;
        border-radius: 16px;
        font-size: 13px;
        line-height: 1.5;
        word-break: break-word;
    }

    .msg-bubble.them {
        background: var(--cr);
        color: var(--gd);
        border-bottom-left-radius: 4px;
        border: 1.5px solid var(--bd);
    }

    .msg-bubble.me {
        background: var(--gd);
        color: var(--yg);
        border-bottom-right-radius: 4px;
    }

    .msg-time {
        font-size: 10px;
        color: var(--tm);
        margin-top: 3px;
        font-weight: 600;
    }

    .me .msg-time {
        text-align: right;
    }

    .msg-role-tag {
        display: inline-block;
        font-size: 9px;
        font-weight: 800;
        border-radius: 4px;
        padding: 1px 5px;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .tag-helpdesk {
        background: #d4f0d4;
        color: var(--gm);
    }

    .tag-tech {
        background: #fff4cc;
        color: #7a5a00;
    }

    .tag-admin {
        background: var(--rdl);
        color: var(--rd);
    }

    .tag-employee {
        background: var(--ygl);
        color: var(--gd);
    }

    .tag-executive {
        background: #e8e0ff;
        color: #4a1a8a;
    }

    /* Date divider */
    .chat-date-divider {
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: var(--tm);
        position: relative;
        margin: 8px 0;
    }

    .chat-date-divider::before,
    .chat-date-divider::after {
        content: '';
        position: absolute;
        top: 50%;
        width: 35%;
        height: 1px;
        background: var(--bd);
    }

    .chat-date-divider::before {
        left: 0;
    }

    .chat-date-divider::after {
        right: 0;
    }

    /* Input area */
    .chat-input-wrap {
        border-top: 1.5px solid var(--bd);
        padding: 12px 14px;
        background: #fff;
    }

    .chat-role-pill {
        font-size: 10px;
        font-weight: 800;
        border-radius: 4px;
        padding: 2px 8px;
        display: inline-block;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: .3px;
    }

    .role-helpdesk {
        background: #d4f0d4;
        color: var(--gm);
    }

    .role-tech {
        background: #fff4cc;
        color: #7a5a00;
    }

    .role-admin {
        background: var(--rdl);
        color: var(--rd);
    }

    .role-employee {
        background: var(--ygl);
        color: var(--gd);
    }

    .role-executive {
        background: #e8e0ff;
        color: #4a1a8a;
    }

    .chat-input-row {
        display: flex;
        gap: 8px;
        align-items: flex-end;
    }

    .chat-input-row textarea {
        flex: 1;
        border: 1.5px solid var(--bd);
        border-radius: 20px;
        padding: 9px 14px;
        font-size: 13px;
        resize: none;
        outline: none;
        font-family: 'Nunito Sans', sans-serif;
        max-height: 80px;
        overflow-y: auto;
        color: var(--gd);
        background: var(--cr);
        transition: border-color .2s;
    }

    .chat-input-row textarea:focus {
        border-color: var(--gl);
        background: #fff;
    }

    .chat-attach-btn {
        width: 38px;
        height: 38px;
        background: var(--cr);
        color: var(--tm);
        border: 1.5px solid var(--bd);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: all .2s;
        font-size: 14px;
    }

    .chat-attach-btn:hover {
        border-color: var(--gl);
        color: var(--gd);
        background: var(--ygl);
    }

    /* Pending attachments (picked, not yet sent) */
    .chat-pending-attachments {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 8px;
    }

    .chat-pending-attachments:empty {
        display: none;
    }

    .chat-pending-chip {
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--ygl);
        border: 1px solid var(--bd);
        border-radius: 8px;
        padding: 4px 8px;
        font-size: 11px;
        font-weight: 700;
        color: var(--gd);
        max-width: 160px;
    }

    .chat-pending-chip span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .chat-pending-chip button {
        background: none;
        border: none;
        color: var(--tm);
        cursor: pointer;
        font-size: 13px;
        line-height: 1;
        padding: 0;
        flex-shrink: 0;
    }

    .chat-pending-chip button:hover {
        color: #e24b4a;
    }

    /* Attachments inside a sent bubble */
    .msg-attachments {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin-top: 6px;
    }

    .msg-bubble.me .msg-attachments,
    .me .msg-attachments {
        align-items: flex-end;
    }

    .msg-attach-img {
        max-width: 200px;
        max-height: 160px;
        border-radius: 10px;
        cursor: pointer;
        display: block;
        object-fit: cover;
        border: 1.5px solid var(--bd);
    }

    .msg-attach-file {
        display: flex;
        align-items: center;
        gap: 6px;
        background: var(--cr);
        border: 1.5px solid var(--bd);
        border-radius: 10px;
        padding: 6px 10px;
        font-size: 11px;
        font-weight: 700;
        color: var(--gd);
        cursor: pointer;
        max-width: 200px;
    }

    .msg-attach-file span {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .msg-attach-file:hover {
        border-color: var(--gl);
        background: var(--ygl);
    }

    .chat-send-btn {
        width: 38px;
        height: 38px;
        background: var(--gd);
        color: var(--yg);
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        flex-shrink: 0;
        transition: background .2s, transform .15s;
        font-size: 14px;
    }

    .chat-send-btn:hover {
        background: var(--gm);
        transform: scale(1.08);
    }

    .chat-send-btn:active {
        transform: scale(.95);
    }

    /* Empty state */
    .chat-empty {
        text-align: center;
        padding: 32px 20px;
        color: var(--tm);
    }

    .chat-empty i {
        font-size: 32px;
        opacity: .3;
        display: block;
        margin-bottom: 8px;
    }

    .chat-empty p {
        font-size: 13px;
        font-weight: 600;
        margin: 0;
    }
</style>

<script>
    const TICKET_ID = '{{ $ticket->id }}';
    const CURRENT_USER_ID = '{{ Auth::id() }}';
    const CHAT_MAX_ATTACHMENTS = 5;
    const CHAT_MAX_ATTACHMENT_MB = 10;
    let pollInterval = null;
    let lastMsgId = null;
    let isCollapsed = false;
    let pendingFiles = [];

    // ── Avatar class from role
    function avatarClass(role) {
        const map = {
            'IT Admin': 'av-admin', 'IT Support Specialist': 'av-tech',
            'Helpdesk': 'av-helpdesk', 'Executive': 'av-executive',
        };
        return map[role] || 'av-employee';
    }

    // ── Role tag class
    function roleTagClass(role) {
        const map = {
            'IT Admin': 'tag-admin', 'IT Support Specialist': 'tag-tech',
            'Helpdesk': 'tag-helpdesk', 'Executive': 'tag-executive',
        };
        return map[role] || 'tag-employee';
    }

    // ── Build a message bubble HTML
    function buildBubble(msg) {
        const side = msg.is_me ? 'me' : 'them';
        const avClass = avatarClass(msg.role);
        const tagCls = roleTagClass(msg.role);
        const textHtml = msg.message ? `<div class="msg-bubble ${side}">${escapeHtml(msg.message)}</div>` : '';
        const attachHtml = buildAttachmentsHtml(msg.attachments);
        return `
        <div class="msg-wrap ${msg.is_me ? 'me' : ''}" data-id="${msg.id}">
            <div class="msg-av ${avClass}">${msg.initials}</div>
            <div class="msg-content">
                <div class="msg-sender">
                    <span class="msg-role-tag ${tagCls}">${msg.role || 'User'}</span>
                    ${msg.is_me ? 'You' : msg.sender}
                </div>
                ${textHtml}
                ${attachHtml}
                <div class="msg-time">${msg.time_ago}</div>
            </div>
        </div>
    `;
    }

    // ── Build attachment thumbnails/chips for a bubble — images auto-preview
    // inline, everything else (PDFs) shows as a clickable file chip. Both use
    // data-attach-* attributes (not inline onclick) so an arbitrary filename
    // can't break out of the HTML attribute.
    function buildAttachmentsHtml(attachments) {
        if (!attachments || !attachments.length) return '';

        const items = attachments.map(a => {
            const nameAttr = escapeAttr(a.name);
            const mimeAttr = escapeAttr(a.mime_type || '');
            const isImage = (a.mime_type || '').startsWith('image/');

            if (isImage) {
                return `<img class="msg-attach-img" src="${a.view_url}" alt="${nameAttr}"
                    data-attach-id="${a.id}" data-attach-name="${nameAttr}" data-attach-mime="${mimeAttr}">`;
            }

            return `<div class="msg-attach-file" data-attach-id="${a.id}" data-attach-name="${nameAttr}" data-attach-mime="${mimeAttr}">
                <i class="bi bi-file-earmark-pdf"></i><span>${escapeHtml(a.name)}</span>
            </div>`;
        }).join('');

        return `<div class="msg-attachments">${items}</div>`;
    }

    // ── Escape HTML
    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // ── Escape a value for safe use inside an HTML attribute
    function escapeAttr(str) {
        return escapeHtml(String(str)).replace(/'/g, '&#039;');
    }

    // ── File picker: client-side cap (count + size) + type filter, same rules
    // as every other attachment picker in the app
    function handleChatFilePick(e) {
        const files = Array.from(e.target.files);
        const accepted = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

        const oversize = files.find(f => f.size > CHAT_MAX_ATTACHMENT_MB * 1024 * 1024);
        if (oversize) {
            alert(`"${oversize.name}" exceeds the ${CHAT_MAX_ATTACHMENT_MB}MB limit and will be skipped.`);
        }

        const rejected = files.find(f => !accepted.includes(f.type));
        if (rejected) {
            alert(`"${rejected.name}" isn't an image or PDF and will be skipped.`);
        }

        const valid = files.filter(f => f.size <= CHAT_MAX_ATTACHMENT_MB * 1024 * 1024 && accepted.includes(f.type));
        const combined = [...pendingFiles, ...valid];

        if (combined.length > CHAT_MAX_ATTACHMENTS) {
            alert(`You can attach up to ${CHAT_MAX_ATTACHMENTS} files per message.`);
        }

        pendingFiles = combined.slice(0, CHAT_MAX_ATTACHMENTS);
        e.target.value = '';
        renderPendingAttachments();
    }

    function removePendingFile(index) {
        pendingFiles.splice(index, 1);
        renderPendingAttachments();
    }

    function renderPendingAttachments() {
        const box = document.getElementById('chatPendingAttachments');
        box.innerHTML = pendingFiles.map((f, i) => `
            <div class="chat-pending-chip">
                <i class="bi bi-paperclip"></i>
                <span>${escapeHtml(f.name)}</span>
                <button type="button" onclick="removePendingFile(${i})">✕</button>
            </div>
        `).join('');
    }

    // ── Load all messages
    function loadMessages() {
        fetch(`/tickets/${TICKET_ID}/messages`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                const $box = document.getElementById('chatMessages');
                const msgs = data.messages;

                if (!msgs.length) {
                    $box.innerHTML = `
                <div class="chat-empty">
                    <i class="bi bi-chat-dots"></i>
                    <p>No messages yet.<br>Start the conversation!</p>
                </div>`;
                    return;
                }

                let html = '';
                let lastDate = '';
                msgs.forEach(msg => {
                    const msgDate = new Date(msg.time).toDateString();
                    if (msgDate !== lastDate) {
                        html += `<div class="chat-date-divider">${msg.time.split(',')[0]}</div>`;
                        lastDate = msgDate;
                    }
                    html += buildBubble(msg);
                });

                $box.innerHTML = html;
                scrollToBottom();

                // Track last message id for polling
                if (msgs.length) lastMsgId = msgs[msgs.length - 1].id;
            })
            .catch(() => {
                document.getElementById('chatMessages').innerHTML =
                    '<div class="chat-loading" style="color:#e24b4a">Failed to load messages.</div>';
            });
    }

    // ── Poll for new messages every 3 seconds
    function startPolling() {
        // Clear any existing interval first
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(() => {
            if (!TICKET_ID) return;

            fetch(`/tickets/${TICKET_ID}/messages`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
                .then(r => {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.json();
                })
                .then(data => {
                    const msgs = data.messages;
                    if (!msgs) return;

                    const $box = document.getElementById('chatMessages');
                    if (!$box) return;

                    const current = $box.querySelectorAll('.msg-wrap').length;

                    if (msgs.length > current) {
                        let html = '';
                        let lastDate = '';

                        msgs.forEach(msg => {
                            // Safe date parsing
                            let msgDate = 'Unknown';
                            try { msgDate = new Date(msg.created_at || msg.time).toDateString(); } catch (e) { }

                            if (msgDate !== lastDate) {
                                html += `<div class="chat-date-divider">${msg.time ? msg.time.split(',')[0] : 'Today'}</div>`;
                                lastDate = msgDate;
                            }
                            html += buildBubble(msg);
                        });

                        $box.innerHTML = html;
                        scrollToBottom();

                        // Update badge if collapsed
                        if (isCollapsed) {
                            const newUnread = msgs.filter(m => !m.is_me && !m.is_read).length;
                            updateUnreadBadge(newUnread);
                        }
                    }
                })
                .catch(err => {
                    // Silently fail — don't spam console
                    // console.error('Polling error:', err);
                });

        }, 3000);
    }

    // ── Send message (text, attachments, or both)
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value.trim();
        if (!msg && !pendingFiles.length) return;

        const filesToSend = pendingFiles;
        input.value = '';
        input.style.height = 'auto';
        pendingFiles = [];
        renderPendingAttachments();

        const formData = new FormData();
        formData.append('message', msg);
        filesToSend.forEach(f => formData.append('attachments[]', f));

        fetch(`/tickets/${TICKET_ID}/messages`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        })
            .then(r => r.json())
            .then(data => {
                const $box = document.getElementById('chatMessages');
                // Remove empty state if present
                const empty = $box.querySelector('.chat-empty');
                if (empty) empty.remove();

                $box.insertAdjacentHTML('beforeend', buildBubble(data));
                scrollToBottom();
            })
            .catch(() => alert('Failed to send message.'));
    }

    // ── Handle Enter key
    function handleChatKey(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
        // Auto-resize textarea
        const ta = document.getElementById('chatInput');
        setTimeout(() => {
            ta.style.height = 'auto';
            ta.style.height = Math.min(ta.scrollHeight, 80) + 'px';
        }, 0);
    }

    // ── Toggle chat open/close
    function toggleChat() {
        const widget = document.getElementById('chatWidget');
        isCollapsed = !isCollapsed;
        widget.classList.toggle('collapsed', isCollapsed);

        if (!isCollapsed) {
            updateUnreadBadge(0);
            loadMessages();
        }
    }

    // ── Unread badge
    function updateUnreadBadge(count) {
        const badge = document.getElementById('chatUnreadBadge');
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }

    // ── Scroll to bottom
    function scrollToBottom() {
        const box = document.getElementById('chatMessages');
        box.scrollTop = box.scrollHeight;
    }

    // ── Init
    document.addEventListener('DOMContentLoaded', function () {
        loadMessages();
        startPolling();

        // Delegated so it keeps working as messages are re-rendered on load/poll —
        // relies on window.openAttachmentPreview, defined by the attachment
        // preview modal component that every page hosting this chat also includes.
        document.getElementById('chatMessages').addEventListener('click', function (e) {
            const el = e.target.closest('[data-attach-id]');
            if (!el) return;
            openAttachmentPreview(el.dataset.attachId, el.dataset.attachName, el.dataset.attachMime);
        });
    });
</script>