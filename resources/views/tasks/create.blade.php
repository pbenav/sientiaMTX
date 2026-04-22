<x-app-layout>
    @section('title', __('tasks.create') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl ?? route('teams.dashboard', $team) }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                @include('teams.partials.breadcrumb')
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">{{ __('tasks.create') }}</h1>
                    <div class="flex items-center gap-2">
                        <button type="button" onclick="importFromClipboard()" class="text-[10px] font-black uppercase tracking-widest text-emerald-600 dark:text-emerald-400 hover:text-emerald-700 dark:hover:text-emerald-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl border border-emerald-100 dark:border-emerald-500/20 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            {{ __('Pegar JSON') }}
                        </button>
                        <button type="button" onclick="importTask()" class="text-[10px] font-black uppercase tracking-widest text-violet-600 dark:text-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-colors flex items-center gap-1.5 px-3 py-1.5 bg-violet-50 dark:bg-violet-900/30 rounded-xl border border-violet-100 dark:border-violet-500/20 shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0l-4 4m4-4v12" />
                            </svg>
                            {{ __('Cargar Archivo (.json)') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
            <form method="POST" action="{{ route('teams.tasks.store', $team) }}" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <!-- Title -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none transition-all"
                        placeholder="{{ __('tasks.name') }}...">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Assignment Mode -->
                <div class="mb-6 bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700">
                    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3">{{ __('tasks.assignment_mode') ?? 'Modo de Asignación (Si delegas a otros)' }}</label>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="relative flex">
                            <input type="radio" id="mode_shared" name="assignment_mode" value="shared" class="peer sr-only" {{ old('assignment_mode', 'shared') === 'shared' ? 'checked' : '' }}>
                            <label for="mode_shared" class="cursor-pointer w-full p-4 bg-white dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-800 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-950/30 transition-all flex gap-3 hover:border-violet-300 dark:hover:border-violet-700/50 group">
                                <div class="mt-0.5 w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 group-hover:border-violet-400 peer-checked:border-violet-500 peer-checked:bg-violet-500 flex items-center justify-center shrink-0 transition-all">
                                    <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-all"></div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white mb-1">{{ __('tasks.shared_task') ?? 'Tarea Compartida (Colaborativa)' }}</span>
                                    <span class="text-[11px] text-gray-500 leading-tight">{{ __('tasks.shared_hint') ?? 'Es UNA única tarea. Todos trabajan sobre ella, comparten el progreso y el tiempo se suma.' }}</span>
                                </div>
                            </label>
                        </div>
                        <div class="relative flex">
                            <input type="radio" id="mode_distributed" name="assignment_mode" value="distributed" class="peer sr-only" {{ old('assignment_mode') === 'distributed' ? 'checked' : '' }}>
                            <label for="mode_distributed" class="cursor-pointer w-full p-4 bg-white dark:bg-gray-900 border-2 border-gray-100 dark:border-gray-800 rounded-xl peer-checked:border-indigo-500 peer-checked:bg-indigo-50 dark:peer-checked:bg-indigo-950/30 transition-all flex gap-3 hover:border-indigo-300 dark:hover:border-indigo-700/50 group">
                                <div class="mt-0.5 w-5 h-5 rounded-full border-2 border-gray-300 dark:border-gray-600 group-hover:border-indigo-400 peer-checked:border-indigo-500 peer-checked:bg-indigo-500 flex items-center justify-center shrink-0 transition-all">
                                    <div class="w-2 h-2 rounded-full bg-white opacity-0 peer-checked:opacity-100 transition-all"></div>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-sm font-bold text-gray-900 dark:text-white mb-1">{{ __('tasks.distributed_task') ?? 'Plan Maestro (Distribución)' }}</span>
                                    <span class="text-[11px] text-gray-500 leading-tight">{{ __('tasks.distributed_hint') ?? 'Crea un contenedor de solo-lectura y genera una copia (instancia) independiente para cada miembro.' }}</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <x-markdown-editor 
                        name="description" 
                        id="description"
                        :value="old('description')"
                        :label="__('tasks.description')"
                        rows="4"
                        :upload-url="route('teams.forum.upload_image', $team)"
                    />
                </div>

                <!-- Observations (Markdown) -->
                <div>
                    <x-markdown-editor 
                        name="observations" 
                        id="observations"
                        :value="old('observations')"
                        :label="__('tasks.observations')"
                        rows="4"
                        :upload-url="route('teams.forum.upload_image', $team)"
                    />
                </div>

                <!-- Priority + Urgency (the Eisenhower axes) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                            {{ __('tasks.priority') }}
                        </label>
                        <select name="priority" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                            {{ __('tasks.urgency') }}
                        </label>
                        <select name="urgency" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('urgency')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Quadrant preview -->
                <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden transition-all">
                    <span class="font-semibold" id="qp-label"></span>
                    <span class="text-gray-400 ml-1" id="qp-desc"></span>
                </div>

                <!-- Service Dependency -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('Dependencia de Servicio') }}
                    </label>
                    <select name="service_id" 
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="">{{ __('Sin dependencia externa') }}</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}" 
                                {{ old('service_id') == $service->id ? 'selected' : '' }}>
                                {{ $service->icon }} {{ $service->name }} 
                                ({{ $service->getStatusLabel() }})
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1.5 text-[10px] text-gray-500 italic">
                        {{ __('Si el servicio asociado cae, la tarea se marcará automáticamente como bloqueada.') }}
                    </p>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4 font-mono">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date', now()->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Dependency -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.dependency') ?? 'Dependencia (Tarea Padre)' }}
                    </label>
                    <select name="parent_id" id="parent_id_select" style="display: none;"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="">{{ __('tasks.no_dependency') ?? 'Sin dependencia' }}</option>
                        @foreach ($tasks as $t)
                            <option value="{{ $t->id }}" {{ old('parent_id') == $t->id ? 'selected' : '' }}
                                data-assignee="{{ $t->assignedUser ? $t->assignedUser->name : __('tasks.unassigned') }}">
                                {{ $t->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Visibility -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.visibility') }}
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="visibility" value="public" class="peer sr-only"
                                {{ old('visibility', 'private') === 'public' ? 'checked' : '' }}>
                            <div
                                class="w-full p-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-950/30 transition-all flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-violet-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-bold text-gray-900 dark:text-white">{{ __('tasks.public') }}</span>
                                    <span class="text-[10px] text-gray-500">{{ __('tasks.public_hint') }}</span>
                                </div>
                            </div>
                        </label>
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="visibility" value="private" class="peer sr-only"
                                {{ old('visibility', 'private') === 'private' ? 'checked' : '' }}>
                            <div
                                class="w-full p-3 bg-gray-50 dark:bg-gray-800 border-2 border-gray-100 dark:border-gray-700 rounded-xl peer-checked:border-amber-500 peer-checked:bg-amber-50 dark:peer-checked:bg-amber-950/30 transition-all flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-amber-600 shadow-sm border border-gray-100 dark:border-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-bold text-gray-900 dark:text-white">{{ __('tasks.private') }}</span>
                                    <span class="text-[10px] text-gray-500">{{ __('tasks.private_hint') }}</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Autoprogrammable (Recurrence) -->
                <div x-data="{ 
                    isAutoprogrammable: {{ old('is_autoprogrammable', 0) ? 'true' : 'false' }},
                    frequency: '{{ old('autoprogram_settings.frequency', 'daily') }}',
                    labels: {
                        'daily': '{{ __("tasks.days") }}',
                        'weekly': '{{ __("tasks.weeks") }}',
                        'monthly': '{{ __("tasks.months") }}',
                        'yearly': '{{ __("tasks.years") }}'
                    }
                }" class="bg-violet-50/30 dark:bg-gray-900/40 backdrop-blur-md border border-violet-100 dark:border-violet-500/20 rounded-2xl p-6 shadow-sm dark:shadow-[0_0_20px_-12px_rgba(139,92,246,0.3)] transition-all">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('tasks.autoprogrammable') }}</span>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('tasks.autoprogrammable_hint') }}</span>
                            </div>
                        </div>
                        
                        <!-- Segmented Control -->
                        <div class="flex p-1 bg-gray-200 dark:bg-gray-950/50 rounded-xl w-fit self-start sm:self-center border border-transparent dark:border-gray-800">
                            <button type="button" @click="isAutoprogrammable = false" 
                                :class="!isAutoprogrammable ? 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                {{ __('tasks.disabled') }}
                            </button>
                            <button type="button" @click="isAutoprogrammable = true" 
                                :class="isAutoprogrammable ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                                {{ __('tasks.active') }}
                            </button>
                        </div>
                        <input type="hidden" name="is_autoprogrammable" :value="isAutoprogrammable ? 1 : 0">
                    </div>

                    <div x-show="isAutoprogrammable" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform -translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 border-t border-violet-100/50 dark:border-violet-500/10">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('tasks.frequency') ?? 'Frecuencia' }}
                                </label>
                                <select name="autoprogram_settings[frequency]" x-model="frequency" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                    <option value="daily">{{ __('tasks.daily') ?? 'Diaria' }}</option>
                                    <option value="weekly">{{ __('tasks.weekly') ?? 'Semanal' }}</option>
                                    <option value="monthly">{{ __('tasks.monthly') ?? 'Mensual' }}</option>
                                    <option value="yearly">{{ __('tasks.yearly') ?? 'Anual' }}</option>
                                </select>
                            </div>
                            <div x-show="frequency === 'weekly'" class="col-span-2 space-y-3 pb-2" x-transition>
                                <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ __('tasks.days_of_week') ?? 'Días de la semana' }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach(['1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S', '7' => 'D'] as $val => $label)
                                        <label class="cursor-pointer">
                                            <input type="checkbox" name="autoprogram_settings[days][]" value="{{ $val }}" 
                                                {{ in_array($val, old('autoprogram_settings.days', [])) ? 'checked' : '' }}
                                                class="peer sr-only">
                                            <div class="w-9 h-9 rounded-xl border-2 border-gray-100 dark:border-gray-800 flex items-center justify-center text-xs font-black text-gray-400 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/30 peer-checked:text-violet-600 transition-all hover:border-violet-200 shadow-sm">
                                                {{ $label }}
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <p class="text-[9px] text-gray-400 italic">La tarea se generará en cada uno de los días seleccionados.</p>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ __('tasks.interval') ?? 'Repetir cada' }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="autoprogram_settings[interval]" value="1" min="1" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all">
                                    <span class="text-xs font-medium text-gray-500 w-12" x-text="labels[frequency]">días</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ __('tasks.lead_time') ?? 'Antelación de creación (despertar)' }}
                            </label>
                            <div class="flex items-center gap-3">
                                <input type="number" name="autoprogram_settings[lead_value]" value="7" min="1" class="w-24 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                <select name="autoprogram_settings[lead_unit]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    <option value="hours">{{ __('tasks.hours') ?? 'Horas' }}</option>
                                    <option value="days" selected>{{ __('tasks.days') ?? 'Días' }}</option>
                                    <option value="weeks">{{ __('tasks.weeks') ?? 'Semanas' }}</option>
                                    <option value="months">{{ __('tasks.months') ?? 'Meses' }}</option>
                                </select>
                                <span class="text-[10px] text-gray-400 italic">{{ __('tasks.lead_time_hint') ?? 'antes de la fecha señalada' }}</span>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                                </svg>
                                {{ __('tasks.limit') ?? 'Terminar' }}
                            </label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center">
                                        <input type="radio" name="autoprogram_settings[limit_type]" value="count" checked class="peer sr-only">
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                        <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.after_n_times') ?? 'Después de' }}</span>
                                    <input type="number" name="autoprogram_settings[limit_value_count]" value="5" min="1" class="w-16 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all">
                                    <span class="text-xs text-gray-500">{{ __('tasks.times') ?? 'veces' }}</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center">
                                        <input type="radio" name="autoprogram_settings[limit_type]" value="date" class="peer sr-only">
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                        <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.on_date') ?? 'El día' }}</span>
                                    <input type="date" name="autoprogram_settings[limit_value_date]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="autoprogram_settings[skip_weekends]" value="1" checked class="peer sr-only">
                                    <div class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.skip_weekends') ?? 'Saltar fines de semana' }}</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="autoprogram_settings[sequential]" value="1" checked class="peer sr-only">
                                    <div class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.sequential_dependencies') ?? 'Dependencias secuenciales (Gantt)' }}</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Gamification Features (Resiliencia Colectiva) -->
                <div class="bg-amber-50/20 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-6 space-y-6">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 shadow-sm border border-amber-200/20">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">Impacto y Bienestar</h4>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                        <!-- Skill Category Selection -->
                        <div x-data="{ selectedSkills: @json(old('skills', [])) }">
                            <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                                Árbol de Capacidades (Selección Múltiple)
                            </label>
                            <select name="skills[]" multiple class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:border-amber-500 outline-none transition-all text-gray-900 dark:text-white h-32">
                                @foreach($skills as $skill)
                                    <option value="{{ $skill->id }}" :selected="selectedSkills.includes('{{ $skill->id }}') || selectedSkills.includes({{ $skill->id }})">
                                        {{ $skill->name }} ({{ $skill->category }})
                                    </option>
                                @endforeach
                            </select>
                            <p class="text-[10px] text-gray-500 mt-2">Mantén presionado Ctrl (o Cmd) para seleccionar varias habilidades.</p>
                        </div>

                        <!-- Cognitive Load (Energy Drain) -->
                        <div x-data="{ load: 1 }">
                            <label class="flex items-center justify-between text-sm font-bold text-gray-700 dark:text-gray-300 mb-4">
                                <span>Carga Cognitiva (Drenaje de Energía)</span>
                                <span :class="{
                                    'text-emerald-500': load == 1,
                                    'text-blue-500': load == 2,
                                    'text-amber-500': load == 3,
                                    'text-orange-500': load == 4,
                                    'text-red-500': load == 5
                                }" class="font-black tabular-nums transition-colors" x-text="load">1</span>
                            </label>
                            <input type="range" name="cognitive_load" min="1" max="5" step="1" x-model="load"
                                class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500">
                            <div class="flex justify-between text-[10px] text-gray-400 mt-2 font-black uppercase tracking-tighter">
                                <span>Baja</span>
                                <span>Media</span>
                                <span>Extrema</span>
                            </div>
                        </div>

                        <!-- Skill Tree & Backstage -->
                        <div class="space-y-4">
                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-violet-300 dark:hover:border-violet-500/50 transition-all group">
                                <input type="checkbox" name="is_out_of_skill_tree" value="1" class="peer sr-only">
                                <div class="w-5 h-5 rounded border-2 border-gray-200 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Fuera de mi Skill Tree</span>
                                    <span class="text-[9px] text-gray-400 uppercase font-black">+ Puntos de Resiliencia</span>
                                </div>
                            </label>

                            <label class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-500/50 transition-all group">
                                <input type="checkbox" name="is_backstage" value="1" class="peer sr-only">
                                <div class="w-5 h-5 rounded border-2 border-gray-200 dark:border-gray-600 peer-checked:bg-emerald-600 peer-checked:border-emerald-600 transition-all flex items-center justify-center text-white">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Backstage / Preparación</span>
                                    <span class="text-[9px] text-gray-400 uppercase font-black">Visibiliza el esfuerzo invisible</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>


                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @if ($users->count() > 0)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                    </svg>
                                    {{ __('tasks.assigned_to') }}
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" onclick="window.selectAllUsers(true)" class="text-[10px] font-black uppercase tracking-widest text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors">
                                        {{ __('tasks.all') ?? 'Todo' }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-700 text-[10px]">|</span>
                                    <button type="button" onclick="window.selectAllUsers(false)" class="text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                        {{ __('tasks.none') ?? 'Nada' }}
                                    </button>
                                </div>
                            </div>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-3 p-2 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-100 dark:hover:border-gray-700 shadow-sm hover:shadow-md">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            id="user_checkbox_{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', [])) ? 'checked' : '' }}
                                            class="user-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20 transition-all">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 leading-tight group-hover:text-gray-900 dark:group-hover:text-white transition-colors truncate">{{ $user->name }}</span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $user->email }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                {{ __('tasks.assign_groups') }}
                            </label>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-100 dark:hover:border-gray-700 shadow-sm hover:shadow-md">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            data-members="{{ json_encode($group->users->pluck('id')) }}"
                                            onchange="window.syncGroupMembers(this)"
                                            {{ in_array($group->id, old('assigned_groups', [])) ? 'checked' : '' }}
                                            class="group-checkbox accent-indigo-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500/20 transition-all">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 leading-tight group-hover:text-gray-900 dark:group-hover:text-white transition-colors truncate">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                                                {{ $group->users->count() }} {{ __('teams.members') }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Integrated Attachments Section -->
                <div class="pt-8 border-t border-gray-100 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('tasks.attachments') }}</h3>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('tasks.attachments_hint') ?? 'Sube archivos relevantes para esta tarea' }}</p>
                            </div>
                        </div>
                                <div class="flex items-center gap-2">
                                    @php
                                        $isGoogleLinked = auth()->user()->teams()->where('team_id', $team->id)->wherePivotNotNull('google_token')->exists();
                                    @endphp
                                    @if($isGoogleLinked)
                                        <button type="button" onclick="openDrivePicker()"
                                            class="bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 text-xs font-bold px-4 py-2 rounded-xl border border-blue-200 dark:border-blue-800 transition-all shadow-sm flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                                            </svg>
                                            {{ __('Google Drive') }}
                                        </button>
                                    @endif
                                    <label class="cursor-pointer bg-violet-50 dark:bg-violet-900/20 hover:bg-violet-100 dark:hover:bg-violet-900/40 text-violet-600 dark:text-violet-400 px-4 py-2 rounded-xl text-xs font-bold transition-all border border-violet-200 dark:border-violet-500/20 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                                        </svg>
                                        {{ __('tasks.add_attachment') }}
                                        <input type="file" name="attachments[]" multiple class="hidden" onchange="updateFileList(this)">
                                    </label>
                                </div>
                                <input type="hidden" name="drive_attachments" id="drive_attachments_input">
                    </div>

                    <div id="file-list-preview" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pb-4">
                        <!-- Temporary list for selected files -->
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('teams.dashboard', $team) }}"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-all font-medium">{{ __('tasks.back') }}</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">
                        {{ __('tasks.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-control { border-radius: 0.75rem !important; border-color: #e5e7eb !important; background-color: #f9fafb !important; padding: 0.625rem 1rem !important; transition: all 0.2s; }
        .dark .ts-control { background-color: #1f2937 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .ts-control:focus { border-color: #7c3aed !important; ring-color: rgba(124, 58, 237, 0.2) !important; }
        .ts-dropdown { border-radius: 1rem !important; border-color: #e5e7eb !important; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important; margin-top: 5px !important; margin-bottom: 20px !important; }
        .dark .ts-dropdown { background-color: #111827 !important; border-color: #374151 !important; color: #f3f4f6 !important; }
        .ts-dropdown .active { background-color: #7c3aed !important; color: white !important; }
        .ts-dropdown .option { padding: 0.5rem 1rem !important; }
        
        /* Ocultar el select original para evitar duplicidad if TomSelect tarda un instante */
        #parent_id_select { display: none; }
    </style>

    <script>
        // --- Global Helpers for Assignments (Direct Scope for Compatibility) ---
        window.selectAllUsers = function(status) {
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                cb.checked = status;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
        };

        window.syncGroupMembers = function(groupCb) {
            try {
                const memberIds = JSON.parse(groupCb.getAttribute('data-members'));
                const isChecked = groupCb.checked;
                memberIds.forEach(id => {
                    const userCb = document.getElementById('user_checkbox_' + id);
                    if (userCb) {
                        userCb.checked = isChecked;
                        userCb.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                });
            } catch (err) {
                console.error('Group sync error:', err);
            }
        };

        window.applyImportData = function(data) {
            try {
                if (!data.type || !data.type.startsWith('sientia_task')) {
                    throw new Error('El formato no es un JSON de Sientia MTX válido.');
                }
                const task = data.task;
                
                // Title
                document.querySelector('[name="title"]').value = task.title || '';
                
                // Description (Rich Editor)
                const descEl = document.getElementById('description');
                if (descEl) {
                    if (window.EasyMDEInstances && window.EasyMDEInstances['description']) {
                        window.EasyMDEInstances['description'].value(task.description || '');
                    } else {
                        descEl.value = task.description || '';
                    }
                }

                // Observations (Rich Editor)
                const obsEl = document.getElementById('observations');
                if (obsEl) {
                    if (window.EasyMDEInstances && window.EasyMDEInstances['observations']) {
                        window.EasyMDEInstances['observations'].value(task.observations || '');
                    } else {
                        obsEl.value = task.observations || '';
                    }
                }
                
                if (task.priority) document.querySelector('[name="priority"]').value = task.priority;
                if (task.urgency) document.querySelector('[name="urgency"]').value = task.urgency;
                
                // Visibility
                const visRadio = document.querySelector(`input[name="visibility"][value="${task.visibility}"]`);
                if (visRadio) visRadio.checked = true;

                // Cog Load
                const cogLoad = document.querySelector('[name="cognitive_load"]');
                if (cogLoad) {
                    cogLoad.value = task.cognitive_load || 1;
                    cogLoad.dispatchEvent(new Event('input'));
                }

                // Checkboxes
                if (document.querySelector('[name="is_out_of_skill_tree"]'))
                    document.querySelector('[name="is_out_of_skill_tree"]').checked = !!task.is_out_of_skill_tree;
                
                if (document.querySelector('[name="is_backstage"]'))
                    document.querySelector('[name="is_backstage"]').checked = !!task.is_backstage;

                // Trigger any change listeners
                document.querySelector('[name="priority"]').dispatchEvent(new Event('change'));

                Swal.fire({
                    icon: 'success',
                    title: '¡Datos inyectados!',
                    text: 'El formulario se ha rellenado con éxito.',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false
                });
            } catch (err) {
                Swal.fire('Error de Importación', err.message, 'error');
            }
        };

        window.importFromClipboard = async function() {
            try {
                const text = await navigator.clipboard.readText();
                if (!text) throw new Error('El portapapeles está vacío.');
                const data = JSON.parse(text);
                window.applyImportData(data);
            } catch (err) {
                Swal.fire('Error al pegar', 'Asegúrate de haber copiado el JSON correcto. ' + err.message, 'error');
            }
        };

        window.importTask = function() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = 'application/json';
            input.onchange = e => {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = event => {
                    try {
                        const data = JSON.parse(event.target.result);
                        window.applyImportData(data);
                    } catch (err) {
                        Swal.fire('Error', 'Archivo JSON corrupto.', 'error');
                    }
                };
                reader.readAsText(file);
            };
            input.click();
        };

        document.addEventListener('DOMContentLoaded', function() {
            // --- Eisenhower Matrix Preview ---
            const quadrantData = @json(__('tasks.quadrants'));
            const priorityEl = document.querySelector('[name="priority"]');
            const urgencyEl = document.querySelector('[name="urgency"]');
            const preview = document.getElementById('quadrant-preview');
            const highLevels = ['high', 'critical'];

            const qColors = {
                1: { border: 'border-red-200 dark:border-red-700', bg: 'bg-red-50 dark:bg-red-950/30', text: 'text-red-600 dark:text-red-300' },
                2: { border: 'border-blue-200 dark:border-blue-700', bg: 'bg-blue-50 dark:bg-blue-950/30', text: 'text-blue-600 dark:text-blue-300' },
                3: { border: 'border-amber-200 dark:border-amber-700', bg: 'bg-amber-50 dark:bg-amber-950/30', text: 'text-amber-600 dark:text-amber-300' },
                4: { border: 'border-gray-200 dark:border-gray-700', bg: 'bg-gray-50 dark:bg-gray-800', text: 'text-gray-600 dark:text-gray-300' },
            };

            function updatePreview() {
                if (!priorityEl || !urgencyEl || !preview) return;
                const imp = highLevels.includes(priorityEl.value);
                const urg = highLevels.includes(urgencyEl.value);
                let q = 4;
                if (imp && urg) q = 1;
                else if (imp) q = 2;
                else if (urg) q = 3;

                const cfg = qColors[q];
                preview.className = `rounded-xl border p-3 text-xs transition-all shadow-sm dark:shadow-none ${cfg.border} ${cfg.bg}`;
                preview.classList.remove('hidden');
                document.getElementById('qp-label').className = `font-bold uppercase tracking-wider ${cfg.text}`;
                document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
                document.getElementById('qp-desc').className = `text-gray-500 dark:text-gray-400 ml-1 italic font-medium`;
                document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
            }

            priorityEl?.addEventListener('change', updatePreview);
            urgencyEl?.addEventListener('change', updatePreview);
            updatePreview();

            // --- TomSelect for Searchable Dependencies ---
            const parenSelectEl = document.getElementById('parent_id_select');
            if (parenSelectEl) {
                new TomSelect("#parent_id_select", {
                    create: false,
                    sortField: { field: "text", direction: "asc" },
                    placeholder: '{{ __("tasks.search_task") ?? "Buscar tarea..." }}',
                    render: {
                        option: function(data, escape) {
                            return '<div class="flex flex-col py-0.5">' +
                                '<div class="flex items-center gap-2">' +
                                    '<span class="text-[10px] font-mono font-black px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 border border-gray-200 dark:border-gray-600">#' + escape(data.value) + '</span>' +
                                    '<span class="font-bold text-gray-900 dark:text-white">' + escape(data.text) + '</span>' +
                                '</div>' +
                                '<span class="text-[10px] text-gray-500 dark:text-gray-400 font-medium mt-1">' + 
                                    '<i class="inline-block w-1 h-1 rounded-full bg-violet-400 mr-1.5 opacity-60"></i>' + 
                                    escape(data.assignee) + 
                                '</span>' +
                            '</div>';
                        },
                        item: function(data, escape) {
                            return '<div class="flex items-center gap-2">' + 
                                '<span class="text-[9px] font-mono font-bold text-gray-400">#' + escape(data.value) + '</span>' +
                                '<span>' + escape(data.text) + '</span>' +
                                '<span class="text-[10px] text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 px-1.5 py-0.5 rounded border border-gray-200 dark:border-gray-700 font-mono">@' + escape(data.assignee) + '</span>' +
                            '</div>';
                        }
                    }
                });
            }
        });

        let selectedDriveFiles = [];

        function openDrivePicker(folderId = 'root') {
            Swal.fire({
                title: '{{ __("Google Drive") }}',
                html: `
                    <div class="flex flex-col gap-4">
                        <div id="drive-contents" class="max-h-64 overflow-y-auto flex flex-col gap-1 text-left">
                            <div class="flex items-center justify-center py-8">
                                <svg class="animate-spin h-8 w-8 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                `,
                width: '32rem',
                showConfirmButton: false,
                showCancelButton: true,
                cancelButtonText: '{{ __("Cerrar") }}',
                didOpen: () => {
                    loadDriveFolder(folderId);
                },
                background: document.documentElement.classList.contains('dark') ? '#111827' : '#fff',
                color: document.documentElement.classList.contains('dark') ? '#fff' : '#111827'
            });
        }

        function loadDriveFolder(folderId) {
            const container = document.getElementById('drive-contents');
            const teamId = '{{ $team->id }}';
            
            fetch(`{{ route('google.drive.list') }}?team_id=${teamId}&folderId=${folderId}`)
                .then(response => response.json())
                .then(data => {
                    if (!data.files) {
                        container.innerHTML = '<p class="text-center py-4 text-gray-500">No se pudieron cargar los archivos.</p>';
                        return;
                    }

                    container.innerHTML = '';
                    
                    if (folderId !== 'root') {
                        const backBtn = document.createElement('button');
                        backBtn.className = 'p-2 text-blue-600 font-bold text-sm mb-2';
                        backBtn.innerHTML = '⬅️ {{ __("Volver") }}';
                        backBtn.onclick = () => loadDriveFolder('root');
                        container.appendChild(backBtn);
                    }

                    data.files.forEach(file => {
                        const isFolder = file.mimeType === 'application/vnd.google-apps.folder';
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'flex items-center justify-between p-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl w-full text-left';
                        btn.innerHTML = `
                            <div class="flex items-center gap-3">
                                <span>${isFolder ? '📁' : '📄'}</span>
                                <div class="flex flex-col min-w-0">
                                    <span class="text-xs font-bold truncate">${file.name}</span>
                                    <span class="text-[9px] text-gray-400">${file.mimeType.split('.').pop()}</span>
                                </div>
                            </div>
                        `;
                        btn.onclick = () => {
                            if (isFolder) {
                                loadDriveFolder(file.id);
                            } else {
                                selectDriveFile(file);
                            }
                        };
                        container.appendChild(btn);
                    });
                });
        }

        function selectDriveFile(file) {
            if (!selectedDriveFiles.some(f => f.id === file.id)) {
                selectedDriveFiles.push(file);
                updateFileListDisplays();
                Swal.close();
            } else {
                Swal.fire('Info', 'Este archivo ya está seleccionado', 'info');
            }
        }

        function updateFileListDisplays() {
            const list = document.getElementById('file-list-preview');
            // We'll regenerate the whole list including drive files
            const driveInput = document.getElementById('drive_attachments_input');
            driveInput.value = JSON.stringify(selectedDriveFiles);

            // Keep existing logic for local files if possible, or just merge
            // For now, let's just add drive files to the UI list
            renderFiles();
        }

        function renderFiles() {
            const list = document.getElementById('file-list-preview');
            // Note: This won't show the local files again if we cleared it, 
            // so we should handle local files and drive files together.
            // I'll update updateFileList to also call renderFiles.
        }

        window.updateFileList = function(input) {
            renderFilesUI(input);
        }

        function renderFilesUI(fileInput) {
            const list = document.getElementById('file-list-preview');
            list.innerHTML = '';

            // Drive Files
            selectedDriveFiles.forEach((file, index) => {
                const div = document.createElement('div');
                div.className = 'flex items-center justify-between p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-800';
                div.innerHTML = `
                    <div class="flex items-center gap-3 overflow-hidden">
                        <div class="w-8 h-8 rounded-lg bg-blue-100 dark:bg-blue-800 flex items-center justify-center text-blue-600">
                             <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5H7.71zM9.73 15L6.3 21h13.12l3.43-6H9.73zM18.74 3.5l-6.55 11.5 3.43 6L22.18 9.5l-3.44-6z"/>
                            </svg>
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 truncate">${file.name}</span>
                            <span class="text-[9px] text-blue-400 uppercase font-bold">Google Drive</span>
                        </div>
                    </div>
                    <button type="button" onclick="removeDriveFile(${index})" class="text-red-500 hover:text-red-700 p-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                `;
                list.appendChild(div);
            });

            // Local Files
            if (fileInput && fileInput.files.length > 0) {
                Array.from(fileInput.files).forEach(file => {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50';
                    div.innerHTML = `
                        <div class="w-8 h-8 rounded-lg bg-white dark:bg-gray-900 flex items-center justify-center text-gray-400 font-mono text-[9px]">
                            ${file.name.split('.').pop().toUpperCase()}
                        </div>
                        <div class="flex flex-col min-w-0">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-200 truncate">${file.name}</span>
                            <span class="text-[10px] text-gray-400">${(file.size / 1024).toFixed(1)} KB</span>
                        </div>
                    `;
                    list.appendChild(div);
                });
            }
        }

        window.removeDriveFile = function(index) {
            selectedDriveFiles.splice(index, 1);
            document.getElementById('drive_attachments_input').value = JSON.stringify(selectedDriveFiles);
            renderFilesUI(document.querySelector('input[name="attachments[]"]'));
        }

        function parsePHPSize(size) {
            const unit = size.slice(-1).toUpperCase();
            const value = parseFloat(size);
            switch (unit) {
                case 'G': return value * 1024 * 1024 * 1024;
                case 'M': return value * 1024 * 1024;
                case 'K': return value * 1024;
                default: return value;
            }
        }
    </script>
    @endpush
</x-app-layout>
