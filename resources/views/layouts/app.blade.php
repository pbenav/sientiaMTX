@php
    $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
    // Normalize maxWidth to ensure it includes the 'max-w-' prefix if it's a standard size
    if (isset($maxWidth) && !str_starts_with($maxWidth, 'max-w-') && $maxWidth !== 'none') {
        $maxWidth = 'max-w-' . $maxWidth;
    }
    $maxWidth = $maxWidth ?? 'max-w-7xl';
    
    // Get global team context for background tools like chat or drive
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
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
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

    <!-- Metadatos de Nombre de Sitio para Google (SEO / Open Graph) -->
    <meta property="og:site_name" content="Sientia Open Labs">

    <title>{{ config('app.name', 'sientiaMTX') }} — @yield('title', __('navigation.dashboard'))</title>
    <meta name="description" content="@yield('meta_description', 'sientiaMTX — Smart project management with MTX, Gantt, and Kanban for focused teams.')">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@500;600;700&display=swap"
        rel="stylesheet">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Marked.js (Markdown Rendering) -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Sientia Direct Chat & Video Call Engine -->
    @auth
    <script>
        document.addEventListener('alpine:init', () => {
            console.log('🚀 SientiaChat Engine: Initializing stores...');
            
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
                
                init() {
                    this.originalTitle = document.title;
                    console.log('✅ SientiaChat Component: Initialized');
                    this.pollInterval = setInterval(() => this.checkNewMessages(), 4000);

                    // Unlock AudioContext on first user interaction (Brave/Firefox fix)
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
                    if (this.member && this.member.id) {
                        this.open = true;
                        this.fetchMessages();
                        this.$nextTick(() => {
                            const input = this.$refs.chatInput;
                            if (input) input.focus();
                        });
                    } else {
                        Swal.fire({ icon: 'info', title: 'Chat Interno', text: 'No tienes ninguna sala de chat activa. Selecciona un usuario o grupo en la Red Activa o abre un mensaje pendiente.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
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
                        // Actualizar el mensaje optimista con los datos reales del servidor (especialmente el ID real)
                        const idx = this.messages.findIndex(m => m.id === optimisticMsg.id);
                        if (idx !== -1) {
                            this.messages[idx] = { ...this.messages[idx], ...data.message, sender: 'me' };
                        } else {
                            this.fetchMessages(); // Fallback por si acaso
                        }
                    })
                    .catch(err => {
                        console.error('Error sending message:', err);
                        // Quitar el mensaje optimista si falló el envío
                        this.messages = this.messages.filter(m => m.id !== optimisticMsg.id);
                        Swal.fire({ icon: 'error', title: 'Error al enviar', text: 'No se pudo enviar el mensaje.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                    })
                    .finally(() => this.isUploading = false);
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
                                this.incomingCall = { room: callMsg.call_room, sender_id: callMsg.sender_id, sender_name: callMsg.sender_name, sender_photo: callMsg.sender_photo };
                                this.startFlashAndSound();
                                const isGoogleMeet = callMsg.call_room.startsWith('http');
                                Swal.fire({
                                    title: isGoogleMeet ? '🌐 GOOGLE MEET' : '🎥 LLAMADA ENTRANTE',
                                    html: `<div class="flex flex-col items-center gap-4 py-2"><div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 p-0.5 shadow-xl relative"><img src="${callMsg.sender_photo}" class="w-full h-full rounded-[14px] object-cover"></div><div><p class="text-xs font-black uppercase">${callMsg.sender_name}</p><p class="text-xs font-bold mt-2">${isGoogleMeet ? '¡te invita a una reunión!' : '¡te está llamando!'}</p></div></div>`,
                                    showCancelButton: true,
                                    confirmButtonText: isGoogleMeet ? 'Unirse 🚀' : 'Contestar 👍',
                                    confirmButtonColor: isGoogleMeet ? '#0ea5e9' : '#059669',
                                    cancelButtonText: 'Ahora no 👎',
                                    customClass: { popup: 'rounded-[2.5rem] dark:bg-gray-950 dark:text-white' }
                                }).then((result) => {
                                    if (result.isConfirmed) this.acceptCall();
                                    else this.rejectCall();
                                });
                            } else if (!callMsg && (!this.lastNotifiedMsgId || this.lastNotifiedMsgId !== lastMsg.id)) {
                                this.lastNotifiedMsgId = lastMsg.id;
                                if (this.chatSoundsEnabled) this.playMessageChime();
                                this.startMessageFlash();
                                const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 4000 });
                                Toast.fire({ icon: 'info', html: `<div class="text-left py-1 pr-2"><p class="text-xs font-black uppercase">${lastMsg.sender_name}</p><p class="text-xs text-gray-600 dark:text-gray-300 truncate">${lastMsg.text}</p></div>`, didOpen: (t) => { t.style.cursor = 'pointer'; t.onclick = () => this.openChat({ id: lastMsg.sender_id, name: lastMsg.sender_name, photo: lastMsg.sender_photo, team: lastMsg.sender_team, is_group: String(lastMsg.sender_id).startsWith('group_') }); } });
                            }
                            if (this.open && this.member.id === lastMsg.sender_id) this.fetchMessages();
                        } else {
                            Alpine.store('chatStore').setUnread([]);
                        }
                    })
                    .catch(e => console.warn('Chat poll skip:', e));
                },

                acceptCall() {
                    const url = this.incomingCall.room.startsWith('http') ? this.incomingCall.room : 'https://meet.jit.si/' + this.incomingCall.room;
                    window.open(url, '_blank');
                    this.openChat({ id: this.incomingCall.sender_id, name: this.incomingCall.sender_name, photo: this.incomingCall.sender_photo });
                    this.stopFlashAndSound();
                    this.incomingCall = null;
                },

                rejectCall() { this.stopFlashAndSound(); this.incomingCall = null; },

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
                        } catch (e) { console.warn('Call ring failed:', e); }
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
                    } catch (e) { console.warn('Audio chime failed:', e); }
                },
                scrollToBottom() { const c = this.$refs.chatContainer; if (c) c.scrollTop = c.scrollHeight; },
                clearPendingAttachments() { if (this.previewUrl && this.previewUrl.startsWith('blob:')) URL.revokeObjectURL(this.previewUrl); this.pendingFile = null; this.previewUrl = null; this.pendingDriveFile = null; },
                handleFileSelect(e) { const f = e.target.files[0]; if (f) this.processFile(f); e.target.value = ''; },
                processFile(f) { if (f.size > 10 * 1024 * 1024) { alert('⚠️ Límite 10MB'); return; } this.pendingFile = f; this.previewUrl = URL.createObjectURL(f); this.$nextTick(() => this.$refs.chatInput.focus()); },
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
                        if (!d.success || !d.room) {
                            Swal.fire({ icon: 'error', title: 'Error', text: d.message || 'No se pudo iniciar la llamada.', toast: true, position: 'top-end', timer: 4000, showConfirmButton: false });
                            return;
                        }
                        window.open('https://meet.jit.si/' + d.room, '_blank');
                        this.fetchMessages();
                    })
                    .catch(() => Swal.fire({ icon: 'error', title: 'Error de red', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false }));
                },
                startGoogleMeet() {
                    if (this.member.id === {{ auth()->id() }}) return;

                    // Show spinner while creating the Meet space
                    Swal.fire({
                        title: '🌐 Creando sala Meet...',
                        text: 'Conectando con Google Meet',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => Swal.showLoading(),
                    });

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
                        Swal.close();
                        if (!d.success) {
                            const linkToProfile = d.needs_auth
                                ? '<br><a href="/profile?tab=integrations" class="underline text-sky-400 text-xs" target="_blank">Conectar cuenta Google →</a>'
                                : '';
                            Swal.fire({
                                icon: 'error',
                                title: 'No se pudo crear la sala',
                                html: (d.message || 'Error desconocido') + linkToProfile,
                                customClass: { popup: 'rounded-[2rem] dark:bg-gray-950 dark:text-white' },
                            });
                            return;
                        }
                        // Open the Meet URL for the caller immediately
                        window.open(d.meet_url, '_blank');
                        // Refresh chat so the invitation bubble appears
                        this.fetchMessages();
                    })
                    .catch(() => {
                        Swal.close();
                        Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor.', toast: true, position: 'top-end', timer: 3000, showConfirmButton: false });
                    });
                }
            }));
        });
    </script>
    @endauth


    <!-- Marked.js (Markdown Rendering) -->
    @php
        $resolvedTeam = $team ?? ($__data['team'] ?? null);
        if (!$resolvedTeam) {
            $teamRouteParam = request()->route('team');
            if ($teamRouteParam) {
                if (is_object($teamRouteParam)) {
                    $resolvedTeam = $teamRouteParam;
                } else {
                    $resolvedTeam = \App\Models\Team::where('id', $teamRouteParam)
                        ->orWhere('slug', $teamRouteParam)
                        ->first();
                }
            }
        }
        if (!$resolvedTeam) {
            $threadObj = $thread ?? ($__data['thread'] ?? null);
            if ($threadObj) {
                $resolvedTeam = is_object($threadObj) ? ($threadObj->team ?? null) : null;
            }
        }
    @endphp
    <x-markdown-styles :team="$resolvedTeam" />

    <!-- Global Alpine Store for Timer (Performance optimization N -> 1) -->
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
                    } catch(e) { console.error('Timer sync failed', e); }
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
                    
                    // Listeners Centralized
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
                        
                        // Only show pending summary if it hasn't been shown in the last 2 hours
                        const now = Date.now();
                        const twoHours = 2 * 60 * 60 * 1000;
                        
                        if (this.firstCheck && data.count > 0 && (now - this.lastSummaryShown) > twoHours) {
                            if (data.count === 1) {
                                this.showToast(data.unread[0]);
                            } else {
                                Swal.fire({
                                    title: '{{ __("Pendientes") }}',
                                    text: '{{ __("Tienes :count notificaciones pendientes", ["count" => ""]) }}'.replace('""', data.count) + data.count,
                                    icon: 'info',
                                    toast: true,
                                    position: 'top-end',
                                    showConfirmButton: false,
                                    timer: 7000,
                                    timerProgressBar: true,
                                    background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
                                    color: document.documentElement.classList.contains('dark') ? '#f3f4f6' : '#1f2937',
                                    didOpen: (toast) => {
                                        toast.style.zIndex = '9999'; // Force super high z-index
                                        toast.addEventListener('click', () => { window.location.href = '{{ route("notifications.index") }}'; })
                                    }
                                });
                            }
                            this.firstCheck = false;
                            this.lastSummaryShown = now;
                            localStorage.setItem('last_notification_summary_shown', now);
                        } 
                        // If count increased during session, show the new one
                        else if (data.count > this.count && data.unread.length > 0) {
                            this.showToast(data.unread[0]);
                        }
                        
                        if (data.count !== this.count) {
                            window.dispatchEvent(new CustomEvent('notifications-updated', { detail: { count: data.count } }));
                        }
                        
                        this.count = data.count;
                    } catch(e) { console.error('Notification check failed', e); }
                },

                showToast(notification) {
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
                            toast.style.zIndex = '9999'; // Ensure it's above everything
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                            toast.addEventListener('click', () => {
                                window.location.href = '{{ route("notifications.index") }}';
                            })
                        }
                    });
                },
                
                init() {
                    @auth
                    // Initial check
                    setTimeout(() => this.check(), 5000);
                    
                    // Poll less frequently (1 minute)
                    setInterval(() => this.check(), 60000);

                    // Re-check when coming back to the tab
                    document.addEventListener('visibilitychange', () => {
                        if (document.visibilityState === 'visible') {
                            this.check();
                        }
                    });
                    @endauth
                }
            });

            // Initial sync (background)
            Alpine.store('timer').fetch();
        });
    </script>

    <style>
        :root {
            --color-q1: #ef4444;
            /* Red   – Do First  */
            --color-q2: #3b82f6;
            /* Blue  – Schedule  */
            --color-q3: #f59e0b;
            /* Amber – Delegate  */
            --color-q4: #6b7280;
            /* Gray  – Eliminate */
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        /* Essential for sticky elements to work correctly in some browsers */
        html {
            scroll-behavior: smooth;
        }

        h1,
        h2,
        h3,
        h4,
        .heading {
            font-family: 'Space Grotesk', sans-serif;
        }

        [x-cloak] {
            display: none !important;
        }

        .no-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .no-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        /* Prevent layout clipping on mobile for wide content like Kanban */
        @media (max-width: 1024px) {
            #mainContent[data-wide-content="true"] {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            #mainContent {
                max-width: none !important;
                width: 100% !important;
            }
        }

        /* Critical Layout Stability Styles - Prevents FOUC/Flicker */
        @if($layout === 'vertical')
            #sidebar:not(.translate-x-0) {
                transform: translateX(-100%) !important;
            }
            @media (min-width: 1024px) {
                body:not(.sidebar-closed) #sidebar { transform: translateX(0) !important; }
                body:not(.sidebar-closed) #mainContent.lg-layout-v-fix, 
                body:not(.sidebar-closed) footer.lg-layout-v-fix, 
                body:not(.sidebar-closed) .header-v-fix { 
                    padding-left: 18rem !important; 
                }
            }
        @endif

        /* Critical Responsive Visibility - Prevents Mobile Overlays on Desktop FOUC */
        @media (min-width: 640px) { .sm\:hidden { display: none !important; } }
        @media (min-width: 768px) { .md\:hidden { display: none !important; } }
        @media (min-width: 1024px) { .lg\:hidden { display: none !important; } }
        @media (min-width: 1280px) { .xl\:hidden { display: none !important; } }

        /* GLOBAL TOMSELECT FIX: Prevenir que el wrapper herede estilos de Tailwind del select original */
        .ts-wrapper {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            box-shadow: none !important;
        }
        /* Sientia Global Print Overrides */
        @media print {
            body.print-clean-mode nav,
            body.print-clean-mode header,
            body.print-clean-mode footer,
            body.print-clean-mode aside,
            body.print-clean-mode #sidebar,
            body.print-clean-mode .app-header,
            body.print-clean-mode button,
            body.print-clean-mode .no-print,
            body.print-clean-mode #docs-sidebar,
            body.print-clean-mode #docs-mobile-toggle {
                display: none !important;
            }
            body.print-clean-mode main,
            body.print-clean-mode .flex-1 {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                flex: 1 1 100% !important;
            }
        }
    </style>
