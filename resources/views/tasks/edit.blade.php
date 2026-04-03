<x-app-layout>
    @section('title', __('tasks.edit') . ': ' . $task->title)
    @php
        $layout = auth()->check() ? (auth()->user()->layout ?: 'horizontal') : request()->cookie('layout', 'horizontal');
    @endphp

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ $backUrl ?? route('teams.tasks.show', [$team, $task]) }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                @include('teams.partials.breadcrumb')
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">{{ __('tasks.edit') }}</h1>
            </div>
        </div>
    </x-slot>

    <div id="task-edit-container" 
        class="layout-{{ $layout }} max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-5 transition-all duration-300"
        :class="{ 
            'sidebar-is-open': sidebarOpen, 
            'sidebar-is-closed': !sidebarOpen,
            'max-w-7xl': $data.isDualView 
        }"
        x-data="{ isDualView: false }">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
            <form method="POST" action="{{ route('teams.tasks.update', [$team, $task]) }}" class="space-y-6">
                @csrf @method('PATCH')

                @if ($team->isCoordinator(auth()->user()))
                    <div class="mb-6">
                        <label
                            class="block text-sm font-bold text-violet-600 dark:text-violet-400 mb-2 uppercase tracking-wide">{{ __('tasks.owner') }}</label>
                        <select name="created_by_id" required
                            class="w-full bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer font-medium">
                            @foreach ($allMembers as $u)
                                <option value="{{ $u->id }}"
                                    {{ old('created_by_id', $task->created_by_id) == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('created_by_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title', $task->title) }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400">{{ old('description', $task->description) }}</textarea>
                </div>

                <!-- Observations (Markdown) -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.observations') }}</label>
                    <textarea name="observations" id="observations"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">{{ old('observations', $task->observations) }}</textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.priority') }}</label>
                        <select name="priority" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', $task->priority) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.urgency') }}</label>
                        <select name="urgency" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', $task->urgency) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.status') }}</label>
                        <select name="status" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.statuses') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('status', $task->status) === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Quadrant preview (calculated in JS) -->
                <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden mb-6 transition-all">
                    <span class="font-semibold" id="qp-label"></span>
                    <span class="text-gray-400 ml-1" id="qp-desc"></span>
                </div>

                <!-- Visibility -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.visibility') }}
                    </label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative flex cursor-pointer">
                            <input type="radio" name="visibility" value="public" class="peer sr-only"
                                {{ old('visibility', $task->visibility) === 'public' ? 'checked' : '' }}>
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
                                {{ old('visibility', $task->visibility) === 'private' ? 'checked' : '' }}>
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

                <!-- Dependency -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.dependency') ?? 'Dependencia (Tarea Padre)' }}
                    </label>
                    <select name="parent_id"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="">{{ __('tasks.no_dependency') ?? 'Sin dependencia' }}</option>
                        @foreach ($tasks as $t)
                            <option value="{{ $t->id }}"
                                {{ old('parent_id', $task->parent_id) == $t->id ? 'selected' : '' }}>
                                {{ $t->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Autoprogrammable (Recurrence) -->
                @php
                    $apSettings = $task->autoprogram_settings ?? [];
                    $freq = old('autoprogram_settings.frequency', $apSettings['frequency'] ?? 'daily');
                    $interval = old('autoprogram_settings.interval', $apSettings['interval'] ?? 1);
                    $limitType = old('autoprogram_settings.limit_type', $apSettings['limit_type'] ?? 'count');
                    $limitValCount = old('autoprogram_settings.limit_value_count', ($limitType === 'count' ? ($apSettings['limit_value'] ?? 5) : 5));
                    $limitValDate = old('autoprogram_settings.limit_value_date', ($limitType === 'date' ? $apSettings['limit_value'] : ''));
                    $skipW = old('autoprogram_settings.skip_weekends', $apSettings['skip_weekends'] ?? true);
                    $seq = old('autoprogram_settings.sequential', $apSettings['sequential'] ?? true);
                @endphp
                <div x-data="{ 
                    isAutoprogrammable: {{ old('is_autoprogrammable', $task->is_autoprogrammable) ? 'true' : 'false' }},
                    frequency: '{{ $freq }}',
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
                                :class="!isAutoprogrammable ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
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
                                    <option value="daily" {{ $freq === 'daily' ? 'selected' : '' }}>{{ __('tasks.daily') ?? 'Diaria' }}</option>
                                    <option value="weekly" {{ $freq === 'weekly' ? 'selected' : '' }}>{{ __('tasks.weekly') ?? 'Semanal' }}</option>
                                    <option value="monthly" {{ $freq === 'monthly' ? 'selected' : '' }}>{{ __('tasks.monthly') ?? 'Mensual' }}</option>
                                    <option value="yearly" {{ $freq === 'yearly' ? 'selected' : '' }}>{{ __('tasks.yearly') ?? 'Anual' }}</option>
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                    </svg>
                                    {{ __('tasks.interval') ?? 'Repetir cada' }}
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="autoprogram_settings[interval]" value="{{ $interval }}" min="1" class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all">
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
                                @php
                                    $lVal = $task->autoprogram_settings['lead_value'] ?? 7;
                                    $lUnit = $task->autoprogram_settings['lead_unit'] ?? 'days';
                                @endphp
                                <input type="number" name="autoprogram_settings[lead_value]" value="{{ $lVal }}" min="1" class="w-24 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                                <select name="autoprogram_settings[lead_unit]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                                    <option value="hours" {{ $lUnit === 'hours' ? 'selected' : '' }}>{{ __('tasks.hours') ?? 'Horas' }}</option>
                                    <option value="days" {{ $lUnit === 'days' ? 'selected' : '' }}>{{ __('tasks.days') ?? 'Días' }}</option>
                                    <option value="weeks" {{ $lUnit === 'weeks' ? 'selected' : '' }}>{{ __('tasks.weeks') ?? 'Semanas' }}</option>
                                    <option value="months" {{ $lUnit === 'months' ? 'selected' : '' }}>{{ __('tasks.months') ?? 'Meses' }}</option>
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
                                        <input type="radio" name="autoprogram_settings[limit_type]" value="count" {{ $limitType === 'count' ? 'checked' : '' }} class="peer sr-only">
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                        <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.after_n_times') ?? 'Después de' }}</span>
                                    <input type="number" name="autoprogram_settings[limit_value_count]" value="{{ $limitValCount }}" min="1" class="w-16 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all">
                                    <span class="text-xs text-gray-500">{{ __('tasks.times') ?? 'veces' }}</span>
                                </label>
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative flex items-center justify-center">
                                        <input type="radio" name="autoprogram_settings[limit_type]" value="date" {{ $limitType === 'date' ? 'checked' : '' }} class="peer sr-only">
                                        <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                        <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('tasks.on_date') ?? 'El día' }}</span>
                                    <input type="date" name="autoprogram_settings[limit_value_date]" value="{{ $limitValDate }}" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-4 pt-2">
                            <label class="flex items-center gap-2 cursor-pointer group">
                                <div class="relative flex items-center justify-center">
                                    <input type="checkbox" name="autoprogram_settings[skip_weekends]" value="1" {{ $skipW ? 'checked' : '' }} class="peer sr-only">
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
                                    <input type="checkbox" name="autoprogram_settings[sequential]" value="1" {{ $seq ? 'checked' : '' }} class="peer sr-only">
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

                <div class="grid grid-cols-2 gap-4 font-mono">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date"
                            value="{{ old('scheduled_date', $task->scheduled_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($users->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assigned_to') }}</label>
                            @php
                                $assignedIds = $task->assignedTo->pluck('id')->toArray();
                                if ($task->assigned_user_id && !in_array($task->assigned_user_id, $assignedIds)) {
                                    $assignedIds[] = $task->assigned_user_id;
                                }
                            @endphp
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', $assignedIds)) ? 'checked' : '' }}
                                            class="accent-violet-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <span
                                            class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assign_groups') }}</label>
                            @php $assignedGroupIds = $task->assignedGroups->pluck('id')->toArray(); @endphp
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            {{ in_array($group->id, old('assigned_groups', $assignedGroupIds)) ? 'checked' : '' }}
                                            class="accent-indigo-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <span
                                            class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $group->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <button type="button"
                            onclick="confirmDelete('delete-task-form', '{{ __('tasks.delete_confirm') }}')"
                            class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">{{ __('tasks.delete') }}</button>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-all font-medium">{{ __('tasks.back') }}</a>
                        <button type="submit"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">{{ __('tasks.save') }}</button>
                    </div>
                </div>
            </form>

            <form id="delete-task-form" method="POST" action="{{ route('teams.tasks.destroy', [$team, $task]) }}"
                class="hidden">
                @csrf @method('DELETE')
            </form>
        </div>
    </div>
    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <style>
        .EasyMDEContainer .CodeMirror {
            background: #f9fafb;
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            color: #111827;
        }

        .dark .EasyMDEContainer .CodeMirror {
            background: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .EasyMDEContainer .CodeMirror {
            resize: vertical;
        }

        .EasyMDEContainer .editor-toolbar {
            background: #f3f4f6;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            border-color: #e5e7eb;
        }

        .dark .EasyMDEContainer .editor-toolbar {
            background: #111827;
            border-color: #374151;
        }

        .dark .EasyMDEContainer .editor-toolbar button {
            color: #9ca3af;
        }

        .dark .EasyMDEContainer .editor-toolbar button:hover,
        .dark .EasyMDEContainer .editor-toolbar button.active {
            background: #374151;
            color: white;
        }

        /* Ajuste de coordenadas del editor para respetar el layout de Sientia */
        .EasyMDEContainer .CodeMirror-fullscreen {
            z-index: 40 !important;
            top: 64px !important;
            height: calc(100vh - 64px) !important;
            left: 0 !important;
            width: 100% !important;
            position: fixed !important;
        }

        .editor-toolbar.fullscreen {
            z-index: 41 !important;
            top: 64px !important;
            left: 0 !important;
            width: 100% !important;
            border-top: 1px solid #e5e7eb;
            position: fixed !important;
        }

        /* Panel de previsualización: respetamos su ancho nativo del 50% */
        .editor-preview-side {
            z-index: 42 !important;
            top: 64px !important;
            height: calc(100vh - 64px) !important;
            background: #fff !important;
        }

        .dark .editor-preview-side {
            background: #111827 !important;
            border-color: #374151 !important;
        }

        /* Si el layout es VERTICAL y el sidebar está abierto */
        @media (min-width: 1024px) {
            .layout-vertical.sidebar-is-open .EasyMDEContainer .CodeMirror-fullscreen,
            .layout-vertical.sidebar-is-open .editor-toolbar.fullscreen {
                left: 256px !important;
                width: calc(100% - 256px) !important;
            }

            /* La previsualización también debe encogerse para dejar sitio al sidebar si está abierto */
            .layout-vertical.sidebar-is-open .editor-preview-side {
                width: calc(50% - 128px) !important;
            }

            .layout-vertical.sidebar-is-closed .EasyMDEContainer .CodeMirror-fullscreen,
            .layout-vertical.sidebar-is-closed .editor-toolbar.fullscreen {
                left: 0 !important;
                width: 100% !important;
            }
        }

        /* Forzamos que en modo side-by-side los paneles internos NO hereden el ancho 100% */
        .CodeMirror-side-by-side {
            width: 50% !important;
        }
        .editor-preview-side {
            width: 50% !important;
        }

        /* Responsividad para móviles y tablets en Vista Dual */
        @media (max-width: 1023px) {
            .CodeMirror-side-by-side {
                flex-direction: column !important;
            }

            .CodeMirror-side-by-side > .CodeMirror-scroll,
            .CodeMirror-side-by-side > .editor-preview-side {
                width: 100% !important;
                height: 50% !important;
            }

            .CodeMirror-side-by-side > .editor-preview-side {
                border-left: none !important;
                border-top: 1px solid #e5e7eb;
            }

            .dark .CodeMirror-side-by-side > .editor-preview-side {
                border-top-color: #374151;
            }
        }

        .dark .editor-toolbar.fullscreen {
            border-color: #374151;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const easyMDE = new EasyMDE({
                element: document.getElementById('observations'),
                spellChecker: false,
                autosave: {
                    enabled: false,
                },
                status: false,
                minHeight: '150px',
                placeholder: 'Añade observaciones aquí...',
                toolbar: [
                    "bold", "italic", "heading", "|", 
                    "quote", "unordered-list", "ordered-list", "|", 
                    "link", "image", "table", "|", 
                    "preview", 
                    {
                        name: "side-by-side",
                        action: function(editor) {
                            EasyMDE.toggleSideBySide(editor);
                            // Comunicamos el estado a Alpine
                            const container = document.getElementById('task-edit-container');
                            if (container && container.__x) {
                                container.__x.$data.isDualView = editor.isSideBySideActive();
                            }
                        },
                        className: "fa fa-columns",
                        title: "Vista Dual",
                    },
                    "fullscreen", "|", "guide"
                ],
            });

            const quadrantData = @json(__('tasks.quadrants'));
            const priorityEl = document.querySelector('[name="priority"]');
            const urgencyEl = document.querySelector('[name="urgency"]');
            const preview = document.getElementById('quadrant-preview');
            const highLevels = ['high', 'critical'];

            const qColors = {
                1: {
                    border: 'border-red-200 dark:border-red-700',
                    bg: 'bg-red-50 dark:bg-red-950/30',
                    text: 'text-red-600 dark:text-red-300'
                },
                2: {
                    border: 'border-blue-200 dark:border-blue-700',
                    bg: 'bg-blue-50 dark:bg-blue-950/30',
                    text: 'text-blue-600 dark:text-blue-300'
                },
                3: {
                    border: 'border-amber-200 dark:border-amber-700',
                    bg: 'bg-amber-50 dark:bg-amber-950/30',
                    text: 'text-amber-600 dark:text-amber-300'
                },
                4: {
                    border: 'border-gray-200 dark:border-gray-700',
                    bg: 'bg-gray-50 dark:bg-gray-800',
                    text: 'text-gray-600 dark:text-gray-300'
                },
            };

            function updatePreview() {
                const imp = highLevels.includes(priorityEl.value);
                const urg = highLevels.includes(urgencyEl.value);
                let q = 4;
                if (imp && urg) q = 1;
                else if (imp) q = 2;
                else if (urg) q = 3;

                const cfg = qColors[q];
                preview.className =
                    `rounded-xl border p-3 text-xs transition-all shadow-sm dark:shadow-none mb-6 ${cfg.border} ${cfg.bg}`;
                preview.classList.remove('hidden');
                document.getElementById('qp-label').className = `font-bold uppercase tracking-wider ${cfg.text}`;
                document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
                document.getElementById('qp-desc').className =
                    `text-gray-500 dark:text-gray-400 ml-1 italic font-medium`;
                document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
            }

            priorityEl?.addEventListener('change', updatePreview);
            urgencyEl?.addEventListener('change', updatePreview);
            updatePreview();
        });
    </script>
</x-app-layout>
