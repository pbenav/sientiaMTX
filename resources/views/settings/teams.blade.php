<x-app-layout>
    @section('title', 'Gestión Global de Equipos')

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl shadow-sm border border-violet-200 dark:border-violet-800/30">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white heading">Gestión de Equipos</h1>
                    <x-demo-hint>
                        Permite la administración integral de todos los espacios de trabajo de la instancia. Desde este panel se pueden gestionar acciones masivas (como activar Citas Previas o integraciones de WhatsApp para múltiples equipos a la vez) y supervisar las métricas de carga y miembros.
                    </x-demo-hint>
                </div>
            </div>
            <div>
                <a href="{{ route('teams.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-all shadow-lg shadow-violet-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Equipo
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12 px-4">
        <div class="max-w-7xl mx-auto">
            @include('settings.partials.tabs')

            <!-- Bulk Actions Row -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 rounded-xl">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                    <span class="text-xs font-black text-gray-700 dark:text-gray-300 uppercase tracking-widest">Acciones Masivas Globales</span>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <form method="POST" action="{{ route('settings.teams.bulk-settings') }}" class="inline">
                        @csrf
                        <input type="hidden" name="setting" value="has_appointments">
                        <input type="hidden" name="value" value="1">
                        <button type="submit" class="px-4 py-2 bg-violet-50 hover:bg-violet-100 dark:bg-violet-950/30 dark:hover:bg-violet-900/40 border border-violet-150 dark:border-violet-800/80 rounded-xl text-[10px] font-black uppercase tracking-wider text-violet-600 dark:text-violet-400 transition-all shadow-sm">
                            Habilitar Cita Previa a Todos
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings.teams.bulk-settings') }}" class="inline">
                        @csrf
                        <input type="hidden" name="setting" value="has_appointments">
                        <input type="hidden" name="value" value="0">
                        <button type="submit" class="px-4 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-850 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700/85 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 transition-all shadow-sm">
                            Deshabilitar Citas a Todos
                        </button>
                    </form>
                    <span class="h-5 w-px bg-gray-200 dark:bg-gray-700 mx-1"></span>
                    <form method="POST" action="{{ route('settings.teams.bulk-settings') }}" class="inline">
                        @csrf
                        <input type="hidden" name="setting" value="has_whatsapp">
                        <input type="hidden" name="value" value="1">
                        <button type="submit" class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:hover:bg-emerald-900/40 border border-emerald-150 dark:border-emerald-800/80 rounded-xl text-[10px] font-black uppercase tracking-wider text-emerald-600 dark:text-emerald-400 transition-all shadow-sm">
                            Habilitar WhatsApp a Todos
                        </button>
                    </form>
                    <form method="POST" action="{{ route('settings.teams.bulk-settings') }}" class="inline">
                        @csrf
                        <input type="hidden" name="setting" value="has_whatsapp">
                        <input type="hidden" name="value" value="0">
                        <button type="submit" class="px-4 py-2 bg-gray-50 hover:bg-gray-100 dark:bg-gray-850 dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700/85 rounded-xl text-[10px] font-black uppercase tracking-wider text-gray-500 dark:text-gray-400 transition-all shadow-sm">
                            Deshabilitar WhatsApp a Todos
                        </button>
                    </form>
                </div>
            </div>

            <div class="mb-6 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-4 shadow-sm">
                <form action="{{ route('settings.teams') }}" method="GET" class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[250px]">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            class="block w-full pl-9 pr-3 py-2 text-sm border border-gray-100 dark:border-gray-800 rounded-xl bg-gray-50/50 dark:bg-gray-800/50 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all"
                            placeholder="Buscar por nombre o descripción del equipo...">
                    </div>
                    
                    <select name="per_page" onchange="this.form.submit()"
                        class="text-sm bg-gray-50/50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-xl px-3 pr-12 py-2 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 outline-none text-gray-700 dark:text-gray-300 cursor-pointer">
                        <option value="10" {{ request('per_page') == 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ request('per_page', 25) == 25 || request('per_page') == 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
                    </select>

                    <div class="flex items-center gap-2">
                        <button type="submit" class="px-5 py-2 bg-violet-600 hover:bg-violet-700 text-white text-xs font-black uppercase tracking-widest rounded-xl transition-all shadow-lg shadow-violet-500/20">
                            Filtrar
                        </button>
                        @if(request()->anyFilled(['search', 'per_page']))
                            <a href="{{ route('settings.teams') }}" class="px-4 py-2 text-xs font-bold text-gray-500 hover:text-red-500 transition-colors uppercase tracking-widest">
                                Limpiar
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-800">
                                @foreach([
                                    'name' => 'Equipo',
                                    'created_at' => 'Creado'
                                ] as $field => $label)
                                    <th class="px-6 py-4">
                                        <a href="{{ request()->fullUrlWithQuery(['sort' => $field, 'direction' => $sort === $field && $direction === 'asc' ? 'desc' : 'asc']) }}" 
                                           class="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest leading-none {{ $sort === $field ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }} hover:text-violet-500 transition-colors group">
                                            {{ $label }}
                                            <div class="flex flex-col {{ $sort === $field ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }} transition-opacity">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $sort === $field && $direction === 'asc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                                </svg>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 -mt-1 {{ $sort === $field && $direction === 'desc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                </svg>
                                            </div>
                                        </a>
                                    </th>
                                @endforeach
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">Creador</th>
                                <th class="px-6 py-4">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'members_count', 'direction' => $sort === 'members_count' && $direction === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest leading-none {{ $sort === 'members_count' ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }} hover:text-violet-500 transition-colors group">
                                        Miembros
                                        <div class="flex flex-col {{ $sort === 'members_count' ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }} transition-opacity">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $sort === 'members_count' && $direction === 'asc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 -mt-1 {{ $sort === 'members_count' && $direction === 'desc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </a>
                                </th>
                                <th class="px-6 py-4">
                                    <a href="{{ request()->fullUrlWithQuery(['sort' => 'tasks_count', 'direction' => $sort === 'tasks_count' && $direction === 'asc' ? 'desc' : 'asc']) }}" 
                                       class="flex items-center gap-1.5 text-xs font-black uppercase tracking-widest leading-none {{ $sort === 'tasks_count' ? 'text-violet-600 dark:text-violet-400' : 'text-gray-500 dark:text-gray-400' }} hover:text-violet-500 transition-colors group">
                                        Tareas
                                        <div class="flex flex-col {{ $sort === 'tasks_count' ? 'opacity-100' : 'opacity-0 group-hover:opacity-50' }} transition-opacity">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 {{ $sort === 'tasks_count' && $direction === 'asc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                            </svg>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-2 w-2 -mt-1 {{ $sort === 'tasks_count' && $direction === 'desc' ? 'text-violet-600' : 'text-gray-300' }}" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </a>
                                </th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 text-center">Cita Previa</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 text-center">WhatsApp</th>
                                <th class="px-6 py-4 text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($teams as $team)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-2xl bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center font-black text-xs uppercase shadow-sm">
                                                {{ substr($team->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <a href="{{ auth()->user()->can('view', $team) ? route('teams.show', $team) : route('teams.edit', $team) }}" class="text-sm font-bold text-gray-900 dark:text-white hover:text-violet-600 transition-colors inline-flex items-center gap-2">
                                                    <span>{{ $team->name }}</span>
                                                    @if($team->settings['has_whatsapp'] ?? false)
                                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[9px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 shadow-sm animate-bounce-subtle">
                                                            <svg class="w-2.5 h-2.5 fill-current" viewBox="0 0 24 24">
                                                                <path d="M19.057 20.464c-1.42 1.42-3.845 2.536-6.107 2.536-5.411 0-9.816-4.404-9.816-9.816 0-1.745.457-3.418 1.32-4.896L3 3l5.523 1.45c1.4-.801 3.012-1.25 4.718-1.25 5.412 0 9.816 4.404 9.816 9.816 0 2.63-1.02 5.101-2.88 6.966l-.12.482zM12.24 4.81c-4.63 0-8.4 3.77-8.4 8.4 0 1.57.435 3.1 1.254 4.44l.117.195-.824 3.013 3.08-.808.19.113c1.3.774 2.784 1.182 4.3 1.182 4.631 0 8.4-3.77 8.4-8.4 0-2.244-.873-4.354-2.458-5.939A8.345 8.345 0 0012.24 4.81zm4.846 7.258c-.3-.149-1.771-.864-2.044-.962-.273-.099-.472-.149-.671.149-.198.297-.768.962-.94 1.16-.173.199-.347.223-.647.074-.3-.149-1.265-.462-2.41-1.474-.89-.787-1.49-1.758-1.664-2.056-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.521.15-.173.199-.297.298-.495.099-.198.05-.371-.025-.52-.075-.149-.672-1.62-.92-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.771-.717 2.018-1.412.247-.694.247-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                                                            </svg>
                                                            <span>WhatsApp Premium</span>
                                                        </span>
                                                    @endif
                                                </a>
                                                <div class="text-[10px] text-gray-400 uppercase font-black tracking-tight mt-0.5">
                                                    {{ $team->slug }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $team->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $team->creator->name ?? 'Sistema' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                                            {{ $team->members_count }} miembros
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400 border border-violet-200 dark:border-violet-800">
                                            {{ $team->tasks_count }} tareas
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <form method="POST" action="{{ route('settings.teams.toggle-setting', $team) }}" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="setting" value="has_appointments">
                                            <label class="relative inline-flex items-center cursor-pointer" title="Habilitar/Deshabilitar Citas Previas para {{ $team->name }}">
                                                <input type="checkbox" onchange="this.form.submit()" class="sr-only peer" {{ ($team->settings['has_appointments'] ?? false) ? 'checked' : '' }}>
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-500"></div>
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <form method="POST" action="{{ route('settings.teams.toggle-setting', $team) }}" class="inline-block">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="setting" value="has_whatsapp">
                                            <label class="relative inline-flex items-center cursor-pointer" title="Habilitar/Deshabilitar WhatsApp para {{ $team->name }}">
                                                <input type="checkbox" onchange="this.form.submit()" class="sr-only peer" {{ ($team->settings['has_whatsapp'] ?? false) ? 'checked' : '' }}>
                                                <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
                                            </label>
                                        </form>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap">
                                        <div class="inline-flex items-center justify-end gap-1">
                                            <a href="{{ route('teams.edit', $team) }}" class="inline-flex items-center p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-all">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            
                                            <form action="{{ route('teams.destroy', $team) }}" method="POST" class="inline" id="delete-team-{{ $team->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" 
                                                    onclick="confirmDelete({{ $team->id }}, '{{ $team->name }}')"
                                                    class="inline-flex items-center p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($teams->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        {{ $teams->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function confirmDelete(teamId, teamName) {
            Swal.fire({
                title: '¿Eliminar equipo?',
                text: "Estás a punto de eliminar el equipo '" + teamName + "'. Esta acción no se puede deshacer y se borrarán todas sus tareas y datos asociados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Sí, eliminar equipo',
                cancelButtonText: 'Cancelar',
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#ffffff',
                color: document.documentElement.classList.contains('dark') ? '#ffffff' : '#111827',
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-team-' + teamId).submit();
                }
            });
        }
    </script>
    @endpush
</x-app-layout>
