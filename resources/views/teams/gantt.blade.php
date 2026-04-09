<x-app-layout>
    @section('title', __('navigation.gantt') . ' — ' . $team->name)

    <style>
        /* Modern Gantt Aesthetics */
        #gantt-container svg { background-color: transparent !important; }
        .gantt .grid-row:nth-child(even) { fill: #f9fafb; }
        .gantt .grid-row:nth-child(odd) { fill: #ffffff; }
        .gantt .grid-header { fill: #ffffff; stroke: #e5e7eb; }
        .gantt .upper-text { fill: #9ca3af; font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; }
        .gantt .lower-text { fill: #4b5563; font-weight: 800; font-size: 11px; }
        .gantt .tick { stroke: #f3f4f6; }

        .dark .gantt .grid-row:nth-child(even) { fill: #0f172a; }
        .dark .gantt .grid-row:nth-child(odd) { fill: #111827; }
        .dark .gantt .grid-header { fill: #1f2937; stroke: #374151; }
        .dark .gantt .upper-text { fill: #6b7280; }
        .dark .gantt .lower-text { fill: #e5e7eb; }
        .dark .gantt .tick { stroke: #1f2937; }

        .gantt .bar-wrapper { cursor: pointer; transition: transform 0.2s; }
        .gantt .bar { fill: #e5e7eb; stroke: none; opacity: 0.85; }
        .gantt .bar-progress { fill: currentColor; opacity: 1; }
        .gantt .bar-label { fill: #1f2937; font-weight: 700; font-size: 11px; }
        .dark .gantt .bar-label { fill: #ffffff; }
        .dark .gantt .bar { fill: #374151; }

        /* Quadrant Colors */
        svg.gantt .bar-wrapper.gantt-color-q1 rect.bar { fill: #fecaca !important; }
        svg.gantt .bar-wrapper.gantt-color-q1 rect.bar-progress { fill: #ef4444 !important; }
        svg.gantt .bar-wrapper.gantt-color-q2 rect.bar { fill: #bfdbfe !important; }
        svg.gantt .bar-wrapper.gantt-color-q2 rect.bar-progress { fill: #3b82f6 !important; }
        svg.gantt .bar-wrapper.gantt-color-q3 rect.bar { fill: #fde68a !important; }
        svg.gantt .bar-wrapper.gantt-color-q3 rect.bar-progress { fill: #f59e0b !important; }
        svg.gantt .bar-wrapper.gantt-color-q4 rect.bar { fill: #e5e7eb !important; }
        svg.gantt .bar-wrapper.gantt-color-q4 rect.bar-progress { fill: #6b7280 !important; }

        .dark svg.gantt .bar-wrapper.gantt-color-q1 rect.bar { fill: #7f1d1d !important; fill-opacity: 0.4 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q1 rect.bar-progress { fill: #f87171 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q2 rect.bar { fill: #1e3a8a !important; fill-opacity: 0.4 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q2 rect.bar-progress { fill: #60a5fa !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q3 rect.bar { fill: #78350f !important; fill-opacity: 0.4 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q3 rect.bar-progress { fill: #fbbf24 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q4 rect.bar { fill: #374151 !important; fill-opacity: 0.4 !important; }
        .dark svg.gantt .bar-wrapper.gantt-color-q4 rect.bar-progress { fill: #9ca3af !important; }

        .gantt .handle { fill: #9ca3af; }
        .gantt .today-highlight { fill: rgba(16, 185, 129, 0.05) !important; }
        #today-line { stroke: #10b981; stroke-width: 2; }

        .gantt-readonly {
            opacity: 0.8 !important;
        }
        .bar-wrapper.gantt-readonly .handle-group {
            display: none !important;
        }
    </style>

    <x-slot name="header">
        <div class="flex flex-col xl:flex-row xl:items-start justify-between gap-6">
            <div class="flex items-start gap-4 min-w-0 flex-1">
                <a href="{{ route('teams.index') }}" class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50" title="Volver">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div class="min-w-0 flex-1">
                    @include('teams.partials.breadcrumb')
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white truncate tracking-tight">
                        {{ __('navigation.gantt') }}
                    </h1>
                </div>
            </div>
        </div>
        <div class="mt-4 mb-2 flex w-full">@include('teams.partials.view-switcher')</div>
        <div class="flex items-center gap-3 mt-2 border-t border-gray-100 dark:border-gray-800 pt-3">@include('teams.partials.header-actions')</div>
    </x-slot>

    <div class="py-6 space-y-4">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm">
            <form action="{{ route('teams.gantt', $team) }}" method="GET" class="flex flex-wrap items-center gap-4">
                <div class="flex-1 min-w-[200px] relative">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('tasks.search') }}..." class="w-full pl-4 pr-4 py-2 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-sm focus:ring-2 focus:ring-violet-500/50 dark:text-white transition-all">
                </div>
                <select name="status" onchange="this.form.submit()" class="w-40 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.status') }}</option>
                    @foreach (['pending', 'in_progress', 'completed', 'cancelled', 'blocked'] as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ __("tasks.statuses.{$status}") }}</option>
                    @endforeach
                </select>
                <select name="priority" onchange="this.form.submit()" class="w-40 bg-gray-50 dark:bg-gray-800 border-none rounded-xl text-xs font-bold uppercase py-2 cursor-pointer">
                    <option value="">{{ __('tasks.priority') }}</option>
                    @foreach(['low','medium','high','critical'] as $p)
                        <option value="{{$p}}" {{request('priority')==$p?'selected':''}}>{{__("tasks.priorities.{$p}")}}</option>
                    @endforeach
                </select>
                @if(request()->anyFilled(['search','status','priority']))
                    <a href="{{ route('teams.gantt', $team) }}" class="text-xs font-bold text-red-500 uppercase tracking-widest">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-transparent flex justify-between items-center text-xs text-gray-500">
                <div class="flex gap-4">
                    <span class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-red-500"></div> Q1: Hacer</span>
                    <span class="flex items-center gap-1.5"><div class="w-2 h-2 rounded-full bg-blue-500"></div> Q2: Planificar</span>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="changeView('Day')" class="hover:text-violet-600 font-bold uppercase">Día</button>
                    <button onclick="changeView('Week')" class="hover:text-violet-600 font-bold uppercase">Semana</button>
                    <button onclick="changeView('Month')" class="hover:text-violet-600 font-bold uppercase">Mes</button>
                </div>
            </div>

            <!-- Action Wave -->
            <div class="px-8 py-6 bg-white dark:bg-gray-950 border-b border-gray-100 dark:border-gray-800 relative overflow-hidden">
                <div class="flex items-center justify-between mb-6 relative z-10">
                    <div class="flex flex-col">
                        <h4 class="text-[10px] font-black uppercase tracking-[0.3em] text-gray-400 mb-1">Onda de Resiliencia Colectiva</h4>
                        <span class="text-lg font-black text-gray-900 dark:text-white">{{ now()->translatedFormat('F Y') }}</span>
                    </div>
                </div>

                @php
                    $maxWeight = collect($actionHeat)->max('weight') ?: 1;
                    $width = 100 / ($daysInMonth - 1);
                    $pts = []; $upts = [];
                    foreach($actionHeat as $day => $d) {
                        $h = ($d['weight'] / $maxWeight) * 100;
                        $uh = ($d['user_weight'] / $maxWeight) * 100;
                        $pts[] = ($day-1)*$width . ',' . (100-$h);
                        $upts[] = ($day-1)*$width . ',' . (100-$uh);
                    }
                    $pathData = "M0,100 L" . implode(" L", $pts) . " L100,100 Z";
                    $userLineData = "M" . implode(" L", $upts);
                @endphp

                <div class="relative h-24 mb-6">
                    <svg viewBox="0 0 100 100" preserveAspectRatio="none" class="w-full h-full overflow-visible">
                        <defs><linearGradient id="waveGradient" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:rgba(124, 58, 237, 0.3)"/><stop offset="100%" style="stop-color:rgba(59, 130, 246, 0.05)"/></linearGradient></defs>
                        <path id="wave-team-path" d="{{$pathData}}" fill="url(#waveGradient)" class="transition-all duration-1000" />
                        <path id="wave-user-line" d="{{$userLineData}}" fill="none" stroke="#10b981" stroke-width="1.5" stroke-linecap="round" class="drop-shadow-[0_0_8px_rgba(16,185,129,0.6)] transition-all duration-1000" />
                    </svg>
                    <div class="absolute inset-0 flex items-end gap-px">
                        @for($i=1; $i<=$daysInMonth; $i++)
                            @php 
                                $d = $actionHeat[$i]??['weight'=>0,'user_weight'=>0,'count'=>0,'user_count'=>0];
                                $pct = $d['weight']/$maxWeight;
                                $color = "hsl(".((1-$pct)*220).", 70%, 50%)";
                            @endphp
                            <div class="flex-1 h-full relative cursor-pointer z-10 wave-pillar"
                                 onmouseenter="updateWaveTooltip(this, event)" onmouseleave="hideWaveTooltip()"
                                 data-day="{{$i}}" data-count="{{$d['count']}}" data-weight="{{$d['weight']}}"
                                 data-user-count="{{$d['user_count']}}" data-user-weight="{{$d['user_weight']}}"
                                 data-pct="{{round($pct*100)}}" data-color="{{$color}}">
                                <div class="absolute inset-x-0 bottom-0 h-0 hover:h-full bg-gray-400/5 transition-all"></div>
                                <div class="team-dot absolute left-1/2 -translate-x-1/2 w-2.5 h-2.5 rounded-full bg-white border-2 transition-all opacity-0 hover:opacity-100" style="bottom: {{$pct*100}}%; border-color: {{$color}}"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Wave Tooltip -->
            <div id="wave-tooltip" class="fixed z-[9999] pointer-events-none opacity-0 transition-all p-4 bg-white/95 dark:bg-gray-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border dark:border-gray-800 min-w-[200px]">
                <div class="flex flex-col gap-1">
                    <span id="w-tooltip-day" class="text-[10px] font-black uppercase text-gray-400"></span>
                    <span class="text-sm font-black dark:text-white">Carga de Trabajo</span>
                    <div class="h-1 w-full bg-gray-100 dark:bg-gray-800 rounded-full mt-2 overflow-hidden">
                        <div id="w-tooltip-bar" class="h-full transition-all duration-500"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-3 text-[10px] font-bold">
                        <div><p class="text-gray-400 uppercase text-[8px]">Equipo</p><p id="w-tooltip-weight" class="dark:text-white"></p></div>
                        <div><p class="text-emerald-500 uppercase text-[8px]">Propia</p><p id="w-tooltip-user-weight" class="text-emerald-600"></p></div>
                    </div>
                </div>
            </div>

            <div id="gantt-container" class="w-full overflow-x-auto min-h-[500px]"></div>
        </div>
    </div>


    <div id="gantt-tooltip" style="display: none"></div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/frappe-gantt/0.6.1/frappe-gantt.min.js"></script>

    <script>
        let gantt, allTasks = [], collapsedTasks = new Set(), currentMode = 'Week';
        const tooltip = document.getElementById('gantt-tooltip');
        const dragIndicator = document.getElementById('drag-date-indicator');

        async function initGantt() {
            const url = `{{ route('teams.gantt.data', $team) }}?${new URLSearchParams(window.location.search).toString()}`;
            const res = await fetch(url);
            allTasks = await res.json();
            
            if (allTasks.length === 0) {
                document.getElementById('gantt-container').innerHTML = '<div class="p-20 text-center text-gray-500 font-bold">Sin tareas.</div>';
                return;
            }

            allTasks.forEach(t => { if(t.has_children) collapsedTasks.add(t.id); });
            renderActionWave();
            refreshGanttDisplay();
            setupCustomInteractions();
        }

        function refreshGanttDisplay() {
            const display = allTasks.filter(t => !t.dependencies || !collapsedTasks.has(t.dependencies))
                .map(t => {
                    const obj = {...t};
                    if(obj.has_children) obj.name = (collapsedTasks.has(obj.id)?'▶ ':'▼ ') + obj.name.replace(/^[▶▼] /,'');
                    return obj;
                });
            
            document.getElementById('gantt-container').innerHTML = '';
            gantt = new Gantt("#gantt-container", display, {
                header_height: 50, column_width: 30, step: 24, view_modes: ['Day', 'Week', 'Month'],
                bar_height: 30, bar_corner_radius: 6, view_mode: currentMode, language: 'es',
                custom_popup_html: () => '',
                on_click: t => {
                    if(t.has_children) { (collapsedTasks.has(t.id)?collapsedTasks.delete(t.id):collapsedTasks.add(t.id)); refreshGanttDisplay(); }
                    else window.location.href = `{{ url('/teams/'.$team->id.'/tasks') }}/${t.id}`;
                },
                on_date_change: (t, start, end) => {
                    if (t.readonly) {
                        refreshGanttDisplay();
                        return;
                    }
                    const fmt = (d) => d.toISOString().split('T')[0];
                    const payload = { scheduled_date: fmt(start), due_date: fmt(end) };
                    fetch(`{{ url('/teams/'.$team->id.'/tasks') }}/${t.id}/move`, {
                        method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify(payload)
                    }).then(r => r.json()).then(data => {
                        if(data.success) {
                            const idx = allTasks.findIndex(x => x.id == t.id);
                            if(idx!==-1) { allTasks[idx].start = payload.scheduled_date; allTasks[idx].end = payload.due_date; renderActionWave(); }
                        } else refreshGanttDisplay();
                    });
                }
            });
            setTimeout(() => { centerToday(); drawTodayLine(); }, 500);
        }

        function renderActionWave() {
            const days = {{ $daysInMonth }};
            const userId = {{ auth()->id() }};
            const mStart = new Date("{{ now()->startOfMonth()->format('Y-m-d') }}T00:00:00");
            
            // Identify Leaf Tasks to avoid double counting (Master + Instance)
            const parentIds = new Set(allTasks.map(t => t.dependencies).filter(d => d && !isNaN(d)));
            const leafTasks = allTasks.filter(t => !parentIds.has(t.id));

            const heat = [];
            for(let i=1; i<=days; i++) {
                const cur = new Date(mStart); cur.setDate(mStart.getDate()+(i-1)); cur.setHours(0,0,0,0);
                const dayT = leafTasks.filter(t => {
                    const s = new Date(t.start+'T00:00:00'), e = new Date(t.end+'T23:59:59');
                    return cur >= s && cur <= e;
                });
                heat[i] = { 
                    weight: dayT.reduce((a,t)=>a+(parseFloat(t.weight)||0),0), 
                    uweight: dayT.filter(t=>t.user_id==userId).reduce((a,t)=>a+(parseFloat(t.weight)||0),0),
                    count: dayT.length,
                    user_count: dayT.filter(t=>t.user_id==userId).length
                };
            }
            const max = Math.max(...heat.filter(h=>h).map(h=>h.weight)) || 1;
            const wFact = 100/(days-1);
            let pts = [], upts = [];
            for(let i=1; i<=days; i++) {
                const x = (i-1)*wFact, h = (heat[i].weight/max)*100, uh = (heat[i].uweight/max)*100;
                pts.push(`${x},${100-h}`); upts.push(`${x},${100-uh}`);

                // Real-time update of tooltip pillars
                const pillar = document.querySelector(`.wave-pillar[data-day="${i}"]`);
                if(pillar) {
                    const pct = heat[i].weight / max;
                    const color = `hsl(${(1-pct)*220}, 70%, 50%)`;
                    pillar.dataset.weight = heat[i].weight;
                    pillar.dataset.userWeight = heat[i].uweight;
                    pillar.dataset.count = heat[i].count;
                    pillar.dataset.userCount = heat[i].user_count;
                    pillar.dataset.pct = Math.round(pct * 100);
                    pillar.dataset.color = color;
                    
                    const dot = pillar.querySelector('.team-dot');
                    if(dot) {
                        dot.style.bottom = (pct * 100) + '%';
                        dot.style.borderColor = color;
                    }
                }
            }
            document.getElementById('wave-team-path')?.setAttribute('d', `M0,100 L${pts.join(' L')} L100,100 Z`);
            document.getElementById('wave-user-line')?.setAttribute('d', `M${upts.join(' L')}`);
        }

        function setupCustomInteractions() {
            let isDragging = false;
            let dragPart = null;
            let lastUpdate = 0;
            const richTooltip = document.getElementById('drag-date-indicator');

            function updateRichPanel(task, x, width, type = 'Detalles') {
                if (!task) return;
                
                let dateStart, dateEnd;
                try {
                    if (!isDragging) {
                        // Use task data directly when hovering
                        dateStart = new Date(task.start + 'T00:00:00');
                        dateEnd = new Date(task.end + 'T00:00:00');
                    } else {
                        // Manual calculation during drag/resize
                        const colWidth = gantt.options.column_width;
                        const step = gantt.options.step;
                        const offsetStart = (x / colWidth) * step;
                        const offsetEnd = ((x + width) / colWidth) * step;
                        
                        dateStart = new Date(gantt.gantt_start);
                        dateStart.setHours(dateStart.getHours() + offsetStart);
                        dateEnd = new Date(gantt.gantt_start);
                        dateEnd.setHours(dateEnd.getHours() + offsetEnd);
                    }
                } catch (e) {
                    dateStart = new Date();
                    dateEnd = new Date();
                }

                const fmt = d => (d instanceof Date && !isNaN(d)) ? d.toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : '??';
                const diffDays = (dateStart instanceof Date && dateEnd instanceof Date) ? Math.ceil((dateEnd - dateStart) / (1000 * 60 * 60 * 24)) + 1 : 0;

                const nameEl = document.getElementById('drag-task-name');
                if (nameEl) nameEl.innerText = task.name || 'Tarea';

                const progressTextEl = document.getElementById('drag-task-progress-text');
                if (progressTextEl) progressTextEl.innerText = (task.progress || 0) + '%';

                const progressBarEl = document.getElementById('drag-progress-bar');
                if (progressBarEl) progressBarEl.style.width = (task.progress || 0) + '%';

                const avatarEl = document.getElementById('drag-user-avatar');
                if (avatarEl) {
                    avatarEl.innerText = task.user_initials || '??';
                    if (task.user_initials === '??') {
                        avatarEl.className = 'w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-[8px] font-black text-gray-500 dark:text-gray-400 shadow-inner ring-1 ring-black/5';
                    } else {
                        avatarEl.className = 'w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center text-[8px] font-black text-white shadow-sm ring-1 ring-white/20';
                    }
                }

                const userNameEl = document.getElementById('drag-user-name');
                if (userNameEl) userNameEl.innerText = task.user_name || 'Sin asignar';

                const statusEl = document.getElementById('drag-task-status');
                if (statusEl) statusEl.innerText = (task.status_label || task.status || 'PENDIENTE').toString().toUpperCase();

                const priorityEl = document.getElementById('drag-priority-badge');
                if (priorityEl) {
                    priorityEl.innerText = (task.priority_label || task.priority || 'NORMAL').toString().toUpperCase();
                    const pColors = { critical: '#ef4444', high: '#f97316', medium: '#eab308', low: '#3b82f6' };
                    priorityEl.style.backgroundColor = pColors[task.priority] || '#374151';
                }

                const skillsC = document.getElementById('drag-task-skills');
                if (skillsC) {
                    skillsC.innerHTML = '';
                    if (task.skills && task.skills.length > 0) {
                        task.skills.slice(0, 3).forEach(sk => {
                            const s = document.createElement('span');
                            s.className = 'px-1.5 py-0.5 rounded-md bg-gray-100 dark:bg-white/10 text-gray-600 dark:text-white text-[6px] font-black uppercase border border-gray-200 dark:border-white/5';
                            s.innerText = sk.name;
                            skillsC.appendChild(s);
                        });
                    } else {
                        skillsC.innerHTML = '<span class="text-[6px] opacity-30 italic">Sin skills</span>';
                    }
                }

                const typeBadgeEl = document.getElementById('drag-type-badge');
                if (typeBadgeEl) typeBadgeEl.innerText = type.toUpperCase();

                const durationEl = document.getElementById('drag-duration-days');
                if (durationEl) durationEl.innerText = diffDays;

                const startLabelEl = document.getElementById('drag-start-label');
                if (startLabelEl) startLabelEl.innerText = fmt(dateStart);

                const endLabelEl = document.getElementById('drag-end-label');
                if (endLabelEl) endLabelEl.innerText = fmt(dateEnd);
            }

            document.addEventListener('mousedown', e => {
                const wrapper = e.target.closest('.bar-wrapper');
                const handle = e.target.closest('.handle');
                if (wrapper) {
                    isDragging = true;
                    dragPart = handle ? (handle.classList.contains('left') ? 'start' : 'end') : 'range';
                }
            }, true);

            document.addEventListener('mousedown', e => {
                const wrapper = e.target.closest('.bar-wrapper');
                if (wrapper && wrapper.classList.contains('gantt-readonly')) {
                    // Block Gantt from starting a drag/resize
                    e.stopImmediatePropagation();
                    
                    // Since we blocked mousedown, Gantt's on_click won't fire. 
                    // We handle it manually on 'click' to allow expansion/details.
                    wrapper.onclick = (event) => {
                        const id = wrapper.dataset.id;
                        const task = allTasks.find(t => t.id == id);
                        if (!task) return;

                        if (task.has_children) {
                            (collapsedTasks.has(task.id) ? collapsedTasks.delete(task.id) : collapsedTasks.add(task.id));
                            refreshGanttDisplay();
                        } else {
                            window.location.href = `{{ url('/teams/'.$team->id.'/tasks') }}/${task.id}`;
                        }
                    };
                }
            }, true);

            document.addEventListener('mouseup', () => {
                isDragging = false;
                richTooltip.style.display = 'none';
                dragPart = null;
            }, true);

            document.addEventListener('mousemove', e => {
                try {
                    const wrapper = e.target.closest('.bar-wrapper');
                    const isDragState = isDragging || wrapper;

                    if (isDragState) {
                        const activeWrapper = isDragging 
                                    ? (document.querySelector('.bar-wrapper.active, .bar-wrapper.dragging, .bar-wrapper.resizing') || wrapper)
                                    : wrapper;
                        
                        if (activeWrapper) {
                            const task = allTasks.find(x => x.id == activeWrapper.dataset.id);
                            const bar = activeWrapper.querySelector('.bar');
                            
                            if (task && bar) {
                                if (Date.now() - lastUpdate < 16) return;
                                lastUpdate = Date.now();

                                const x = parseFloat(bar.getAttribute('x'));
                                const w = parseFloat(bar.getAttribute('width'));
                                let type = 'Detalles';
                                
                                if (isDragging) {
                                    if (dragPart === 'start') type = 'Nuevo Inicio';
                                    else if (dragPart === 'end') type = 'Nueva Entrega';
                                    else type = 'Reubicando';
                                }

                                updateRichPanel(task, x, w, type);
                                
                                let left = e.clientX + 25;
                                let top = e.clientY - 120;
                                
                                if (left + 300 > window.innerWidth) left = e.clientX - 325;
                                if (top < 10) top = e.clientY + 20;

                                richTooltip.style.left = left + 'px';
                                richTooltip.style.top = top + 'px';
                                richTooltip.style.display = 'flex';
                            }
                        }
                    } else {
                        richTooltip.style.display = 'none';
                    }
                } catch (err) {
                    console.error("Gantt Tooltip Error:", err);
                }
            }, true);
        }

        function showTooltip(task, e) {
            // Unificado
        }

        function updateWaveTooltip(el, e) {
            const d = el.dataset;
            document.getElementById('w-tooltip-day').innerText = `${d.day} {{ now()->translatedFormat('M') }}`;
            document.getElementById('w-tooltip-weight').innerText = `${d.weight}u`;
            document.getElementById('w-tooltip-user-weight').innerText = `${d.userWeight}u`;
            document.getElementById('w-tooltip-bar').style.width = d.pct+'%';
            document.getElementById('w-tooltip-bar').style.backgroundColor = d.color;
            const tt = document.getElementById('wave-tooltip');
            tt.style.opacity = '1'; tt.style.left = (e.clientX-100)+'px'; tt.style.top = (e.clientY-140)+'px';
        }

        function hideWaveTooltip() { document.getElementById('wave-tooltip').style.opacity = '0'; }
        function changeView(m) { currentMode = m; refreshGanttDisplay(); }
        function centerToday() { const c = document.getElementById('gantt-container'), h = c.querySelector('.today-highlight'); if(h) c.scrollLeft = parseFloat(h.getAttribute('x')) - (c.offsetWidth/2); }
        function drawTodayLine() {
            const c = document.getElementById('gantt-container'), s = c.querySelector('svg'), h = c.querySelector('.today-highlight');
            if(!s || !h) return;
            const old = document.getElementById('today-line'); if(old) old.remove();
            const x = parseFloat(h.getAttribute('x')), l = document.createElementNS('http://www.w3.org/2000/svg','line');
            l.setAttribute('id','today-line'); l.setAttribute('x1',x); l.setAttribute('y1',0); l.setAttribute('x2',x); l.setAttribute('y2','100%');
            l.setAttribute('stroke','#10b981'); l.setAttribute('stroke-width','2'); l.setAttribute('stroke-dasharray','4');
            s.appendChild(l);
        }

        document.addEventListener('DOMContentLoaded', initGantt);
    </script>
    <!-- Unified Ultra-Rich Tooltip -->
    <div id="drag-date-indicator" style="display: none; box-shadow: 0 30px 60px -12px rgb(0 0 0 / 0.5);" class="fixed z-[10000] pointer-events-none bg-white/95 dark:bg-gray-900/95 text-gray-900 dark:text-white p-5 rounded-[2rem] flex flex-col gap-4 border border-gray-200 dark:border-white/10 shadow-2xl min-w-[320px] backdrop-blur-xl transition-all duration-200">
        <div class="flex items-start justify-between gap-4">
            <div class="flex flex-col flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <span id="drag-type-badge" class="px-2 py-0.5 rounded-md bg-violet-600 text-[7px] font-black uppercase tracking-wider text-white">REUBICANDO</span>
                    <span id="drag-priority-badge" class="px-2 py-0.5 rounded-md bg-gray-200 dark:bg-gray-700 text-[7px] font-black uppercase tracking-wider text-gray-700 dark:text-gray-300">PRIORIDAD</span>
                </div>
                <span id="drag-task-name" class="text-sm font-black leading-tight truncate text-gray-900 dark:text-white">Tarea</span>
            </div>
            <div class="flex flex-col items-center justify-center p-2 bg-gray-100 dark:bg-white/5 rounded-2xl border border-gray-200 dark:border-white/5">
                <span id="drag-duration-days" class="text-lg font-black leading-none text-violet-600 dark:text-violet-400">0</span>
                <span class="text-[7px] font-bold uppercase opacity-60">Días</span>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-x-6 gap-y-3 py-3 border-y border-gray-100 dark:border-white/5">
            <div class="flex flex-col gap-0.5"><span class="text-[8px] font-black uppercase opacity-50">Responsable</span><div class="flex items-center gap-2"><div id="drag-user-avatar" class="w-5 h-5 rounded-full bg-indigo-600 flex items-center justify-center text-[8px] font-black text-white shadow-sm">??</div><span id="drag-user-name" class="text-[10px] font-extrabold truncate text-gray-700 dark:text-gray-200">Asignado</span></div></div>
            <div class="flex flex-col gap-0.5 text-right"><span class="text-[8px] font-black uppercase opacity-50">Estado</span><span id="drag-task-status" class="text-[10px] font-black uppercase tracking-wide text-gray-800 dark:text-gray-100">PENDIENTE</span></div>
            <div class="flex flex-col gap-0.5"><span class="text-[8px] font-black uppercase opacity-50">Habilidades</span><div id="drag-task-skills" class="flex flex-wrap gap-1 mt-0.5"></div></div>
            <div class="flex flex-col gap-0.5 text-right"><span class="text-[8px] font-black uppercase opacity-50">Progreso</span><span id="drag-task-progress-text" class="text-[10px] font-black text-emerald-600 dark:text-emerald-400">0%</span></div>
        </div>
        <div class="flex items-center justify-between gap-4 px-1">
            <div class="flex flex-col gap-0.5"><span class="text-[7px] font-black uppercase opacity-50">Inicia</span><span id="drag-start-label" class="text-xs font-black text-gray-900 dark:text-white"></span></div>
            <div class="flex-1 flex items-center px-4"><div class="h-px flex-1 bg-gradient-to-r from-transparent via-gray-200 dark:via-white/20 to-transparent"></div></div>
            <div class="flex flex-col gap-0.5 text-right"><span class="text-[7px] font-black uppercase opacity-50">Termina</span><span id="drag-end-label" class="text-xs font-black text-gray-900 dark:text-white"></span></div>
        </div>
        <div class="h-1.5 w-full bg-gray-100 dark:bg-white/5 rounded-full overflow-hidden"><div id="drag-progress-bar" class="h-full bg-gradient-to-r from-emerald-500 to-teal-400 shadow-[0_0_10px_rgba(16,185,129,0.2)]"></div></div>
    </div>
</x-app-layout>
