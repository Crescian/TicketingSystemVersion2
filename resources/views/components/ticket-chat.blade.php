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
            <div class="chat-input-row">
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
    let pollInterval = null;
    let lastMsgId = null;
    let isCollapsed = false;

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
        return `
        <div class="msg-wrap ${msg.is_me ? 'me' : ''}" data-id="${msg.id}">
            <div class="msg-av ${avClass}">${msg.initials}</div>
            <div class="msg-content">
                <div class="msg-sender">
                    <span class="msg-role-tag ${tagCls}">${msg.role || 'User'}</span>
                    ${msg.is_me ? 'You' : msg.sender}
                </div>
                <div class="msg-bubble ${side}">${escapeHtml(msg.message)}</div>
                <div class="msg-time">${msg.time_ago}</div>
            </div>
        </div>
    `;
    }

    // ── Escape HTML
    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
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

    // ── Send message
    function sendMessage() {
        const input = document.getElementById('chatInput');
        const msg = input.value.trim();
        if (!msg) return;

        input.value = '';
        input.style.height = 'auto';

        fetch(`/tickets/${TICKET_ID}/messages`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ message: msg }),
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
    });
</script>