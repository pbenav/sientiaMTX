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
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                    {{ __('navigation.gantt') ?? 'Diagrama de Gantt' }} — {{ $team->name }}
                </h1>
            </div>

            <div class="flex gap-2">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:border-violet-500 hover:text-violet-600 dark:hover:text-violet-400 px-3 py-2 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    {{ __('teams.eisenhower_matrix') }}
                </a>
            </div>
            <div class="flex items-center gap-2 p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm"
                x-data="{ quadrant: 'all' }">
                <button @click="quadrant = 'all'; filterTasks('all')"
                    :class="quadrant === 'all' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' :
                        'text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                    Todos
                </button>
                <button @click="quadrant = '1'; filterTasks(1)"
                    :class="quadrant === '1' ? 'bg-red-500 text-white shadow-sm' :
                        'text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20'"
                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                    Q1
                </button>
                <button @click="quadrant = '2'; filterTasks(2)"
                    :class="quadrant === '2' ? 'bg-blue-500 text-white shadow-sm' :
                        'text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20'"
                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                    Q2
                </button>
                <button @click="quadrant = '3'; filterTasks(3)"
                    :class="quadrant === '3' ? 'bg-amber-500 text-white shadow-sm' :
                        'text-amber-500 hover:bg-amber-50 dark:hover:bg-amber-900/20'"
                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                    Q3
                </button>
                <button @click="quadrant = '4'; filterTasks(4)"
                    :class="quadrant === '4' ? 'bg-gray-500 text-white shadow-sm' :
                        'text-gray-500 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider rounded-lg transition-all">
                    Q4
                </button>
            </div>
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

                    return `
                        <div class="p-1">
                            <h4 class="font-bold text-sm mb-1">${task.name}</h4>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase ${statusColors[task.status]}">${statusLabels[task.status]}</span>
                                <span class="text-[10px] text-gray-500">${task.progress}% completado</span>
                            </div>
                            <div class="text-[10px] text-gray-400 flex flex-col gap-0.5">
                                <span>📅 Inicio: ${task.start}</span>
                                <span>🏁 Fin: ${task.end}</span>
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
        }

        function changeView(mode) {
            gantt.change_view_mode(mode);
        }

        function filterTasks(quadrant) {
            initGantt(quadrant);
        }

        document.addEventListener('DOMContentLoaded', () => initGantt());
    </script>
</x-app-layout>
