<x-app-layout>
    @section('title', __('navigation.gantt') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 overflow-hidden">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}"
                    class="mt-1 p-2 bg-gray-50 dark:bg-gray-800 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-xl transition-all shadow-sm shrink-0"
                    title="{{ __('navigation.back') ?? 'Volver' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl font-black text-gray-900 dark:text-white heading truncate select-none">
                        {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}
                    </h1>
                </div>
            </div>

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

                @if (request()->anyFilled(['search', 'status', 'priority', 'assigned_to', 'type']))
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

        /* Popover styling */
        .gantt-container .header-wrapper {
            display: none;
        }

        .frappe-gantt .details-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            color: #111827;
            padding: 12px;
            font-size: 12px;
            border: 1px solid #e5e7eb;
            backdrop-filter: blur(8px);
        }

        .dark .frappe-gantt .details-container {
            background: rgba(17, 24, 39, 0.95);
            border-color: #374151;
            color: #f3f4f6;
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

        async function fetchTasks(quadrant = 'all') {
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('quadrant', quadrant);
            const url = `{{ route('teams.gantt.data', $team) }}?${urlParams.toString()}`;
            const response = await fetch(url);
            return await response.json();
        }

        async function initGantt(quadrant = 'all') {
            const tasks = await fetchTasks(quadrant);

            if (tasks.length === 0) {
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
                view_mode: 'Week',
                language: 'es',
                custom_popup_html: function(task) {
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
                                ↳ {{ __('tasks.subtask') }} 
                            </span>
                            <span class="text-[10px] text-gray-400 truncate max-w-[120px]">${task.parent_title}</span>
                        </div>
                    ` : '';

                    return `
                        <div class="p-1 min-w-[200px]">
                            <h4 class="font-bold text-sm mb-1 truncate" title="${task.name}">${task.name}</h4>
                            ${parentHtml}
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${statusColors[task.status]}">${statusLabels[task.status]}</span>
                                <span class="text-[10px] text-gray-500">${task.progress}%</span>
                            </div>
                            <div class="text-[10px] text-gray-400 flex flex-col gap-0.5 border-t border-gray-100 dark:border-gray-800 pt-2 mt-2">
                                <div class="flex justify-between"><span>📅 Inicio:</span> <span class="text-gray-600 dark:text-gray-300">${task.start}</span></div>
                                <div class="flex justify-between"><span>🏁 Fin:</span> <span class="text-gray-600 dark:text-gray-300">${task.end}</span></div>
                            </div>
                        </div>
                    `;
                },
                on_click: function(task) {
                    // Redirect to task show view
                    window.location.href = `{{ url('/teams/' . $team->id . '/tasks') }}/${task.id}`;
                },
                on_date_change: function(task, start, end) {
                    console.log('Date changed', task, start, end);

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
                                // Optional: Show a small toast or notification
                                console.log('Task updated successfully');
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
            } else {
                // Fallback: If no highlight (should not happen with our enforcer), center last task
                const bars = container.querySelectorAll('.bar');
                if (bars.length > 0) {
                    const lastBar = bars[bars.length - 1];
                    container.scrollLeft = parseFloat(lastBar.getAttribute('x')) - (container.offsetWidth / 2);
                }
            }
        }

        function drawTodayLine() {
            const container = document.getElementById('gantt-container');
            const svg = container.querySelector('svg');
            const todayHighlight = container.querySelector('.today-highlight');

            if (!svg || !todayHighlight) {
                // Retry once after a short delay if not ready
                if (!window._gantt_retry) {
                    window._gantt_retry = true;
                    setTimeout(drawTodayLine, 1000);
                }
                return;
            };

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
            gantt.change_view_mode(mode);
            window._gantt_retry = false;
            setTimeout(() => {
                centerToday();
                drawTodayLine();
            }, 500);
        }

        function filterTasks(quadrant) {
            window._gantt_retry = false;
            initGantt(quadrant);
        }

        document.addEventListener('DOMContentLoaded', () => initGantt());
    </script>
</x-app-layout>
