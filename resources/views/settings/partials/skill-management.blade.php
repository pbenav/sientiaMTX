    <!-- Listado de Habilidades -->
    <div class="lg:col-span-2 space-y-6">
        @if(isset($team))
            <div class="flex justify-start">
               <form action="{{ route('teams.skills.inherit', $team) }}" method="POST">
                   @csrf
                   <button type="submit" class="flex items-center gap-2 px-4 py-2.5 bg-amber-500/10 hover:bg-amber-500/20 text-amber-700 dark:text-amber-400 border border-amber-500/20 dark:border-amber-500/30 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all duration-300 shadow-sm active:scale-95 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Heredar Especialidades Globales
                   </button>
               </form>
            </div>
        @endif

        <div class="bg-white/80 dark:bg-gray-900/80 border border-gray-100 dark:border-gray-800/80 backdrop-blur-xl rounded-3xl shadow-xl shadow-gray-100/40 dark:shadow-none overflow-hidden text-xs transition-all duration-300">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50/50 dark:bg-gray-800/30 border-b border-gray-100/80 dark:border-gray-800/80">
                        <th class="px-6 py-4.5 text-[10px] font-black text-gray-400 uppercase tracking-widest">Habilidad / Especialidad</th>
                        <th class="px-6 py-4.5 text-[10px] font-black text-gray-400 uppercase tracking-widest hidden md:table-cell">Ámbito</th>
                        <th class="px-6 py-4.5 text-[10px] font-black text-gray-400 uppercase tracking-widest hidden md:table-cell">Descripción</th>
                        <th class="px-6 py-4.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-center">Tareas</th>
                        <th class="px-6 py-4.5 text-[10px] font-black text-gray-400 uppercase tracking-widest text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50/50 dark:divide-gray-800/40">
                    @forelse($skills as $skill)
                        @php
                            $skillColor = $skill->color ?: '#7c3aed';
                        @endphp
                        <tr class="hover:bg-violet-500/[0.02] dark:hover:bg-violet-500/[0.01] transition-all duration-300 group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3.5">
                                    {{-- Gema Orbe tridimensional del icono --}}
                                    <div class="w-11 h-11 shrink-0 rounded-2xl flex items-center justify-center text-xl shadow-sm transition-all duration-300 group-hover:scale-105 select-none" 
                                         style="background: linear-gradient(135deg, {{ $skillColor }}15, {{ $skillColor }}28); border: 1px solid {{ $skillColor }}38; color: {{ $skillColor }}">
                                        @if($skill->icon && strlen($skill->icon) <= 8)
                                            <span>{{ $skill->icon }}</span>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5 opacity-60" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="font-black text-gray-900 dark:text-white text-[13px] group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors duration-300">
                                        {{ $skill->name }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-[11px] hidden md:table-cell text-gray-500">
                                @if($skill->team)
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-blue-500/10 text-blue-700 dark:text-blue-400 border border-blue-500/20 shadow-sm">
                                        {{ $skill->team->name }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl text-[10px] font-extrabold bg-violet-500/10 text-violet-700 dark:text-violet-400 border border-violet-500/20 shadow-sm">
                                        Global
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-[11px] text-gray-400 hidden md:table-cell max-w-[200px] truncate font-medium italic">
                                {{ $skill->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-black bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-100 dark:border-gray-700/80 shadow-sm relative">
                                    @if($skill->tasks_count > 0)
                                        {{-- Punto pulsante de actividad --}}
                                        <span class="relative flex h-2 w-2">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                        </span>
                                    @else
                                        <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                    @endif
                                    <span>{{ $skill->tasks_count }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-1.5 text-xs">
                                    <button onclick="editSkill({{ json_encode($skill) }})" 
                                            class="p-2 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 hover:bg-violet-50 dark:hover:bg-violet-900/20 rounded-xl transition-all duration-300 cursor-pointer"
                                            title="Editar Habilidad">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    
                                    <form action="{{ isset($team) ? route('teams.skills.destroy', [$team, $skill, 'tab' => 'skills']) : route('settings.skills.destroy', $skill) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar esta habilidad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/20 rounded-xl transition-all duration-300 cursor-pointer {{ $skill->tasks_count > 0 ? 'opacity-20 cursor-not-allowed' : '' }}" {{ $skill->tasks_count > 0 ? 'disabled' : '' }} title="Eliminar Habilidad">
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
                            <td colspan="5" class="px-6 py-16 text-center text-gray-400 dark:text-gray-500 italic font-medium">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-gray-300 dark:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    No hay habilidades configuradas todavía.
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Formulario Crear/Editar -->
    <div class="space-y-6">
        <div class="bg-gradient-to-b from-white to-gray-50/40 dark:from-gray-900 dark:to-gray-950/30 border border-gray-100 dark:border-gray-800/80 rounded-3xl p-6 shadow-xl shadow-gray-100/50 dark:shadow-none sticky top-6 text-xs backdrop-blur-xl transition-all duration-300">
            <h2 id="form-title" class="text-lg font-black text-gray-900 dark:text-white mb-6 flex items-center gap-2.5 heading tracking-tight">
                 <div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                 </div>
                 <span>Nueva Habilidad</span>
            </h2>

            <form id="skill-form" action="{{ isset($team) ? route('teams.skills.store', [$team, 'tab' => 'skills']) : route('settings.skills.store') }}" method="POST" class="space-y-5">
                @csrf
                <input type="hidden" name="_method" id="form-method" value="POST">
                
                @if(!isset($team))
                <div class="space-y-1.5">
                    <x-input-label for="team_id" value="Equipo / Ámbito" class="font-extrabold uppercase tracking-widest text-[9px] text-gray-400" />
                    <select id="team_id" name="team_id" class="mt-1 block w-full bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-xl px-4 py-2.5 text-xs text-gray-900 dark:text-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none cursor-pointer font-bold">
                        <option value="">Global (Todos los equipos)</option>
                        @foreach($teams as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                @else
                    <input type="hidden" name="team_id" value="{{ $team->id }}">
                @endif

                <div class="space-y-1.5">
                    <x-input-label for="skill_name" value="Nombre" class="font-extrabold uppercase tracking-widest text-[9px] text-gray-400" />
                    <x-text-input id="skill_name" name="name" type="text" class="mt-1 block w-full text-xs bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-xl px-4 py-2.5 font-bold focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none" required placeholder="Ej: Dinamización" />
                </div>

                <div class="space-y-1.5">
                    <x-input-label for="skill_description" value="Descripción" class="font-extrabold uppercase tracking-widest text-[9px] text-gray-400" />
                    <textarea id="skill_description" name="description" rows="3" class="mt-1 block w-full bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-xl px-4 py-3 text-xs text-gray-900 dark:text-white focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none font-medium italic placeholder:not-italic" placeholder="¿En qué consiste esta especialidad?"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <x-input-label for="skill_icon" value="Icono (Emoji)" class="font-extrabold uppercase tracking-widest text-[9px] text-gray-400" />
                        <x-text-input id="skill_icon" name="icon" type="text" class="mt-1 block w-full text-center text-xl bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-xl py-1.5 font-bold focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all outline-none" placeholder="💠" maxlength="4" />
                    </div>
                    <div class="space-y-1.5">
                        <x-input-label for="skill_color" value="Color" class="font-extrabold uppercase tracking-widest text-[9px] text-gray-400" />
                        <x-text-input id="skill_color" name="color" type="color" class="mt-1 block w-full h-11 p-1 cursor-pointer bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200/60 dark:border-gray-700/60 rounded-xl focus:ring-4 focus:ring-violet-500/10 focus:border-violet-500 transition-all" value="#7c3aed" />
                    </div>
                </div>

                {{-- Emoji Selector Palette (Gemas Compactas) --}}
                <div class="space-y-2 pt-1">
                    <span class="text-[9px] font-extrabold text-gray-400 uppercase tracking-widest block">Seleccionar Emoji Rápido</span>
                    <div class="gap-1 p-1.5 bg-gray-50/80 dark:bg-gray-800/40 rounded-2xl border border-gray-200/60 dark:border-gray-700/60 max-h-[140px] overflow-y-auto no-scrollbar shadow-inner"
                         style="display: grid; grid-template-columns: repeat(10, minmax(0, 1fr));">
                        @php
                            $emojis = [
                                '🤖', '💻', '🔬', '🚀', '🧠', '⚙️', '🔌', '⚡', '🧬', '🧪',
                                '🎨', '🖌️', '📷', '🎬', '🎭', '🎻', '🎮', '🧵', '🔨', '📐',
                                '💼', '📊', '📅', '📋', '📈', '🎯', '💡', '🧩', '🗝️', '🔍',
                                '🎓', '📚', '✍️', '✏️', '🗣️', '📖', '🩺', '💊', '🏥', '🤝',
                                '❤️', '🌟', '🛡️', '🌱', '🪴', '🌍', '🗺️', '💠', '💎', '🔮'
                            ];
                        @endphp
                        @foreach($emojis as $emoji)
                            <button type="button" onclick="selectEmoji('{{ $emoji }}')"
                                class="text-[15px] p-0 hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm hover:scale-125 active:scale-95 rounded-lg transition-all duration-200 focus:outline-none flex items-center justify-center h-7 w-full select-none cursor-pointer">
                                {{ $emoji }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-between gap-4">
                    <button type="button" id="cancel-btn" onclick="resetForm()" class="hidden text-[10px] font-black text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition-colors uppercase tracking-widest py-3">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-700 hover:to-indigo-700 text-white font-black py-3 px-6 rounded-2xl shadow-lg shadow-violet-500/20 dark:shadow-none hover:shadow-violet-500/30 transition-all active:scale-[0.98] uppercase text-[10px] tracking-widest cursor-pointer">
                        Guardar Habilidad
                    </button>
                </div>
            </form>
        </div>
    </div>

<script>
    function selectEmoji(emoji) {
        document.getElementById('skill_icon').value = emoji;
        // Animación suave de confirmación en el input
        const input = document.getElementById('skill_icon');
        input.classList.add('ring-4', 'ring-emerald-500/20', 'border-emerald-500');
        setTimeout(() => {
            input.classList.remove('ring-4', 'ring-emerald-500/20', 'border-emerald-500');
        }, 600);
    }

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
        title.innerHTML = '<div class="w-9 h-9 rounded-xl bg-violet-500/10 text-violet-600 dark:text-violet-400 flex items-center justify-center shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg></div> Editar Habilidad';
        cancelBtn.classList.remove('hidden');
        
        // Fill fields
        if (document.getElementById('team_id')) {
            document.getElementById('team_id').value = skill.team_id || '';
        }
        document.getElementById('skill_name').value = skill.name;
        document.getElementById('skill_description').value = skill.description || '';
        document.getElementById('skill_icon').value = skill.icon || '💠';
        document.getElementById('skill_color').value = skill.color || '#7c3aed';
        
        // Scroll to form smoothly
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
        title.innerHTML = '<div class="w-9 h-9 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-sm"><svg xmlns="http://www.w3.org/2000/svg" class="h-5.5 w-5.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg></div> Nueva Habilidad';
        cancelBtn.classList.add('hidden');
        
        form.reset();
    }
</script>
