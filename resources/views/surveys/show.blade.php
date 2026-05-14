<x-app-layout>
    <div class="py-8 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50 to-white dark:from-gray-950 dark:to-gray-900 min-h-screen">
        <div class="max-w-4xl mx-auto">
            
            <!-- Breadcrumbs / Navigation -->
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-4">
                    <a href="{{ route('teams.surveys.index', $team) }}" class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 text-gray-500 hover:text-indigo-600 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black uppercase tracking-widest text-indigo-600 mb-0.5">{{ __('Módulo de Encuestas') }}</span>
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight uppercase leading-none">{{ $survey->title }}</h1>
                    </div>
                </div>

                @can('update', $survey)
                <div class="flex items-center gap-2">
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 text-gray-500 hover:text-indigo-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                             class="absolute right-0 mt-3 w-56 bg-white dark:bg-gray-900 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 py-3 z-50">
                            
                            <a href="{{ route('teams.surveys.edit', [$team, $survey]) }}" class="flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                {{ __('Editar Encuesta') }}
                            </a>

                            @if(!$survey->is_closed)
                                <form action="{{ route('teams.surveys.close', [$team, $survey]) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ __('Cerrar Encuesta') }}
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('teams.surveys.reactivate', [$team, $survey]) }}" method="POST">
                                    @csrf @method('PUT')
                                    <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        {{ __('Reactivar Encuesta') }}
                                    </button>
                                </form>
                            @endif

                            <div class="my-2 border-t border-gray-100 dark:border-gray-800"></div>

                            <form action="{{ route('teams.surveys.destroy', [$team, $survey]) }}" method="POST" onsubmit="return confirm('¿Estás seguro de que quieres eliminar esta encuesta?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    {{ __('Eliminar') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                @endcan
            </div>

            <!-- Main Content Card -->
            <div class="bg-white dark:bg-gray-900 rounded-[3rem] shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden mb-12">
                <!-- Status Banner -->
                <div class="px-8 py-4 bg-gray-50 dark:bg-gray-800/50 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="p-2 bg-white dark:bg-gray-900 rounded-xl shadow-sm">
                            <span class="w-3 h-3 block rounded-full {{ $survey->is_closed ? 'bg-red-500' : 'bg-emerald-500 animate-pulse' }}"></span>
                        </div>
                        <span class="text-xs font-black uppercase tracking-widest text-gray-500 dark:text-gray-400">
                            {{ $survey->is_closed ? __('Encuesta Finalizada') : __('Encuesta en Curso') }}
                        </span>
                    </div>
                    @if($survey->expires_at)
                        <div class="flex items-center gap-2 text-xs font-bold text-gray-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ $survey->is_expired ? __('Expiró') : __('Finaliza') }} {{ $survey->expires_at->diffForHumans() }}
                        </div>
                    @endif
                </div>

                <div class="p-8 sm:p-12">
                    <!-- Survey Info -->
                    <div class="mb-12">
                        <h2 class="text-3xl font-black text-gray-900 dark:text-white mb-4 tracking-tight uppercase">{{ $survey->title }}</h2>
                        @if($survey->description)
                            <p class="text-lg text-gray-600 dark:text-gray-400 font-medium leading-relaxed max-w-2xl">{{ $survey->description }}</p>
                        @endif
                    </div>

                    @if(session('success'))
                        <div class="mb-10 p-6 bg-emerald-50 dark:bg-emerald-500/10 border border-emerald-200 dark:border-emerald-500/20 rounded-[2rem] flex items-center gap-4 text-emerald-700 dark:text-emerald-400">
                            <div class="p-3 bg-emerald-600 text-white rounded-2xl shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="font-black uppercase tracking-wider text-sm">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if($hasVoted && !$survey->allow_multiple_votes && !$survey->is_closed)
                        <div class="mb-10 p-6 bg-indigo-50 dark:bg-indigo-500/10 border border-indigo-200 dark:border-indigo-500/20 rounded-[2rem] flex items-center gap-4 text-indigo-700 dark:text-indigo-400">
                            <div class="p-3 bg-indigo-600 text-white rounded-2xl shadow-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            <div>
                                <h4 class="font-black uppercase tracking-wider text-xs mb-1">{{ __('¡Gracias por participar!') }}</h4>
                                <p class="font-medium text-sm">{{ __('Ya has registrado tus votos. Puedes ver la tendencia actual a continuación.') }}</p>
                            </div>
                        </div>
                    @endif

                    @if(!$hasVoted || ($hasVoted && !$survey->is_closed))
                    <div class="space-y-12" x-data="votingManager(@json($userVotes->map(fn($v) => $v->pluck('option_id'))))">
                        <form action="{{ route('teams.surveys.vote', [$team, $survey]) }}" method="POST" id="survey-form">
                            @csrf
                            
                            <div class="space-y-16">
                                @foreach($survey->questions as $index => $question)
                                    <div class="relative">
                                        <!-- Question Label -->
                                        <div class="flex items-start gap-6 mb-8">
                                            <div class="w-12 h-12 shrink-0 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center font-black text-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20">
                                                {{ $index + 1 }}
                                            </div>
                                            <div>
                                                <h3 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight uppercase mb-2">{{ $question->title }}</h3>
                                                @if($question->description)
                                                    <p class="text-gray-500 dark:text-gray-400 font-medium">{{ $question->description }}</p>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Question Content -->
                                        <div class="pl-0 sm:pl-16">
                                            @if($question->type === 'single_choice' || $question->type === 'multiple_choice')
                                                <div class="grid grid-cols-1 gap-4">
                                                    @foreach($question->options as $option)
                                                        <label class="group relative flex items-center p-6 bg-gray-50 dark:bg-gray-800/50 border-2 rounded-3xl cursor-pointer transition-all duration-300 hover:shadow-xl hover:shadow-indigo-500/5"
                                                               :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600 bg-indigo-50/50 dark:bg-indigo-500/10' : 'border-transparent'">
                                                            
                                                            <div class="relative flex items-center justify-center w-6 h-6 border-2 border-gray-300 dark:border-gray-600 rounded-lg group-hover:border-indigo-500 transition-colors overflow-hidden"
                                                                 :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'border-indigo-600' : ''">
                                                                <input type="{{ $question->type === 'single_choice' ? 'radio' : 'checkbox' }}" 
                                                                       name="answers[{{ $question->id }}]{{ $question->type === 'multiple_choice' ? '[]' : '' }}" 
                                                                       value="{{ $option->id }}"
                                                                       x-model="answers['{{ $question->id }}']"
                                                                       class="peer absolute opacity-0 cursor-pointer">
                                                                
                                                                <div class="w-full h-full bg-indigo-600 p-1 transition-opacity duration-300"
                                                                     :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'opacity-100' : 'opacity-0'">
                                                                    <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                                </div>
                                                            </div>

                                                            <div class="ml-6">
                                                                <span class="block text-lg font-bold text-gray-900 dark:text-white transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                                                                      :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                                                    {{ $option->label }}
                                                                </span>
                                                                @if($option->description)
                                                                    <span class="block text-sm text-gray-500 dark:text-gray-400 font-medium">
                                                                        {{ $option->description }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            @elseif($question->type === 'rating')
                                                <div class="flex flex-col items-center p-8 bg-gray-50 dark:bg-gray-800/30 rounded-[2.5rem] border border-gray-100 dark:border-gray-800">
                                                    <div class="flex gap-4">
                                                        <template x-for="i in 5">
                                                            <button type="button" @click="answers['{{ $question->id }}'] = i" 
                                                                    class="p-2 transition-all duration-300 transform hover:scale-125"
                                                                    :class="answers['{{ $question->id }}'] >= i ? 'text-amber-400 drop-shadow-xl' : 'text-gray-300 dark:text-gray-600'">
                                                                <svg class="w-12 h-12" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                                            </button>
                                                        </template>
                                                    </div>
                                                    <input type="hidden" name="answers[{{ $question->id }}]" :value="answers['{{ $question->id }}']">
                                                    <div class="mt-4 text-xs font-black uppercase tracking-[0.2em] text-gray-400">
                                                        <span x-show="!answers['{{ $question->id }}']">{{ __('Haz clic para valorar') }}</span>
                                                        <span x-show="answers['{{ $question->id }}']" x-text="answers['{{ $question->id }}'] + ' / 5 Estrellas'"></span>
                                                    </div>
                                                </div>
                                            @elseif($question->type === 'text')
                                                <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 border-2 border-transparent focus-within:border-indigo-500/50 transition-all duration-300">
                                                    <textarea name="answers[{{ $question->id }}]" rows="4" x-model="answers['{{ $question->id }}']"
                                                              class="w-full bg-transparent border-none focus:ring-0 text-lg text-gray-900 dark:text-white placeholder-gray-400 font-medium resize-none"
                                                              placeholder="{{ __('Escribe aquí tus ideas o comentarios...') }}"></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-16 pt-12 border-t border-gray-100 dark:border-gray-800 flex justify-center">
                                <button type="submit" 
                                        class="group relative inline-flex items-center justify-center px-16 py-6 font-black text-white tracking-widest uppercase transition-all duration-500 ease-in-out transform bg-indigo-600 rounded-full hover:scale-105 active:scale-95 shadow-[0_20px_50px_rgba(79,70,229,0.3)] hover:shadow-[0_20px_50px_rgba(79,70,229,0.5)] overflow-hidden">
                                    <div class="absolute inset-0 w-full h-full bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700"></div>
                                    <span class="relative flex items-center gap-3 text-xl">
                                        {{ $hasVoted ? __('Actualizar mis respuestas') : __('Enviar Encuesta') }}
                                        <svg class="w-6 h-6 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif

                    <!-- Results Section -->
                    @if($showResults)
                        <div class="mt-24 pt-16 border-t-2 border-dashed border-gray-100 dark:border-gray-800">
                            <div class="flex items-center justify-between mb-16">
                                <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight uppercase">
                                    {{ __('Tendencia Actual') }}
                                </h2>
                                <div class="flex items-center gap-2 px-6 py-3 bg-indigo-50 dark:bg-indigo-500/10 rounded-full border border-indigo-100 dark:border-indigo-500/20 shadow-inner">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                                    <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 uppercase tracking-widest pl-2">
                                        {{ $totalVotes }} {{ trans_choice('miembro ha participado|miembros han participado', $totalVotes) }}
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-24">
                                @foreach($survey->questions as $question)
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-8 border-l-4 border-indigo-600 pl-4">{{ $question->title }}</h3>
                                        
                                        @if($question->type !== 'text')
                                            <div class="space-y-8">
                                                @php 
                                                    $qTotalVotes = $question->votes()->count();
                                                    $maxVotes = $question->options->max('votes_count');
                                                @endphp
                                                @foreach($question->options as $option)
                                                    @php
                                                        $percentage = $qTotalVotes > 0 ? round(($option->votes_count / $qTotalVotes) * 100, 1) : 0;
                                                        $isWinner = $qTotalVotes > 0 && $maxVotes === $option->votes_count;
                                                    @endphp
                                                    <div class="relative">
                                                        <div class="flex items-center justify-between mb-3 px-2">
                                                            <div class="flex items-center gap-3">
                                                                <span class="text-lg font-black {{ $isWinner ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300' }}">
                                                                    {{ $option->label }}
                                                                </span>
                                                            </div>
                                                            <div class="text-right">
                                                                <span class="text-xl font-black {{ $isWinner ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500' }}">
                                                                    {{ $percentage }}%
                                                                </span>
                                                                <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">
                                                                    ({{ $option->votes_count }} {{ __('votos') }})
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="h-4 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden shadow-inner border border-gray-100 dark:border-gray-800">
                                                            <div class="h-full rounded-full transition-all duration-1000 ease-out {{ $isWinner ? 'bg-gradient-to-r from-indigo-500 to-violet-600 shadow-[0_0_15px_rgba(79,70,229,0.4)]' : 'bg-gray-300 dark:bg-gray-700' }}"
                                                                 style="width: {{ $percentage }}%">
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <!-- Free Text Answers List -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                @foreach($question->votes as $vote)
                                                    @if($vote->text_value)
                                                        <div class="bg-gray-50 dark:bg-gray-800/50 p-6 rounded-[2rem] border border-gray-100 dark:border-gray-800 relative group transition-all hover:border-emerald-500/30">
                                                            <div class="absolute -top-3 -left-3 w-8 h-8 bg-emerald-500 text-white rounded-xl flex items-center justify-center shadow-lg transform -rotate-12 group-hover:rotate-0 transition-transform">
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/></svg>
                                                            </div>
                                                            <p class="text-gray-700 dark:text-gray-300 font-medium italic leading-relaxed pt-2">"{{ $vote->text_value }}"</p>
                                                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center gap-3">
                                                                <img src="{{ $vote->user->profile_photo_url }}" alt="{{ $vote->user->name }}" class="w-6 h-6 rounded-full shadow-sm">
                                                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-500">{{ $vote->user->name }}</span>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Creator Footer Info -->
            <div class="flex items-center justify-center gap-4 text-gray-400 mb-20">
                <div class="h-px w-12 bg-gray-200 dark:bg-gray-800"></div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-black uppercase tracking-[0.2em]">{{ __('Creada por') }}</span>
                    <img src="{{ $survey->creator->profile_photo_url }}" class="w-6 h-6 rounded-full border border-white dark:border-gray-900 shadow-sm">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">{{ $survey->creator->name }}</span>
                </div>
                <div class="h-px w-12 bg-gray-200 dark:bg-gray-800"></div>
            </div>
        </div>
    </div>

    <script>
        function votingManager(initialAnswers) {
            return {
                answers: {},
                init() {
                    // Initialize answers object
                    const initialData = initialAnswers || {};
                    // We need to transform the data if it's from PHP
                    Object.keys(initialData).forEach(qId => {
                        this.answers[qId] = initialData[qId];
                    });
                },
                isSelected(qId, oId, type) {
                    if (!this.answers[qId]) return false;
                    if (type === 'single_choice') {
                        return this.answers[qId] == oId;
                    } else if (type === 'multiple_choice') {
                        return Array.isArray(this.answers[qId]) && this.answers[qId].includes(oId);
                    }
                    return false;
                }
            }
        }
    </script>
</x-app-layout>
