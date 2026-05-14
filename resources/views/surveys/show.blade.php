<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <a href="{{ route('teams.surveys.index', $team) }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $survey->title }}
                </h2>
            </div>
            <div class="flex items-center gap-2">
                @if($survey->is_active && !$survey->is_closed)
                    <form method="POST" action="{{ route('teams.surveys.close', ['team' => $team, 'survey' => $survey]) }}">
                        @csrf
                        <x-secondary-button type="button" onclick="if(confirm('{{ __('¿Cerrar esta encuesta?') }}')) this.closest('form').submit()">
                            {{ __('Cerrar') }}
                        </x-secondary-button>
                    </form>
                @endif

                @if($survey->is_closed || !$survey->is_active)
                    <form method="POST" action="{{ route('teams.surveys.reactivate', ['team' => $team, 'survey' => $survey]) }}">
                        @csrf
                        <x-primary-button type="button" onclick="if(confirm('{{ __('¿Reactivar esta encuesta?') }}')) this.closest('form').submit()">
                            {{ __('Reactivar') }}
                        </x-primary-button>
                    </form>
                @endif

                @can('update', $survey)
                    <a href="{{ route('teams.surveys.edit', ['team' => $team, 'survey' => $survey]) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                        {{ __('Editar') }}
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Survey Info Card -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <!-- Status Badges -->
                    <div class="flex flex-wrap items-center gap-3 mb-4">
                        @if($survey->is_expired)
                            <span class="px-3 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 text-sm rounded-full">
                                {{ __('Expirada') }}
                            </span>
                        @elseif($survey->is_closed)
                            <span class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm rounded-full">
                                {{ __('Cerrada') }}
                            </span>
                        @elseif(!$survey->is_active)
                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-full">
                                {{ __('Inactiva') }}
                            </span>
                        @else
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 text-sm rounded-full">
                                {{ __('Activa') }}
                            </span>
                        @endif

                        @if($survey->type === 'single_choice')
                            <span class="px-3 py-1 bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 text-sm rounded-full">
                                {{ __('Opción única') }}
                            </span>
                        @elseif($survey->type === 'multiple_choice')
                            <span class="px-3 py-1 bg-indigo-100 dark:bg-indigo-900 text-indigo-800 dark:text-indigo-200 text-sm rounded-full">
                                {{ __('Opción múltiple') }}
                            </span>
                        @elseif($survey->type === 'rating')
                            <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 text-sm rounded-full">
                                {{ __('Calificación') }}
                            </span>
                        @elseif($survey->type === 'text')
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 text-sm rounded-full">
                                {{ __('Respuesta abierta') }}
                            </span>
                        @endif
                    </div>

                    <!-- Description -->
                    @if($survey->description)
                        <p class="text-gray-700 dark:text-gray-300 mb-4">{{ $survey->description }}</p>
                    @endif

                    <!-- Meta Info -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 dark:text-gray-400">
                        <div>
                            <span class="font-semibold">{{ __('Creada por:') }}</span>
                            <span class="ml-1">{{ $survey->creator?->name ?? 'N/A' }}</span>
                        </div>
                        <div>
                            <span class="font-semibold">{{ __('Total de votos:') }}</span>
                            <span class="ml-1">{{ $survey->vote_count }}</span>
                        </div>
                        @if($survey->expires_at)
                            <div>
                                <span class="font-semibold">{{ __('Expira:') }}</span>
                                <span class="ml-1">{{ $survey->expires_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                        @if($survey->published_at)
                            <div>
                                <span class="font-semibold">{{ __('Publicada:') }}</span>
                                <span class="ml-1">{{ $survey->published_at->format('d/m/Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Voting Form -->
            @if($survey->is_active && !$survey->is_closed && !$survey->is_expired)
                @if($hasVoted && $survey->type !== 'multiple_choice' && $survey->type !== 'rating')
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-blue-800 dark:text-blue-200">{{ __('Ya has votado en esta encuesta') }}</p>
                                    @if($survey->show_results_before_voting)
                                        <p class="text-sm text-blue-600 dark:text-blue-400">{{ __('Puedes ver los resultados a continuación') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">
                                {{ __('Tu respuesta') }}
                            </h3>

                            <form id="voteForm" method="POST" action="{{ route('teams.surveys.vote', ['team' => $team, 'survey' => $survey]) }}">
                                @csrf

                                @if($survey->type === 'single_choice')
                                    <div class="space-y-3">
                                        @foreach($survey->options as $option)
                                            @php
                                                $isChecked = isset($userVotes['option_id']) && $userVotes['option_id'] == $option->id;
                                            @endphp
                                            <label class="flex items-center p-4 border-2 {{ $isChecked ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600' : 'border-gray-200 dark:border-gray-700' }} rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <input type="radio" name="option_id" value="{{ $option->id }}" class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500" {{ $isChecked ? 'checked' : '' }} {{ ($hasVoted && !$survey->allow_multiple_votes) ? 'disabled' : '' }}>
                                                <span class="ml-3 text-gray-800 dark:text-gray-200">{{ $option->label }}</span>
                                                @if($option->description)
                                                    <p class="ml-3 text-sm text-gray-500 dark:text-gray-400">{{ $option->description }}</p>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>

                                @elseif($survey->type === 'multiple_choice')
                                    <div class="space-y-3">
                                        @foreach($survey->options as $option)
                                            @php
                                                $isChecked = isset($userVotes['option_ids']) && in_array($option->id, $userVotes['option_ids']);
                                            @endphp
                                            <label class="flex items-center p-4 border-2 {{ $isChecked ? 'border-blue-400 bg-blue-50 dark:bg-blue-900/20 dark:border-blue-600' : 'border-gray-200 dark:border-gray-700' }} rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <input type="checkbox" name="option_ids[]" value="{{ $option->id }}" class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $isChecked ? 'checked' : '' }}>
                                                <span class="ml-3 text-gray-800 dark:text-gray-200">{{ $option->label }}</span>
                                                @if($option->description)
                                                    <p class="ml-3 text-sm text-gray-500 dark:text-gray-400">{{ $option->description }}</p>
                                                @endif
                                            </label>
                                        @endforeach
                                    </div>

                                @elseif($survey->type === 'rating')
                                    <div class="flex items-center justify-center gap-2" id="ratingContainer">
                                        @for($i = 5; $i >= 1; $i--)
                                            <button type="button" class="text-4xl text-gray-300 hover:text-yellow-400 transition-colors rating-btn" data-value="{{ $i }}" onclick="setRating({{ $i }})">★</button>
                                        @endfor
                                        <input type="hidden" name="rating_value" id="ratingValue" required>
                                    </div>
                                    <p class="text-center text-sm text-gray-500 dark:text-gray-400 mt-2" id="ratingLabel">{{ __('Selecciona una calificación') }}</p>

                                @elseif($survey->type === 'text')
                                    <textarea name="text_value" rows="5" class="w-full px-3 py-2 border-gray-300 dark:border-gray-700 rounded-lg focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-900 dark:text-gray-100" placeholder="{{ __('Escribe tu respuesta aquí...') }}" {{ $hasVoted && !$survey->allow_multiple_votes ? 'disabled' : '' }}>{{ old('text_value', $userVotes['text_value'] ?? '') }}</textarea>
                                @endif

                                @if(!$hasVoted || $survey->allow_multiple_votes)
                                    <div class="mt-6 flex justify-end">
                                        <x-primary-button type="submit">
                                            {{ __('Enviar respuesta') }}
                                        </x-primary-button>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                @endif
            @endif

            <!-- Results Section -->
            @if($showResults)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ __('Resultados') }}
                            </h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $survey->vote_count }} {{ __('votos totales') }}
                            </span>
                        </div>

                        @if($survey->options->count() > 0)
                            <div class="space-y-4">
                                @foreach($survey->options as $option)
                                    @php
                                        $voteCount = $results[$option->id]['vote_count'] ?? 0;
                                        $percentage = $results[$option->id]['percentage'] ?? 0;
                                        $isUserSelected = $results[$option->id]['is_user_selected'] ?? false;
                                    @endphp
                                    <div>
                                        <div class="flex items-center justify-between mb-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-800 dark:text-gray-200 font-medium">{{ $option->label }}</span>
                                                @if($survey->type === 'single_choice' && $isUserSelected)
                                                    <span class="text-xs bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-2 py-0.5 rounded">
                                                        {{ __('Tu voto') }}
                                                    </span>
                                                @endif
                                            </div>
                                            <span class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ $voteCount }} {{ __('votos') }} ({{ $percentage }}%)
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                                            <div class="h-2.5 rounded-full transition-all duration-500"
                                                 style="width: {{ $percentage }}%; background-color: {{ $option->color ?? '#3B82F6' }};">
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif($survey->type === 'text')
                            <div class="mt-4">
                                <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">
                                    {{ __('Respuestas recibidas') }} ({{ $survey->votes()->whereNotNull('text_value')->count() }})
                                </h4>
                                @if($survey->votes()->whereNotNull('text_value')->count() > 0)
                                    <div class="space-y-3 max-h-96 overflow-y-auto">
                                        @foreach($survey->votes()->whereNotNull('text_value')->with('user')->orderBy('voted_at', 'desc')->get() as $vote)
                                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                                    {{ __('Por:') }} {{ $vote->user?->name ?? 'Anónimo' }} - {{ $vote->voted_at->format('d/m/Y H:i') }}
                                                </p>
                                                <p class="text-gray-800 dark:text-gray-200">{{ $vote->text_value }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                        {{ __('Aún no hay respuestas') }}
                                    </p>
                                @endif
                            </div>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-center py-4">
                                {{ __('Aún no hay votos') }}
                            </p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        function setRating(value) {
            document.getElementById('ratingValue').value = value;
            const buttons = document.querySelectorAll('.rating-btn');
            const labels = ['Muy malo', 'Malo', 'Regular', 'Bueno', 'Muy bueno'];

            buttons.forEach((btn, index) => {
                if (index < value) {
                    btn.classList.remove('text-gray-300');
                    btn.classList.add('text-yellow-400');
                } else {
                    btn.classList.remove('text-yellow-400');
                    btn.classList.add('text-gray-300');
                }
            });

            document.getElementById('ratingLabel').textContent = labels[value - 1];
        }

        // Handle checkbox selection for multiple choice
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const parent = this.closest('label');
                if (this.checked) {
                    parent.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-400', 'dark:border-blue-600');
                } else {
                    parent.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-400', 'dark:border-blue-600');
                }
            });
        });

        // Handle radio selection styling
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('input[type="radio"]').forEach(r => {
                    const parent = r.closest('label');
                    parent.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-400', 'dark:border-blue-600');
                });
                const parent = this.closest('label');
                parent.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'border-blue-400', 'dark:border-blue-600');
            });
        });
    </script>
    @endpush
</x-app-layout>
