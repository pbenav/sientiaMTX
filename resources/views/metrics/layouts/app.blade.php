@php
    $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
    if (isset($maxWidth) && !str_starts_with($maxWidth, 'max-w-') && $maxWidth !== 'none') {
        $maxWidth = 'max-w-' . $maxWidth;
    }
    $maxWidth = $maxWidth ?? 'max-w-7xl';

    $currentTeamContext = request()->route('team');
    if (!$currentTeamContext && auth()->check()) {
        $currentTeamContext = auth()->user()->teams()->first();
    }
    if ($currentTeamContext && !is_object($currentTeamContext)) {
        $currentTeamContext = \App\Models\Team::find($currentTeamContext);
    }
    $hasGoogleLinked = false;
    if (auth()->check() && $currentTeamContext) {
        $hasGoogleLinked = auth()->user()->teams()->where('team_id', $currentTeamContext->id)->wherePivotNotNull('google_token')->exists();
    }
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="h-full {{ (auth()->check() ? auth()->user()->theme === 'dark' || (auth()->user()->theme === 'system' && request()->cookie('theme') === 'dark') : request()->cookie('theme') === 'dark') ? 'dark' : '' }}">
<script>
    (function() {
        const theme = "{{ auth()->check() ? auth()->user()->theme : request()->cookie('theme', 'system') }}";
        if (theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();
</script>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta property="og:site_name" content="Sientia Open Labs">

    <title>{{ config('app.name', 'sientiaMTX') }} — @yield('title', __('metrics.dashboard'))</title>
    <meta name="description" content="@yield('meta_description', 'sientiaMTX Metrics Dashboard')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <style>
        [x-cloak] { display: none !important; }
        [data-fouc-hide] { display: none !important; }
        body.swal2-shown,
        body.swal2-shown *,
        .swal2-container,
        .swal2-container *,
        .swal2-popup,
        .swal2-modal,
        .swal2-backdrop-show {
            cursor: auto !important;
            pointer-events: auto !important;
        }
    </style>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @auth
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('chatStore', {
                unreadConversations: [],
                setUnread(list) { this.unreadConversations = list; },
                markAsRead(senderId) { this.unreadConversations = this.unreadConversations.filter(c => parseInt(c.id) !== parseInt(senderId)); },
                hasUnread(senderId) { return this.unreadConversations.some(c => parseInt(c.id) === parseInt(senderId)); },
                get totalCount() { return this.unreadConversations.length; }
            });

            Alpine.data('sientiaChat', () => ({
                open: false,
                messages: [],
                message: '',
                member: { id: null, name: '', photo: '', status: '', email: '', telegram: '' },
                isTyping: false,
                chatSoundsEnabled: {{ (auth()->check() && (auth()->user()->notification_settings['chat_sounds'] ?? true)) ? 'true' : 'false' }},
                activeCallRoom: null,
                incomingCall: null,
                pollInterval: null,
                presenceInterval: null,
                lastUserActivity: Date.now(),
                presenceIdleThresholdMs: 5 * 60 * 1000,
                titleInterval: null,
                originalTitle: '',
                lastNotifiedMsgId: null,
                callRingInterval: null,
                showEmojis: false,
                pendingFile: null,
                pendingDriveFile: null,
                previewUrl: null,
                isUploading: false,
                replyingTo: null,
                addingMember: false,
                allUsers: [],
                searchUserQuery: '',
                recentGroups: [],
                showingRecentGroups: false,

                getFilteredUsersForAdd() {
                    const all = this.allUsers || [];
                    if (!this.searchUserQuery) return all;
                    const q = String(this.searchUserQuery).toLowerCase();
                    return all.filter(u => u.name && String(u.name).toLowerCase().includes(q));
                },
                fetchUsersForChat() {
                    if (this.allUsers.length > 0) return;
                    fetch('/chat/users')
                        .then(r => r.json())
                        .then(d => this.allUsers = d.users || [])
                        .catch(e => console.error('Error fetching users:', e));
                },
                fetchRecentGroups() {
                    fetch('/chat/groups/recent')
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                this.recentGroups = d.groups || [];
                            }
                        })
                        .catch(e => console.error('Error fetching recent groups:', e));
                },
                addMemberToGroup(userId) {
                    const isGroup = String(this.member.id).startsWith('group_');
                    if (isGroup) {
                        const groupId = String(this.member.id).replace('group_', '');
                        fetch(`/chat/group/${groupId}/members`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: JSON.stringify({ user_id: userId })
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                this.member.status = d.status;
                                this.addingMember = false;
                                this.searchUserQuery = '';
                                this.fetchMessages();
                            }
                        })
                        .catch(e => console.error('Error fetching users:', e));
                    } else {
                        fetch('/chat/group', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                            body: JSON.stringify({ receiver_ids: [this.member.id, userId] })
                        })
                        .then(r => r.json())
                        .then(d => {
                            if (d.success) {
                                this.addingMember = false;
                                this.searchUserQuery = '';
                                this.openChat(d.group);
                            }
                        })
                        .catch(e => console.error('Error creating group:', e));
                    }
                },
                renameActiveGroup(newName) {
                    if (!newName.trim()) return;
                    const groupId = String(this.member.id).replace('group_', '');
                    fetch(`/chat/group/${groupId}/rename`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: newName })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            this.member.name = d.name;
                        }
                    })
                    .catch(e => console.error('Error renaming group:', e));
                },
                deleteGroupChat(groupId) {
                    const cleanGroupId = String(groupId).replace('group_', '');
                    fetch(`/chat/group/${cleanGroupId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            if (this.member && String(this.member.id) === `group_${cleanGroupId}`) {
                                this.chatOpen = false;
                                this.member = null;
                            }
                            this.fetchRecentGroups();
                        }
                    })
                    .catch(e => console.error('Error deleting group:', e));
                },

                init() {
                    this.originalTitle = document.title;
                    this.pollInterval = setInterval(() => this.checkNewMessages(), 4004);

                    const activityEvents = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click', 'focus'];
                    const recordActivity = () => { this.lastUserActivity = Date.now(); };
                    activityEvents.forEach(evt => window.addEventListener(evt, recordActivity, { passive: true }));

                    const sendPresencePing = () => {
                        if (document.hidden) return;
                        const idleMs = Date.now() - this.lastUserActivity;
                        if (idleMs > this.presenceIdleThresholdMs) return;
                        fetch('/comms/presence', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                        }).catch(() => {});
                    };

                    sendPresencePing();
                    this.presenceInterval = setInterval(sendPresencePing, 60000);

                    const unlockAudio = () => {
                        if (window.sientiaAudioCtx && window.sientiaAudioCtx.state === 'suspended') {
                            window.sientiaAudioCtx.resume();
                        }
                        document.removeEventListener('click', unlockAudio);
                        document.removeEventListener('keydown', unlockAudio);
                    };
                    document.addEventListener('click', unlockAudio);
                    document.addEventListener('keydown', unlockAudio);
                },

                openChat(detail) {
                    this.member = detail;
                    try { localStorage.setItem('sientia_last_chat', JSON.stringify(detail)); } catch(e) {}
                    this.open = true;
                    this.activeCallRoom = null;
                    this.incomingCall = null;
                    this.replyingTo = null;
                    this.clearPendingAttachments();
                    Alpine.store('chatStore').markAsRead(detail.id);
                    this.fetchMessages();
                    this.$nextTick(() => {
                        const input = this.$refs.chatInput;
                        if (input) input.focus();
                    });
                },

                openLastChat() {
                    if (Alpine.store('chatStore').unreadConversations.length > 0) {
                        this.openChat(Alpine.store('chatStore').unreadConversations[0]);
                        return;
                    }
                    if (this.member && this.member.id) {
                        this.open = true;
                        this.fetchMessages();
                        this.$nextTick(() => {
                            const input = this.$refs.chatInput;
                            if (input) input.focus();
                        });
                    }
                },

                close() { this.open = false; this.activeCallRoom = null; this.stopFlashAndSound(); },

                fetchMessages() {
                    if (!this.member.id) return;
                    fetch('/chat/' + this.member.id + '?_=' + Date.now(), {
                        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.member) this.member = { ...this.member, ...data.member };
                        this.messages = data.messages || [];
                        this.$nextTick(() => this.scrollToBottom());
                    })
                    .catch(err => console.error('Error fetching chat messages:', err));
                },

                sendMessage() {
                    if (this.isUploading) return;
                    const text = this.message.trim();
                    if (!text && !this.pendingFile && !this.pendingDriveFile) return;
                    if (!this.member.id) return;

                    this.isUploading = true;
                    this.message = '';
                    this.showEmojis = false;

                    const optimisticMsg = {
                        id: Date.now(),
                        sender: 'me',
                        text: text,
                        file_name: this.pendingDriveFile ? this.pendingDriveFile.name : (this.pendingFile ? this.pendingFile.name : null),
                        file_type: this.pendingDriveFile ? 'file' : (this.pendingFile ? (this.pendingFile.type.startsWith('image/') ? 'image' : 'file') : null),
                        file_url: this.previewUrl,
                        storage_provider: this.pendingDriveFile ? 'google' : 'local',
                        web_view_link: this.pendingDriveFile ? this.pendingDriveFile.webViewLink : null,
                        time: new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
                        parent_id: this.replyingTo ? this.replyingTo.id : null,
                        parent_text: this.replyingTo ? (this.replyingTo.text || (this.replyingTo.file_name ? '📎 ' + this.replyingTo.file_name : '...')) : null,
                        parent_sender_name: this.replyingTo ? (this.replyingTo.sender === 'me' ? 'Tú' : this.member.name) : null
                    };

                    this.messages.push(optimisticMsg);
                    const replyToId = this.replyingTo ? this.replyingTo.id : null;
                    this.replyingTo = null;
                    const fileToUpload = this.pendingFile;
                    const driveFileToUpload = this.pendingDriveFile;
                    this.clearPendingAttachments();
                    this.$nextTick(() => this.scrollToBottom());

                    const formData = new FormData();
                    formData.append('receiver_id', this.member.id);
                    if (text) formData.append('message', text);
                    if (fileToUpload) formData.append('file', fileToUpload);
                    if (driveFileToUpload) formData.append('drive_file', JSON.stringify(driveFileToUpload));
                    if (replyToId) formData.append('parent_id', replyToId);

                    fetch('/chat', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
                        body: formData
                    })
                    .then(r => r.ok ? r.json() : Promise.reject(r))
                    .then(data => {
                        const idx = this.messages.findIndex(m => m.id === optimisticMsg.id);
                        if (idx !== -1) {
                            this.messages[idx] = { ...this.messages[idx], ...data.message, sender: 'me' };
                        } else {
                            this.fetchMessages();
                        }
                    })
                    .catch(err => {
                        this.messages = this.messages.filter(m => m.id !== optimisticMsg.id);
                    })
                    .finally(() => this.isUploading = false);
                },

                deleteMessage(msgId) {
                    fetch(`/chat/message/${msgId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            this.messages = this.messages.filter(m => m.id !== msgId);
                        }
                    })
                    .catch(e => console.error('Error deleting message:', e));
                },

                clearChat() {
                    if (!this.member || !this.member.id) return;
                    fetch('/chat/clear/' + this.member.id, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json'
                        }
                    }).then(r => r.json())
                    .then(d => {
                        if(d.success) {
                            this.messages = [];
                        }
                    });
                },

                checkNewMessages() {
                    fetch('/comms/heartbeat?_=' + Date.now(), {
                        headers: { 'Cache-Control': 'no-cache', 'Pragma': 'no-cache', 'Accept': 'application/json' }
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.unread.length > 0) {
                            const uniqueMap = {};
                            data.unread.forEach(m => {
                                if (!uniqueMap[m.sender_id]) {
                                    uniqueMap[m.sender_id] = { id: m.sender_id, name: m.sender_name, photo: m.sender_photo, team: m.sender_team, text: m.text || (m.file_name ? '📎 Adjunto' : '...') };
                                }
                            });
                            Alpine.store('chatStore').setUnread(Object.values(uniqueMap));

                            const callMsg = data.unread.find(m => m.call_room);
                            const lastMsg = data.unread[0];

                            if (callMsg && !this.activeCallRoom && (!this.incomingCall || this.incomingCall.room !== callMsg.call_room)) {
                                this.rejectedCalls = this.rejectedCalls || [];
                                if (!this.rejectedCalls.includes(callMsg.call_room)) {
                                    this.incomingCall = { room: callMsg.call_room, sender_id: callMsg.sender_id, sender_name: callMsg.sender_name, sender_photo: callMsg.sender_photo };
                                    this.startFlashAndSound();
                                }
                            } else if (!callMsg && (!this.lastNotifiedMsgId || this.lastNotifiedMsgId !== lastMsg.id)) {
                                this.lastNotifiedMsgId = lastMsg.id;
                                if (this.chatSoundsEnabled) this.playMessageChime();
                                this.startMessageFlash();
                            }
                            if (this.open && this.member.id === lastMsg.sender_id) this.fetchMessages();
                        } else {
                            Alpine.store('chatStore').setUnread([]);
                        }
                    })
                    .catch(e => {});
                },

                acceptCall() {
                    const url = this.incomingCall.room.startsWith('http') ? this.incomingCall.room : 'https://meet.jit.si/' + this.incomingCall.room;
                    window.open(url, '_blank');
                    this.openChat({ id: this.incomingCall.sender_id, name: this.incomingCall.sender_name, photo: this.incomingCall.sender_photo });
                    this.stopFlashAndSound();
                    this.incomingCall = null;
                },

                rejectCall(room) {
                    if (room) {
                        this.rejectedCalls = this.rejectedCalls || [];
                        if (!this.rejectedCalls.includes(room)) this.rejectedCalls.push(room);
                    }
                    this.stopFlashAndSound();
                    this.incomingCall = null;
                },

                startFlashAndSound() {
                    if (this.titleInterval) clearInterval(this.titleInterval);
                    this.titleInterval = setInterval(() => { document.title = document.title === this.originalTitle ? '📞 LLAMADA...' : this.originalTitle; }, 1000);
                    this.stopCallSound();
                    const ring = () => {
                        try {
                            if (!window.sientiaAudioCtx) {
                                window.sientiaAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                            }
                            const ctx = window.sientiaAudioCtx;
                            if (ctx.state === 'suspended') ctx.resume();

                            const notes = [523.25, 659.25, 783.99, 1046.50];
                            notes.forEach((f, i) => {
                                setTimeout(() => {
                                    if (!this.callRingInterval) return;
                                    const osc = ctx.createOscillator();
                                    const gain = ctx.createGain();
                                    osc.connect(gain);
                                    gain.connect(ctx.destination);
                                    osc.type = 'sine';
                                    osc.frequency.setValueAtTime(f, ctx.currentTime);
                                    gain.gain.setValueAtTime(0.3, ctx.currentTime);
                                    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.6);
                                    osc.start();
                                    osc.stop(ctx.currentTime + 0.6);
                                }, i * 150);
                            });
                        } catch (e) {}
                    };
                    ring();
                    this.callRingInterval = setInterval(ring, 2000);
                },

                stopCallSound() { if (this.callRingInterval) { clearInterval(this.callRingInterval); this.callRingInterval = null; } },
                stopFlashAndSound() { this.stopCallSound(); if (this.titleInterval) { clearInterval(this.titleInterval); this.titleInterval = null; } document.title = this.originalTitle; },
                startMessageFlash() { if (this.titleInterval) clearInterval(this.titleInterval); this.titleInterval = setInterval(() => { document.title = document.title === this.originalTitle ? '💬 MENSAJE...' : this.originalTitle; }, 1200); const stop = () => { this.stopFlashAndSound(); window.removeEventListener('focus', stop); }; window.addEventListener('focus', stop, {once: true}); },
                playMessageChime() {
                    try {
                        if (!window.sientiaAudioCtx) {
                            window.sientiaAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        }
                        const ctx = window.sientiaAudioCtx;
                        if (ctx.state === 'suspended') ctx.resume();

                        const notes = [660, 880];
                        notes.forEach((f, i) => {
                            setTimeout(() => {
                                const osc = ctx.createOscillator();
                                const gain = ctx.createGain();
                                osc.connect(gain);
                                gain.connect(ctx.destination);
                                osc.type = 'sine';
                                osc.frequency.setValueAtTime(f, ctx.currentTime);
                                gain.gain.setValueAtTime(0.4, ctx.currentTime);
                                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.5);
                                osc.start();
                                osc.stop(ctx.currentTime + 0.5);
                            }, i * 120);
                        });
                    } catch (e) {}
                },
                scrollToBottom() { const c = this.$refs.chatContainer; if (c) c.scrollTop = c.scrollHeight; },
                clearPendingAttachments() { if (this.previewUrl && this.previewUrl.startsWith('blob:')) URL.revokeObjectURL(this.previewUrl); this.pendingFile = null; this.previewUrl = null; this.pendingDriveFile = null; },
                handleFileSelect(e) { const f = e.target.files[0]; if (f) this.processFile(f); e.target.value = ''; },
                processFile(f) { if (f.size > 10 * 1024 * 1024) { return; } this.pendingFile = f; this.previewUrl = URL.createObjectURL(f); this.$nextTick(() => this.$refs.chatInput.focus()); },
                handlePaste(e) { const items = (e.clipboardData || e.originalEvent.clipboardData).items; for (let i in items) { if (items[i].kind === 'file') { const b = items[i].getAsFile(); const f = new File([b], `img_${Date.now()}.png`, { type: b.type }); this.processFile(f); } } },
                insertEmoji(e) { const i = this.$refs.chatInput; const s = i.selectionStart; const en = i.selectionEnd; this.message = this.message.substring(0, s) + e + this.message.substring(en); this.$nextTick(() => { i.focus(); const n = s + e.length; i.setSelectionRange(n, n); }); },
                startSientiaCall() {
                    if (this.member.id === {{ auth()->id() }}) return;
                    fetch('/chat/call', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ receiver_id: this.member.id })
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success && d.room) {
                            window.open('https://meet.jit.si/' + d.room, '_blank');
                            this.fetchMessages();
                        }
                    })
                    .catch(() => {});
                },
                startGoogleMeet() {
                    if (this.member.id === {{ auth()->id() }}) return;
                    fetch('/chat/meet', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ receiver_id: this.member.id }),
                    })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) {
                            window.open(d.meet_url, '_blank');
                            this.fetchMessages();
                        }
                    })
                    .catch(() => {});
                }
            }));
        });
    </script>
    @endauth

    <x-markdown-styles :team="$resolvedTeam ?? null" />

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('timer', {
                activeTaskId: {{ auth()->check() ? (auth()->user()->activeTaskLog()?->task_id ?? 'null') : 'null' }},
                elapsed: {{ auth()->check() && auth()->user()->activeTaskLog() ? auth()->user()->activeTaskLog()->start_at->diffInSeconds(now()) : 0 }},
                timer: null,

                async fetch() {
                    try {
                        const res = await fetch('{{ route('time-logs.status') }}');
                        const data = await res.json();
                        this.activeTaskId = data.active_task_id;
                        this.elapsed = Math.floor(data.task_elapsed);
                        if (this.activeTaskId) this.tick();
                        else this.stop();
                    } catch(e) {}
                },
                tick() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = setInterval(() => { this.elapsed++; }, 1000);
                },
                stop() {
                    if (this.timer) clearInterval(this.timer);
                    this.timer = null;
                    this.activeTaskId = null;
                },
                init() {
                    if (this.activeTaskId) this.tick();

                    window.addEventListener('task-started', (e) => {
                        this.activeTaskId = e.detail.taskId;
                        this.elapsed = 0;
                        this.tick();
                    });

                    window.addEventListener('workday-toggled', (e) => {
                        if (!e.detail.working) this.stop();
                    });
                }
            });

            Alpine.store('notifications', {
                count: {{ auth()->check() ? Auth::user()->unreadNotifications->count() : 0 }},
                lastChecked: Date.now(),
                firstCheck: true,
                lastSummaryShown: localStorage.getItem('last_notification_summary_shown') || 0,

                async check() {
                    try {
                        const res = await fetch('{{ route('notifications.unread-count') }}');
                        const data = await res.json();

                        const now = Date.now();
                        const twoHours = 2 * 60 * 60 * 1000;

                        if (this.firstCheck && data.count > 0 && (now - this.lastSummaryShown) > twoHours) {
                            this.firstCheck = false;
                            this.lastSummaryShown = now;
                            localStorage.setItem('last_notification_summary_shown', now);
                        }
                        else if (data.count > this.count && data.unread.length > 0) {
                            this.showToast(data.unread[0]);
                        }

                        if (data.count !== this.count) {
                            window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { count: data.count } }));
                        }

                        this.count = data.count;
                    } catch(e) {}
                },

                showToast(notification) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '{{ __("Nueva notificación") }}',
                            text: notification.data.message || '{{ __("Tienes una nueva actualización") }}',
                            icon: 'info',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 6000,
                            timerProgressBar: true,
                            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                            color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                            didOpen: (toast) => {
                                toast.style.zIndex = '9999';
                                toast.addEventListener('click', () => { window.location.href = '{{ route("notifications.index") }}'; })
                            }
                        });
                    }
                },

                init() {
                    @auth
                    setTimeout(() => this.check(), 5000);
                    setInterval(() => this.check(), 60000);

                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.check();
                        }
                    });
                    @endauth
                }
            });
        });
    </script>
</head>

<body class="h-full bg-gray-50 dark:bg-gray-950 font-inter text-sm antialiased">
    <div class="min-h-full">
        <x-installs-navbar />

        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 md:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
