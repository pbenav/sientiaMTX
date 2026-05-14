<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                {{ __('Editar Encuesta') }}
            </h2>
            <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('Cancelar') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form action="{{ route('teams.surveys.update', [$team, $survey]) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <!-- Título -->
                        <div class="mb-6">
                            <label for="title" class="block text-sm font-medium text-gray-700">{{ __('Título') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="title" value="{{ old('title', $survey->title) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('title') border-red-500 @enderror">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Descripción -->
                        <div class="mb-6">
                            <label for="description" class="block text-sm font-medium text-gray-700">{{ __('Descripción') }}</label>
                            <textarea name="description" id="description" rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-500 @enderror">{{ old('description', $survey->description) }}</textarea>
                            @error('description')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Tipo de encuesta -->
                        <div class="mb-6">
                            <label for="type" class="block text-sm font-medium text-gray-700">{{ __('Tipo de Encuesta') }} <span class="text-red-500">*</span></label>
                            <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('type') border-red-500 @enderror">
                                <option value="single_choice" {{ old('type', $survey->type) === 'single_choice' ? 'selected' : '' }}>{{ __('Opción única') }}</option>
                                <option value="multiple_choice" {{ old('type', $survey->type) === 'multiple_choice' ? 'selected' : '' }}>{{ __('Opción múltiple') }}</option>
                                <option value="rating" {{ old('type', $survey->type) === 'rating' ? 'selected' : '' }}>{{ __('Valoración (1-5)') }}</option>
                                <option value="text" {{ old('type', $survey->type) === 'text' ? 'selected' : '' }}>{{ __('Texto libre') }}</option>
                            </select>
                            @error('type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Opciones -->
                        <div id="optionsContainer" class="mb-6 {{ in_array($survey->type, ['single_choice', 'multiple_choice']) ? '' : 'hidden' }}">
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Opciones') }}</label>
                                <button type="button" id="addOption" class="text-sm text-blue-600 hover:text-blue-800">
                                    {{ __('+ Añadir opción') }}
                                </button>
                            </div>
                            <div id="optionsList" class="space-y-3">
                                @if(old('options'))
                                    @foreach(old('options') as $index => $option)
                                        <div class="flex items-center gap-2 option-item">
                                            <input type="color" name="option_colors[]" value="{{ old('option_colors.'.$index, '#3B82F6') }}" class="w-10 h-8 rounded cursor-pointer" />
                                            <input type="text" name="options[]" value="{{ $option }}" placeholder="Opción {{ $index + 1 }}" required
                                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                            <button type="button" class="remove-option text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                @else
                                    @foreach($survey->options as $index => $option)
                                        <div class="flex items-center gap-2 option-item">
                                            <input type="color" name="option_colors[]" value="{{ old('option_colors.'.$index, $option->color ?? '#3B82F6') }}" class="w-10 h-8 rounded cursor-pointer" />
                                            <input type="text" name="options[]" value="{{ old('options.'.$index, $option->label) }}" placeholder="Opción {{ $index + 1 }}" required
                                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                            <button type="button" class="remove-option text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                            @error('options')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Texto libre -->
                        <div id="textContainer" class="mb-6 {{ $survey->type === 'text' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Instrucciones para el texto libre') }}</label>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Se pedirá a los usuarios que escriban su respuesta en un campo de texto.') }}</p>
                        </div>

                        <!-- Valoración -->
                        <div id="ratingContainer" class="mb-6 {{ $survey->type === 'rating' ? '' : 'hidden' }}">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Escala de valoración') }}</label>
                            <div class="mt-2 flex items-center gap-2">
                                <span class="text-sm">1</span>
                                <input type="range" min="3" max="10" value="5" id="ratingScale" class="flex-1" />
                                <span class="text-sm">{{ __('estrellas') }}</span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Número de estrellas en la escala de valoración.') }}</p>
                        </div>

                        <!-- Configuración adicional -->
                        <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">{{ __('Configuración') }}</h3>
                            
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $survey->is_active) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ __('Encuesta activa') }}</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="allow_multiple_votes" value="1" {{ old('allow_multiple_votes', $survey->allow_multiple_votes) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ __('Permitir múltiples votos') }}</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="show_results_before_voting" value="1" {{ old('show_results_before_voting', $survey->show_results_before_voting) ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ __('Mostrar resultados antes de votar') }}</span>
                                </label>

                                <div>
                                    <label for="expires_at" class="block text-sm text-gray-700">{{ __('Fecha de expiración') }}</label>
                                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $survey->expires_at?->format('Y-m-d\TH:i')) }}"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                {{ __('Cancelar') }}
                            </a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                                {{ __('Guardar Cambios') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const typeSelect = document.getElementById('type');
        const optionsContainer = document.getElementById('optionsContainer');
        const textContainer = document.getElementById('textContainer');
        const ratingContainer = document.getElementById('ratingContainer');
        const optionsList = document.getElementById('optionsList');
        const addOptionBtn = document.getElementById('addOption');

        function updateVisibility() {
            const type = typeSelect.value;
            optionsContainer.classList.toggle('hidden', type !== 'single_choice' && type !== 'multiple_choice');
            textContainer.classList.toggle('hidden', type !== 'text');
            ratingContainer.classList.toggle('hidden', type !== 'rating');
            
            const optionInputs = optionsContainer.querySelectorAll('input[required]');
            optionInputs.forEach(input => {
                input.required = type === 'single_choice' || type === 'multiple_choice';
            });
        }

        typeSelect.addEventListener('change', updateVisibility);

        addOptionBtn.addEventListener('click', function() {
            const index = optionsList.children.length;
            const colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#F97316'];
            const color = colors[index % colors.length];
            
            const div = document.createElement('div');
            div.className = 'flex items-center gap-2 option-item';
            div.innerHTML = `
                <input type="color" name="option_colors[]" value="${color}" class="w-10 h-8 rounded cursor-pointer" />
                <input type="text" name="options[]" placeholder="Opción ${index + 1}" required
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                <button type="button" class="remove-option text-red-600 hover:text-red-800">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            `;
            optionsList.appendChild(div);
        });

        optionsList.addEventListener('click', function(e) {
            if (e.target.closest('.remove-option')) {
                if (optionsList.children.length > 2) {
                    e.target.closest('.option-item').remove();
                }
            }
        });

        updateVisibility();
    </script>
</x-app-layout>
