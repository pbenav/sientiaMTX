<div class="space-y-6">
    <!-- Autoprogrammable (Recurrence) -->
    @php
        $apSettings = $activity->autoprogram_settings ?? [];
        $freq = old('autoprogram_settings.frequency', $apSettings['frequency'] ?? 'daily');
        $interval = old('autoprogram_settings.interval', $apSettings['interval'] ?? 1);
        $limitType = old('autoprogram_settings.limit_type', $apSettings['limit_type'] ?? 'count');
        $limitValCount = old(
            'autoprogram_settings.limit_value_count',
            $limitType === 'count' ? $apSettings['limit_value'] ?? 5 : 5,
        );
        $limitValDate = old(
            'autoprogram_settings.limit_value_date',
            $limitType === 'date' ? $apSettings['limit_value'] : '',
        );
        $skipW = old('autoprogram_settings.skip_weekends', $apSettings['skip_weekends'] ?? true);
        $seq = old('autoprogram_settings.sequential', $apSettings['sequential'] ?? true);
    @endphp
    <div x-data="{
        isAutoprogrammable: {{ old('is_autoprogrammable', $activity->is_autoprogrammable) ? 'true' : 'false' }},
        frequency: '{{ $freq }}',
        monthlyType: '{{ old('autoprogram_settings.monthly_type', $apSettings['monthly_type'] ?? 'date') }}',
        labels: {
            'daily': '{{ __('activities.days') }}',
            'weekly': '{{ __('activities.weeks') }}',
            'monthly': '{{ __('activities.months') }}',
            'yearly': '{{ __('activities.years') }}'
        }
    }"
        class="bg-violet-50/30 dark:bg-gray-900/40 backdrop-blur-md border border-violet-100 dark:border-violet-500/20 rounded-2xl p-6 shadow-sm dark:shadow-[0_0_20px_-12px_rgba(139,92,246,0.3)] transition-all">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-violet-100 dark:bg-violet-500/10 flex items-center justify-center text-violet-600 dark:text-violet-400 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </div>
                <div class="flex flex-col">
                    <span
                        class="text-sm font-bold text-gray-900 dark:text-white">{{ __('activities.autoprogrammable') }}</span>
                    <span
                        class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('activities.autoprogrammable_hint') }}</span>
                </div>
            </div>

            <!-- Segmented Control -->
            <div
                class="flex p-1 bg-gray-200 dark:bg-gray-950/50 rounded-xl w-fit self-start sm:self-center border border-transparent dark:border-gray-800">
                <button type="button" @click="isAutoprogrammable = false"
                    :class="!isAutoprogrammable ?
                        'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm' :
                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                    {{ __('activities.disabled') }}
                </button>
                <button type="button" @click="isAutoprogrammable = true"
                    :class="isAutoprogrammable ? 'bg-violet-600 text-white shadow-lg shadow-violet-500/20' :
                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                    class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all duration-200">
                    {{ __('activities.active') }}
                </button>
            </div>
            <input type="hidden" name="is_autoprogrammable" :value="isAutoprogrammable ? 1 : 0">
        </div>

        <div x-show="isAutoprogrammable" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform -translate-y-4"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            class="space-y-6 pt-6 border-t border-violet-100/50 dark:border-violet-500/10">
            <div class="grid grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label
                        class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('activities.frequency') ?? 'Frecuencia' }}
                    </label>
                    <select name="autoprogram_settings[frequency]" x-model="frequency"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="daily" {{ $freq === 'daily' ? 'selected' : '' }}>
                            {{ __('activities.daily') ?? 'Diaria' }}</option>
                        <option value="weekly" {{ $freq === 'weekly' ? 'selected' : '' }}>
                            {{ __('activities.weekly') ?? 'Semanal' }}</option>
                        <option value="monthly" {{ $freq === 'monthly' ? 'selected' : '' }}>
                            {{ __('activities.monthly') ?? 'Mensual' }}</option>
                        <option value="yearly" {{ $freq === 'yearly' ? 'selected' : '' }}>
                            {{ __('activities.yearly') ?? 'Anual' }}</option>
                    </select>
                </div>
                <div x-show="frequency === 'weekly'" class="col-span-2 space-y-3 pb-2" x-transition>
                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('activities.days_of_week') ?? 'Días de la semana' }}
                    </label>
                    <div class="flex flex-wrap gap-2">
                        @foreach(['1' => 'L', '2' => 'M', '3' => 'X', '4' => 'J', '5' => 'V', '6' => 'S', '7' => 'D'] as $val => $label)
                            <label class="relative cursor-pointer">
                                <input type="checkbox" name="autoprogram_settings[days][]" value="{{ $val }}" 
                                    {{ in_array($val, old('autoprogram_settings.days', $apSettings['days'] ?? [])) ? 'checked' : '' }}
                                    class="peer sr-only">
                                <div class="w-9 h-9 rounded-xl border-2 border-gray-100 dark:border-gray-800 flex items-center justify-center text-xs font-black text-gray-400 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/30 peer-checked:text-violet-600 transition-all hover:border-violet-200 shadow-sm">
                                    {{ $label }}
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[9px] text-gray-400 italic">La tarea se generará en cada uno de los días seleccionados.</p>
                </div>
                <div x-show="frequency === 'monthly'" class="col-span-2 space-y-3 pb-2" x-transition x-cloak>
                    <label class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{ __('Patrón Mensual') }}
                    </label>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <label class="relative flex items-center gap-3 cursor-pointer group">
                            <div class="relative flex items-center justify-center">
                                <input type="radio" name="autoprogram_settings[monthly_type]" value="date" x-model="monthlyType" class="peer sr-only">
                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('El mismo día del mes') }}</span>
                        </label>
                        <label class="relative flex items-center gap-3 cursor-pointer group">
                            <div class="relative flex items-center justify-center">
                                <input type="radio" name="autoprogram_settings[monthly_type]" value="ordinal" x-model="monthlyType" class="peer sr-only">
                                <div class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all"></div>
                                <div class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('Un día específico de la semana') }}</span>
                        </label>
                    </div>
                    
                    <div x-show="monthlyType === 'ordinal'" class="flex items-center gap-2 mt-3" x-transition>
                        <span class="text-sm text-gray-500">{{ __('El') }}</span>
                        <select name="autoprogram_settings[monthly_ordinal]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            <option value="first" {{ old('autoprogram_settings.monthly_ordinal', $apSettings['monthly_ordinal'] ?? '') === 'first' ? 'selected' : '' }}>{{ __('Primer') }}</option>
                            <option value="second" {{ old('autoprogram_settings.monthly_ordinal', $apSettings['monthly_ordinal'] ?? '') === 'second' ? 'selected' : '' }}>{{ __('Segundo') }}</option>
                            <option value="third" {{ old('autoprogram_settings.monthly_ordinal', $apSettings['monthly_ordinal'] ?? '') === 'third' ? 'selected' : '' }}>{{ __('Tercer') }}</option>
                            <option value="fourth" {{ old('autoprogram_settings.monthly_ordinal', $apSettings['monthly_ordinal'] ?? '') === 'fourth' ? 'selected' : '' }}>{{ __('Cuarto') }}</option>
                            <option value="last" {{ old('autoprogram_settings.monthly_ordinal', $apSettings['monthly_ordinal'] ?? '') === 'last' ? 'selected' : '' }}>{{ __('Último') }}</option>
                        </select>
                        <select name="autoprogram_settings[monthly_day]" class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-3 py-1.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            <option value="monday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'monday' ? 'selected' : '' }}>{{ __('Lunes') }}</option>
                            <option value="tuesday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'tuesday' ? 'selected' : '' }}>{{ __('Martes') }}</option>
                            <option value="wednesday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'wednesday' ? 'selected' : '' }}>{{ __('Miércoles') }}</option>
                            <option value="thursday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'thursday' ? 'selected' : '' }}>{{ __('Jueves') }}</option>
                            <option value="friday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'friday' ? 'selected' : '' }}>{{ __('Viernes') }}</option>
                            <option value="saturday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'saturday' ? 'selected' : '' }}>{{ __('Sábado') }}</option>
                            <option value="sunday" {{ old('autoprogram_settings.monthly_day', $apSettings['monthly_day'] ?? '') === 'sunday' ? 'selected' : '' }}>{{ __('Domingo') }}</option>
                        </select>
                        <span class="text-sm text-gray-500">{{ __('del mes') }}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label
                        class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        {{ __('activities.interval') ?? 'Repetir cada' }}
                    </label>
                    <div class="flex items-center gap-2">
                        <input type="number" name="autoprogram_settings[interval]"
                            value="{{ $interval }}" min="1"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all">
                        <span class="text-xs font-medium text-gray-500 w-12"
                            x-text="labels[frequency]">días</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <label
                    class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ __('activities.lead_time') ?? 'Antelación de creación (despertar)' }}
                </label>
                <div class="flex items-center gap-3">
                    @php
                        $lVal = $activity->autoprogram_settings['lead_value'] ?? 7;
                        $lUnit = $activity->autoprogram_settings['lead_unit'] ?? 'days';
                    @endphp
                    <input type="number" name="autoprogram_settings[lead_value]"
                        value="{{ $lVal }}" min="1"
                        class="w-24 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
                    <select name="autoprogram_settings[lead_unit]"
                        class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none cursor-pointer">
                        <option value="hours" {{ $lUnit === 'hours' ? 'selected' : '' }}>
                            {{ __('activities.hours') ?? 'Horas' }}</option>
                        <option value="days" {{ $lUnit === 'days' ? 'selected' : '' }}>
                            {{ __('activities.days') ?? 'Días' }}</option>
                        <option value="weeks" {{ $lUnit === 'weeks' ? 'selected' : '' }}>
                            {{ __('activities.weeks') ?? 'Semanas' }}</option>
                        <option value="months" {{ $lUnit === 'months' ? 'selected' : '' }}>
                            {{ __('activities.months') ?? 'Meses' }}</option>
                    </select>
                    <span
                        class="text-[10px] text-gray-400 italic">{{ __('activities.lead_time_hint') ?? 'antes de la fecha señalada' }}</span>
                </div>
            </div>

            <div class="space-y-3">
                <label
                    class="flex items-center gap-2 text-xs font-bold text-violet-600 dark:text-violet-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                    </svg>
                    {{ __('activities.limit') ?? 'Terminar' }}
                </label>
                <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
                    <label class="relative flex items-center gap-3 cursor-pointer group">
                        <div class="relative flex items-center justify-center">
                            <input type="radio" name="autoprogram_settings[limit_type]" value="count"
                                {{ $limitType === 'count' ? 'checked' : '' }} class="peer sr-only">
                            <div
                                class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all">
                            </div>
                            <div
                                class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all">
                            </div>
                        </div>
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('activities.after_n_times') ?? 'Después de' }}</span>
                        <input type="number" name="autoprogram_settings[limit_value_count]"
                            value="{{ $limitValCount }}" min="1"
                            class="w-16 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all">
                        <span class="text-xs text-gray-500">{{ __('activities.times') ?? 'veces' }}</span>
                    </label>
                    <label class="relative flex items-center gap-3 cursor-pointer group">
                        <div class="relative flex items-center justify-center">
                            <input type="radio" name="autoprogram_settings[limit_type]" value="date"
                                {{ $limitType === 'date' ? 'checked' : '' }} class="peer sr-only">
                            <div
                                class="w-4 h-4 rounded-full border-2 border-gray-300 dark:border-gray-600 peer-checked:border-violet-500 transition-all">
                            </div>
                            <div
                                class="absolute w-2 h-2 rounded-full bg-violet-500 scale-0 peer-checked:scale-100 transition-all">
                            </div>
                        </div>
                        <span
                            class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('activities.on_date') ?? 'El día' }}</span>
                        <input type="date" name="autoprogram_settings[limit_value_date]"
                            value="{{ $limitValDate }}"
                            class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-lg px-2 py-1 text-xs text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                    </label>
                </div>
            </div>

            <div class="flex flex-wrap gap-4 pt-2">
                <label class="flex items-center gap-2 cursor-pointer group">
                    <div class="relative flex items-center justify-center">
                        <input type="checkbox" name="autoprogram_settings[skip_weekends]" value="1"
                            {{ $skipW ? 'checked' : '' }} class="peer sr-only">
                        <div
                            class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                        </svg>
                        </div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('activities.skip_weekends') ?? 'Saltar fines de semana' }}</span>
                </label>
                <label class="flex items-center gap-2 cursor-pointer group">
                    <div class="relative flex items-center justify-center">
                        <input type="checkbox" name="autoprogram_settings[sequential]" value="1"
                            {{ $seq ? 'checked' : '' }} class="peer sr-only">
                        <div
                            class="w-4 h-4 rounded border border-gray-300 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                        </svg>
                        </div>
                    </div>
                    <span
                        class="text-xs font-medium text-gray-500 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('activities.sequential_dependencies') ?? 'Dependencias secuenciales (Gantt)' }}</span>
                </label>
            </div>
        </div>
    </div>

    <!-- Timeline Lock -->
    <div class="bg-gray-50 dark:bg-gray-800/50 p-4 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center text-red-600 dark:text-red-400 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <div class="flex flex-col">
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Bloquear programación (Inamovible)') }}</span>
                <span class="text-[11px] text-gray-500 dark:text-gray-400">{{ __('Evita que la tarea sea desplazada o redimensionada en el Gantt de forma accidental.') }}</span>
            </div>
        </div>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="checkbox" name="is_timeline_locked" value="1" {{ old('is_timeline_locked', $activity->is_timeline_locked) ? 'checked' : '' }} class="sr-only peer">
            <div class="w-11 h-6 bg-gray-200 dark:bg-gray-700 rounded-full peer peer-focus:ring-4 peer-focus:ring-red-500/20 dark:peer-focus:ring-red-800/20 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-red-600"></div>
        </label>
    </div>

    <!-- Gamification Features (Resiliencia Colectiva) -->
    <div
        class="bg-amber-50/20 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-2xl p-6 space-y-6">
        <div class="flex items-center gap-3 mb-2">
            <div
                class="w-8 h-8 rounded-lg bg-amber-100 dark:bg-amber-900/40 flex items-center justify-center text-amber-600 shadow-sm border border-amber-200/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <h4 class="text-sm font-bold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                Impacto y Bienestar</h4>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Skill Category Selection -->
            <div x-data="{ selectedSkills: @json(old('skills', $activity->skills->pluck('id')->toArray())) }">
                <label class="block text-sm font-bold text-gray-700 dark:text-gray-300 mb-2">
                    Árbol de Capacidades (Selección Múltiple)
                </label>
                <select name="skills[]" multiple
                    class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-sm focus:border-amber-500 outline-none transition-all text-gray-900 dark:text-white h-64 resize-y">
                    @foreach ($skills as $skill)
                        <option value="{{ $skill->id }}"
                            :selected="selectedSkills.includes({{ $skill->id }})">
                            {{ $skill->name }} ({{ $skill->category }})
                        </option>
                    @endforeach
                </select>
                <p class="text-[10px] text-gray-500 mt-2">Mantén presionado Ctrl (o Cmd) para seleccionar
                    varias habilidades.</p>
            </div>
            <!-- Cognitive Load (Energy Drain) -->
            <div x-data="{ load: {{ $activity->cognitive_load ?? 1 }} }">
                <label
                    class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4 flex items-center justify-between">
                    <span>Carga Cognitiva (Drenaje de Energía)</span>
                    <span
                        :class="{
                            'text-emerald-500': load == 1,
                            'text-blue-500': load == 2,
                            'text-amber-500': load == 3,
                            'text-orange-500': load == 4,
                            'text-red-500': load == 5
                        }"
                        class="font-black tabular-nums transition-colors"
                        x-text="load">{{ $activity->cognitive_load ?? 1 }}</span>
                </label>
                <input type="range" name="cognitive_load" min="1" max="5"
                    step="1" x-model="load"
                    class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-amber-500">
                <div
                    class="flex justify-between text-[10px] text-gray-400 mt-2 font-black uppercase tracking-tighter">
                    <span>Baja</span>
                    <span>Media</span>
                    <span>Extrema</span>
                </div>
            </div>

            <!-- Skill Tree & Backstage -->
            <div class="space-y-4">
                <label
                    class="relative flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-violet-300 dark:hover:border-violet-500/50 transition-all group">
                    <input type="checkbox" name="is_out_of_skill_tree" value="1"
                        class="peer sr-only" {{ $activity->is_out_of_skill_tree ? 'checked' : '' }}>
                    <div
                        class="w-5 h-5 rounded border-2 border-gray-200 dark:border-gray-600 peer-checked:bg-violet-600 peer-checked:border-violet-600 transition-all flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Fuera
                            de mi Skill Tree</span>
                        <span class="text-[9px] text-gray-400 uppercase font-black">+ Puntos de
                            Resiliencia</span>
                    </div>
                </label>

                <label
                    class="relative flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-100 dark:border-gray-700 cursor-pointer hover:border-emerald-300 dark:hover:border-emerald-500/50 transition-all group">
                    <input type="checkbox" name="is_backstage" value="1" class="peer sr-only"
                        {{ $activity->is_backstage ? 'checked' : '' }}>
                    <div
                        class="w-5 h-5 rounded border-2 border-gray-200 dark:border-gray-600 peer-checked:bg-emerald-600 peer-checked:border-emerald-600 transition-all flex items-center justify-center text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="flex flex-col">
                        <span
                            class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-gray-900 dark:group-hover:text-white">Backstage
                            / Preparación</span>
                        <span class="text-[9px] text-gray-400 uppercase font-black">Visibiliza el esfuerzo
                            invisible</span>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <!-- Progress -->
    <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-600 dark:text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
            </svg>
            {{ __('Progreso') }}
        </h3>
        <div class="flex items-center gap-4">
            <input type="range" name="progress_percentage" min="0" max="100" value="{{ old('progress_percentage', $activity->progress_percentage ?? 0) }}" class="flex-1 h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer accent-violet-500">
            <span class="text-sm font-bold text-gray-900 dark:text-white font-mono tabular-nums w-12 text-right">{{ old('progress_percentage', $activity->progress_percentage ?? 0) }}%</span>
        </div>
    </div>
</div>
