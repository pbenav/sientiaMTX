@php
    $isVisibleView = request()->routeIs('teams.dashboard') || 
                   request()->routeIs('teams.tasks.index') || 
                   request()->routeIs('teams.gantt') || 
                   request()->routeIs('teams.kanban');
    $showSubtasks = session('show_all_subtasks', false);
@endphp

@if($isVisibleView)
    <button onclick="toggleGlobalSubtasks()" 
        title="{{ $showSubtasks ? 'Ocultar todos los desgloses' : 'Mostrar todos los desgloses' }}"
        class="flex flex-col items-center justify-center gap-0.5 px-1.5 sm:px-3 py-2 rounded-xl transition-all shrink-0 {{ $showSubtasks ? 'text-indigo-600 dark:text-indigo-400 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
        <div class="flex items-center justify-center h-4 sm:h-5 w-4 sm:w-5 shrink-0">
            @if($showSubtasks)
                {{-- Icon: Folders or Multi-line open --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            @else
                {{-- Icon: Folders or Multi-line closed --}}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 sm:h-5 w-4 sm:w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
            @endif
        </div>
        <span class="hidden sm:block text-[9px] font-bold uppercase tracking-tight whitespace-nowrap leading-none">Subtareas</span>
    </button>

    <script>
        if (typeof toggleGlobalSubtasks === 'undefined') {
            window.toggleGlobalSubtasks = function() {
                let token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}';
                
                // Determine absolute state: if any subtask is currently expanded, we hide ALL.
                // But since we use session, we just toggle the session and reload.
                // The user said "interruptor on/off".
                
                fetch('{{ route('tasks.toggle-subtasks-visibility') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Content-Type': 'application/json'
                    }
                }).then(res => res.json()).then(() => window.location.reload());
            }
        }
    </script>
@endif
