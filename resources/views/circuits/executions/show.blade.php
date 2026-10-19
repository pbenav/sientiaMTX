<x-app-layout>
    @section('title', $circuit->title . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <a href="{{ route('teams.circuits.index', $team) }}"
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="truncate">{{ $circuit->title }}</span>
                </h1>
                <span class="text-xs font-mono font-bold text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded">#{{ $circuit->id }}</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                @if($circuit->is_active && ($circuit->initiated_by_id === auth()->id() || $team->owner_id === auth()->id() || $team->isCoordinator(auth()->user())))
                    <button type="button" onclick="document.getElementById('modal-cancel-circuit').classList.remove('hidden')"
                        class="px-2.5 py-1 text-xs font-bold rounded-lg text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-900/20 hover:bg-rose-100 dark:hover:bg-rose-900/40 border border-rose-200 dark:border-rose-800 transition-colors">
                        Cancelar Tramitación
                    </button>
                @endif
                @include('teams.partials.header-toolbar')
            </div>
        </div>

        @include('teams.partials.team-view-nav')
        @include('circuits.partials.circuits-nav')
    </x-slot>

    <div class="space-y-6">

        {{-- Circuit Metadata Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="space-y-2 flex-1 min-w-0">
                <div class="flex items-center gap-2.5 flex-wrap">
                    @php
                        $statusClasses = match($circuit->status) {
                            'approved' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
                            'in_progress' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 animate-pulse',
                            'returned' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300',
                            'rejected' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300',
                            'published' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300',
                            'cancelled' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            default => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                        };
                        $statusLabel = match($circuit->status) {
                            'approved' => 'Aprobado Definitivo',
                            'in_progress' => 'En Tramitación',
                            'returned' => 'Devuelto (En subsanación)',
                            'rejected' => 'Rechazado / Denegado',
                            'published' => 'Publicado en Tablón Edictal',
                            'cancelled' => 'Cancelado',
                            default => ucfirst($circuit->status),
                        };
                    @endphp

                    <span class="px-2.5 py-0.5 text-xs font-black uppercase tracking-wider rounded-lg {{ $statusClasses }}">
                        {{ $statusLabel }}
                    </span>

                    @if($circuit->template)
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400">
                            Plantilla: <strong class="text-gray-800 dark:text-gray-200">{{ $circuit->template->name }}</strong>
                        </span>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                    <span>Iniciado por: <strong class="text-gray-700 dark:text-gray-300">{{ $circuit->initiator?->name }}</strong></span>
                    <span>Fecha: <strong class="text-gray-700 dark:text-gray-300">{{ $circuit->started_at?->format('d/m/Y H:i') ?? $circuit->created_at->format('d/m/Y H:i') }}</strong></span>

                    @if($circuit->circuitable)
                        <div class="flex items-center gap-1.5 font-bold">
                            <span>Elemento vinculado:</span>
                            @if($circuit->circuitable instanceof \App\Models\Activity)
                                <a href="{{ route('teams.activities.show', [$team, $circuit->circuitable]) }}" class="text-violet-600 dark:text-violet-400 hover:underline inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    Actividad: {{ $circuit->circuitable->title }}
                                </a>
                            @elseif($circuit->circuitable instanceof \App\Models\Expediente)
                                <a href="{{ route('teams.expedientes.show', [$team, $circuit->circuitable]) }}" class="text-violet-600 dark:text-violet-400 hover:underline inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                    Expediente: {{ $circuit->circuitable->code }} — {{ $circuit->circuitable->title }}
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Documento Oficial Firmado & Progress indicator --}}
            <div class="flex items-center gap-3 shrink-0 flex-wrap">
                <a href="{{ route('teams.circuits.document', [$team, $circuit]) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl text-xs font-black bg-violet-50 hover:bg-violet-100 dark:bg-violet-900/30 dark:hover:bg-violet-900/50 text-violet-700 dark:text-violet-300 border border-violet-200 dark:border-violet-700 transition-all shadow-2xs group">
                    <svg class="w-4 h-4 text-violet-600 dark:text-violet-400 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Diligencia / PDF Oficial</span>
                    @if($circuit->signatures_count > 0)
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-black bg-emerald-500 text-white">
                            {{ $circuit->signatures_count }} {{ $circuit->signatures_count === 1 ? 'firma' : 'firmas' }}
                        </span>
                    @endif
                </a>

                <div class="flex flex-col items-end">
                    <span class="text-[10px] uppercase font-bold text-gray-400">Progreso Global</span>
                    <div class="flex items-center gap-2 mt-1">
                        <div class="w-28 h-2 bg-gray-100 dark:bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-600 rounded-full" style="width: {{ $circuit->progress_percentage }}%"></div>
                        </div>
                        <span class="text-xs font-black text-violet-600 dark:text-violet-400">{{ $circuit->progress_percentage }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Public Board Banner if Published -->
        @if($circuit->publication)
            <div class="p-6 bg-gradient-to-r from-teal-500/10 via-emerald-500/10 to-transparent border border-teal-200 dark:border-teal-800/50 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-teal-500 text-white flex items-center justify-center shrink-0 shadow-lg shadow-teal-500/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded-md bg-teal-100 dark:bg-teal-900/40 text-teal-800 dark:text-teal-300">
                                Tablón Edictal Activo
                            </span>
                            <span class="text-xs font-mono font-bold text-gray-600 dark:text-gray-300">
                                Código: {{ $circuit->publication->document_code }}
                            </span>
                        </div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white mt-1">
                            Este documento está publicado al servicio de la ciudadanía
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            Publicado el {{ $circuit->publication->publication_date->format('d/m/Y') }}
                            @if($circuit->publication->expiration_date)
                                · Vigente hasta el {{ $circuit->publication->expiration_date->format('d/m/Y') }}
                            @endif
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2.5 shrink-0">
                    <a href="{{ route('public.circuits.show', $circuit->publication->public_slug) }}" target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all">
                        <span>Ver en Tablón Público</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>
        @elseif($circuit->status === \App\Models\CircuitExecution::STATUS_APPROVED)
            <!-- Option to publish on public notice board if approved -->
            <div class="p-6 bg-gradient-to-r from-violet-500/10 via-blue-500/10 to-transparent border border-violet-200 dark:border-violet-800/50 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-2xl bg-violet-600 text-white flex items-center justify-center shrink-0 shadow-lg shadow-violet-600/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-base font-black text-gray-900 dark:text-white">
                            ¡Tramitación Aprobada y Concluida!
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Todos los pasos del circuito han sido validados con éxito. Puedes publicar el documento en el Tablón Edictal público.
                        </p>
                    </div>
                </div>
                <form action="{{ route('teams.circuits.publish', [$team, $circuit]) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-violet-600 hover:bg-violet-700 text-white shadow-md shadow-violet-600/20 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>
                        <span>Publicar en Tablón Edictal</span>
                    </button>
                </form>
            </div>
        @endif

        <!-- Action Card for Current Assignee -->
        @if($currentUserCanAct && $circuit->currentStep)
            <div class="bg-gradient-to-br from-violet-600 via-indigo-600 to-purple-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl shadow-indigo-600/20 relative overflow-hidden"
                x-data="{ 
                    decision: 'approved', 
                    sigMethod: 'internal_certificate', 
                    signNow: {{ $circuit->currentStep->requires_signature ? 'true' : 'true' }} 
                }">

                <!-- Background decorative glow -->
                <div class="absolute -right-12 -top-12 w-64 h-64 bg-white/10 rounded-full blur-2xl pointer-events-none"></div>

                <div class="relative z-10">
                    <div class="flex items-center gap-2.5 text-xs font-black uppercase tracking-wider text-violet-200">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                        Acción Requerida Sobre tu Mesa
                    </div>

                    <h2 class="text-xl sm:text-2xl font-black mt-2">
                        Paso {{ $circuit->currentStep->order }}: {{ $circuit->currentStep->name }} ({{ $circuit->currentStep->action_type_label }})
                    </h2>

                    @if($circuit->currentStep->description)
                        <p class="text-sm text-violet-100 mt-1 max-w-3xl">
                            {{ $circuit->currentStep->description }}
                        </p>
                    @endif

                    <!-- Decision Form -->
                    <form action="{{ route('teams.circuits.steps.process', [$team, $circuit, $circuit->currentStep]) }}" method="POST" enctype="multipart/form-data" class="mt-6 space-y-4">
                        @csrf

                        <!-- Decision Mode Selector -->
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="decision" value="approved" x-model="decision" class="sr-only">
                                <div class="p-4 rounded-2xl border-2 transition-all flex items-center gap-3 text-sm font-black"
                                    :class="decision === 'approved' ? 'bg-white text-gray-900 border-white shadow-lg' : 'bg-white/10 text-white border-white/20 hover:bg-white/15'">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div>
                                        <div>Aprobar / Conforme</div>
                                        <div class="text-[11px] font-normal opacity-80">Avanza al siguiente paso</div>
                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="decision" value="returned" x-model="decision" class="sr-only">
                                <div class="p-4 rounded-2xl border-2 transition-all flex items-center gap-3 text-sm font-black"
                                    :class="decision === 'returned' ? 'bg-white text-gray-900 border-white shadow-lg' : 'bg-white/10 text-white border-white/20 hover:bg-white/15'">
                                    <div class="w-8 h-8 rounded-xl bg-amber-500 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M3 10h10a5 5 0 015 5v2m-15-7l4-4m-4 4l4 4"/></svg>
                                    </div>
                                    <div>
                                        <div>Devolver para Subsanar</div>
                                        <div class="text-[11px] font-normal opacity-80">Solicitar correcciones</div>
                                    </div>
                                </div>
                            </label>

                            <label class="cursor-pointer">
                                <input type="radio" name="decision" value="rejected" x-model="decision" class="sr-only">
                                <div class="p-4 rounded-2xl border-2 transition-all flex items-center gap-3 text-sm font-black"
                                    :class="decision === 'rejected' ? 'bg-white text-gray-900 border-white shadow-lg' : 'bg-white/10 text-white border-white/20 hover:bg-white/15'">
                                    <div class="w-8 h-8 rounded-xl bg-rose-500 text-white flex items-center justify-center shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </div>
                                    <div>
                                        <div>Denegar / Rechazar</div>
                                        <div class="text-[11px] font-normal opacity-80">Finaliza negativamente</div>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <!-- Comments / Justification Input -->
                        <div class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20">
                            <label for="comments" class="block text-xs font-bold uppercase tracking-wider text-violet-100 mb-1.5">
                                <span x-show="decision === 'approved'">Observaciones o Justificación del Dictamen (Opcional)</span>
                                <span x-show="decision === 'returned'" class="text-amber-200">Motivo de devolución y aspectos a subsanar (Obligatorio)</span>
                                <span x-show="decision === 'rejected'" class="text-rose-200">Motivo formal de la denegación (Obligatorio)</span>
                            </label>
                            <textarea id="comments" name="comments" rows="2"
                                placeholder="Indica observaciones, motivación o diligencia de visto bueno..."
                                class="w-full rounded-xl bg-white/20 border-white/30 text-white placeholder-violet-200/60 focus:bg-white focus:text-gray-900 focus:border-white focus:ring-2 focus:ring-white text-sm transition-all"
                                :required="decision !== 'approved'"></textarea>
                        </div>

                        <!-- Panel de Firma Digital Electrónica -->
                        <div x-show="decision === 'approved'" class="bg-white/10 backdrop-blur-md rounded-2xl p-4 border border-white/20 space-y-3">
                            <div class="flex items-center justify-between flex-wrap gap-2">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-emerald-500 text-white flex items-center justify-center shrink-0 shadow-sm">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                    </div>
                                    <div>
                                        <div class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                                            <span>Diligencia de Firma Digital</span>
                                            @if($circuit->currentStep->requires_signature)
                                                <span class="px-2 py-0.2 rounded-full bg-amber-400 text-amber-950 font-black text-[9px] tracking-normal uppercase">Requerida</span>
                                            @endif
                                        </div>
                                        <div class="text-[11px] text-violet-200">
                                            @if($circuit->currentStep->requires_signature)
                                                Esta etapa exige la firma digital formal del revisor antes de concluir.
                                            @else
                                                Puedes registrar tu firma en el documento oficial generado.
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="sign_now" value="1" x-model="signNow"
                                        {{ $circuit->currentStep->requires_signature ? 'checked disabled' : '' }}
                                        class="sr-only peer">
                                    @if($circuit->currentStep->requires_signature)
                                        <input type="hidden" name="sign_now" value="1">
                                    @endif
                                    <div class="w-9 h-5 bg-white/30 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-white after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-emerald-400"></div>
                                    <span class="ml-2.5 text-xs font-bold text-white">Estampar Firma Digital</span>
                                </label>
                            </div>

                            <div x-show="signNow" class="space-y-3 pt-2 border-t border-white/15">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-violet-200 mb-1">Método de Firma Digital</label>
                                        <select name="signature_method" x-model="sigMethod"
                                            class="w-full text-xs rounded-xl bg-white/20 border-white/30 text-white focus:bg-white focus:text-gray-900 focus:ring-white">
                                            <option value="internal_certificate" class="text-gray-900">Firma Electrónica Acreditada (Sientia.MTX)</option>
                                            <option value="autofirma" class="text-gray-900">Certificado Digital (AutoFirma / DNIe)</option>
                                            <option value="manual_upload" class="text-gray-900">Subir PDF firmado externamente</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-[10px] font-bold uppercase tracking-wider text-violet-200 mb-1">Firmante Certificado</label>
                                        <input type="text" readonly value="{{ auth()->user()->name }} ({{ auth()->user()->email }})"
                                            class="w-full text-xs rounded-xl bg-white/10 border-white/20 text-violet-100 cursor-not-allowed">
                                    </div>
                                </div>

                                <!-- Subida de archivo firmado si es manual -->
                                <div x-show="sigMethod === 'manual_upload'" class="p-3 bg-black/20 rounded-xl space-y-2">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-violet-200">
                                        Selecciona el archivo PDF firmado con tu software externo:
                                    </label>
                                    <input type="file" name="signed_file" accept=".pdf,application/pdf"
                                        class="w-full text-xs text-violet-200 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-white file:text-violet-900 hover:file:bg-violet-100 cursor-pointer">
                                </div>

                                <!-- Aviso AutoFirma -->
                                <div x-show="sigMethod === 'autofirma'" class="p-3 bg-black/20 rounded-xl flex items-center justify-between text-[11px] text-violet-100 gap-2">
                                    <span>Se invocará el certificado de firma reconocido en tu estación de trabajo (FNMT / DNIe).</span>
                                    <a href="https://firmaelectronica.gob.es/Home/Descargas.html" target="_blank" class="underline text-emerald-300 font-bold shrink-0">Descargar AutoFirma</a>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <button type="submit"
                                class="px-6 py-3 rounded-xl font-black text-sm bg-white text-violet-900 hover:bg-violet-50 shadow-lg shadow-black/20 hover:scale-[1.02] active:scale-[0.98] transition-all flex items-center gap-2">
                                <template x-if="decision === 'approved'">
                                    <span class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                        <span x-text="signNow ? 'Firmar Digitalmente y Aprobar' : 'Aprobar y Concluir Etapa'"></span>
                                    </span>
                                </template>
                                <span x-show="decision === 'returned'">Enviar para Subsanación</span>
                                <span x-show="decision === 'rejected'">Confirmar Rechazo</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <!-- Stepper Component -->
        @include('circuits.partials.stepper', ['circuit' => $circuit])

        <!-- Content details of the underlying item -->
        @if($circuit->circuitable)
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-6">
                <h3 class="text-base font-black text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                    Contenido del Documento / Propuesta
                </h3>
                <div class="prose dark:prose-invert max-w-none text-sm text-gray-700 dark:text-gray-300">
                    @if($circuit->circuitable->description)
                        {!! $circuit->circuitable->description !!}
                    @else
                        <p class="text-gray-400 italic">Sin descripción detallada.</p>
                    @endif
                </div>
            </div>
        @endif

    </div>

    <!-- Modal Cancel -->
    <div id="modal-cancel-circuit" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs hidden">
        <div class="bg-white dark:bg-gray-800 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-black text-gray-900 dark:text-white">¿Cancelar esta tramitación?</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                La tramitación se marcará como cancelada y quedará archivada en el historial.
            </p>
            <form action="{{ route('teams.circuits.cancel', [$team, $circuit]) }}" method="POST" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="reason" class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Motivo de la cancelación</label>
                    <textarea id="reason" name="reason" rows="3" required placeholder="Explica brevemente por qué se cancela..."
                        class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-white"></textarea>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('modal-cancel-circuit').classList.add('hidden')"
                        class="px-4 py-2 text-xs font-bold rounded-xl text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                        Cerrar
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-xs font-black rounded-xl bg-rose-600 text-white hover:bg-rose-700 shadow-md shadow-rose-600/20">
                        Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

