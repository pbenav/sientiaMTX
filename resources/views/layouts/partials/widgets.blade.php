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
        <div x-show="!cleanMode" x-cloak x-transition class="contents"
             x-init="$el.removeAttribute('style')">
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
            <a href="{{ route('teams.activities.index', $mobileTeamId) }}"
               class="flex flex-col items-center justify-center flex-1 gap-1 text-gray-400 dark:text-gray-500 transition-colors
                      {{ request()->routeIs('teams.activities.*') ? 'text-violet-600 dark:text-violet-400' : 'hover:text-gray-700 dark:hover:text-gray-300' }}">
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
    <x-image-editor />
    @stack('modals')
    @stack('scripts')
    @if(($notifSettings['telegram'] ?? false) || (config('services.whatsapp.enabled', true) && ($notifSettings['whatsapp'] ?? false)))
    <!-- Lottie Web for animated stickers -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js" defer></script>
    @endif

    @auth
        <x-task-quick-view-modal />
        <div x-show="!cleanMode" x-cloak x-transition class="contents"
             x-init="$el.removeAttribute('style')">
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
             class="fixed inset-0 md:inset-auto md:top-1/2 md:left-1/2 md:-translate-x-1/2 md:-translate-y-1/2 z-[9999] w-full h-full md:w-[65%] md:h-[80%] md:max-w-5xl bg-white dark:bg-gray-950 border border-gray-100 dark:border-gray-800 rounded-none md:rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.15)] flex flex-col overflow-hidden transform transition-all duration-300"
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
                    <div class="min-w-0 flex-1">
                        <div class="min-w-0" x-data="{ editingName: false, newName: '' }">
                            <div class="flex items-center gap-1.5 min-w-0">
                                <!-- Modo Visualización -->
                                <template x-if="!editingName">
                                    <div class="flex items-center gap-1.5 min-w-0">
                                        <p class="text-xs font-black text-gray-900 dark:text-white uppercase truncate tracking-tight" x-text="member.name"></p>
                                        <template x-if="member.is_group">
                                            <button @click="editingName = true; newName = member.name;" class="text-gray-400 hover:text-emerald-500 transition-colors shrink-0" title="Editar nombre del grupo">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Modo Edición -->
                                <template x-if="editingName">
                                    <div class="flex items-center gap-1 min-w-0 w-full">
                                        <input type="text" x-model="newName" @keydown.enter="renameActiveGroup(newName); editingName = false;" @keydown.escape="editingName = false" class="bg-white dark:bg-gray-800 border border-emerald-500 rounded-lg text-[10px] px-2 py-0.5 font-bold uppercase tracking-tight focus:ring-1 focus:ring-emerald-500 focus:outline-none w-48 truncate" x-ref="editGroupNameInput" x-init="$nextTick(() => $refs.editGroupNameInput.focus())">
                                        <button @click="renameActiveGroup(newName); editingName = false;" class="text-emerald-500 hover:text-emerald-600 transition-colors shrink-0" title="Guardar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        </button>
                                        <button @click="editingName = false" class="text-gray-400 hover:text-rose-500 transition-colors shrink-0" title="Cancelar">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                </template>
                            </div>
                            <template x-if="member.team">
                                <p class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold uppercase tracking-wider truncate" x-text="member.team"></p>
                            </template>
                        </div>
                        <p class="text-[9px] text-emerald-500 font-bold truncate tracking-tight" :title="member.status" x-text="member.status"></p>
                    </div>
                </div>

                <div class="flex items-center gap-1 shrink-0">
                    <!-- Chats Grupales Recientes -->
                    <div class="relative" @click.away="showingRecentGroups = false">
                        <button @click="showingRecentGroups = !showingRecentGroups; if(showingRecentGroups) fetchRecentGroups();" class="p-2 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 text-emerald-500 rounded-xl transition-colors" title="Chats Grupales Recientes 👥">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </button>

                        <!-- Dropdown de grupos recientes -->
                        <div x-show="showingRecentGroups" x-transition class="absolute right-0 top-full mt-2 w-72 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[100] flex flex-col" style="display: none;">
                            <div class="p-3 border-b border-gray-100 dark:border-gray-700 shrink-0 bg-gray-50/50 dark:bg-gray-900/50">
                                <p class="text-[10px] font-black uppercase text-gray-400 dark:text-gray-500 tracking-wider">Historial de Grupos</p>
                            </div>
                            <div class="max-h-64 overflow-y-auto custom-scrollbar p-1 flex-1">
                                <template x-for="g in recentGroups" :key="g.id">
                                    <div class="w-full flex items-center justify-between p-1 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 rounded-xl transition-all group/item">
                                        <button @click="openChat(g); showingRecentGroups = false;" class="flex-1 flex items-center gap-3 p-1.5 text-left min-w-0">
                                            <img :src="g.photo" class="w-8 h-8 rounded-xl object-cover shadow-sm group-hover:shadow-emerald-200 dark:group-hover:shadow-none transition-shadow shrink-0">
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200 truncate" x-text="g.name"></p>
                                                <p class="text-[9px] text-gray-500 dark:text-gray-400 truncate mt-0.5">
                                                    <span class="font-bold text-emerald-600 dark:text-emerald-400" x-text="g.last_message ? g.last_message.sender_name + ': ' : ''"></span>
                                                    <span x-text="g.last_message ? g.last_message.text : 'Sin mensajes'"></span>
                                                </p>
                                            </div>
                                            <div class="shrink-0 flex flex-col items-end gap-1">
                                                <span class="text-[8px] font-medium text-gray-400" x-text="g.last_message ? g.last_message.time : ''"></span>
                                                <span class="text-[8px] font-black text-emerald-600 bg-emerald-50 dark:bg-emerald-950/30 px-1 py-0.5 rounded" x-text="g.status"></span>
                                            </div>
                                        </button>
                                        <button @click.stop="deleteGroupChat(g.id)" class="mr-1 p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-950/30 rounded-xl transition-all opacity-0 group-hover/item:opacity-100 focus:opacity-100 shrink-0" title="Eliminar Chat Grupal 🗑️">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </template>
                                <div x-show="recentGroups.length === 0" class="p-4 text-center text-xs text-gray-400 font-medium">
                                    No tienes grupos recientes
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Members to Chat -->
                    <div class="relative" @click.away="addingMember = false">
                        <button @click="addingMember = !addingMember; if(addingMember) fetchUsersForChat();" class="p-2 hover:bg-violet-50 dark:hover:bg-violet-900/30 text-violet-500 rounded-xl transition-colors" title="Añadir miembro al chat">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </button>

                        <!-- Dropdown -->
                        <div x-show="addingMember" x-transition class="absolute right-0 top-full mt-2 w-64 bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-700 overflow-hidden z-[100] flex flex-col" style="display: none;">
                            <div class="p-2 border-b border-gray-100 dark:border-gray-700 shrink-0 bg-gray-50/50 dark:bg-gray-900/50">
                                <input type="text" x-model="searchUserQuery" placeholder="Buscar miembro..." class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs px-3 py-2 focus:ring-2 focus:ring-violet-500 focus:border-transparent transition-shadow">
                            </div>
                            <div class="max-h-48 overflow-y-auto custom-scrollbar flex-1 p-1">
                                <template x-for="u in getFilteredUsersForAdd()" :key="u.id">
                                    <button @click="addMemberToGroup(u.id)" class="w-full flex items-center gap-2 p-2 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-xl transition-colors text-left group">
                                        <img :src="u.photo" class="w-7 h-7 rounded-lg object-cover shadow-sm group-hover:shadow-violet-200 dark:group-hover:shadow-none transition-shadow">
                                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300 truncate" x-text="u.name"></span>
                                    </button>
                                </template>
                                <div x-show="getFilteredUsersForAdd().length === 0" class="p-4 text-center text-xs text-gray-400 font-medium">
                                    No hay usuarios
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Video Call Button (Google Meet) -->
                    <button @click="startGoogleMeet()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-xl transition-colors" title="Crear Google Meet Rápido">
                        <svg class="w-5 h-5" viewBox="0 0 512 512" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <g transform="translate(0, 45.4)">
                                <path d="m289.6 256 49.9 57 67.1 42.9 11.7-99.6-11.7-97.3-68.4 37.7z" fill="#00832d"/>
                                <path d="M0 346.7v84.8c0 19.4 15.7 35.1 35.1 35.1h84.8l17.6-64.1-17.6-55.8-58.2-17.6z" fill="#0066da"/>
                                <path d="M119.9 45.4 0 165.3l61.7 17.6 58.2-17.6 17.3-55.1z" fill="#e94235"/>
                                <path d="M119.9 165.3H0v181.4h119.9z" fill="#2684fc"/>
                                <path d="M483.3 96.2 406.6 159v196.9l77 63.1c11.5 9 28.4.8 28.4-13.9V109.7c0-14.8-17.2-22.9-28.7-13.5M289.6 256v90.7H119.9v119.9h251.6c19.4 0 35.1-15.7 35.1-35.1v-75.6z" fill="#00ac47"/>
                                <path d="M371.5 45.4H119.9v119.9h169.7V256l117-96.9V80.5c0-19.4-15.7-35.1-35.1-35.1" fill="#ffba00"/>
                            </g>
                        </svg>
                    </button>

                    <!-- Video Call Button (Jitsi) -->
                    <button @click="startSientiaCall()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 text-emerald-500 rounded-xl transition-colors" title="Iniciar Videollamada Sientia">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5l4.72-4.72a.75.75 0 011.28.53v11.38a.75.75 0 01-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 002.25-2.25v-9a2.25 2.25 0 00-2.25-2.25h-9A2.25 2.25 0 002.25 7.5v9a2.25 2.25 0 002.25 2.25z"/></svg>
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
                                <div class="opacity-0 group-hover:opacity-100 mr-2 my-auto flex items-center gap-1 transition-all">
                                    <!-- Delete Button Me -->
                                    <button @click="deleteMessage(msg.id)" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-all shrink-0 focus:opacity-100" title="Eliminar mensaje">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                    <!-- Reply Button Me -->
                                    <button @click="replyingTo = msg; $nextTick(() => $refs.chatInput.focus())" class="p-1.5 text-gray-400 hover:text-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 rounded-lg transition-all shrink-0 focus:opacity-100" title="Responder">
                                        <svg class="w-4 h-4 transform -scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" /></svg>
                                    </button>
                                </div>

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
                        el.querySelector('#print-btn-with').onclick = () => { window._sientiaPrintMode = 'with'; Swal.close(); };
                        el.querySelector('#print-btn-without').onclick = () => { window._sientiaPrintMode = 'without'; Swal.close(); };
                    }
                });

                if (!window._sientiaPrintMode) return;
                const withHeaders = window._sientiaPrintMode === 'with';
                window._sientiaPrintMode = null;

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
                            <script src="https://cdn.tailwindcss.com"><\/script>
                            <script src="https://cdn.tailwindcss.com?plugins=typography"><\/script>
                            <script>
                                tailwind.config = {
                                    theme: {
                                        extend: {
                                            fontFamily: { sans: ['Inter', 'sans-serif'] },
                                        }
                                    }
                                }
                            <\/script>
                            <style>
                                body {
                                    font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                                    padding: 40px 60px;
                                    color: #1e293b;
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
                                    .prose a { text-decoration: none; color: #000; }
                                }
                            </style>
                        </head>
                        <body>
                            ${headerHtml}
                            <div class="content prose prose-sm max-w-none break-words leading-relaxed">${htmlContent}</div>
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
                        el.querySelector('#print-page-btn-with').onclick = () => { window._sientiaPrintPageMode = 'with'; Swal.close(); };
                        el.querySelector('#print-page-btn-without').onclick = () => { window._sientiaPrintPageMode = 'without'; Swal.close(); };
                    }
                });

                if (!window._sientiaPrintPageMode) return;
                const withHeaders = window._sientiaPrintPageMode === 'with';
                window._sientiaPrintPageMode = null;

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