</head>

<body class="h-full bg-white text-gray-900 dark:bg-gray-950 dark:text-gray-100 antialiased"
    :class="{ 'sidebar-closed': !sidebarOpen && layout === 'vertical' }"
    x-data="{
    layout: '{{ $layout }}',
    sidebarOpen: false,
    mounted: false,
    cleanMode: localStorage.getItem('cleanMode') === 'true',
    toggleCleanMode() {
        this.cleanMode = !this.cleanMode;
        localStorage.setItem('cleanMode', this.cleanMode);
    },
    init() {
        this.$nextTick(() => { 
            this.mounted = true; 
            this.sidebarOpen = (window.innerWidth >= 1024 && this.layout === 'vertical');
        });
        window.addEventListener('resize', () => {
            if (window.innerWidth < 1024) {
                this.sidebarOpen = false;
            }
        });
    },
    async updateLayout(newLayout) {
        if (this.layout === newLayout) return;
        
        this.layout = newLayout;
        document.cookie = 'layout=' + newLayout + '; path=/; max-age=' + (30 * 24 * 60 * 60) + '; SameSite=Lax';

        @auth
        try {
            await fetch('{{ route('layout.update') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ layout: newLayout })
            });
        } catch (error) {
            console.error('Error updating layout:', error);
        }
        @endauth

        window.location.reload();
    }
}">

    <div id="app-root" class="min-h-screen flex flex-col">
        @include('partials.welcome-modal')
        @include('partials.work-schedule-modal')
    @include('layouts.navigation-sidebar')

    <!-- Navigation -->
    <nav x-show="layout === 'horizontal'" style="{{ $layout === 'vertical' ? 'display:none' : '' }}"
        x-data="{ mobileMenuOpen: false }"
        class="bg-white border-b border-gray-200 dark:bg-gray-950 dark:border-gray-800 sticky top-0 z-[80] w-full overflow-visible">
        <div class="max-w-none lg:{{ $maxWidth }} mx-auto px-2 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="{{ auth()->check() ? (request()->route('team') ? route('teams.dashboard', request()->route('team')) : route('dashboard')) : route('home') }}"
                    class="flex items-center gap-2 group shrink-0">
                    <div
                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-violet-500 to-indigo-600 flex items-center justify-center shadow-lg group-hover:shadow-violet-500/30 transition-all duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-white" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                            stroke-linejoin="round">
                            <rect x="3" y="3" width="8" height="8" rx="1" />
                            <rect x="13" y="3" width="8" height="8" rx="1" />
                            <rect x="3" y="13" width="8" height="8" rx="1" />
                            <rect x="13" y="13" width="8" height="8" rx="1" />
                        </svg>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white text-lg tracking-tight"
                        style="font-family:'Space Grotesk',sans-serif">sientia<span
                            class="text-violet-600 dark:text-violet-400">MTX</span></span>
                </a>

                <!-- Right side: flex container taking remaining space -->
                <div class="flex items-center gap-1 sm:gap-3 flex-1 justify-end min-w-0">

                    <!-- 1. DESKTOP: Inline Icons (Labels only on lg+) -->
                    <div class="hidden lg:flex items-center gap-1 sm:gap-3 overflow-x-auto min-w-0 [&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]">
                    @auth
                        @if(auth()->user()->favoriteTeam)
                            <!-- Favorite Team Desktop -->
                            <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}"
                                class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-amber-500 dark:text-amber-400 hover:text-amber-600 dark:hover:text-amber-300 transition-all rounded-xl hover:bg-amber-50 dark:hover:bg-amber-900/20 relative group"
                                title="Escritorio de {{ auth()->user()->favoriteTeam->name }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5 fill-amber-400/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                                </svg>
                                <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none">Escritorio</span>
                            </a>
                        @endif

                        <!-- My Teams -->
                        <a href="{{ route('teams.index') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all rounded-xl hover:bg-violet-50 dark:hover:bg-violet-500/10 relative group"
                            title="{{ __('navigation.my_teams') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none">{{ __('navigation.my_teams') }}</span>
                            @php $teamCount = auth()->user()->teams()->count(); @endphp
                            @if($teamCount > 0)
                                <span class="absolute top-1 right-2 flex h-4 w-4 items-center justify-center rounded-full bg-violet-600 text-[10px] font-bold text-white shadow-sm ring-2 ring-white dark:ring-gray-950 px-1">
                                    {{ $teamCount }}
                                </span>
                            @endif
                        </a>

                        <!-- Global Surveys -->
                        <a href="{{ route('global-surveys.index') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-all rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-500/10 relative group {{ request()->routeIs('global-surveys.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-600 dark:text-indigo-400' : '' }}"
                            title="{{ __('Encuestas Globales') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">Globales</span>
                        </a>

                        <!-- Disk Usage -->
                        <a href="{{ route('media.index') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all rounded-xl hover:bg-blue-50 dark:hover:bg-blue-500/10 {{ request()->routeIs('media.index') ? 'bg-blue-50 dark:bg-blue-500/10 text-blue-600 dark:text-blue-400' : '' }}"
                            title="{{ __('tasks.disk_quota') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('tasks.disk_quota') }}</span>
                        </a>

                        <a href="{{ route('docs') }}"
                            class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[3.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->is('docs*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                            title="{{ __('Documentación') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                            <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">Doc</span>
                        </a>

                        @can('admin')
                            <a href="{{ route('settings.users') }}"
                                class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[4.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.users') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.users') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('navigation.users') }}</span>
                            </a>

                            <a href="{{ route('settings.mail') }}"
                                class="flex flex-col items-center justify-center min-w-[3rem] lg:min-w-[5.5rem] px-2 h-14 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-all rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 {{ request()->routeIs('settings.mail*') ? 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white' : '' }}"
                                title="{{ __('navigation.settings') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mb-0.5" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                <span class="hidden lg:block text-[9px] font-bold uppercase tracking-tight leading-none text-center px-1">{{ __('navigation.settings') }}</span>
                            </a>
                        @endcan
                    </div>
 
                    <!-- 2. TABLET & MOBILE (sm to lg): Main Menu Dropdown -->
                    <div class="hidden sm:block lg:hidden relative shrink-0" x-data="{ open: false }">
                        <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 px-3 h-11 text-sm font-bold uppercase tracking-tight text-gray-500 hover:text-violet-600 bg-gray-50 dark:bg-gray-800/80 rounded-xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <span>{{ __('Menú') }}</span>
                        </button>
                        <div x-show="open" x-transition:enter="transition ease-out duration-200" 
                             x-transition:enter-start="opacity-0 translate-y-1" 
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-150"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-1"
                             x-cloak style="display: none;"
                             class="absolute right-0 mt-2 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl overflow-hidden z-[90]">
                            @auth
                             <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-100 dark:border-gray-700">
                                 <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">{{ __('Accesos Rápidos') }}</span>
                             </div>
                             @if(auth()->user()->favoriteTeam)
                              <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}" class="flex items-center gap-3 px-4 py-3 text-sm text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 hover:text-amber-700 dark:hover:text-amber-300 transition-colors border-b border-gray-100 dark:border-gray-800">
                                  <svg class="h-5 w-5 text-amber-500 fill-amber-500/20" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" /></svg>
                                  <span class="font-bold">Escritorio Favorito</span>
                              </a>
                             @endif
                             <a href="{{ route('teams.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-violet-50 dark:hover:bg-violet-500/10 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                 <span class="font-bold">{{ __('navigation.my_teams') }}</span>
                             </a>
                             <a href="{{ route('global-surveys.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" /></svg>
                                 <span class="font-bold">Encuestas Globales</span>
                             </a>
                             <a href="{{ route('media.index') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-500/10 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" /></svg>
                                 <span class="font-bold">{{ __('tasks.disk_quota') }}</span>
                             </a>
                             <a href="{{ route('docs') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                 <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" /></svg>
                                 <span class="font-bold">Doc</span>
                             </a>
                             @can('admin')
                                 <div class="px-4 py-2 mt-1 text-[10px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50 dark:bg-gray-800/80 border-y border-gray-100 dark:border-gray-700">{{ __('Administración') }}</div>
                                 <a href="{{ route('settings.users') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.users') }}</span>
                                 </a>
                                 <a href="{{ route('settings.mail') }}" class="flex items-center gap-3 px-4 py-3 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                                     <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                     <span class="font-bold">{{ __('navigation.settings') }}</span>
                                 </a>
                             @endcan
                             
                             {{-- System Preferences for Tablet/Medium screens --}}
                             <div class="px-4 py-2 mt-1 text-[10px] font-black uppercase tracking-widest text-gray-400 bg-gray-50 dark:bg-gray-800/80 border-y border-gray-100 dark:border-gray-700">{{ __('Preferencias') }}</div>
                             <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 bg-white dark:bg-gray-900">
                                 @auth @include('layouts.partials.workday-timer') @endauth
                                 @include('layouts.partials.theme-toggle')
                                 @include('layouts.partials.layout-toggle')
                                 @include('layouts.partials.clean-mode-toggle')
                                 @include('layouts.partials.language-toggle')
                                 @include('layouts.partials.zoom-controls')
                             </div>
                             @endauth
                        </div>
                    </div>
                    <!-- Right Utilities & User Profile (Fixed) -->
                    <div class="flex items-center gap-1 sm:gap-3 shrink-0">

                    @endauth

                    <!-- Utility controls: hidden on mobile, shown on md+ (tablets and desktop) -->
                    <div class="hidden md:flex items-center gap-1 pl-2 ml-1 border-l border-gray-200 dark:border-gray-800">
                        @include('layouts.partials.system-tools')
                    </div>

                    <!-- Mobile: just notifications bell + hamburger -->
                    <div class="flex items-center sm:hidden gap-2 ml-auto">
                        @auth
                        <!-- Chat Notification: Mobile -->
                        <div class="relative inline-flex items-center sm:hidden" x-data="{ open: false }">
                             <button @click="open = !open" @click.outside="open = false"
                                     class="relative p-2 text-gray-400"
                                     title="{{ __('Chat Interno') }}">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                 </svg>
                                 <template x-if="$store.chatStore.totalCount > 0">
                                      <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-emerald-500 text-[9px] font-bold text-white flex items-center justify-center"
                                            x-text="$store.chatStore.totalCount > 9 ? '9+' : $store.chatStore.totalCount">
                                      </span>
                                 </template>
                             </button>
                             <!-- Dropdown -->
                             <div x-show="open" x-transition x-cloak style="display: none"
                                  class="absolute right-0 mt-12 top-0 w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl z-[100] overflow-hidden">
                                  <div class="max-h-64 overflow-y-auto custom-scrollbar">
                                      <template x-if="$store.chatStore.totalCount === 0">
                                          <div class="p-4 text-center text-gray-400 text-xs">Sin chats pendientes</div>
                                      </template>
                                      <template x-for="conv in $store.chatStore.unreadConversations" :key="conv.id">
                                          <button @click="open = false; $dispatch('open-chat', conv)" class="w-full p-3 flex items-center gap-3 hover:bg-gray-50 dark:hover:bg-gray-800 border-b last:border-0 text-left dark:border-gray-800">
                                              <img :src="conv.photo" class="w-8 h-8 rounded-lg object-cover">
                                              <div class="min-w-0 flex-1">
                                                  <h6 class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="conv.name"></h6>
                                                  <template x-if="conv.team">
                                                      <p class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider truncate" x-text="conv.team"></p>
                                                  </template>
                                                  <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate font-medium mt-0.5" x-text="conv.text"></p>
                                              </div>
                                          </button>
                                      </template>
                                  </div>
                             </div>
                        </div>

                        <a href="{{ route('notifications.index') }}" class="relative p-2 text-gray-400" x-data>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-0.5 right-0.5 h-4 w-4 rounded-full bg-red-500 text-[9px] font-bold text-white flex items-center justify-center"
                                      x-text="$store.notifications.count > 9 ? '9+' : $store.notifications.count"></span>
                            </template>
                        </a>
                        @endauth
                        <!-- Hamburger -->
                        <button @click="layout === 'vertical' ? (sidebarOpen = true) : window.dispatchEvent(new CustomEvent('mobile-menu-open'))"
                            class="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            aria-label="Menu">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>



                    @auth
                        <!-- Chat Notification Dropdown: Desktop -->
                        <div class="hidden sm:inline-flex relative items-center" x-data="{ open: false }">
                             <button @click="open = !open" @click.outside="open = false"
                                     class="relative p-2 text-gray-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors duration-150 rounded-xl hover:bg-emerald-50 dark:hover:bg-emerald-500/10"
                                     title="{{ __('Chat Interno') }}">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                     <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                                 </svg>
                                 <template x-if="$store.chatStore.totalCount > 0">
                                     <span class="absolute top-1 right-1 flex h-4 w-4">
                                         <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                         <span class="relative inline-flex rounded-full h-4 w-4 bg-emerald-500 text-[10px] text-white font-bold items-center justify-center"
                                               x-text="$store.chatStore.totalCount > 9 ? '9+' : $store.chatStore.totalCount">
                                         </span>
                                     </span>
                                 </template>
                             </button>
                             
                             <!-- Dropdown with unread -->
                             <div x-show="open" x-transition x-cloak style="display: none"
                                  class="absolute right-0 mt-12 top-0 w-72 bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-700 rounded-2xl shadow-2xl z-[100] overflow-hidden transform origin-top-right">
                                  <div class="p-3 border-b border-gray-50 dark:border-gray-800 bg-emerald-50/30 dark:bg-emerald-900/20">
                                      <p class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Chats sin leer</p>
                                  </div>
                                  <div class="max-h-64 overflow-y-auto custom-scrollbar">
                                      <template x-if="$store.chatStore.totalCount === 0">
                                          <div class="p-6 text-center text-gray-400 italic text-xs">¡Estás al día! 🎉</div>
                                      </template>
                                      <template x-for="conv in $store.chatStore.unreadConversations" :key="conv.id">
                                          <button @click="open = false; $dispatch('open-chat', conv)" class="w-full p-3 flex items-start gap-3 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors border-b border-gray-50 dark:border-gray-800 last:border-0 text-left">
                                              <img :src="conv.photo" class="w-9 h-9 rounded-xl object-cover shadow-sm border border-white dark:border-gray-700 shrink-0">
                                              <div class="min-w-0 flex-1">
                                                  <h6 class="text-xs font-bold text-gray-900 dark:text-white truncate" x-text="conv.name"></h6>
                                                  <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate font-medium mt-0.5" x-text="conv.text"></p>
                                              </div>
                                          </button>
                                      </template>
                                  </div>
                             </div>
                        </div>

                        <!-- Notifications Bell: hidden on mobile (in mobile block above) -->
                        <a href="{{ route('notifications.index') }}" 
                           class="hidden sm:inline-flex relative p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors duration-150 rounded-xl hover:bg-violet-50 dark:hover:bg-violet-500/10" 
                           title="{{ __('Notificaciones') }}"
                           x-data
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <template x-if="$store.notifications.count > 0">
                                <span class="absolute top-1 right-1 flex h-4 w-4">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500 text-[10px] text-white font-bold items-center justify-center"
                                          x-text="$store.notifications.count > 99 ? '99+' : $store.notifications.count">
                                    </span>
                                </span>
                            </template>
                        </a>

                        <!-- User menu: hidden on mobile -->
                        <div class="hidden sm:block relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.outside="open = false"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white transition-colors">
                                <img src="{{ auth()->user()->profile_photo_url }}" 
                                    alt="{{ auth()->user()->name }}"
                                    class="w-8 h-8 rounded-full object-cover shadow-sm border border-white dark:border-gray-800 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform"
                                    :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <div x-show="open" x-transition x-cloak style="display: none"
                                class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-visible z-[90]">
                                <div
                                    class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-transparent rounded-t-xl">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                                        {{ auth()->user()->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                        {{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    {{ __('navigation.profile') }}
                                </a>
                                <a href="{{ route('credits') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                                    </svg>
                                    {{ __('credits.title') }}
                                </a>
                                <a href="{{ route('media.index') }}"
                                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                                    </svg>
                                    {{ __('tasks.disk_quota') }}
                                </a>

                                <!-- Embedded Utilities for Mobile/Small tablets (Hidden when visible in header) -->
                                <div class="hidden sm:flex md:hidden flex-wrap items-center justify-center gap-2 px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50 sm:justify-start dark:bg-gray-800/50">
                                    @auth @include('layouts.partials.workday-timer') @endauth
                                    @include('layouts.partials.theme-toggle')
                                    @include('layouts.partials.layout-toggle')
                                    @include('layouts.partials.clean-mode-toggle')
                                    @include('layouts.partials.language-toggle')
                                </div>

                                <div class="border-t border-gray-100 dark:border-gray-700">
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors text-left font-medium rounded-b-xl">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                            </svg>
                                            {{ __('navigation.logout') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors px-3 py-1.5 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
                            {{ __('navigation.login') }}
                        </a>
                        <a href="{{ route('register') }}"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-4 py-1.5 rounded-lg font-medium transition-all shadow-lg hover:shadow-violet-500/30">
                            {{ __('navigation.register') }}
                        </a>
                    @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>

    {{-- ============================================================
         MOBILE SLIDE-IN DRAWER
         Full navigation panel triggered by hamburger button
         ============================================================ --}}
    @auth
    @php
        $drawerTeamId = null;
        if (request()->route('team')) {
            $drawerTeamId = is_object(request()->route('team'))
                ? request()->route('team')->id
                : request()->route('team');
        }
    @endphp
    {{-- Drawer controlled via custom window event 'mobile-menu-open' --}}
    <div id="mobile-drawer"
         x-data="{ open: false }"
         x-init="
            window.addEventListener('mobile-menu-open', () => open = true);
            window.addEventListener('mobile-menu-close', () => open = false);
         "
         class="sm:hidden">

        {{-- Backdrop --}}
        <div x-show="open"
             x-cloak
             style="display: none"
             x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="open = false"
             class="fixed inset-0 z-[999] bg-black/40 backdrop-blur-sm">
        </div>

        {{-- Drawer panel --}}
        <div x-show="open"
             style="display: none"
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 z-[9999] w-72 bg-white dark:bg-gray-900 shadow-2xl flex flex-col overflow-y-auto transform">
        

            {{-- Drawer header --}}
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
                <span class="font-bold text-gray-900 dark:text-white text-lg" style="font-family:'Space Grotesk',sans-serif">
                    sientia<span class="text-violet-600 dark:text-violet-400">MTX</span>
                </span>
                <button @click="open = false" class="p-2 rounded-lg text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- User info --}}
            <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center gap-3">
                <img src="{{ auth()->user()->profile_photo_url }}" 
                    alt="{{ auth()->user()->name }}"
                    class="w-10 h-10 rounded-full object-cover shadow border border-white dark:border-gray-700 shrink-0">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            {{-- Navigation links --}}
            <nav class="flex-1 px-3 py-4 space-y-1">

                {{-- Main --}}
                <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Principal</p>
                @if(auth()->user()->favoriteTeam)
                <a href="{{ route('teams.dashboard', auth()->user()->favoriteTeam) }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors bg-amber-50 dark:bg-amber-900/10 text-amber-700 dark:text-amber-400 hover:bg-amber-100 dark:hover:bg-amber-900/20 mb-2 border border-amber-100 dark:border-amber-900/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 fill-amber-500/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                    </svg>
                    Escritorio Favorito
                </a>
                @endif

                <a href="{{ route('teams.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('teams.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ __('navigation.my_teams') }}
                </a>
                <a href="{{ route('global-surveys.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('global-surveys.*') ? 'bg-indigo-50 dark:bg-indigo-500/10 text-indigo-700 dark:text-indigo-400 font-bold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                    </svg>
                    Encuestas Globales
                </a>
                <a href="{{ route('notifications.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('notifications.*') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Notificaciones
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="ml-auto bg-red-500 text-white text-[10px] font-bold rounded-full px-2 py-0.5">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </a>
                <a href="{{ route('media.index') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                          {{ request()->routeIs('media.index') ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                    </svg>
                    {{ __('tasks.disk_quota') }}
                </a>
                <a href="{{ route('docs') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18 18.247 18.477 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Documentación
                </a>

                {{-- Team views (if inside a team) --}}
                @if($drawerTeamId)
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Vistas del Equipo</p>
                    @php
                        $drawerViews = [
                            ['name' => 'Escritorio', 'route' => route('teams.time-reports', $drawerTeamId), 'active' => request()->routeIs('teams.time-reports')],
                            ['name' => __('forum.title') ?? 'Foro', 'route' => route('teams.forum.index', $drawerTeamId), 'active' => request()->routeIs('teams.forum.*')],
                            ['name' => 'Expedientes', 'route' => route('teams.expedientes.index', $drawerTeamId), 'active' => request()->routeIs('teams.expedientes.*')],
                            ['name' => __('navigation.task_list'), 'route' => route('teams.tasks.index', $drawerTeamId), 'active' => request()->routeIs('teams.tasks.*')],
                            ['name' => __('teams.eisenhower_matrix'), 'route' => route('teams.eisenhower', $drawerTeamId), 'active' => request()->routeIs('teams.eisenhower')],
                            ['name' => __('navigation.gantt'), 'route' => route('teams.gantt', $drawerTeamId), 'active' => request()->routeIs('teams.gantt')],
                            ['name' => __('navigation.kanban'), 'route' => route('teams.kanban', $drawerTeamId), 'active' => request()->routeIs('teams.kanban')],
                            ['name' => __('teams.view_members'), 'route' => route('teams.members', $drawerTeamId), 'active' => request()->routeIs('teams.members')],
                            ['name' => __('Encuestas'), 'route' => route('teams.surveys.index', $drawerTeamId), 'active' => request()->routeIs('teams.surveys.*')],
                        ];

                    @endphp
                    @foreach($drawerViews as $dv)
                        <a href="{{ $dv['route'] }}" @click="open = false"
                           class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-colors
                                  {{ $dv['active'] ? 'bg-violet-50 dark:bg-violet-500/10 text-violet-700 dark:text-violet-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                            {{ $dv['name'] }}
                        </a>
                    @endforeach
                </div>
                @endif

                @can('admin')
                <div class="pt-3">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2">Administración</p>
                    <a href="{{ route('settings.users') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.users') }}
                    </a>
                    <a href="{{ route('settings.mail') }}" @click="open = false"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                        {{ __('navigation.settings') }}
                    </a>
                </div>
                @endcan

                {{-- Mobile Utilities --}}
                <div class="pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                    <p class="px-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-3">Preferencias</p>
                    <div class="flex flex-wrap items-center gap-3 px-3">
                        @auth @include('layouts.partials.workday-timer') @endauth
                        @include('layouts.partials.theme-toggle')
                        @include('layouts.partials.layout-toggle')
                        @include('layouts.partials.clean-mode-toggle')
                        @include('layouts.partials/language-toggle')
                    </div>
                </div>
            </nav>

            {{-- Footer actions --}}
            <div class="px-3 py-4 border-t border-gray-100 dark:border-gray-800 space-y-1">
                <a href="{{ route('profile.edit') }}" @click="open = false"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Mi Perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        {{ __('navigation.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endauth

    <!-- Flash Messages -->
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)" x-cloak
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-emerald-50 dark:bg-emerald-900/90 border border-emerald-200 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
            <span class="text-sm">{{ session('success') }}</span>
            <button @click="show = false"
                class="ml-auto text-emerald-500 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-white transition-colors">✕</button>
        </div>
    @endif

    @if (session('warning'))
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)" x-cloak
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-amber-50 dark:bg-amber-900/90 border border-amber-200 dark:border-amber-700 text-amber-800 dark:text-amber-200 px-4 py-3 rounded-xl shadow-2xl flex items-center gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-amber-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="text-sm font-medium">{{ session('warning') }}</span>
            <button @click="show = false"
                class="ml-auto text-amber-500 dark:text-amber-400 hover:text-amber-700 dark:hover:text-white transition-colors">✕</button>
        </div>
    @endif

    @if (session('error') || $errors->any())
        <div x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 6000)" x-cloak
            class="fixed top-20 right-4 z-50 max-w-sm w-full bg-red-50 dark:bg-red-900/90 border border-red-200 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded-xl shadow-2xl flex items-start gap-3 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 mt-0.5 text-red-400" fill="none"
                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-sm">
                @if (session('error'))
                    {{ session('error') }}
                @endif
                @foreach ($errors->all() as $err)
                    <div>{{ $err }}</div>
                @endforeach
            </div>
            <button @click="show = false"
                class="ml-auto text-red-500 dark:text-red-400 hover:text-red-700 dark:hover:text-white shrink-0 transition-colors">✕</button>
        </div>
    @endif

    <div x-show="layout === 'vertical'"
        style="{{ $layout === 'horizontal' ? 'display:none' : '' }}"
        class="sticky top-0 z-20 w-full bg-white/80 dark:bg-gray-950/80 backdrop-blur-xl border-b border-gray-200 dark:border-gray-800 transition-all duration-300 {{ $layout === 'vertical' ? 'header-v-fix' : '' }}"
        :class="sidebarOpen ? 'lg:pl-72' : ''">
        <div class="w-full">
            <!-- Row 1: Global Navigation & System Tools -->
            <div class="flex items-center justify-between px-2 sm:px-6 lg:px-8 py-2 border-b border-gray-100 dark:border-gray-800/50">
                <div class="flex items-center shrink-0">
                    <!-- Toggle button -->
                    <button x-show="!sidebarOpen" @click="sidebarOpen = true"
                        class="p-2 rounded-lg text-gray-400 hover:text-violet-600 hover:bg-violet-50 dark:hover:bg-violet-500/10 transition-all shadow-sm"
                        title="{{ __('Open Sidebar') }}" x-cloak>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    @if(isset($team))
                        <span class="ml-2 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest hidden sm:block">{{ $team->name }}</span>
                    @endif
                </div>

                <!-- System Tools (Top Right) -->
                <div class="flex items-center gap-1.5 shrink-0">
                    @include('teams.partials.header-actions-extra', ['layout' => 'vertical'])
                </div>
            </div>

            <!-- Row 2: Page specific content (Slot) -->
            <div class="w-full px-2 sm:px-6 lg:px-8 py-2">
                <div class="min-w-0">
                    @if (isset($header))
                        {{ $header }}
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Page content -->
    <main id="mainContent" 
        class="px-3 sm:px-6 lg:px-8 py-8 pb-24 sm:pb-8 {{ $layout === 'vertical' ? 'lg-layout-v-fix' : '' }}"
        style="{{ $layout === 'vertical' ? 'padding-left: 18rem;' : '' }}"
        data-wide-content="{{ ($maxWidth === 'max-w-full' || $maxWidth === 'max-w-none') ? 'true' : 'false' }}"
        :class="[
            layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : '',
            'w-full max-w-none lg:{{ $maxWidth }} lg:mx-auto'
        ]">
        <script>
            if (window.innerWidth < 1024) {
                document.getElementById('mainContent').style.paddingLeft = '0';
            }
        </script>

        @if (isset($header) && $layout === 'horizontal')
            <div class="mb-6">
                {{ $header }}
            </div>
        @endif

        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="mt-auto border-t border-gray-200 dark:border-gray-800 py-4 {{ $layout === 'vertical' ? 'lg-layout-v-fix' : '' }}"
        style="{{ $layout === 'vertical' ? 'padding-left: 18rem;' : '' }}"
        :class="layout === 'vertical' ? (sidebarOpen ? 'lg:pl-72' : '') : ''">
        <script>
            if (window.innerWidth < 1024) {
                document.querySelector('footer').style.paddingLeft = '0';
            }
        </script>
        <div
            class="max-w-none lg:{{ $maxWidth }} lg:mx-auto px-2 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center text-xs text-gray-500 dark:text-gray-400 font-medium">
            <div class="mb-2 md:mb-0 flex items-center gap-2">
                <span class="font-bold">© {{ date('Y') }} <a href="https://www.sientia.com" class="hover:underline hover:text-violet-600 transition-colors">Sientia</a></span>
                <span class="mx-1">|</span>
                <span>v{{ config('app.version', '1.0.0') }}</span>
                <span class="mx-1">|</span>
                <a href="https://www.gnu.org/licenses/agpl-3.0.txt" target="_blank"
                    class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">Licencia AGPL v3</a>
            </div>
            <div class="flex items-center space-x-6">
                <!-- Open Source Links -->
                <div class="flex items-center gap-3 border-r border-gray-200 dark:border-gray-800 pr-4 mr-2">
                    <a href="https://github.com/pbenav" target="_blank" title="GitHub" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>
                    </a>
                    <a href="https://gitlab.com/pbenav" target="_blank" title="GitLab" class="hover:text-gray-900 dark:hover:text-white transition-colors">
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189c-.135-.417-.724-.417-.86 0L16.425 9.452h-8.85l-2.664-8.189c-.135-.417-.724-.417-.86 0L1.387 9.452.045 13.587c-.11.34.01.711.306.925l11.65 8.458 11.648-8.458c.296-.214.416-.585.306-.925z"/></svg>
                    </a>
                </div>
                
                <div class="flex items-center gap-4">
                    <a href="{{ route('privacy') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Privacidad') }}</a>
                    <a href="{{ route('terms') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Términos') }}</a>
                    <a href="{{ route('cookies') }}" class="hover:text-violet-600 dark:hover:text-violet-400 transition-colors">{{ __('Cookies') }}</a>
                </div>
                <span class="text-gray-300 dark:text-gray-700">|</span>
                <div class="flex items-center gap-5">
                    <a href="https://www.patreon.com/cw/sientia" target="_blank"
                        class="text-orange-600 hover:text-orange-700 font-bold transition-colors flex items-center gap-1.5 group">
                        <i class="fab fa-patreon group-hover:scale-110 transition-transform"></i>
                        Patreon
                    </a>
                    <span class="text-gray-300 dark:text-gray-700 mx-1">|</span>
                    <a href="https://buymeacoffee.com/sientia" target="_blank"
                        class="text-yellow-600 hover:text-yellow-700 font-bold transition-colors flex items-center gap-1.5 group">
                        <i class="fas fa-coffee group-hover:scale-110 transition-transform"></i>
                        Buy me a coffee
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Telegram Chat Experiment -->
    @auth
        @php
            $notifSettings = auth()->user()->notification_settings ?? auth()->user()->defaultNotificationSettings();
            
            $layoutTeam = $team ?? null;
            if (!$layoutTeam && request()->route('team')) {
                $routeTeam = request()->route('team');
                $layoutTeam = is_object($routeTeam) ? $routeTeam : \App\Models\Team::find($routeTeam);
            }

            $currTeam = request()->route('team') ?? $team ?? null;
            $currTeamId = $currTeam ? (is_object($currTeam) ? $currTeam->id : $currTeam) : null;
            
            $currTask = request()->route('task') ?? $task ?? null;
            $currTaskId = $currTask ? (is_object($currTask) ? $currTask->id : $currTask) : null;
            
            if (!$currTeamId && $currTask && is_object($currTask)) {
                $currTeamId = $currTask->team_id;
            }

            $currThread = request()->route('thread') ?? $thread ?? null;
            $currThreadId = $currThread ? (is_object($currThread) ? $currThread->id : $currThread) : null;
            
            if (!$currThreadId && $currTask && is_object($currTask) && $currTask->forumThread) {
                $currThreadId = $currTask->forumThread->id;
            }

            $currMessage = request()->route('message') ?? $message ?? null;
            $currMessageId = $currMessage ? (is_object($currMessage) ? $currMessage->id : $currMessage) : null;
        @endphp
        <div x-show="!cleanMode" x-transition class="contents">
            @if($notifSettings['telegram'] ?? false)
                @include('partials.telegram-widget')
            @endif
            @if(config('services.whatsapp.enabled', true) && (($notifSettings['whatsapp'] ?? false) || ($layoutTeam && ($layoutTeam->settings['has_whatsapp'] ?? false))))
                @include('partials.whatsapp-widget')
            @endif
            <x-ai-assistant :team-id="$currTeamId" :task-id="$currTaskId" :thread-id="$currThreadId" :message-id="$currMessageId" />
        </div>
    @endauth

    {{-- ============================================================
         MOBILE BOTTOM NAVIGATION BAR
         Visible only on small screens (< sm = 640px)
         ============================================================ --}}
    @auth
    @php
        $mobileTeamId = null;
        if (request()->route('team')) {
            $mobileTeamId = is_object(request()->route('team'))
                ? request()->route('team')->id
                : request()->route('team');
        }
    @endphp
    <nav class="fixed bottom-0 left-0 right-0 z-50 sm:hidden bg-white dark:bg-gray-950 border-t border-gray-200 dark:border-gray-800 shadow-2xl">
        <div class="flex items-stretch h-16">

            {{-- Dashboard / My Teams --}}
            <a href="{{ route('teams.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.index') || request()->routeIs('dashboard') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Inicio</span>
            </a>

            {{-- Tasks (only if inside a team) --}}
            @if($mobileTeamId)
            <a href="{{ route('teams.tasks.index', $mobileTeamId) }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.tasks.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Tareas</span>
            </a>
            @else
            <a href="{{ route('dashboard') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Dashboard</span>
            </a>
            @endif

            {{-- Notifications --}}
            <a href="{{ route('notifications.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 relative text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('notifications.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if(auth()->user()->unreadNotifications->count() > 0)
                        <span class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white">
                            {{ auth()->user()->unreadNotifications->count() > 9 ? '9+' : auth()->user()->unreadNotifications->count() }}
                        </span>
                    @endif
                </div>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Avisos</span>
            </a>

            {{-- Views (team switcher trigger) - only inside a team --}}
            @if($mobileTeamId)
            <a href="{{ route('teams.dashboard', $mobileTeamId) }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.dashboard') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Matriz</span>
            </a>
            @else
            <a href="{{ route('media.index') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors hover:text-gray-700 dark:hover:text-gray-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                </svg>
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Archivos</span>
            </a>
            @endif

            {{-- Profile / User --}}
            <a href="{{ route('profile.edit') }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('profile.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
                <img src="{{ auth()->user()->profile_photo_url }}" 
                    alt="{{ auth()->user()->name }}"
                    class="w-6 h-6 rounded-full object-cover shadow-sm border border-white dark:border-gray-800 shrink-0">
                <span class="text-[9px] font-bold uppercase tracking-tight leading-none">Perfil</span>
            </a>

        </div>
    </nav>
    @endauth

    <script>
        window.confirmDelete = function(formId, message) {
            Swal.fire({
                title: '{{ __('teams.danger_zone') }}',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '{{ __('teams.confirm_ok') }}',
                cancelButtonText: '{{ __('teams.confirm_cancel') }}',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(formId).submit();
                }
            });
        }
        
        // Alias for compatibility with other views
        window.handleGlobalDelete = window.confirmDelete;

        window.openGoogleAuth = function(teamId = null) {
            const width = 600;
            const height = 700;
            const left = (window.innerWidth - width) / 2;
            const top = (window.innerHeight - height) / 2;
            
            // Mark that we are starting a Google Auth process
            localStorage.setItem('google_auth_in_progress', '1');
            
            let url = "{{ route('google.auth') }}?popup=1";
            if (teamId) url += "&team_id=" + teamId;

            const popup = window.open(url, 'GoogleAuth', `width=${width},height=${height},top=${top},left=${left}`);

            const messageHandler = function(event) {
                if (event.data === 'google-auth-success') {
                    localStorage.removeItem('google_auth_in_progress');
                    window.removeEventListener('message', messageHandler);
                    location.reload();
                }
            };

            window.addEventListener('message', messageHandler);

            // Redundant fallback listener via LocalStorage
            window.addEventListener('storage', function(event) {
                if (event.key === 'google-auth-trigger') {
                    localStorage.removeItem('google_auth_in_progress');
                    location.reload();
                }
            });
        };

        // GHOST POPUP DETECTOR:
        // If this page loads in a window that has an opener AND we marked an auth in progress,
        // it means we are a popup that was redirected to the dashboard by mistake.
        (function() {
            if (window.opener && localStorage.getItem('google_auth_in_progress') === '1') {
                console.log("Ghost popup detected! Closing and notifying parent...");
                localStorage.removeItem('google_auth_in_progress');
                localStorage.setItem('google_auth_trigger', Date.now());
                if (window.opener.postMessage) {
                    window.opener.postMessage('google-auth-success', '*');
                }
                window.close();
            }
        })();

        @if (session('google_reauth_required'))
            document.addEventListener('DOMContentLoaded', function() {
                openGoogleAuth();
            });
        @endif
    </script>

    <!-- Global Zoom Logic -->
    <script>
        (function() {
            window.applyGlobalZoom = function(val) {
                const appRoot = document.getElementById('app-root');
                if (appRoot) {
                    const zoomPercent = (parseFloat(val) * 100);
                    appRoot.style.zoom = zoomPercent + '%';
                }
                // Avisar a los componentes de UI del cambio de zoom
                window.dispatchEvent(new CustomEvent('global-zoom-changed', { detail: val }));
            }

            window.adjustGlobalZoom = function(delta) {
                let currentZoom = parseFloat(localStorage.getItem('global_zoom') || '1.0');
                currentZoom = Math.round((currentZoom + delta) * 100) / 100;
                currentZoom = Math.max(0.5, Math.min(1.5, currentZoom));
                localStorage.setItem('global_zoom', currentZoom);
                window.applyGlobalZoom(currentZoom);
            }

            // Apply zoom on load
            document.addEventListener('DOMContentLoaded', function() {
                const savedZoom = localStorage.getItem('global_zoom') || '1.0';
                window.applyGlobalZoom(parseFloat(savedZoom));
            });
        })();
    </script>
    </div>



    <script>
        // Global handler for Markdown links to open in new tab
        document.addEventListener('click', function(e) {
            const link = e.target.closest('.markdown-content a');
            if (link && !link.hasAttribute('target')) {
                link.setAttribute('target', '_blank');
                link.setAttribute('rel', 'noopener noreferrer');
            }
        }, true);
    </script>
    @stack('modals')
    @stack('scripts')
    @if(($notifSettings['telegram'] ?? false) || (config('services.whatsapp.enabled', true) && ($notifSettings['whatsapp'] ?? false)))
    <!-- Lottie Web for animated stickers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js" defer></script>
    @endif
    
    @auth
        <x-task-quick-view-modal />
        <div x-show="!cleanMode" x-transition class="contents">
            <x-quick-notes />
        </div>
        
        <!-- Widget de Comunicación Premium en Vivo Global (Sientia Direct & Videollamadas) -->
        <div x-data="sientiaChat" @open-chat.window="openChat($event.detail)" @open-last-chat.window="openLastChat()">
        <!-- Backdrop blur overlay -->
        <div x-show="open" 
             @click="close()" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-black/40 backdrop-blur-md z-[9998]"
             style="display: none;">
        </div>

        <div @open-chat.window="openChat($event.detail)"
             class="fixed inset-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 z-[9999] w-full h-full md:w-[65%] md:h-[80%] md:max-w-5xl bg-white/95 dark:bg-gray-950/95 border border-gray-100 dark:border-gray-800 rounded-none md:rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] flex flex-col overflow-hidden backdrop-blur-xl transform transition-all duration-300"
             x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-12 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-12 scale-95"
        style="display: none;"
        >
            <!-- Header -->
            <div class="p-5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between bg-gray-50/50 dark:bg-gray-900/50 shrink-0">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 p-0.5 shadow-sm relative shrink-0">
                        <img :src="member.photo" :alt="member.name" class="w-full h-full rounded-[14px] object-cover border border-white dark:border-gray-800 shadow-inner">
                    </div>
                    <div class="min-w-0">
                        <div class="min-w-0">
                            <p class="text-xs font-black text-gray-900 dark:text-white uppercase truncate tracking-tight" x-text="member.name"></p>
                            <template x-if="member.team">
                                <p class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider truncate" x-text="member.team"></p>
                            </template>
                        </div>
                        <p class="text-[9px] text-emerald-500 font-bold truncate tracking-tight" x-text="member.status"></p>
                    </div>
                </div>
                
                <div class="flex items-center gap-1 shrink-0">
                    <!-- Video Call Button (Sientia) -->
                    <button @click="startSientiaCall()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 text-emerald-500 rounded-xl transition-colors" title="Iniciar Videollamada Sientia">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
                    </button>

                    <!-- Video Call Button (Google Meet) -->
                    <button @click="startGoogleMeet()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 text-sky-500 rounded-xl transition-colors" title="Crear Google Meet Rápido">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </button>
                    
                    <!-- Clear Chat Button -->
                    <button @click="clearChat()" class="p-2 hover:bg-rose-50 dark:hover:bg-rose-950/30 text-rose-500 rounded-xl transition-colors" title="Limpiar Conversación 🧹">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    
                    <!-- Close Button -->
                    <button @click="close()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Chat Area -->
            <div x-ref="chatContainer" class="flex-1 overflow-y-auto p-5 space-y-4 custom-scrollbar bg-gray-50/20 dark:bg-gray-900/10">
                <template x-for="msg in messages" :key="msg.id">
                    <div>
                        <!-- System message -->
                        <template x-if="msg.sender === 'system'">
                            <div class="flex justify-center my-2">
                                <span class="px-3 py-1 bg-gray-100 dark:bg-gray-800 text-[10px] font-black text-gray-400 uppercase rounded-lg" x-text="msg.text"></span>
                            </div>
                        </template>
                        
                        <!-- My message -->
                        <template x-if="msg.sender === 'me'">
                            <div class="flex justify-end group relative my-1">
                                <!-- Reply Button Me -->
                                <button @click="replyingTo = msg; $nextTick(() => $refs.chatInput.focus())" class="opacity-0 group-hover:opacity-100 mr-2 my-auto p-1.5 text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-all shrink-0 focus:opacity-100" title="Responder">
                                    <svg class="w-4 h-4 transform -scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                </button>

                                <div class="max-w-[75%] bg-emerald-600 text-white rounded-3xl rounded-tr-sm px-4 py-3 shadow-md relative">
                                    <!-- Quoted Context -->
                                    <template x-if="msg.parent_id">
                                        <div class="mb-2 px-2.5 py-1.5 bg-black/10 dark:bg-black/20 rounded-xl border-l-4 border-emerald-300 text-white/90 text-[10px] font-medium flex flex-col opacity-90 backdrop-blur-sm mb-3 border-b border-r border-emerald-700/20">
                                            <span class="font-black uppercase text-[8px] text-emerald-100 opacity-80" x-text="msg.parent_sender_name || 'Mensaje'"></span>
                                            <span class="truncate mt-0.5 italic" x-text="msg.parent_text"></span>
                                        </div>
                                    </template>
                                    <!-- Media Renderer -->
                                    <template x-if="msg.file_type === 'image'">
                                        <div class="mb-2 -mx-2 first:-mt-1">
                                            <img :src="msg.file_url" class="w-full max-h-64 object-contain rounded-2xl shadow-lg cursor-pointer" @click="window.open(msg.file_url, '_blank')" @load="$nextTick(() => scrollToBottom())">
                                        </div>
                                    </template>

                                    <!-- Local File -->
                                    <template x-if="msg.file_type === 'file' && msg.storage_provider === 'local'">
                                        <a :href="msg.file_url" target="_blank" class="mb-2 flex items-center gap-2 p-2 bg-emerald-700/30 hover:bg-emerald-700/50 border border-white/10 rounded-xl text-xs font-bold text-white truncate transition-all group">
                                            <div class="p-1.5 bg-white/20 rounded-lg group-hover:scale-110 transition-transform">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <span class="truncate" x-text="msg.file_name || 'Descargar Archivo'"></span>
                                        </a>
                                    </template>

                                    <!-- Google Drive File -->
                                    <template x-if="msg.storage_provider === 'google'">
                                        <a :href="msg.web_view_link" target="_blank" class="mb-2 flex items-center gap-2 p-2 bg-emerald-700/30 hover:bg-emerald-700/50 border border-white/10 rounded-xl text-xs font-bold text-white truncate transition-all group">
                                            <div class="p-1 bg-white rounded-lg shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 48 48">
                                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                    <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                    <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                                </svg>
                                            </div>
                                            <div class="truncate">
                                                <p class="truncate" x-text="msg.file_name || 'Google Drive'"></p>
                                                <p class="text-[8px] text-white/70">Google Drive</p>
                                            </div>
                                        </a>
                                    </template>

                                    <p class="text-xs font-semibold leading-relaxed whitespace-pre-wrap" x-show="msg.text" x-text="msg.text"></p>
                                    <template x-if="msg.call_room">
                                        <button @click="window.open(msg.call_room.startsWith('http') ? msg.call_room : 'https://meet.jit.si/' + msg.call_room, '_blank')" class="mt-2 block w-full py-2 bg-white/20 hover:bg-white/30 text-white font-black text-[9px] uppercase rounded-xl transition-all">
                                            <span x-text="msg.call_room.startsWith('http') ? '🌐 Abrir Google Meet' : '🎥 Unirse a la videoconferencia'"></span>
                                        </button>
                                    </template>
                                    <span class="block text-[8px] opacity-60 text-right mt-1" x-text="msg.time"></span>
                                </div>
                            </div>
                        </template>
                        
                        <!-- Their message -->
                        <template x-if="msg.sender === 'them'">
                            <div class="flex justify-start group relative my-1">
                                <div class="max-w-[75%] bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-100 border border-gray-100 dark:border-gray-800 rounded-3xl rounded-tl-sm px-4 py-3 shadow-sm relative">
                                    <template x-if="String(member.id).startsWith('group_')">
                                        <p class="text-[9px] font-black uppercase text-emerald-600 dark:text-emerald-400 mb-0.5 opacity-80" x-text="msg.sender_name"></p>
                                    </template>
                                    <!-- Quoted Context -->
                                    <template x-if="msg.parent_id">
                                        <div class="mb-2 px-2.5 py-1.5 bg-gray-50 dark:bg-gray-900 rounded-xl border-l-4 border-emerald-500 text-gray-600 dark:text-gray-300 text-[10px] font-medium flex flex-col opacity-90 border border-gray-100 dark:border-gray-800 shadow-inner mb-3">
                                            <span class="font-black uppercase text-[8px] text-emerald-600 dark:text-emerald-400" x-text="msg.parent_sender_name || 'Mensaje'"></span>
                                            <span class="truncate mt-0.5 font-bold italic" x-text="msg.parent_text"></span>
                                        </div>
                                    </template>
                                    <!-- Media Renderer -->
                                    <template x-if="msg.file_type === 'image'">
                                        <div class="mb-2 -mx-2 first:-mt-1">
                                            <img :src="msg.file_url" class="w-full max-h-64 object-contain rounded-2xl shadow-lg cursor-pointer" @click="window.open(msg.file_url, '_blank')" @load="$nextTick(() => scrollToBottom())">
                                        </div>
                                    </template>

                                    <!-- Local File -->
                                    <template x-if="msg.file_type === 'file' && msg.storage_provider === 'local'">
                                        <a :href="msg.file_url" target="_blank" class="mb-2 flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 hover:bg-gray-100 border border-gray-100 dark:border-gray-700 rounded-xl text-xs font-bold text-emerald-600 truncate transition-all group">
                                            <div class="p-1.5 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg group-hover:scale-110 transition-transform">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <span class="truncate" x-text="msg.file_name || 'Descargar Archivo'"></span>
                                        </a>
                                    </template>

                                    <!-- Google Drive -->
                                    <template x-if="msg.storage_provider === 'google'">
                                        <a :href="msg.web_view_link" target="_blank" class="mb-2 flex items-center gap-2 p-2 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-100 dark:border-blue-800 rounded-xl text-xs font-bold text-blue-600 dark:text-blue-400 truncate transition-all group">
                                            <div class="p-1 bg-white dark:bg-gray-800 rounded-lg border border-blue-100 dark:border-blue-900/50 shrink-0">
                                                <svg class="w-4 h-4" viewBox="0 0 48 48">
                                                    <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                                    <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                                    <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                                                </svg>
                                            </div>
                                            <div class="truncate">
                                                <p class="truncate" x-text="msg.file_name || 'Google Drive'"></p>
                                                <p class="text-[8px] text-gray-500 font-medium">Google Drive</p>
                                            </div>
                                        </a>
                                    </template>
                                    
                                    <p class="text-xs font-semibold leading-relaxed whitespace-pre-wrap" x-show="msg.text" x-text="msg.text"></p>
                                    <template x-if="msg.call_room">
                                        <button @click="window.open(msg.call_room.startsWith('http') ? msg.call_room : 'https://meet.jit.si/' + msg.call_room, '_blank')" class="mt-2 block w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-[9px] uppercase rounded-xl transition-all">
                                            <span x-text="msg.call_room.startsWith('http') ? '🌐 Unirse a Google Meet' : '🎥 Aceptar y Unirse'"></span>
                                        </button>
                                    </template>
                                    <span class="block text-[8px] text-gray-400 text-right mt-1" x-text="msg.time"></span>
                                </div>
                                <!-- Reply Button Them -->
                                <button @click="replyingTo = msg; $nextTick(() => $refs.chatInput.focus())" class="opacity-0 group-hover:opacity-100 ml-2 my-auto p-1.5 text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-all shrink-0 focus:opacity-100" title="Responder">
                                    <svg class="w-4 h-4 transform -scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                </button>
                            </div>
                        </template>
                    </div>
                </template>
                
                <!-- Typing Indicator -->
                <div x-show="isTyping" class="flex justify-start" style="display: none;">
                    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-800 rounded-3xl rounded-tl-sm px-4 py-3 shadow-sm flex items-center gap-1">
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="w-1.5 h-1.5 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
                    </div>
                </div>
            </div>
            
            <!-- Input Area -->
            <!-- Area de vista previa de adjuntos -->
            <!-- Area de vista previa de adjuntos -->
            <div x-show="previewUrl || pendingDriveFile" class="p-2 px-4 bg-gray-50 dark:bg-gray-900 border-t border-gray-100 dark:border-gray-800 flex items-center gap-3 animate-in slide-in-from-bottom-4" style="display:none;">
                <div class="relative w-16 h-16 rounded-xl overflow-hidden border border-white dark:border-gray-800 shadow-md bg-white dark:bg-gray-800 flex items-center justify-center shrink-0">
                    <!-- Local Image -->
                    <template x-if="pendingFile && pendingFile.type.startsWith('image/')">
                        <img :src="previewUrl" class="w-full h-full object-cover">
                    </template>
                    <!-- Local File Generic -->
                    <template x-if="pendingFile && !pendingFile.type.startsWith('image/')">
                        <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </template>
                    <!-- Drive File Icon -->
                    <template x-if="pendingDriveFile">
                        <svg class="w-8 h-8" viewBox="0 0 48 48">
                            <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                            <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                            <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                        </svg>
                    </template>
                    
                    <button @click="clearPendingAttachments()" class="absolute top-0 right-0 p-1 bg-red-500 text-white rounded-bl-lg shadow hover:bg-red-600 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-[10px] font-black text-gray-900 dark:text-white truncate" x-text="pendingDriveFile ? pendingDriveFile.name : (pendingFile ? pendingFile.name : '')"></p>
                    <p class="text-[9px] text-emerald-600 font-bold uppercase tracking-wider" x-text="pendingDriveFile ? 'Google Drive' : (pendingFile ? Math.round(pendingFile.size / 1024) + ' KB' : '')"></p>
                </div>
            </div>

            <!-- Input Area Enhanced -->
            <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-950 shrink-0" 
                 @drive-file-selected.window="if (open) { pendingDriveFile = $event.detail.file; pendingFile = null; previewUrl = null; $nextTick(() => $refs.chatInput.focus()); }">
                <div class="flex items-end gap-3">
                    
                    <!-- Tools Column: Grouped for max visibility & aesthetics -->
                    <div class="flex items-center gap-1 mb-1 px-1">
                        <div class="relative">
                            <button @click="showEmojis = !showEmojis" class="p-2 text-gray-700 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-all" title="Emoticonos">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </button>
                            
                            <!-- Emoji Box WITH ZERO PADDING REQ BY USER AND FIXED HEIGHT SCROLL -->
                            <div x-show="showEmojis" @click.away="showEmojis = false" class="absolute bottom-full left-0 mb-2 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 w-60 max-h-64 overflow-y-auto custom-scrollbar z-50 animate-in fade-in slide-in-from-bottom-2" style="display: none;">
                                <div class="grid grid-cols-8 gap-0 p-0 border-collapse">
                                    <template x-for="emoji in ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','😋','😎','😍','😘','🥰','😗','😙','😚','☺️','🙂','🤗','🤩','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','🥱','😴','😌','😛','😜','😝','🤤','😒','😓','😔','😕','🙃','🤑','😲','☹️','🙁','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','🥵','🥶','😳','🤪','😵','🥴','😠','😡','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🥳','🥺','🤠','🤡','🤥','🤫','🤭','🧐','🤓','😈','👿','👹','👺','💀','👻','👽','🤖','💩','😺','😸','😹','😻','😼','😽','🙀','😿','😾','🙈','🙉','🙊','💋','💌','💘','💝','💖','💗','💓','💞','💕','💟','❣️','💔','❤️','🧡','💛','💚','💙','💜','🤎','🖤','🤍','💯','💢','💥','💫','💦','💨','🕳️','💣','💬','🗨️','🗯️','💭','💤','👋','🤚','🖐️','✋','🖖','👌','🤏','✌️','🤞','🤟','🤘','🤙','👈','👉','👆','🖕','👇','☝️','👍','👎','✊','👊','🤛','🤜','👏','🙌','👐','🤲','🤝','🙏','✍️','💅','🤳','💪','🦾','🦵','🦿','🦶','👂','🦻','👃','🧠','🦷','🦴','👀','👁️','👅','👄']">
                                        <button @click="insertEmoji(emoji)" class="w-full h-8 flex items-center justify-center text-lg m-0 p-0 hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors border-0 bg-transparent" x-text="emoji"></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <input type="file" x-ref="fileInput" class="hidden" @change="handleFileSelect($event)">
                        <button @click="$refs.fileInput.click()" class="p-2 text-gray-700 hover:text-emerald-600 dark:text-gray-300 dark:hover:text-emerald-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-all" title="Adjuntar archivo local">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                        </button>

                        @if ($currentTeamContext)
                        <button @click="$dispatch('open-drive-picker', { mode: 'collect' })" class="p-2 text-gray-700 hover:text-blue-600 dark:text-gray-300 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-all relative group" title="Vincular Google Drive">
                            <svg class="w-5 h-5" viewBox="0 0 48 48">
                                <path fill="#FFC107" d="M17 6H11L2 22l3 5h6l9-16z"/>
                                <path fill="#2196F3" d="M37 42H11l-9-15 4-7h26l9 16z"/>
                                <path fill="#4CAF50" d="M15 6l9 16 9-16H15z"/>
                            </svg>
                        </button>
                        @endif
                    </div>

                    <!-- Textarea container -->
                    <div class="flex-1 min-h-[44px] relative bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl focus-within:ring-2 focus-within:ring-emerald-500/20 transition-all overflow-hidden flex flex-col">
                        <!-- Reply Preview Widget -->
                        <div x-show="replyingTo" class="bg-gray-200/40 dark:bg-gray-800/40 p-2.5 px-3 flex justify-between items-center border-b border-gray-200/50 dark:border-gray-700/50 backdrop-blur-md" style="display:none;">
                             <div class="flex-1 min-w-0 pl-2 border-l-4 border-emerald-500">
                                 <p class="text-[9px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wide" x-text="'Respondiendo a ' + (replyingTo?.sender === 'me' ? 'Tú' : member.name)"></p>
                                 <p class="text-[10px] text-gray-600 dark:text-gray-300 truncate font-bold mt-0.5" x-text="replyingTo?.text || (replyingTo?.file_name ? '📎 ' + replyingTo.file_name : '...')"></p>
                             </div>
                             <button @click="replyingTo = null" class="p-1.5 text-gray-400 hover:text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-lg transition-colors shrink-0"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                        <textarea x-ref="chatInput" 
                               x-model="message" 
                               @keydown.enter="if(!$event.shiftKey) { $event.preventDefault(); sendMessage(); }" 
                               @paste="handlePaste($event)"
                               rows="3"
                               placeholder="Escribe un mensaje... (Shift+Intro para línea nueva)" 
                               class="w-full bg-transparent border-0 px-4 py-3 text-xs font-bold text-gray-900 dark:text-white placeholder:text-gray-400 dark:placeholder:text-gray-600 focus:ring-0 resize-none custom-scrollbar"></textarea>
                    </div>
                    
                    <!-- Send Button -->
                    <button @click="sendMessage()" 
                            :disabled="isUploading || (!message.trim() && !pendingFile && !pendingDriveFile)"
                            class="p-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl shadow-lg shadow-emerald-500/25 transition-all active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed shrink-0 mb-1">
                        <template x-if="!isUploading">
                            <svg class="w-5 h-5 transform rotate-90" fill="currentColor" viewBox="0 0 24 24"><path d="M2 21l21-9L2 3v7l15 2-15 2z"/></svg>
                        </template>
                        <template x-if="isUploading">
                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        </template>
                    </button>
                </div>
            </div>

            <!-- Banner de Llamada Entrante Premium -->
            <div x-show="incomingCall"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-12 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-12 scale-95"
                 class="fixed bottom-6 right-6 z-[10000] w-[350px] bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-800 rounded-[2rem] shadow-2xl p-5 flex flex-col gap-4"
                 style="display: none;"
            >
                 <div class="flex items-center gap-3">
                     <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-400 to-teal-600 p-0.5 shadow-sm relative shrink-0">
                         <img :src="incomingCall ? incomingCall.sender_photo : ''" class="w-full h-full rounded-[14px] object-cover border border-white dark:border-gray-800 shadow-inner">
                     </div>
                     <div class="min-w-0">
                         <p class="text-xs font-black text-gray-900 dark:text-white uppercase truncate tracking-tight" x-text="incomingCall ? incomingCall.sender_name : ''"></p>
                         <p class="text-[10px] text-emerald-500 font-bold animate-pulse">Te invita a una videoconferencia... 🎥</p>
                     </div>
                 </div>
                 <div class="grid grid-cols-2 gap-3">
                     <button @click="rejectCall()" class="py-3 bg-gray-50 dark:bg-gray-900 hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-2xl font-black text-[10px] uppercase transition-all">
                         Rechazar ❌
                     </button>
                     <button @click="acceptCall()" class="py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-2xl font-black text-[10px] uppercase shadow-lg shadow-emerald-500/25 transition-all animate-pulse-subtle">
                         Aceptar ✅
                     </button>
                 </div>
            </div>
        </div>


        @if ($currentTeamContext)
            <x-google-drive-picker :team="$currentTeamContext" />
        @endif
    @endauth

    <!-- 🖨️ Sientia MTX Global Premium Print Utility 🖨️ -->
    <script>
        window.SientiaPrint = {
            async print(title, htmlContent, options = {}) {
                const isDark = document.documentElement.classList.contains('dark');
                
                const result = await Swal.fire({
                    title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Formato de Impresión</span>',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f3f4f6' : '#1f2937',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                    },
                    html: `
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                            ¿Deseas incluir las cabeceras corporativas y la marca de agua de MTX?
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
                            <button type="button" id="print-btn-with" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/50 dark:bg-indigo-950/30 hover:border-indigo-600 transition-all text-center group">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="font-black text-[10px] uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Con Cabeceras</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Estilo oficial MTX</div>
                            </button>
                            <button type="button" id="print-btn-without" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-slate-900 hover:border-gray-600 transition-all text-center group">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:scale-110 transition-transform shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </div>
                                <div class="font-black text-[10px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Sin Cabeceras</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Contenido limpio</div>
                            </button>
                        </div>
                    `,
                    didOpen: (el) => {
                        el.querySelector('#print-btn-with').onclick = () => Swal.close({ value: 'with' });
                        el.querySelector('#print-btn-without').onclick = () => Swal.close({ value: 'without' });
                    }
                });

                if (!result || !result.value) return;
                const withHeaders = result.value === 'with';

                const printWin = window.open('', '_blank', 'width=850,height=900');
                const brandLabel = options.brand || 'Sientia MTX';
                const headerHtml = withHeaders ? `
                    <div class="print-header">
                        <div class="title-container">
                            <span class="brand">${brandLabel}</span>
                            <h1 class="title">${title}</h1>
                            <div class="meta">Generado el ${new Date().toLocaleDateString()} a las ${new Date().toLocaleTimeString()}</div>
                        </div>
                    </div>
                ` : `<h1 style="font-size: 22px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 30px; letter-spacing: -0.02em;">${title}</h1>`;

                const watermarkHtml = withHeaders ? `<div class="logo-watermark">Sientia.</div>` : '';

                printWin.document.write(`
                    <!DOCTYPE html>
                    <html>
                        <head>
                            <title>${title}</title>
                            <meta charset="utf-8">
                            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
                            <style>
                                body { 
                                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; 
                                    padding: 40px 60px; 
                                    color: #1e293b; 
                                    line-height: 1.6;
                                    background-color: #fff;
                                    -webkit-print-color-adjust: exact;
                                    print-color-adjust: exact;
                                }
                                .print-header { 
                                    border-bottom: 4px solid #4f46e5; 
                                    margin-bottom: 40px; 
                                    padding-bottom: 20px; 
                                }
                                .brand { 
                                    font-weight: 900; 
                                    font-size: 10px; 
                                    text-transform: uppercase; 
                                    letter-spacing: 0.3em; 
                                    color: #6366f1; 
                                    margin-bottom: 8px;
                                    display: block;
                                }
                                .title { 
                                    font-size: 26px; 
                                    font-weight: 900; 
                                    color: #0f172a; 
                                    margin: 0; 
                                    line-height: 1.2;
                                    letter-spacing: -0.02em;
                                }
                                .meta { 
                                    font-size: 10px; 
                                    color: #94a3b8; 
                                    font-weight: 700; 
                                    text-transform: uppercase;
                                    margin-top: 10px;
                                }
                                .content { 
                                    font-size: 14px; 
                                    color: #334155;
                                    word-wrap: break-word;
                                }
                                .content h1 { font-size: 18px; font-weight: 800; margin-top: 24px; color: #0f172a; margin-bottom: 12px; }
                                .content h2 { font-size: 16px; font-weight: 700; margin-top: 20px; color: #1e293b; margin-bottom: 10px; }
                                .content h3 { font-size: 14px; font-weight: 700; margin-top: 16px; color: #334155; }
                                .content p { margin-bottom: 12px; }
                                .content ul, .content ol { padding-left: 20px; margin-bottom: 15px; }
                                .content li { margin-bottom: 4px; }
                                .content img { max-width: 100%; height: auto; border-radius: 8px; margin: 10px 0; }
                                .content blockquote { border-left: 4px solid #e2e8f0; padding-left: 16px; color: #64748b; font-style: italic; margin: 15px 0; }
                                .content code { font-family: Consolas, Monaco, monospace; background-color: #f1f5f9; padding: 2px 4px; border-radius: 4px; font-size: 12px; }
                                .content pre { background-color: #f8fafc; padding: 12px; border-radius: 8px; overflow-x: auto; border: 1px solid #e2e8f0; margin: 15px 0; font-size: 12px; }
                                .content table { border-collapse: collapse; width: 100%; margin: 15px 0; }
                                .content th, .content td { border: 1px solid #e2e8f0; padding: 8px 10px; text-align: left; font-size: 12px; }
                                .content th { background-color: #f8fafc; font-weight: 700; color: #1e293b; }
                                .logo-watermark {
                                    position: fixed;
                                    bottom: 30px;
                                    right: 30px;
                                    opacity: 0.06;
                                    font-weight: 900;
                                    font-size: 20px;
                                    letter-spacing: -0.05em;
                                    color: #4f46e5;
                                    pointer-events: none;
                                }
                                @media print {
                                    body { padding: 0; color: #000; }
                                    .print-header { border-color: #000; }
                                    .brand { color: #000; }
                                    .content a { text-decoration: none; color: #000; }
                                }
                            </style>
                        </head>
                        <body>
                            ${headerHtml}
                            <div class="content">${htmlContent}</div>
                            ${watermarkHtml}
                            <script>
                                window.onload = function() {
                                    window.print();
                                    setTimeout(function() { window.close(); }, 500);
                                };
                            <\/script>
                        </body>
                    </html>
                `);
                printWin.document.close();
            },

            async printPage() {
                const isDark = document.documentElement.classList.contains('dark');
                
                const result = await Swal.fire({
                    title: '<span class="text-xs font-black uppercase tracking-widest text-indigo-600">Imprimir Página</span>',
                    background: isDark ? '#0f172a' : '#ffffff',
                    color: isDark ? '#f3f4f6' : '#1f2937',
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        popup: 'rounded-[2.5rem] shadow-2xl border border-gray-200 dark:border-gray-800 p-6',
                    },
                    html: `
                        <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-6 text-center px-4">
                            ¿Deseas imprimir la página actual manteniendo la cabecera y navegación corporativa?
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-2">
                            <button type="button" id="print-page-btn-with" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-indigo-100 dark:border-indigo-950 bg-indigo-50/50 dark:bg-indigo-950/30 hover:border-indigo-600 transition-all text-center group">
                                <div class="w-12 h-12 rounded-2xl bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 group-hover:scale-110 transition-transform shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div class="font-black text-[10px] uppercase tracking-widest text-indigo-700 dark:text-indigo-300">Con Cabeceras</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Mantener marca</div>
                            </button>
                            <button type="button" id="print-page-btn-without" class="flex flex-col items-center gap-3 p-5 rounded-[2rem] border-2 border-gray-100 dark:border-gray-800 bg-white dark:bg-slate-900 hover:border-gray-600 transition-all text-center group">
                                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-gray-600 dark:text-gray-400 group-hover:scale-110 transition-transform shadow-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"/></svg>
                                </div>
                                <div class="font-black text-[10px] uppercase tracking-widest text-gray-700 dark:text-gray-300">Sin Cabeceras</div>
                                <div class="text-[9px] font-bold text-gray-400 uppercase tracking-tight">Ocultar menús</div>
                            </button>
                        </div>
                    `,
                    didOpen: (el) => {
                        el.querySelector('#print-page-btn-with').onclick = () => Swal.close({ value: 'with' });
                        el.querySelector('#print-page-btn-without').onclick = () => Swal.close({ value: 'without' });
                    }
                });

                if (!result || !result.value) return;
                const withHeaders = result.value === 'with';

                if (!withHeaders) {
                    document.body.classList.add('print-clean-mode');
                }
                
                setTimeout(() => {
                    window.print();
                    setTimeout(() => {
                        document.body.classList.remove('print-clean-mode');
                    }, 1000);
                }, 200);
            }
        };
    </script>

    <!-- 💫 Sientia Premium UX: Intelligent Scroll & State Preserver 💫 -->
    <script>
        (function() {
            // Clave única por ruta para evitar conflictos de scroll entre distintas páginas
            const scrollKey = "sientia_scroll_pos_" + window.location.pathname;

            // 1. RESTAURACIÓN INSTANTÁNEA
            document.addEventListener("DOMContentLoaded", function() {
                const savedScroll = sessionStorage.getItem(scrollKey);
                if (savedScroll !== null) {
                    // Un micro-retardo de 30ms garantiza que el layout de Tailwind/Alpine ya se haya estabilizado
                    setTimeout(function() {
                        window.scrollTo({
                            top: parseInt(savedScroll, 10),
                            behavior: 'instant' // Evita la animación de scroll fluido al recargar para dar sensación de inmediatez
                        });
                        // Una vez restaurado, limpiamos la sesión para no forzar el scroll en visitas posteriores no deseadas
                        sessionStorage.removeItem(scrollKey);
                    }, 30);
                }
            });

            // 2. CAPTURA AL ABANDONAR LA VISTA (Refresco, Enlaces de acción, etc.)
            window.addEventListener("beforeunload", function() {
                sessionStorage.setItem(scrollKey, window.scrollY);
            });

            // 3. BLINDAJE EXTRA PARA FORMULARIOS
            // Salvaguarda ante submits que bloquean temporalmente antes de iniciar la recarga
            document.addEventListener("submit", function() {
                sessionStorage.setItem(scrollKey, window.scrollY);
            });
        })();
    </script>

    <!-- 🔗 Sientia Global Link Security & Navigation Flow 🔗 -->
    <script>
        (function() {
            /**
             * Processes all links within markdown-rendered containers to ensure external links
             * open in a new tab, preserving the application's SPA-like navigation flow.
             */
            const processMarkdownLinks = (container) => {
                if (!container || typeof container.querySelectorAll !== 'function') return;
                
                const markdownContainers = container.querySelectorAll('.prose, .markdown-content');
                
                markdownContainers.forEach(mc => {
                    const links = mc.querySelectorAll('a');
                    links.forEach(link => {
                        // Check if it's an external link
                        const href = link.getAttribute('href');
                        if (!href) return;

                        const isExternal = (href.startsWith('http') || href.startsWith('//')) && 
                                         !href.includes(window.location.hostname) && 
                                         !link.hasAttribute('target');

                        if (isExternal) {
                            link.setAttribute('target', '_blank');
                            link.setAttribute('rel', 'noopener noreferrer');
                        }
                    });
                });
            };

            // 1. Initial process on load
            document.addEventListener("DOMContentLoaded", () => {
                processMarkdownLinks(document);
                
                // 2. Observer for dynamic content (AI Assistant, Quick Notes, Livewire)
                const observer = new MutationObserver(mutations => {
                    mutations.forEach(mutation => {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === 1) { // Element node
                                if (node.matches('.prose, .markdown-content') || node.querySelector('.prose, .markdown-content')) {
                                    processMarkdownLinks(node);
                                }
                            }
                        });
                    });
                });

                observer.observe(document.body, { childList: true, subtree: true });
            });
            
            // 3. Hook into specific app events that might re-render markdown
            window.addEventListener('quicknote-state-changed', () => {
                setTimeout(() => processMarkdownLinks(document), 150);
            });
        })();
    </script>
</body>

</html>
