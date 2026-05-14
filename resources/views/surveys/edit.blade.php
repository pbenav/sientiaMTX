<x-app-layout>
    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen">
        <div class="max-w-3xl mx-auto">
            
            <!-- Header -->
            <div class="mb-10">
                <nav class="flex items-center gap-2 text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">
                    <a href="{{ route('teams.surveys.index', $team) }}" class="hover:text-indigo-600 transition-colors">{{ __('Encuestas') }}</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="hover:text-indigo-600 transition-colors truncate max-w-[150px]">{{ $survey->title }}</a>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    <span class="text-gray-900 dark:text-white">{{ __('Editar') }}</span>
                </nav>
                <h1 class="text-4xl font-black text-gray-900 dark:text-white tracking-tight">{{ __('Ajustar Encuesta') }}</h1>
            </div>

            <!-- Form Container -->
            <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl shadow-indigo-500/5 border border-gray-100 dark:border-gray-800 overflow-hidden">
                <form action="{{ route('teams.surveys.update', [$team, $survey]) }}" method="POST" class="p-8 sm:p-12">
                    @csrf
                    @method('PATCH')
                    
                    <div class="space-y-10">
                        <!-- Basic Info -->
                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-1">{{ __('Título de la Encuesta') }}</label>
                                <input type="text" name="title" value="{{ old('title', $survey->title) }}" required
                                       class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500/50 rounded-2xl px-6 py-4 text-lg font-bold text-gray-900 dark:text-white placeholder-gray-400 transition-all focus:ring-0 shadow-inner">
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-1">{{ __('Descripción') }}</label>
                                <textarea name="description" rows="3"
                                          class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500/50 rounded-2xl px-6 py-4 text-gray-900 dark:text-white placeholder-gray-400 transition-all focus:ring-0 shadow-inner font-medium">{{ old('description', $survey->description) }}</textarea>
                            </div>
                        </div>

                        <!-- Type (Read Only in Edit for data integrity) -->
                        <div>
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-2 px-1">{{ __('Tipo de Respuesta') }}</label>
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border-2 border-gray-100 dark:border-gray-700">
                                <span class="text-indigo-600 dark:text-indigo-400 font-black uppercase tracking-widest text-sm">
                                    {{ $survey->type_label }}
                                </span>
                                <p class="text-[10px] text-gray-400 font-bold mt-1 uppercase tracking-tighter">
                                    {{ __('El tipo de encuesta no se puede cambiar para preservar la integridad de los votos.') }}
                                </p>
                            </div>
                        </div>

                        <!-- Settings Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pt-6 border-t border-gray-100 dark:border-gray-800">
                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 px-1">{{ __('Configuración') }}</label>
                                <div class="space-y-4">
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative w-12 h-6 bg-gray-200 dark:bg-gray-800 rounded-full transition-colors peer-checked:bg-indigo-600">
                                            <input type="hidden" name="show_results_before_voting" value="0">
                                            <input type="checkbox" name="show_results_before_voting" value="1" {{ $survey->show_results_before_voting ? 'checked' : '' }} class="peer absolute opacity-0 w-full h-full cursor-pointer">
                                            <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6 shadow-sm"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('Ver resultados antes de votar') }}</span>
                                    </label>

                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <div class="relative w-12 h-6 bg-gray-200 dark:bg-gray-800 rounded-full transition-colors peer-checked:bg-indigo-600">
                                            <input type="hidden" name="is_active" value="0">
                                            <input type="checkbox" name="is_active" value="1" {{ $survey->is_active ? 'checked' : '' }} class="peer absolute opacity-0 w-full h-full cursor-pointer">
                                            <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6 shadow-sm"></div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ __('Encuesta Activa') }}</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-4 px-1">{{ __('Fecha de Expiración') }}</label>
                                <input type="datetime-local" name="expires_at" value="{{ $survey->expires_at ? $survey->expires_at->format('Y-m-d\TH:i') : '' }}"
                                       class="w-full bg-gray-50 dark:bg-gray-800/50 border-2 border-transparent focus:border-indigo-500/50 rounded-2xl px-6 py-4 text-gray-900 dark:text-white transition-all focus:ring-0 shadow-inner font-bold">
                            </div>
                        </div>

                        <div class="pt-10 flex flex-col sm:flex-row items-center justify-between gap-6">
                            <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="text-xs font-black text-gray-400 hover:text-gray-600 uppercase tracking-widest transition-colors">
                                &larr; {{ __('Cancelar cambios') }}
                            </a>
                            <button type="submit" 
                                    class="group relative inline-flex items-center justify-center px-12 py-5 font-black text-white tracking-widest uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-full hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                 
                                 <!-- Premium Glow Effect -->
                                 <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 opacity-100 group-hover:opacity-90 transition-opacity"></div>
                                 <div class="absolute inset-0 w-full h-full bg-[radial-gradient(circle_at_top_left,rgba(255,255,255,0.2),transparent_70%)]"></div>
                                 
                                 <span class="relative flex items-center gap-3 text-lg">
                                     {{ __('Guardar Cambios') }}
                                     <svg class="w-6 h-6 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                     </svg>
                                 </span>
                             </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
