<x-app-layout>
    @section('title', 'Editar Expediente — ' . $expediente->code)

    <x-slot name="header">
        <div class="flex items-start gap-4">
            <a href="{{ route('teams.expedientes.show', [$team, $expediente]) }}"
                class="mt-1 p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-[10px] font-black bg-violet-100 dark:bg-violet-900/50 text-violet-600 dark:text-violet-400 px-2 py-0.5 rounded-lg uppercase tracking-wider">{{ $expediente->code }}</span>
                    <div class="text-xs font-medium text-gray-400 uppercase tracking-wider">{{ $team->name }}</div>
                </div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white heading tracking-tight">
                    Editar Expediente
                </h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto">
        <form action="{{ route('teams.expedientes.update', [$team, $expediente]) }}" method="POST" class="space-y-6 bg-white dark:bg-gray-900 p-6 sm:p-8 rounded-3xl border border-gray-200 dark:border-gray-800 shadow-xl">
            @csrf
            @method('PUT')

            <!-- Título -->
            <div>
                <label for="title" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Título del Expediente *</label>
                <input type="text" name="title" id="title" required autofocus
                    value="{{ old('title', $expediente->title) }}"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white transition-all font-bold"
                    placeholder="Ej: Auditoría Anual Q3, Reforma Local 4...">
                <x-input-error class="mt-2" :messages="$errors->get('title')" />
            </div>

            <!-- Descripción -->
            <div>
                <label for="description" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Descripción / Contexto</label>
                <textarea name="description" id="description" rows="4"
                    class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white transition-all text-sm"
                    placeholder="Escribe detalles relevantes sobre este expediente...">{{ old('description', $expediente->description) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('description')" />
            </div>

            <!-- Triple row for Priority, Visibility & Status -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                <div>
                    <label for="priority" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Prioridad</label>
                    <select name="priority" id="priority" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        @foreach(['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta', 'critical' => 'Crítica'] as $key => $label)
                            <option value="{{ $key }}" {{ old('priority', $expediente->priority) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="visibility" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Privacidad 🔒</label>
                    <select name="visibility" id="visibility" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        <option value="public" {{ old('visibility', $expediente->visibility) === 'public' ? 'selected' : '' }}>🌎 Público</option>
                        <option value="private" {{ old('visibility', $expediente->visibility) === 'private' ? 'selected' : '' }}>🔒 Privado</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Estado</label>
                    <select name="status" id="status" required
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white font-bold text-sm">
                        @foreach([
                            'open' => 'Abierto', 
                            'active' => 'En Curso', 
                            'on_hold' => 'En Espera',
                            'closed' => 'Cerrado / Finalizado',
                            'cancelled' => 'Cancelado'
                        ] as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $expediente->status) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Dates row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="start_date" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Fecha Inicio</label>
                    <input type="date" name="start_date" id="start_date"
                        value="{{ old('start_date', $expediente->start_date ? $expediente->start_date->format('Y-m-d') : '') }}"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white text-sm">
                </div>
                <div>
                    <label for="end_date" class="block text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-2">Fecha Fin Est.</label>
                    <input type="date" name="end_date" id="end_date"
                        value="{{ old('end_date', $expediente->end_date ? $expediente->end_date->format('Y-m-d') : '') }}"
                        class="w-full px-4 py-3 bg-gray-50 dark:bg-gray-800 border-none rounded-xl focus:ring-2 focus:ring-violet-500 text-gray-900 dark:text-white text-sm">
                </div>
            </div>

            <!-- End of basic administrative data -->

            <div class="pt-4 flex items-center justify-end gap-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('teams.expedientes.show', [$team, $expediente]) }}" class="text-sm font-bold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-violet-600 hover:bg-violet-500 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-lg hover:shadow-violet-500/30 hover:-translate-y-0.5 transition-all active:scale-95">
                    Actualizar Expediente
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
        document.addEventListener('DOMContentLoaded', function () {
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
        });
    </script>
@endpush
