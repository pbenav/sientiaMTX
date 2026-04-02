@php
    $isVisibleView = request()->routeIs('teams.dashboard') || 
                   request()->routeIs('teams.tasks.index') || 
                   request()->routeIs('teams.tasks.show') || 
                   request()->routeIs('teams.gantt') || 
                   request()->routeIs('teams.kanban');
    $hideCompleted = session('hide_completed_tasks', true);
@endphp

@if($isVisibleView)
    <button onclick="toggleHideCompletedTasks()" 
        title="{{ $hideCompleted ? (__('tasks.show_completed') ?? 'Mostrar Completadas') : (__('tasks.hide_completed') ?? 'Ocultar Completadas') }}"
        class="flex flex-col items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl transition-all min-w-[64px] sm:min-w-[80px] {{ $hideCompleted ? 'text-violet-600 dark:text-violet-400 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' }}">
        <div class="flex items-center justify-center h-5 w-5 shrink-0">
            @if($hideCompleted)
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
            @endif
        </div>
        <span class="text-[9px] font-bold uppercase tracking-tight whitespace-nowrap leading-none">{{ $hideCompleted ? (__('tasks.completed_hidden') ?? 'Ocultas') : (__('tasks.completed_visible') ?? 'Visibles') }}</span>
    </button>

    <script>
        if (typeof toggleHideCompletedTasks === 'undefined') {
            window.toggleHideCompletedTasks = function() {
                let token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '{{ csrf_token() }}';
                fetch('{{ route('tasks.toggle-hide-completed') }}', {
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
