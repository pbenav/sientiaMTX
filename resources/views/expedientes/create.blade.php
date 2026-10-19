<x-app-layout>
    @section('title', 'Nuevo Expediente — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-start gap-4">
            <a href="{{ route('teams.expedientes.index', $team) }}"
                class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <div class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-1">{{ $team->name }}</div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white heading tracking-tight flex items-center gap-3">
                    {{ __("Crear Nuevo Expediente") }}
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('teams.expedientes.store', $team) }}" method="POST" class="space-y-6 bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl">
            @csrf

            <!-- Título -->
            <div>
                <label for="title" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Expedient Title *") }}</label>
                <input type="text" name="title" id="title" required autofocus
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white transition-all font-bold"
                    placeholder="{{ __('e.g. Annual Audit Q3, Local Reform 4, Perez vs Gomez Case...') }}">
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>

            <!-- Descripción -->
            <div>
                <label for="description" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Description / Context") }}</label>
                <textarea name="description" id="description" rows="4"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white transition-all text-sm"
                    placeholder="{{ __('Write relevant details about this case...') }}"></textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>

            <!-- Triple row for Priority, Visibility & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                    <label for="priority" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Priority") }}</label>
                    <select name="priority" id="priority" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        <option value="low">{{ __("Low") }}</option>
                        <option value="medium" selected>{{ __("Medium") }}</option>
                        <option value="high">{{ __("High") }}</option>
                        <option value="critical">{{ __("Critical") }}</option>
                    </select>
                </div>
                <div>
                    <label for="visibility" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Privacy") }} 🔒</label>
                    <select name="visibility" id="visibility" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        <option value="public" selected>🌎 {{ __("Public (All Team)") }}</option>
                        <option value="private">🔒 {{ __("Private (Restricted)") }}</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Initial Status") }}</label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        <option value="open" selected>{{ __("Open") }}</option>
                        <option value="active">{{ __("In Progress / Active") }}</option>
                        <option value="on_hold">{{ __("On Hold") }}</option>
                    </select>
                </div>
            </div>

            <!-- Dates row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Start Date (Optional)") }}</label>
                    <input type="date" name="start_date" id="start_date"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">{{ __("Estimated End Date (Optional)") }}</label>
                    <input type="date" name="end_date" id="end_date"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white text-sm">
                </div>
            </div>

            <!-- Asignaciones -->
            <div class="p-5 bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/30 rounded-2xl space-y-5 mt-6">
                <h3 class="text-sm font-black text-violet-900 dark:text-violet-300 uppercase tracking-widest flex items-center gap-2 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                    {{ __("Assignment and Participation") }}
                </h3>
                
                <!-- {{ __("Main Responsible") }} -->
                <div class="mb-8">
                    <label for="assigned_user_id" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                        {{ __("Main Responsible") }}
                    </label>
                    <select name="assigned_user_id" id="assigned_user_id" class="w-full text-sm">
                        <option value="">-- {{ __("Unassigned") }} --</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ old('assigned_user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('assigned_user_id')" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8" x-data="{
                    selectAll(status) {
                        document.querySelectorAll('.user-checkbox').forEach(cb => {
                            cb.checked = status;
                            cb.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                    },
                    syncGroup(groupCb) {
                        try {
                            const memberIds = JSON.parse(groupCb.dataset.members);
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
                    }
                }">
                    @if ($users->count() > 0)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    {{ __("Collaborators (Multiple)") }}
                                </label>
                                <div class="flex gap-2">
                                    <button type="button" @click="selectAll(true)" class="text-[10px] font-black uppercase tracking-widest text-violet-600 hover:text-violet-700 dark:text-violet-400 dark:hover:text-violet-300 transition-colors">
                                        {{ __("All") }}
                                    </button>
                                    <span class="text-gray-300 dark:text-gray-700 text-[10px]">|</span>
                                    <button type="button" @click="selectAll(false)" class="text-[10px] font-black uppercase tracking-widest text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                        {{ __("None") }}
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
                            <x-input-error class="mt-2" :messages="$errors->get('assigned_to')" />
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div class="space-y-3">
                            <label class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                {{ __("Involved Groups") }}
                            </label>
                            <div class="bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-2xl p-4 space-y-2.5 max-h-80 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-3 p-3 rounded-xl hover:bg-white dark:hover:bg-gray-800 cursor-pointer group transition-all border border-transparent hover:border-gray-100 dark:hover:border-gray-700 shadow-sm hover:shadow-md">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            data-members="{{ json_encode($group->users->pluck('id')) }}"
                                            @change="syncGroup($el)"
                                            {{ in_array($group->id, old('assigned_groups', [])) ? 'checked' : '' }}
                                            class="group-checkbox accent-violet-600 w-5 h-5 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-violet-500/20 transition-all">
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-sm font-bold text-gray-700 dark:text-gray-200 leading-tight group-hover:text-gray-900 dark:group-hover:text-white transition-colors truncate">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">
                                                {{ $group->users->count() }} {{ __("Members") }}
                                            </span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                            <x-input-error class="mt-2" :messages="$errors->get('assigned_groups')" />
                        </div>
                    @endif
                </div>
                <p class="text-xs text-violet-600 dark:text-violet-400 font-medium">💡 {{ __('If the file is private and you add collaborators, configure privacy accordingly to allow visibility.') }}</p>
            </div>

            <!-- Circuito de Tramitación y Aprobación -->
            @php
                $templatesList = $circuitTemplates ?? $team->circuitTemplates()->active()->forTarget('expediente')->get();
            @endphp
            <div class="p-5 bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800/30 rounded-2xl space-y-4 mt-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-black text-violet-900 dark:text-violet-300 uppercase tracking-widest flex items-center gap-2">
                        <svg class="w-4 h-4 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                        {{ __('circuits.circuit_template') ?? 'Circuito de Tramitación y Aprobación' }}
                    </h3>
                    @if(Route::has('teams.circuits.templates.create'))
                        <a href="{{ route('teams.circuits.templates.create', $team) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-violet-600 dark:text-violet-400 hover:underline">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            <span>Nueva plantilla</span>
                        </a>
                    @endif
                </div>

                <select name="circuit_template_id" id="expediente_circuit_template_id"
                    class="w-full bg-white dark:bg-gray-800 border-none rounded-xl px-4 py-3 text-sm text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500 shadow-sm cursor-pointer">
                    <option value="">{{ __('(Sin circuito asignado)') }}</option>
                    @foreach ($templatesList as $tmpl)
                        <option value="{{ $tmpl->id }}" {{ old('circuit_template_id') == $tmpl->id ? 'selected' : '' }}>
                            ⚡ {{ $tmpl->name }} ({{ $tmpl->steps->count() }} pasos: {{ $tmpl->steps->pluck('name')->take(3)->implode(' → ') }})
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Define la cadena de vistos buenos, justificaciones y firmas oficiales para la resolución de este expediente.
                </p>

                <div class="mt-2 flex items-center gap-2">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="start_circuit_now" value="1" {{ old('start_circuit_now') ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                        <span class="ml-2.5 text-xs font-bold text-gray-700 dark:text-gray-300">
                            Iniciar tramitación del circuito automáticamente al crear el expediente
                        </span>
                    </label>
                </div>
            </div>

            <!-- End of administrative inputs -->

            <div class="pt-4 flex items-center justify-end gap-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('teams.expedientes.index', $team) }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    {{ __("Cancel") }}
                </a>
                <button type="submit" class="px-8 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-violet-500/30 hover:-translate-y-0.5 transition-all active:scale-95">
                    {{ __("Guardar Expediente") }}
                </button>
            </div>
        </form>
    </div>
</x-app-layout>
@push('scripts')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.2.2/dist/js/tom-select.complete.min.js"></script>
    <style>
        .ts-control {
            border-radius: 0.75rem !important;
            padding: 0.75rem 1rem !important;
            border: none !important;
            background-color: #ffffff !important;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        .dark .ts-control {
            background-color: #111827 !important;
            color: white !important;
            border: 1px solid #374151 !important;
        }
        .ts-dropdown {
            border-radius: 0.75rem !important;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1) !important;
            border: 1px solid #f3f4f6 !important;
            margin-top: 8px !important;
            overflow: hidden !important;
        }
        .dark .ts-dropdown {
            background-color: #1f2937 !important;
            border-color: #374151 !important;
            color: #e5e7eb !important;
        }
        .ts-dropdown .active { 
            background-color: #f5f3ff !important; 
            color: #7c3aed !important; 
        }
        .dark .ts-dropdown .active { background-color: #7c3aed !important; color: #ffffff !important; }
        .ts-dropdown .option { padding: 8px 12px !important; }
        
        .ts-wrapper.multi .ts-control > div {
            background: #f5f3ff !important;
            color: #6d28d9 !important;
            border: 1px solid #ddd6fe !important;
            border-radius: 6px !important;
            padding: 2px 8px !important;
            font-weight: 600 !important;
        }
        .dark .ts-wrapper.multi .ts-control > div {
            background: #374151 !important;
            color: #e0e7ff !important;
            border-color: #4b5563 !important;
        }
    </style>
    <script>
        // --- End of global helpers ---

        document.addEventListener('DOMContentLoaded', function () {
            if (document.getElementById('related_ids')) {
                new TomSelect('#related_ids', {
                    plugins: {
                        'remove_button': { title: 'Quitar' }
                    },
                    maxItems: null,
                    render: {
                        option: function(data, escape) {
                            return '<div class="py-1.5 px-2 border-b border-gray-50 dark:border-gray-800/50">' +
                                '<div class="font-bold text-gray-900 dark:text-white text-xs">' + escape(data.text) + '</div>' +
                            '</div>';
                        }
                    }
                });
            }

            if (document.getElementById('assigned_user_id')) {
                new TomSelect('#assigned_user_id', {
                    create: false,
                    sortField: { field: "text", direction: "asc" }
                });
            }
        });
    </script>
@endpush
