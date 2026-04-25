    <!-- Listado de Habilidades -->
    <div class="lg:col-span-2 space-y-6">
        @if(isset($team))
            <div class="flex justify-start">
               <form action="{{ route('teams.skills.inherit', $team) }}" method="POST">
                   @csrf
                   <button type="submit" class="flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-800 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-amber-100 dark:hover:bg-amber-900/40 transition-all shadow-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Heredar Especialidades Globales
                   </button>
               </form>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-sm overflow-hidden text-xs">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/30 border-b border-gray-100 dark:border-gray-800">
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-wider">Habilidad</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-wider hidden md:table-cell">Ámbito</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-wider hidden md:table-cell">Descripción</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-wider text-center">Tareas</th>
                        <th class="px-6 py-4 text-[10px] font-black text-gray-400 uppercase tracking-wider text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 dark:divide-gray-800/50">
                    @forelse($skills as $skill)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/20 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-xl shadow-sm overflow-hidden" 
                                         style="background-color: {{ $skill->color ?: '#f3f4f6' }}20; color: {{ $skill->color ?: '#6b7280' }}">
                                        @if($skill->icon && strlen($skill->icon) <= 8)
                                            <span>{{ $skill->icon }}</span>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="font-bold text-gray-900 dark:text-white">{{ $skill->name }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[11px] hidden md:table-cell text-gray-500">
                                @if($skill->team)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 border border-blue-100 dark:border-blue-800/50">
                                        {{ $skill->team->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-bold bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400 border border-gray-100 dark:border-gray-700/50">
                                        Global
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-[11px] text-gray-400 hidden md:table-cell max-w-xs truncate font-medium italic">
                                {{ $skill->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-black bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-400">
                                    {{ $skill->tasks_count }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2 text-xs">
                                    <button onclick="editSkill({{ json_encode($skill) }})" 
                                            class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <form action="{{ isset($team) ? route('teams.skills.destroy', [$team, $skill, 'tab' => 'skills']) : route('settings.skills.destroy', $skill) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta habilidad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors {{ $skill->tasks_count > 0 ? 'opacity-20 cursor-not-allowed' : '' }}" {{ $skill->tasks_count > 0 ? 'disabled' : '' }}>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500 italic">
                                No hay habilidades configuradas todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formulario Crear/Editar -->
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 shadow-sm sticky top-6 text-xs">
            <h2 id="form-title" class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2 heading">
                 <div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                 </div>
                 Nueva Habilidad
            </h2>

            <form id="skill-form" action="{{ isset($team) ? route('teams.skills.store', [$team, 'tab' => 'skills']) : route('settings.skills.store') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                
                @if(!isset($team))
                <div>
                    <x-input-label for="team_id" value="Equipo / Ámbito" />
                    <select id="team_id" name="team_id" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none cursor-pointer font-bold">
                        <option value="">Global (Todos los equipos)</option>
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                @endif

                <div>
                    <x-input-label for="skill_name" value="Nombre" />
                    <x-text-input id="skill_name" name="name" type="text" class="mt-1 block w-full text-xs" required placeholder="Ej: Dinamización" />
                </div>

                <div>
                    <x-input-label for="skill_description" value="Descripción" />
                    <textarea id="skill_description" name="description" rows="3" class="mt-1 block w-full bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 rounded-xl px-4 py-3 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 transition-all outline-none font-medium italic" placeholder="¿En qué consiste esta especialidad?"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="skill_icon" value="Icono (Emoji)" />
                        <x-text-input id="skill_icon" name="icon" type="text" class="mt-1 block w-full text-center text-xl" placeholder="💠" maxlength="4" />
                    </div>
                    <div>
                        <x-input-label for="skill_color" value="Color" />
                        <x-text-input id="skill_color" name="color" type="color" class="mt-1 block w-full h-11 p-1 cursor-pointer bg-transparent border-none" value="#7c3aed" />
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between gap-4">
                    <button type="button" id="cancel-btn" onclick="resetForm()" class="hidden text-[10px] font-black text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors uppercase tracking-widest">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 bg-gray-900 dark:bg-violet-600 hover:bg-black dark:hover:bg-violet-700 text-white font-black py-3 px-6 rounded-2xl shadow-lg shadow-gray-200 dark:shadow-violet-900/20 transition-all active:scale-95 uppercase text-[11px] tracking-widest">
                        Guardar Habilidad
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
    function getBaseRoute() {
        const isTeam = {{ isset($team) ? 'true' : 'false' }};
        const tid = "{{ isset($team) ? $team->id : '' }}";
        return isTeam ? `/teams/${tid}/skills?tab=skills` : '/settings/skills';
    }

    function editSkill(skill) {
        const form = document.getElementById('skill-form');
        const methodInput = document.getElementById('form-method');
        const title = document.getElementById('form-title');
        const cancelBtn = document.getElementById('cancel-btn');
        
        // Update URL and Method
        form.action = `${getBaseRoute()}/${skill.id}`;
        methodInput.value = 'PATCH';
        title.innerHTML = '<div class="w-8 h-8 rounded-lg bg-violet-500/10 text-violet-500 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></div> Editar Habilidad';
        cancelBtn.classList.remove('hidden');
        
        // Fill fields
        if (document.getElementById('team_id')) {
            document.getElementById('team_id').value = skill.team_id || '';
        }
        document.getElementById('skill_name').value = skill.name;
        document.getElementById('skill_description').value = skill.description || '';
        document.getElementById('skill_icon').value = skill.icon || '💠';
        document.getElementById('skill_color').value = skill.color || '#7c3aed';
        
        // Scroll to form only if it's not and we have room
        const formElement = document.getElementById('skill-form');
        formElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function resetForm() {
        const form = document.getElementById('skill-form');
        const methodInput = document.getElementById('form-method');
        const title = document.getElementById('form-title');
        const cancelBtn = document.getElementById('cancel-btn');
        
        form.action = getBaseRoute();
        methodInput.value = 'POST';
        title.innerHTML = '<div class="w-8 h-8 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center"><svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></div> Nueva Habilidad';
        cancelBtn.classList.add('hidden');
        
        form.reset();
    }
</script>
