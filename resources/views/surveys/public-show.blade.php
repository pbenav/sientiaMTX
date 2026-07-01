<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }} — SientiaMTX</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        #promoter-map {
            height: 100%;
            width: 100%;
            z-index: 1;
        }
        .custom-promoter-pin {
            background: none;
            border: none;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">
    <!-- Header/Navbar Institucional -->
    <header class="bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-150 dark:border-gray-800 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-tr from-violet-600 to-indigo-600 text-white rounded-xl shadow-md">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5H4.5V21M3 21h18"/></svg>
                </div>
                <div>
                    <span class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-wider">SIENTIA MTX</span>
                    <span class="block text-[9px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('Canal de Participación Ciudadana') }}</span>
                </div>
            </div>
            @if(auth()->check())
                <a href="{{ $survey->team_id ? route('teams.surveys.index', $survey->team_id) : route('global-surveys.index') }}" class="flex items-center gap-1.5 px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-xs font-black uppercase tracking-widest text-gray-700 dark:text-gray-300 rounded-xl transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    <span>{{ __('Volver al Gestor') }}</span>
                </a>
            @endif
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8 w-full flex-1">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
            
            <!-- Columna Principal (70%): Encuesta y Resultados -->
            <div class="lg:col-span-8 space-y-8">
                <!-- Título y Descripción de la Encuesta -->
                <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl border border-gray-100 dark:border-gray-800 p-6 sm:p-8 flex flex-col relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-gradient-to-bl from-violet-500/10 to-transparent pointer-events-none rounded-full"></div>
                    <div class="flex flex-wrap items-center gap-4 mb-4">
                        <span class="inline-flex items-center gap-1.5 text-[9px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full {{ $survey->is_closed ? 'bg-red-50 text-red-600 dark:bg-red-500/10 dark:text-red-400' : 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400 animate-pulse' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $survey->is_closed ? 'bg-red-500' : 'bg-emerald-500' }}"></span>
                            {{ $survey->is_closed ? __('Finalizada') : __('Activa') }}
                        </span>
                        @if($survey->expires_at)
                            <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                                {{ $survey->is_expired ? __('Expiró') : __('Finaliza') }} {{ $survey->expires_at->diffForHumans() }}
                            </span>
                        @endif
                    </div>
                    <h1 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $survey->title }}</h1>
                    @if($survey->description)
                        <p class="mt-4 text-sm text-gray-500 dark:text-gray-400 font-medium leading-relaxed">{{ $survey->description }}</p>
                    @endif
                </div>

                @if(session('success'))
                    <div class="p-6 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-[2rem] flex items-center gap-4 text-emerald-700 dark:text-emerald-400 shadow-md">
                        <div class="p-3 bg-emerald-600 text-white rounded-2xl shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </div>
                        <span class="font-black uppercase tracking-wider text-sm">{{ session('success') }}</span>
                    </div>
                @endif

                @if(session('error'))
                    <div class="p-6 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-[2rem] flex items-center gap-4 text-red-700 dark:text-red-400 shadow-md">
                        <div class="p-3 bg-red-600 text-white rounded-2xl shadow-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <span class="font-black uppercase tracking-wider text-sm">{{ session('error') }}</span>
                    </div>
                @endif

                @if($showResults)
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl p-6 sm:p-8 border border-gray-100 dark:border-gray-800">
                        <h2 class="text-lg sm:text-xl font-black text-gray-900 dark:text-white tracking-tight uppercase mb-6 border-b border-gray-100 dark:border-gray-850 pb-4">
                            {{ __('Resultados y Participación') }}
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($survey->questions as $question)
                                <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col h-full">
                                    <div class="mb-3 border-l-3 border-indigo-600 pl-3">
                                        <h3 class="text-xs sm:text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight" title="{{ $question->title }}">{{ $question->title }}</h3>
                                    </div>
                                    
                                    @if($question->type !== 'text')
                                        <div class="flex flex-1 items-center gap-4">
                                            <div class="flex-1 space-y-3">
                                                @php 
                                                    $qTotalVotes = $question->votes()->count();
                                                    $maxVotes = $question->options->max('votes_count');
                                                @endphp
                                                @foreach($question->options->sortByDesc('votes_count')->take(3) as $option)
                                                    @php
                                                        $percentage = $qTotalVotes > 0 ? round(($option->votes_count / $qTotalVotes) * 100, 0) : 0;
                                                        $isWinner = $qTotalVotes > 0 && $maxVotes === $option->votes_count;
                                                    @endphp
                                                    <div class="relative">
                                                        <div class="flex items-center justify-between mb-1 px-0.5">
                                                            <span class="text-[11px] sm:text-xs font-bold {{ $isWinner ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">
                                                                {{ $option->label }}
                                                            </span>
                                                            <span class="text-xs font-black {{ $isWinner ? 'text-indigo-600' : 'text-gray-400' }}">
                                                                {{ $percentage }}%
                                                            </span>
                                                        </div>
                                                        <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-800/50 rounded-full overflow-hidden">
                                                            <div class="h-full rounded-full {{ $isWinner ? 'bg-indigo-600' : 'bg-gray-400 dark:bg-gray-600' }}"
                                                                 style="width: {{ $percentage }}%">
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="relative shrink-0 flex justify-center">
                                                <div class="w-[80px] h-[80px]">
                                                    <canvas id="chart-{{ $question->id }}" 
                                                            data-type="{{ $question->type }}" 
                                                            data-labels='@json($question->options->pluck("label"))'
                                                            data-values='@json($question->options->pluck("votes_count"))'></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="flex-1 mt-2 space-y-2 overflow-y-auto max-h-[150px] custom-scrollbar pr-2">
                                            @forelse($question->votes->take(5) as $vote)
                                                @if($vote->text_value)
                                                    <div class="p-3 bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50 shadow-sm">
                                                        <p class="text-xs text-gray-600 dark:text-gray-400 font-medium leading-tight italic">"{{ $vote->text_value }}"</p>
                                                    </div>
                                                @endif
                                            @empty
                                                <div class="text-center py-4 text-xs font-bold text-gray-400 uppercase tracking-widest">{{ __('Sin comentarios todavía') }}</div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(!$hasVoted || ($hasVoted && !$survey->is_closed && $survey->allow_multiple_votes))
                    @php
                        $initialAnswers = [];
                    @endphp
                    <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl overflow-hidden p-6 sm:p-8 border border-gray-100 dark:border-gray-800" x-data="votingManager(@json($initialAnswers))">
                        <form action="{{ route('public.surveys.store', $survey->uuid) }}" method="POST" id="survey-form">
                            @csrf
                            <div class="space-y-8">
                                @foreach($survey->questions as $index => $question)
                                    <div class="relative">
                                        <div class="flex items-start gap-4 mb-4">
                                            <div class="w-8 h-8 shrink-0 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center font-black text-base text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20 shadow-sm">
                                                {{ $index + 1 }}
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-base font-black text-gray-900 dark:text-white tracking-tight uppercase mb-1">
                                                    {{ $question->title }}
                                                    @if($question->is_required)<span class="text-red-500 ml-1">*</span>@endif
                                                </h3>
                                                @if($question->description)
                                                    <p class="text-sm text-gray-500 dark:text-gray-400 font-medium leading-normal">{{ $question->description }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="pl-0 sm:pl-12">
                                            @if($question->type === 'single_choice' || $question->type === 'multiple_choice')
                                                <div class="grid grid-cols-1 gap-4">
                                                    @foreach($question->options as $option)
                                                        <label class="group relative flex items-center p-4 bg-gray-50 dark:bg-gray-800/50 border-2 rounded-2xl cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/5"
                                                               :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-500/10' : 'border-transparent'">
                                                            <div class="relative flex items-center justify-center w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded-md group-hover:border-indigo-500 transition-colors overflow-hidden"
                                                                 :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600' : ''">
                                                                <input type="{{ $question->type === 'single_choice' ? 'radio' : 'checkbox' }}" 
                                                                       name="answers[{{ $question->id }}]{{ $question->type === 'multiple_choice' ? '[]' : '' }}" 
                                                                       value="{{ $option->id }}"
                                                                       x-model="answers['{{ $question->id }}']"
                                                                       class="peer absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                                                       {{ $question->is_required && $question->type === 'single_choice' ? 'required' : '' }}>
                                                                <div class="w-full h-full bg-indigo-600 p-1 transition-opacity duration-300 pointer-events-none"
                                                                     :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'opacity-100' : 'opacity-0'">
                                                                    <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                            </div>
                                                            <div class="ml-4">
                                                                <span class="block text-sm font-bold text-gray-900 dark:text-white transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                                                                      :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                                                    {{ $option->label }}
                                                                </span>
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif($question->type === 'rating')
                                                <div class="flex flex-col items-center p-6 bg-gray-50 dark:bg-gray-800/30 rounded-3xl border border-gray-150 dark:border-gray-800">
                                                    <div class="flex gap-4">
                                                        <template x-for="i in 5">
                                                            <button type="button" @click="answers['{{ $question->id }}'] = i" 
                                                                    class="p-2 transition-all duration-300 transform hover:scale-125"
                                                                    :class="answers['{{ $question->id }}'] >= i ? 'text-amber-400 drop-shadow-xl' : 'text-gray-300 dark:text-gray-600'">
                                                                <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                    <input type="hidden" name="answers[{{ $question->id }}]" :value="answers['{{ $question->id }}']" {{ $question->is_required ? 'required' : '' }}>
                                                </div>
                                            @elseif($question->type === 'text')
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-2xl p-4 border-2 border-transparent focus-within:border-indigo-500/50 transition-all duration-300">
                                                    <textarea name="answers[{{ $question->id }}]" rows="3" x-model="answers['{{ $question->id }}']"
                                                              class="w-full bg-transparent border-none focus:ring-0 text-base text-gray-900 dark:text-white placeholder-gray-400 font-medium resize-none"
                                                              placeholder="{{ __('Escribe aquí tus ideas o comentarios...') }}"
                                                              {{ $question->is_required ? 'required' : '' }}></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            @if(isset($survey->data_protection['enabled']) && $survey->data_protection['enabled'])
                                @php
                                    $dpTemplate = $survey->data_protection['template'] ?? '';
                                    $dpTemplate = str_replace(
                                        ['{responsable}', '{correo}', '{direccion}', '{url}', '{finalidad}'],
                                        [
                                            '<strong>' . e($survey->data_protection['responsible'] ?? 'No especificado') . '</strong>',
                                            '<strong>' . e($survey->data_protection['dpo_email'] ?? 'No especificado') . '</strong>',
                                            '<strong>' . e($survey->data_protection['address'] ?? 'No especificado') . '</strong>',
                                            !empty($survey->data_protection['url']) ? '<a href="'.e($survey->data_protection['url']).'" target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">Enlace a la política ampliada</a>' : '<strong>No especificado</strong>',
                                            '<strong>' . e($survey->data_protection['purpose'] ?? 'No especificado') . '</strong>',
                                        ],
                                        e($dpTemplate)
                                    );
                                    // Hack since we escaped everything but we injected strong/a tags.
                                    $dpTemplate = str_replace(['&lt;strong&gt;', '&lt;/strong&gt;', '&lt;a href=', '&quot;', ' target=_blank class=text-indigo-600 hover:text-indigo-800 underline&gt;', '&lt;/a&gt;'], ['<strong>', '</strong>', '<a href=', '"', ' target="_blank" class="text-indigo-600 hover:text-indigo-800 underline">', '</a>'], $dpTemplate);
                                @endphp
                                <div class="mt-10 pt-8 border-t border-gray-100 dark:border-gray-800">
                                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-2xl p-6 border border-gray-100 dark:border-gray-800">
                                        <div class="flex items-start gap-3 mb-3">
                                            <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg shrink-0">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                                            </div>
                                            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-widest mt-0.5">Protección de Datos</h4>
                                        </div>
                                        <div class="text-[11px] text-gray-600 dark:text-gray-400 leading-relaxed font-medium">
                                            {!! nl2br($dpTemplate) !!}
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="mt-10 pt-8 border-t border-gray-100 dark:border-gray-800"></div>
                            @endif

                            <div class="mt-8 flex justify-center">
                                <button type="submit" 
                                        class="group relative inline-flex items-center justify-center px-10 py-4 font-bold text-white tracking-wider uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-2xl hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                    <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700"></div>
                                    <span class="relative flex items-center gap-2 text-lg">
                                        {{ __('Enviar Respuestas') }}
                                        <svg class="w-5 h-5 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                @endif
            </div>

            <!-- Columna Lateral (30%): Organismo Emisor / Transparencia -->
            <div class="lg:col-span-4 space-y-6">
                <!-- Tarjeta de Información Oficial del Canal Ciudadano -->
                <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden p-6 sm:p-8 flex flex-col gap-6">
                    <!-- Cabecera Institucional -->
                    <div class="flex items-center gap-4 pb-4 border-b border-gray-100 dark:border-gray-800">
                        <div class="p-3 bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 rounded-2xl shadow-sm">
                            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5H4.5V21M3 21h18M12 6.75a.75.75 0 11-.75.75.75.75 0 01.75-.75z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest">{{ __('Canal Ciudadano') }}</h3>
                            <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Consulta Pública y Transparencia') }}</p>
                        </div>
                    </div>

                    <!-- Organismo Promotor -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Entidad Promotora') }}</h4>
                        <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-2xl border border-gray-100/50 dark:border-gray-800">
                            <img src="{{ $survey->creator->profile_photo_url }}" class="w-12 h-12 rounded-full border border-white dark:border-gray-800 shadow-md">
                            <div class="min-w-0 flex-1">
                                <span class="block text-sm font-black text-gray-900 dark:text-white uppercase tracking-tight truncate">{{ $survey->creator->name }}</span>
                                <span class="inline-flex items-center gap-1.5 text-[9px] font-black text-violet-600 dark:text-violet-400 uppercase tracking-widest mt-1 bg-violet-50 dark:bg-violet-500/10 px-2 py-0.5 rounded-full">
                                    {{ $survey->creator->working_area_name ?: __('Ayuntamiento General') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Mapa Interactivo / Localización -->
                    <div class="space-y-4">
                        <h4 class="text-xs font-black text-gray-400 dark:text-gray-500 uppercase tracking-widest">{{ __('Ubicación del Centro') }}</h4>
                        <div class="relative w-full h-[220px] rounded-[1.5rem] border border-gray-100 dark:border-gray-800 overflow-hidden shadow-inner">
                            <div id="promoter-map" class="w-full h-full"></div>
                            <div class="absolute bottom-3 left-3 right-3 bg-white/95 dark:bg-gray-900/95 backdrop-blur-sm p-3 rounded-xl shadow-lg border border-gray-100 dark:border-gray-800 z-[1000] flex items-center gap-2.5">
                                <div class="p-1.5 bg-violet-50 dark:bg-violet-500/10 text-violet-600 dark:text-violet-400 rounded-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/></svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <span class="block text-[10px] font-black text-gray-900 dark:text-white uppercase tracking-tight truncate">{{ $survey->creator->working_area_name ?: __('Ayuntamiento General') }}</span>
                                    <span class="block text-[9px] font-bold text-gray-400 truncate">
                                        @if($survey->creator->location_lat && $survey->creator->location_lng)
                                            Lat: {{ number_format($survey->creator->location_lat, 4) }}, Lng: {{ number_format($survey->creator->location_lng, 4) }}
                                        @else
                                            {{ __('Ubicación Centralizada') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Nota de Privacidad y RGPD -->
                    <div class="p-4 bg-emerald-50/50 dark:bg-emerald-500/5 rounded-2xl border border-emerald-100/50 dark:border-emerald-500/10 flex items-start gap-3">
                        <div class="p-2 bg-emerald-600 text-white rounded-xl shadow-lg shadow-emerald-600/20 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
                        </div>
                        <div class="flex-1">
                            <span class="block text-xs font-black text-emerald-800 dark:text-emerald-400 uppercase tracking-widest mb-1">{{ __('Garantía de Confidencialidad') }}</span>
                            <p class="text-[10px] text-emerald-700/80 dark:text-emerald-400/80 font-bold leading-normal">
                                {{ __('Tus respuestas son totalmente anónimas y se procesan de acuerdo con el RGPD para la mejora continua de la gestión ciudadana.') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="text-center mt-12 pb-8">
            <p class="text-xs text-gray-400 font-bold uppercase tracking-widest">Desarrollado con <span class="text-indigo-600 dark:text-indigo-400 font-black">sientiaMTX</span></p>
        </div>
    </div>

    <!-- Leaflet JS and promoter map instantiation -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const lat = {{ $survey->creator->location_lat ?: '40.416775' }};
            const lng = {{ $survey->creator->location_lng ?: '-3.703790' }};

            const map = L.map('promoter-map', {
                zoomControl: false,
                attributionControl: false
            }).setView([lat, lng], 13);

            const isDark = document.documentElement.classList.contains('dark');
            const cartoUrl = isDark ?
                'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png' :
                'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';

            L.tileLayer(cartoUrl, {
                maxZoom: 20
            }).addTo(map);

            // Icono del PIN premium adaptativo
            const pinIcon = L.divIcon({
                html: `
                    <div class="relative w-8 h-8 flex items-center justify-center">
                        <span class="absolute w-6 h-6 bg-violet-500/35 rounded-full animate-ping opacity-75"></span>
                        <div class="w-7.5 h-7.5 bg-gradient-to-tr from-violet-500 to-indigo-600 rounded-full border-2 border-white dark:border-gray-950 flex items-center justify-center text-white shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.5H4.5V21M3 21h18"/></svg>
                        </div>
                    </div>`,
                className: 'custom-promoter-pin',
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            L.marker([lat, lng], { icon: pinIcon }).addTo(map);

            setTimeout(() => {
                map.invalidateSize();
            }, 250);
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const isDark = document.documentElement.classList.contains('dark');
            const textColor = isDark ? '#94a3b8' : '#64748b';
            
            const colors = [
                '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#f59e0b', 
                '#10b981', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6'
            ];

            document.querySelectorAll('canvas[id^="chart-"]').forEach(canvas => {
                const ctx = canvas.getContext('2d');
                const type = canvas.dataset.type;
                const labels = JSON.parse(canvas.dataset.labels);
                const values = JSON.parse(canvas.dataset.values);

                let chartConfig = {
                    type: type === 'single_choice' ? 'doughnut' : 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: type === 'single_choice' ? colors : colors[0],
                            borderRadius: type === 'single_choice' ? 0 : 8,
                            borderWidth: 0,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: { display: false }
                        }
                    }
                };

                if (type === 'multiple_choice' || type === 'rating') {
                    chartConfig.options.indexAxis = type === 'multiple_choice' ? 'y' : 'x';
                    chartConfig.options.scales = {
                        y: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { color: textColor, font: { weight: 'bold' } }
                        },
                        x: {
                            beginAtZero: true,
                            grid: { display: false },
                            ticks: { color: textColor, font: { weight: 'bold' } }
                        }
                    };
                }

                if (type === 'single_choice') {
                    chartConfig.options.cutout = '60%';
                }

                new Chart(ctx, chartConfig);
            });
        });

        function votingManager(initialAnswers) {
            return {
                answers: {},
                init() {
                    const data = initialAnswers || {};
                    @foreach($survey->questions as $question)
                        @php
                            $qId = $question->id;
                            $type = $question->type;
                        @endphp
                        
                        if (data['{{ $qId }}'] !== undefined) {
                            @if($type === 'multiple_choice')
                                this.answers['{{ $qId }}'] = Array.isArray(data['{{ $qId }}']) 
                                    ? data['{{ $qId }}'].map(String) 
                                    : [String(data['{{ $qId }}'])];
                            @elseif($type === 'single_choice' || $type === 'rating')
                                const val = data['{{ $qId }}'];
                                this.answers['{{ $qId }}'] = Array.isArray(val) ? String(val[0]) : String(val);
                            @else
                                this.answers['{{ $qId }}'] = String(data['{{ $qId }}']);
                            @endif
                        } else {
                            @if($type === 'multiple_choice')
                                this.answers['{{ $qId }}'] = [];
                            @else
                                this.answers['{{ $qId }}'] = '';
                            @endif
                        }
                    @endforeach
                },
                isSelected(qId, oId, type) {
                    const current = this.answers[String(qId)];
                    if (current === undefined || current === null || current === '') return false;
                    
                    if (type === 'single_choice') {
                        return String(current) === String(oId);
                    } else if (type === 'multiple_choice') {
                        return Array.isArray(current) && current.includes(String(oId));
                    }
                    return false;
                }
            }
        }
    </script>
</body>
</html>
