@php
    $user = auth()->user();
    $activeTaskLog = $user ? $user->activeTaskLog() : null;
    $activeActivity = $activeTaskLog ? ($activeTaskLog->activity ?? $activeTaskLog->task) : null;
@endphp

@if($activeTaskLog && $activeActivity)
    @php
        $startTimestamp = $activeTaskLog->start_at ? $activeTaskLog->start_at->timestamp : now()->timestamp;
        $activeTeamId = $activeActivity->team_id ?? (isset($team) ? $team->id : null);
    @endphp

    <div x-data="{
        taskId: {{ $activeActivity->id }},
        loading: false,
        visible: true,
        startTime: {{ $startTimestamp }},
        seconds: Math.max(0, Math.floor(Date.now() / 1000) - {{ $startTimestamp }}),
        formatTime(sec) {
            const h = String(Math.floor(sec / 3600)).padStart(2, '0');
            const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
            const s = String(sec % 60).padStart(2, '0');
            return `${h}:${m}:${s}`;
        },
        stopTimer() {
            if (this.loading) return;
            this.loading = true;
            fetch('{{ route('time-logs.toggle-task', $activeActivity->id) }}', {
                method: 'POST',
                headers: { 
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (window.Alpine && Alpine.store('timer')) {
                    Alpine.store('timer').stop();
                }
                window.dispatchEvent(new CustomEvent('task-stopped', { detail: { taskId: this.taskId } }));
                window.location.reload();
            })
            .catch(err => {
                console.error('Error stopping timer:', err);
                this.loading = false;
            });
        }
    }" 
    x-show="visible"
    x-init="setInterval(() => { seconds = Math.max(0, Math.floor(Date.now() / 1000) - startTime); }, 1000)"
    class="bg-gradient-to-r from-violet-700 via-purple-700 to-indigo-700 dark:from-violet-950 dark:via-purple-900 dark:to-indigo-950 rounded-2xl p-4 text-white shadow-xl flex items-center justify-between gap-4 flex-wrap border border-violet-400/30 mb-6 transition-all">
        
        <div class="flex items-center gap-3.5 min-w-0">
            <div class="relative flex shrink-0">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <div class="relative inline-flex rounded-full h-10 w-10 bg-emerald-500 items-center justify-center text-white font-black shadow-md">
                    <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-black uppercase tracking-widest bg-emerald-400/20 text-emerald-300 border border-emerald-400/40 px-2.5 py-0.5 rounded-md flex items-center gap-1.5 shadow-xs">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                        Contador de Cita / Actividad Activo
                    </span>
                    <span class="text-xs font-mono font-black bg-black/40 text-emerald-300 px-2.5 py-0.5 rounded-md border border-white/10 tracking-wider tabular-nums" x-text="formatTime(seconds)">
                        00:00:00
                    </span>
                </div>
                <h4 class="text-sm font-bold truncate mt-1 text-white flex items-center gap-2">
                    <span class="truncate">{{ $activeActivity->name ?? $activeActivity->title }}</span>
                    @if(isset($activeActivity->type))
                        <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded bg-white/15 text-violet-100 shrink-0">
                            {{ $activeActivity->type }}
                        </span>
                    @endif
                </h4>
            </div>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            @if($activeTeamId)
                <a href="{{ route('teams.activities.show', [$activeTeamId, $activeActivity->id]) }}"
                   class="text-xs font-bold bg-white/10 hover:bg-white/20 text-white px-3.5 py-2 rounded-xl transition-all flex items-center gap-1.5 active:scale-95 border border-white/15">
                    <span>Ver Actividad</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
                </a>
            @endif
            
            <button type="button" @click="stopTimer()" :disabled="loading" class="text-xs font-black bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow-lg active:scale-95 border border-rose-400/40 cursor-pointer disabled:opacity-50">
                <svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M6 6h12v12H6z"/></svg>
                <span x-text="loading ? 'Deteniendo...' : 'Parar Contador'">Parar Contador</span>
            </button>
        </div>
    </div>
@endif
