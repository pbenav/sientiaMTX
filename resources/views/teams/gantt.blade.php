<x-app-layout>
    @section('title', __('navigation.gantt') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight">
                        {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}
                    </h1>
                </div>
            </div>
        </div>

        <!-- View Switcher Sub-Header -->
        <div class="mt-4 mb-2">
            @include('teams.partials.view-switcher')
        </div>

        <!-- Action Buttons Row -->
        <div class="flex items-center gap-3 shrink-0 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">
            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="py-6 space-y-4">
        <!-- Filters and Search Bar -->
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm transition-all">
            <form action="{{ route('teams.gantt', $team) }}" method="GET" class="flex flex-wrap items-center gap-4">
                <!-- Search -->
                <div class="flex-1 min-w-[200px] relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="{{ __('tasks.search') }}..."
                        class="w-full pl-10 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                </div>

                <!-- Status Filter -->
                <div class="w-40">
                    <select name="status" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.status') }}</option>
                        @foreach (['pending', 'in_progress', 'completed', 'cancelled', 'blocked'] as $status)
                            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>
                                {{ __("tasks.statuses.{$status}") }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Priority Filter -->
                <div class="w-40">
                    <select name="priority" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.priority') }}</option>
                        @foreach (['low', 'medium', 'high', 'critical'] as $priority)
                            <option value="{{ $priority }}"
                                {{ request('priority') === $priority ? 'selected' : '' }}>
                                {{ __("tasks.priorities.{$priority}") }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Assigned To Filter -->
                <div class="w-48">
                    <select name="assigned_to" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.assigned_to') }}</option>
                        @if (isset($members))
                            @foreach ($members as $member)
                                <option value="{{ $member->id }}"
                                    {{ request('assigned_to') == $member->id ? 'selected' : '' }}>
                                    {{ $member->name }}
                                </option>
                            @endforeach
                        @endif
                    </select>
                </div>

                <!-- Type Filter -->
                <div class="w-40">
                    <select name="type" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="">{{ __('tasks.type') }}</option>
                        <option value="template" {{ request('type') === 'template' ? 'selected' : '' }}>
                            {{ __('tasks.template') }}</option>
                        <option value="instance" {{ request('type') === 'instance' ? 'selected' : '' }}>
                            {{ __('tasks.subtask') }}</option>
                        <option value="plain" {{ request('type') === 'plain' ? 'selected' : '' }}>
                            {{ __('tasks.task') }}</option>
                    </select>
                </div>

                <!-- Time Range Filter -->
                <div class="w-40">
                    <select name="time_range" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="all" {{ request('time_range') === 'all' ? 'selected' : '' }}>{{ __('tasks.all_time') ?? 'Cualquier fecha' }}</option>
                        <option value="1" {{ request('time_range') === '1' ? 'selected' : '' }}>{{ __('tasks.next_month') ?? 'Próximo mes' }}</option>
                        <option value="3" {{ request('time_range') === '3' ? 'selected' : '' }}>{{ __('tasks.next_3_months') ?? 'Próximos 3 meses' }}</option>
                        <option value="6" {{ request('time_range') === '6' ? 'selected' : '' }}>{{ __('tasks.next_6_months') ?? 'Próximos 6 meses' }}</option>
                    </select>
                </div>

                <!-- Limit Filter -->
                <div class="w-32">
                    <select name="limit" onchange="this.form.submit()"
                        class="w-full bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 py-2 focus:ring-2 focus:ring-violet-500/50 cursor-pointer">
                        <option value="all" {{ request('limit') === 'all' ? 'selected' : '' }}>{{ __('tasks.all_tasks') ?? 'Sin límite' }}</option>
                        <option value="25" {{ request('limit') === '25' ? 'selected' : '' }}>{{ __('tasks.top_25') ?? 'Top 25' }}</option>
                        <option value="50" {{ request('limit') === '50' ? 'selected' : '' }}>{{ __('tasks.top_50') ?? 'Top 50' }}</option>
                        <option value="100" {{ request('limit') === '100' ? 'selected' : '' }}>{{ __('tasks.top_100') ?? 'Top 100' }}</option>
                    </select>
                </div>

                @if (request()->anyFilled(['search', 'status', 'priority', 'assigned_to', 'type', 'time_range', 'limit']))
                    <a href="{{ route('teams.gantt', $team) }}"
                        class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">
                        {{ __('tasks.clear_filters') }}
                    </a>
                @endif
            </form>
        </div>

        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm dark:shadow-none overflow-hidden transition-colors">
            <div
                class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-transparent flex justify-between items-center text-xs text-gray-500">
                <div class="flex gap-4">
                    <span class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-red-500"></div> Q1: Hacer
                    </span>
                    <span class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-blue-500"></div> Q2: Planificar
                    </span>
                    <span class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-amber-500"></div> Q3: Delegar
                    </span>
                    <span class="flex items-center gap-1.5">
                        <div class="w-2 h-2 rounded-full bg-gray-500"></div> Q4: Eliminar
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="changeView('Day')"
                        class="hover:text-violet-600 font-bold uppercase tracking-tighter transition-colors">Día</button>
                    <button onclick="changeView('Week')"
                        class="hover:text-violet-600 font-bold uppercase tracking-tighter transition-colors">Semana</button>
                    <button onclick="changeView('Month')"
                        class="hover:text-violet-600 font-bold uppercase tracking-tighter transition-colors">Mes</button>
                </div>
            </div>

            <div id="gantt-container" class="gantt-target overflow-x-auto min-h-[500px]"></div>
        </div>
    </div>

    <!-- Frappe Gantt Resources -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>

    <style>
        /* Custom styles for Frappe Gantt matching MTX theme */
        .gantt-target {
            background-color: transparent !important;
        }

        .gantt .grid-header {
            fill: #f9fafb !important;
            stroke: #e5e7eb !important;
        }

        .dark .gantt .grid-header {
            fill: #111827 !important;
            stroke: #374151 !important;
        }

        .gantt .grid-row {
            fill: transparent !important;
        }

        .gantt .grid-row:nth-child(even) {
            fill: rgba(0, 0, 0, 0.01) !important;
        }

        .dark .gantt .grid-row:nth-child(even) {
            fill: rgba(255, 255, 255, 0.01) !important;
        }

        .gantt .tick {
            stroke: #e5e7eb !important;
        }

        .dark .gantt .tick {
            stroke: #1f2937 !important;
        }

        .gantt .upper-text {
            fill: #6b7280 !important;
            font-weight: 600 !important;
            text-transform: uppercase !important;
            font-size: 10px !important;
        }

        .gantt .lower-text {
            fill: #9ca3af !important;
            font-size: 10px !important;
        }

        /* Bar styles by quadrant */
        .gantt .bar {
            fill: #7c3aed;
        }

        /* Q1: Red */
        .gantt .gantt-q1 .bar-filled {
            fill: #ef4444 !important;
        }

        .gantt .gantt-q1 .bar {
            fill: rgba(239, 68, 68, 0.4) !important;
        }

        /* Q2: Blue */
        .gantt .gantt-q2 .bar-filled {
            fill: #3b82f6 !important;
        }

        .gantt .gantt-q2 .bar {
            fill: rgba(59, 130, 246, 0.4) !important;
        }

        /* Q3: Amber */
        .gantt .gantt-q3 .bar-filled {
            fill: #f59e0b !important;
        }

        .gantt .gantt-q3 .bar {
            fill: rgba(245, 158, 11, 0.4) !important;
        }

        /* Q4: Gray */
        .gantt .gantt-q4 .bar-filled {
            fill: #6b7280 !important;
        }

        .gantt .gantt-q4 .bar {
            fill: rgba(107, 114, 128, 0.4) !important;
        }

        .gantt .bar-label {
            fill: #111827 !important;
            font-weight: 500 !important;
            font-size: 11px !important;
        }

        .dark .gantt .bar-label {
            fill: #f3f4f6 !important;
        }

        .gantt .arrow {
            stroke: #9ca3af !important;
            stroke-width: 1.5 !important;
        }

        .dark .gantt .arrow {
            stroke: #4b5563 !important;
        }

        .gantt .handle {
            fill: #9ca3af !important;
        }

        /* Hierarchical Styling */
        .gantt .gantt-master .bar {
            stroke: rgba(0, 0, 0, 0.2) !important;
            stroke-width: 1px !important;
        }

        .dark .gantt .gantt-master .bar {
            stroke: rgba(255, 255, 255, 0.2) !important;
        }

        .gantt .gantt-instance .bar {
            rx: 12 !important; /* Extra rounded for instances */
            ry: 12 !important;
            height: 22px !important; /* Thinner bars for subtasks */
            y: 4px !important; /* Vertical offset to center the thinner bar */
        }
        
        .gantt .gantt-instance .bar-filled {
            height: 22px !important;
            y: 4px !important;
        }

        .gantt .gantt-instance .bar-label {
            font-size: 10px !important;
            opacity: 0.8;
        }

        /* Popover styling */
        .gantt-container .header-wrapper {
            display: none;
        }

        /* Sobreescritura del contenedor BASE de Frappe Gantt */
        .gantt-container .popup-wrapper {
            background: #f9fafb !important; /* bg-gray-50 */
            border-radius: 16px !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25) !important;
            padding: 0 !important;
            color: #111827 !important;
            border: 1px solid #e5e7eb !important;
            opacity: 1 !important;
            min-width: 280px !important;
        }

        .dark .gantt-container .popup-wrapper {
            background: #1f2937 !important; /* bg-gray-800 */
            border-color: #374151 !important;
            color: #f3f4f6 !important;
        }

        /* Limpiamos el contenedor interno para que no duplique estilos */
        .frappe-gantt .details-container {
            background: transparent !important;
            box-shadow: none !important;
            border: none !important;
            padding: 16px !important;
        }

        /* Variables de Estilo Sientia */
        :root {
            --gantt-title: #111827;
            --gantt-label: #6b7280;
            --gantt-value: #374151;
            --gantt-sep: #e5e7eb;
        }

        .dark {
            --gantt-title: #FFFFFF;
            --gantt-label: #9ca3af;
            --gantt-value: #f3f4f6;
            --gantt-sep: #4b5563;
        }

        /* Today line highlight */
        .gantt .today-highlight {
            fill: rgba(16, 185, 129, 0.05) !important;
        }

        #today-line {
            stroke: #10b981;
            stroke-width: 1;
            /* Solid line for better visibility on screen */
        }

        /* Hide the dummy task used to extend range */
        .gantt .gantt-today-marker-task .bar-group,
        .gantt .gantt-today-marker-task .bar-label {
            display: none !important;
        }

        /* Blocked task pulse in Gantt */
        .gantt .bar-group[data-status="blocked"] .bar {
            fill: #ef4444 !important;
            animation: pulse-red 2s infinite;
        }

        @keyframes pulse-red {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }

            100% {
                opacity: 1;
            }
        }
    </style>

    <script>
        let gantt;
        let allTasks = [];
        let collapsedTasks = new Set();
        let currentMode = 'Week';

        async function fetchTasks(quadrant = 'all') {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('quadrant', quadrant);
            const url = `{{ route('teams.gantt.data', $team) }}?${urlParams.toString()}`;
            const response = await fetch(url);
            return await response.json();
        }

        function refreshGanttDisplay() {
            // Filter tasks based on collapse state
            const tasksToDisplay = allTasks.filter(task => {
                // If it's the today marker, always show
                if (task.id === 'today_marker') return true;
                
                // If the parent is collapsed, hide this task
                if (task.dependencies && collapsedTasks.has(task.dependencies)) {
                    return false;
                }
                return true;
            }).map(task => {
                // Clone task to avoid modifying original
                const t = { ...task };
                
                // Update label with arrow if it has children
                if (t.has_children) {
                    const icon = collapsedTasks.has(t.id) ? '▶ ' : '▼ ';
                    t.name = icon + t.name.replace(/^[▶▼] /, '');
                }
                return t;
            });

            // If gantt exists, we refresh it (though Frappe Gantt doesn't have a perfect refresh for tree views, 
            // re-init is often safer to ensure correct layout and row heights).
            renderGantt(tasksToDisplay);
        }

        async function initGantt(quadrant = 'all') {
            allTasks = await fetchTasks(quadrant);

            if (allTasks.length === 0 || (allTasks.length === 1 && allTasks[0].id === 'today_marker')) {
                document.getElementById('gantt-container').innerHTML = `
                    <div class="flex flex-col items-center justify-center p-20 text-gray-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-4 opacity-20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        <p class="font-medium">No hay tareas para mostrar en este cuadrante.</p>
                        <p class="text-xs mt-1">Asegúrate de que las tareas tengan fechas asignadas.</p>
                    </div>
                `;
                return;
            }

            refreshGanttDisplay();
        }

        function renderGantt(tasks) {
            document.getElementById('gantt-container').innerHTML = '';

            gantt = new Gantt("#gantt-container", tasks, {
                header_height: 50,
                column_width: 30,
                step: 24,
                view_modes: ['Day', 'Week', 'Month'],
                bar_height: 30,
                bar_corner_radius: 6,
                arrow_curve: 5,
                padding: 18,
                view_mode: currentMode,
                language: 'es',
                custom_popup_html: function(task) {
                    if (task.id === 'today_marker') return ''; // No popup for dummy task

                    const statusLabels = {
                        'pending': 'Pendiente',
                        'in_progress': 'En curso',
                        'completed': 'Terminada',
                        'cancelled': 'Cancelada',
                        'blocked': 'Bloqueada'
                    };
                    const statusColors = {
                        'pending': 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                        'in_progress': 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                        'completed': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
                        'cancelled': 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
                        'blocked': 'bg-red-500 text-white shadow-lg animate-pulse',
                    };

                    const parentHtml = task.parent_title ? `
                        <div class="flex items-center gap-1 mt-1 mb-2">
                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20">
                                ↳ Subtarea 
                            </span>
                            <span class="text-[10px] text-gray-400 truncate max-w-[120px]">${task.parent_title}</span>
                        </div>
                    ` : '';

                    return `
                        <div class="p-1 min-w-[240px]">
                            <a href="{{ url('/teams/' . $team->id . '/tasks') }}/${task.id}" class="hover:underline decoration-2">
                                <h4 class="font-black text-base mb-1 truncate" style="color: var(--gantt-title) !important;" title="${task.name}">${task.name}</h4>
                            </a>
                            ${parentHtml}
                            <div class="flex items-center gap-3 mb-3">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-black uppercase ${statusColors[task.status]}" style="color: inherit !important;">${statusLabels[task.status]}</span>
                                <span class="text-xs font-black" style="color: var(--gantt-label) !important;">${task.progress}%</span>
                            </div>
                            <div class="text-xs flex flex-col gap-2 border-t pt-3 mt-1 font-bold" style="border-color: var(--gantt-sep) !important;">
                                <div class="flex justify-between items-center" style="margin-bottom: 2px;">
                                    <span style="color: var(--gantt-label) !important;">📅 INICIO</span> 
                                    <span style="color: var(--gantt-value) !important; font-weight: 900; font-family: monospace;">${task.start}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span style="color: var(--gantt-label) !important;">🏁 FIN</span> 
                                    <span style="color: var(--gantt-value) !important; font-weight: 900; font-family: monospace;">${task.end}</span>
                                </div>
                            </div>
                        </div>
                    `;
                },
                on_click: function(task) {
                    if (task.has_children) {
                        // Toggle collapse
                        if (collapsedTasks.has(task.id)) {
                            collapsedTasks.delete(task.id);
                        } else {
                            collapsedTasks.add(task.id);
                        }
                        refreshGanttDisplay();
                    } else {
                        // Redirect to task show view if not collapsible
                        window.location.href = `{{ url('/teams/' . $team->id . '/tasks') }}/${task.id}`;
                    }
                },
                on_date_change: function(task, start, end) {
                    if (task.id === 'today_marker') return;

                    // Update task dates via AJAX
                    fetch(`{{ url('/teams/' . $team->id . '/tasks') }}/${task.id}/move`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                scheduled_date: start,
                                due_date: end
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Update current local data to reflect changes if needed
                                let updated = allTasks.find(t => t.id === task.id);
                                if (updated) {
                                    updated.start = start;
                                    updated.end = end;
                                }
                            }
                        })
                        .catch(error => console.error('Error updating task:', error));
                }
            });

            // Post-initialization: Add status attributes to bar groups for CSS targeting
            setTimeout(() => {
                tasks.forEach(t => {
                    const el = document.querySelector(`.bar-group[data-id="${t.id}"]`);
                    if (el) el.setAttribute('data-status', t.status);
                });
            }, 500);
            
            // Center today line and add custom line
            setTimeout(() => {
                centerToday();
                drawTodayLine();
            }, 800);
        }

        function centerToday() {
            const container = document.getElementById('gantt-container');
            const todayElement = container.querySelector('.today-highlight');
            if (todayElement) {
                const x = parseFloat(todayElement.getAttribute('x'));
                const containerWidth = container.offsetWidth;
                container.scrollLeft = x - (containerWidth / 2);
            }
        }

        function drawTodayLine() {
            const container = document.getElementById('gantt-container');
            const svg = container.querySelector('svg');
            const todayHighlight = container.querySelector('.today-highlight');

            if (!svg || !todayHighlight) return;

            const existing = document.getElementById('today-line');
            if (existing) existing.remove();

            const x = parseFloat(todayHighlight.getAttribute('x'));

            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('id', 'today-line');
            line.setAttribute('x1', x);
            line.setAttribute('y1', 0);
            line.setAttribute('x2', x);
            line.setAttribute('y2', '100%');
            svg.appendChild(line);
        }

        function changeView(mode) {
            currentMode = mode;
            gantt.change_view_mode(mode);
            setTimeout(() => {
                centerToday();
                drawTodayLine();
            }, 500);
        }

        function filterTasks(quadrant) {
            initGantt(quadrant);
        }

        document.addEventListener('DOMContentLoaded', () => initGantt());
    </script>
</x-app-layout>
