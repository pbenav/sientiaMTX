<x-app-layout>
    @section('title', 'Editar Plantilla: ' . $template->name . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.circuits.templates.index', $team) }}"
                    class="p-1.5 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-lg transition-all shrink-0"
                    title="{{ __('Back') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                @include('teams.partials.breadcrumb')
                <span class="text-gray-300 dark:text-gray-700 mx-1">/</span>
                <h1 class="text-base font-black text-gray-900 dark:text-white heading truncate select-none tracking-tight flex items-center gap-1.5">
                    <svg class="h-4 w-4 text-violet-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span class="truncate">Editar: {{ $template->name }}</span>
                </h1>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav')
        @include('circuits.partials.circuits-nav')
    </x-slot>

    @php
        $initialSteps = $template->steps->map(function($s) {
            return [
                'name' => $s->name,
                'description' => $s->description ?? '',
                'action_type' => $s->action_type,
                'assignee_type' => $s->assignee_type,
                'assignee_id' => $s->assignee_id ? (int)$s->assignee_id : null,
                'requires_signature' => (bool)$s->requires_signature,
            ];
        })->values()->all();
    @endphp

    <div class="space-y-6 max-w-5xl mx-auto"
        x-data="{
            steps: {{ json_encode($initialSteps) }},
            addStep() {
                this.steps.push({
                    name: 'Nueva Etapa',
                    description: '',
                    action_type: 'approval',
                    assignee_type: 'specific_user',
                    assignee_id: null,
                    requires_signature: false
                });
            },
            removeStep(index) {
                if (this.steps.length > 1) {
                    this.steps.splice(index, 1);
                }
            }
        }">

        <form action="{{ route('teams.circuits.templates.update', [$team, $template]) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- General Info Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <h3 class="text-base font-black text-gray-900 dark:text-white">Datos Generales</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="name" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Nombre de la Plantilla *</label>
                        <input type="text" id="name" name="name" value="{{ old('name', $template->name) }}" required
                            class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                    </div>

                    <div class="md:col-span-2">
                        <label for="description" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Descripción</label>
                        <textarea id="description" name="description" rows="2"
                            class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">{{ old('description', $template->description) }}</textarea>
                    </div>

                    <div>
                        <label for="target_type" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Aplica a</label>
                        <select id="target_type" name="target_type"
                            class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                            <option value="both" {{ $template->target_type === 'both' ? 'selected' : '' }}>Tanto Actividades como Expedientes</option>
                            <option value="activity" {{ $template->target_type === 'activity' ? 'selected' : '' }}>Solo Actividades (documentos, tareas, etc.)</option>
                            <option value="expediente" {{ $template->target_type === 'expediente' ? 'selected' : '' }}>Solo Expedientes (dossiers)</option>
                        </select>
                    </div>

                    <div class="flex items-center gap-6 pt-5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $template->is_active ? 'checked' : '' }}
                                class="rounded-md border-gray-300 text-violet-600 focus:ring-violet-500">
                            <span class="text-xs font-bold text-gray-700 dark:text-gray-300">Plantilla activa</span>
                        </label>

                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="auto_publish" value="1" {{ ($template->settings['auto_publish'] ?? false) ? 'checked' : '' }}
                                class="rounded-md border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="text-xs font-bold text-teal-700 dark:text-teal-300">Publicar automáticamente en Tablón al aprobar</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Steps Builder Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 shadow-sm border border-gray-100 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-black text-gray-900 dark:text-white">Etapas del Circuito</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Los pasos se ejecutarán en orden correlativo
                        </p>
                    </div>

                    <button type="button" @click="addStep()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-xl text-violet-600 dark:text-violet-400 bg-violet-50 dark:bg-violet-900/30 hover:bg-violet-100 dark:hover:bg-violet-900/50 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                        <span>Añadir Etapa</span>
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="(step, index) in steps" :key="index">
                        <div class="p-4 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-750/30 space-y-3 relative group">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-violet-600 text-white font-black text-xs flex items-center justify-center shrink-0" x-text="index + 1"></span>
                                    <span class="text-xs font-bold text-gray-500 dark:text-gray-400">Etapa <span x-text="index + 1"></span></span>
                                </div>

                                <button type="button" @click="removeStep(index)" x-show="steps.length > 1"
                                    class="text-gray-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors p-1" title="Eliminar etapa">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Nombre de la Etapa *</label>
                                    <input type="text" :name="`steps[${index}][name]`" x-model="step.name" required
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tipo de Acción *</label>
                                    <select :name="`steps[${index}][action_type]`" x-model="step.action_type" required
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                        <option value="endorsement">Visto Bueno</option>
                                        <option value="justification">Justificación / Memoria</option>
                                        <option value="approval">Aprobación / Firma</option>
                                        <option value="publication">Publicación en Tablón</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Asignar a *</label>
                                    <select :name="`steps[${index}][assignee_type]`" x-model="step.assignee_type" required
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                        <option value="specific_user">Usuario específico</option>
                                        <option value="team_role">Rol del equipo</option>
                                        <option value="activity_responsible">Responsable asignado</option>
                                        <option value="creator">Autor / Iniciador</option>
                                        <option value="dynamic">Dinámico (elegir al tramitar)</option>
                                    </select>
                                </div>

                                <div class="md:col-span-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Instrucciones para el revisor</label>
                                    <input type="text" :name="`steps[${index}][description]`" x-model="step.description"
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                </div>

                                <div class="md:col-span-2" x-show="step.assignee_type === 'specific_user'">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Seleccionar Usuario</label>
                                    <select :name="`steps[${index}][assignee_id]`" x-model="step.assignee_id"
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                        <option value="">-- Elige un miembro del equipo --</option>
                                        @foreach($members as $m)
                                            <option value="{{ $m->id }}">{{ $m->name }} ({{ $m->email }})</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-2" x-show="step.assignee_type === 'team_role'">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Seleccionar Rol de Equipo</label>
                                    <select :name="`steps[${index}][assignee_id]`" x-model="step.assignee_id"
                                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white focus:ring-violet-500 focus:border-violet-500">
                                        <option value="">-- Elige un rol --</option>
                                        @foreach($roles as $r)
                                            <option value="{{ $r->id }}">{{ ucfirst($r->name) }} @if($r->description) - {{ $r->description }} @endif</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Requiere Firma Digital Toggle -->
                                <div class="md:col-span-4 pt-2 border-t border-gray-200/60 dark:border-gray-700/60 flex items-center justify-between">
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" :name="`steps[${index}][requires_signature]`" value="1" x-model="step.requires_signature" class="sr-only peer">
                                        <div class="w-8 h-4 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all dark:border-gray-600 peer-checked:bg-violet-600"></div>
                                        <span class="ml-2.5 text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                            Requiere Firma Digital / Certificado Electrónico en esta etapa
                                        </span>
                                    </label>
                                    <span class="text-[10px] text-gray-400 italic" x-show="step.requires_signature">
                                        Exige firma formal reconocida antes de dar por aprobado el paso
                                    </span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('teams.circuits.templates.index', $team) }}"
                    class="px-5 py-2.5 text-xs font-bold rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </a>

                <button type="submit"
                    class="px-6 py-2.5 text-xs font-black rounded-xl bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-600/20 transition-all hover:scale-105 active:scale-95">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</x-app-layout>

