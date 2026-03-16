<x-app-layout>
    @section('title', __('navigation.gantt') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                        {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }}
                    </h1>
                </div>
            </div>

            @include('teams.partials.header-actions')
        </div>
    </x-slot>

    <div class="py-6">
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
            stroke-width: 1.5;
            stroke-dasharray: 4;
        }
    </style>

    <script>
        let gantt;
        let allTasks = [];

        async function fetchTasks(quadrant = 'all') {
            const url = `{{ route('teams.gantt.data', $team) }}?quadrant=${quadrant}`;
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
                        'cancelled': 'Cancelada'
                    };
                    const statusColors = {
                        'pending': 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400',
                        'in_progress': 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400',
                        'completed': 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400',
                        'cancelled': 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400',
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

            // Center today line and add custom line
            setTimeout(() => {
                centerToday();
                drawTodayLine();
            }, 500);
        }

        function centerToday() {
            const container = document.getElementById('gantt-container');
            const todayElement = container.querySelector('.today-highlight');
            if (todayElement) {
                const x = todayElement.getAttribute('x');
                const containerWidth = container.offsetWidth;
                container.scrollLeft = x - (containerWidth / 2);
            }
        }

        function drawTodayLine() {
            const svg = document.querySelector('#gantt-container svg');
            const todayHighlight = document.querySelector('.today-highlight');
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
            gantt.change_view_mode(mode);
            setTimeout(() => {
                centerToday();
                drawTodayLine();
            }, 300);
        }

        function filterTasks(quadrant) {
            initGantt(quadrant);
        }

        document.addEventListener('DOMContentLoaded', () => initGantt());
    </script>
</x-app-layout>
