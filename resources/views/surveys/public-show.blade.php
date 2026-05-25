<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $survey->title }} — SientiaMTX</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="max-w-4xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-10">
            <h1 class="text-3xl font-black text-gray-900 dark:text-white uppercase tracking-tight">{{ $survey->title }}</h1>
            @if($survey->description)
                <p class="mt-3 text-gray-500 dark:text-gray-400">{{ $survey->description }}</p>
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

        @if(session('error'))
            <div class="mb-10 p-6 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 rounded-[2rem] flex items-center gap-4 text-red-700 dark:text-red-400">
                <div class="p-3 bg-red-600 text-white rounded-2xl shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <span class="font-black uppercase tracking-wider text-sm">{{ session('error') }}</span>
            </div>
        @endif

        @if($showResults)
            <div class="mb-12 bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl p-8 sm:p-12 border border-gray-100 dark:border-gray-800">
                <h2 class="text-xl sm:text-2xl font-black text-gray-900 dark:text-white tracking-tight uppercase mb-8">
                    {{ __('Resultados') }}
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @foreach($survey->questions as $question)
                        <div class="bg-gray-50 dark:bg-gray-800/40 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm flex flex-col h-full">
                            <div class="mb-4 border-l-2 border-indigo-600 pl-3">
                                <h3 class="text-sm sm:text-base font-black text-gray-900 dark:text-white uppercase tracking-tight" title="{{ $question->title }}">{{ $question->title }}</h3>
                            </div>
                            
                            @if($question->type !== 'text')
                                <div class="flex flex-1 items-center gap-4">
                                    <div class="flex-1 space-y-2">
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
                                                <div class="h-1 w-full bg-gray-200 dark:bg-gray-800/50 rounded-full overflow-hidden">
                                                    <div class="h-full rounded-full {{ $isWinner ? 'bg-indigo-600' : 'bg-gray-400 dark:bg-gray-600' }}"
                                                         style="width: {{ $percentage }}%">
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="relative shrink-0 flex justify-center">
                                        <div class="w-[90px] h-[90px]">
                                            <canvas id="chart-{{ $question->id }}" 
                                                    data-type="{{ $question->type }}" 
                                                    data-labels='@json($question->options->pluck("label"))'
                                                    data-values='@json($question->options->pluck("votes_count"))'></canvas>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="flex-1 mt-2 space-y-2 overflow-y-auto max-h-[120px] custom-scrollbar pr-2">
                                    @foreach($question->votes->take(5) as $vote)
                                        @if($vote->text_value)
                                            <div class="p-2 bg-white dark:bg-gray-800/50 rounded-xl border border-gray-100 dark:border-gray-700/50">
                                                <p class="text-xs text-gray-600 dark:text-gray-400 font-medium leading-tight italic">"{{ $vote->text_value }}"</p>
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

        @if(!$hasVoted || ($hasVoted && !$survey->is_closed && $survey->allow_multiple_votes))
            @php
                $initialAnswers = [];
                // Para encuestas públicas no autocompletamos si permitimos múltiples o si ya votó pero puede volver a votar
                // O si queremos permitir editar, pero normalmente una encuesta pública con allow_multiple_votes es un voto nuevo.
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-[2rem] shadow-xl overflow-hidden mb-12 p-8 sm:p-12 border border-gray-100 dark:border-gray-800" x-data="votingManager(@json($initialAnswers))">
                <form action="{{ route('public.surveys.store', $survey->uuid) }}" method="POST" id="survey-form">
                    @csrf
                    <div class="space-y-16">
                        @foreach($survey->questions as $index => $question)
                            <div class="relative">
                                <div class="flex items-start gap-6 mb-8">
                                    <div class="w-12 h-12 shrink-0 rounded-2xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center font-black text-xl text-indigo-600 dark:text-indigo-400 border border-indigo-100 dark:border-indigo-500/20">
                                        {{ $index + 1 }}
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-black text-gray-900 dark:text-white tracking-tight uppercase mb-2">
                                            {{ $question->title }}
                                            @if($question->is_required)<span class="text-red-500 ml-1">*</span>@endif
                                        </h3>
                                        @if($question->description)
                                            <p class="text-gray-500 dark:text-gray-400 font-medium">{{ $question->description }}</p>
                                        @endif
                                    </div>
                                </div>

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
                                                               class="peer absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                                                               {{ $question->is_required && $question->type === 'single_choice' ? 'required' : '' }}>
                                                        <div class="w-full h-full bg-indigo-600 p-1 transition-opacity duration-300 pointer-events-none"
                                                             :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'opacity-100' : 'opacity-0'">
                                                            <svg class="w-full h-full text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="4" d="M5 13l4 4L19 7"/></svg>
                                                        </div>
                                                    </div>
                                                    <div class="ml-6">
                                                        <span class="block text-sm font-bold text-gray-900 dark:text-white transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                                                              :class="isSelected({{ $question->id }}, {{ $option->id }}, '{{ $question->type }}') ? 'text-indigo-600 dark:text-indigo-400' : ''">
                                                            {{ $option->label }}
                                                        </span>
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
                                            <input type="hidden" name="answers[{{ $question->id }}]" :value="answers['{{ $question->id }}']" {{ $question->is_required ? 'required' : '' }}>
                                        </div>
                                    @elseif($question->type === 'text')
                                        <div class="bg-gray-50 dark:bg-gray-800 rounded-3xl p-6 border-2 border-transparent focus-within:border-indigo-500/50 transition-all duration-300">
                                            <textarea name="answers[{{ $question->id }}]" rows="4" x-model="answers['{{ $question->id }}']"
                                                      class="w-full bg-transparent border-none focus:ring-0 text-lg text-gray-900 dark:text-white placeholder-gray-400 font-medium resize-none"
                                                      placeholder="{{ __('Escribe aquí tus ideas o comentarios...') }}"
                                                      {{ $question->is_required ? 'required' : '' }}></textarea>
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
                                {{ __('Enviar Respuestas') }}
                                <svg class="w-6 h-6 transition-transform duration-500 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        @endif
        
        <div class="text-center mt-8 pb-8">
            <p class="text-xs text-gray-400">Desarrollado con <span class="font-bold">sientiaMTX</span></p>
        </div>
    </div>

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
