<x-app-layout>
    @section('title', __('tasks.create') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.dashboard', $team) }}" class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-white heading">{{ __('tasks.create') }} — {{ $team->name }}</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <form method="POST" action="{{ route('teams.tasks.store', $team) }}" class="space-y-5">
                @csrf

                <!-- Title -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition-all"
                        placeholder="{{ __('tasks.name') }}...">
                    @error('title')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white placeholder-gray-500 outline-none transition-all resize-none"
                        placeholder="{{ __('tasks.description') }}...">{{ old('description') }}</textarea>
                </div>

                <!-- Priority + Urgency (the Eisenhower axes) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">
                            {{ __('tasks.priority') }}
                            <span class="text-gray-500 font-normal text-xs ml-1">(importance)</span>
                        </label>
                        @php $priorityColors = ['low'=>'text-green-400','medium'=>'text-yellow-400','high'=>'text-orange-400','critical'=>'text-red-400']; @endphp
                        <select name="priority" required
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">
                            {{ __('tasks.urgency') }}
                            <span class="text-gray-500 font-normal text-xs ml-1">(urgency)</span>
                        </label>
                        <select name="urgency" required
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('urgency')
                            <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Quadrant preview (calculated in JS) -->
                <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden transition-all">
                    <span class="font-semibold" id="qp-label"></span>
                    <span class="text-gray-400 ml-1" id="qp-desc"></span>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date') }}"
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($users->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.assigned_to') }}</label>
                            <div
                                class="bg-gray-800 border border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', [])) ? 'checked' : '' }}
                                            class="accent-violet-500 w-4 h-4 rounded border-gray-600">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-300 leading-none">{{ $user->name }}</span>
                                            <span class="text-[10px] text-gray-500">{{ $user->email }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('Assign to Groups') }}</label>
                            <div
                                class="bg-gray-800 border border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            {{ in_array($group->id, old('assigned_groups', [])) ? 'checked' : '' }}
                                            class="accent-indigo-500 w-4 h-4 rounded border-gray-600">
                                        <div class="flex flex-col">
                                            <span class="text-sm text-gray-300 leading-none">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500">{{ $group->users->count() }}
                                                {{ __('members') }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-2 border-t border-gray-800">
                    <a href="{{ route('teams.dashboard', $team) }}"
                        class="text-sm text-gray-400 hover:text-white px-4 py-2.5 rounded-xl border border-gray-700 hover:border-gray-600 transition-all">{{ __('tasks.back') }}</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-lg hover:shadow-violet-500/30">
                        {{ __('tasks.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const quadrantData = @json(__('tasks.quadrants'));
        const priorityEl = document.querySelector('[name="priority"]');
        const urgencyEl = document.querySelector('[name="urgency"]');
        const preview = document.getElementById('quadrant-preview');
        const highLevels = ['high', 'critical'];

        const qColors = {
            1: {
                border: 'border-red-700',
                bg: 'bg-red-950/30',
                text: 'text-red-300'
            },
            2: {
                border: 'border-blue-700',
                bg: 'bg-blue-950/30',
                text: 'text-blue-300'
            },
            3: {
                border: 'border-amber-700',
                bg: 'bg-amber-950/30',
                text: 'text-amber-300'
            },
            4: {
                border: 'border-gray-700',
                bg: 'bg-gray-800',
                text: 'text-gray-300'
            },
        };

        function updatePreview() {
            const imp = highLevels.includes(priorityEl.value);
            const urg = highLevels.includes(urgencyEl.value);
            let q = 4;
            if (imp && urg) q = 1;
            else if (imp) q = 2;
            else if (urg) q = 3;

            const cfg = qColors[q];
            preview.className = `rounded-xl border p-3 text-xs transition-all ${cfg.border} ${cfg.bg}`;
            preview.classList.remove('hidden');
            document.getElementById('qp-label').className = `font-semibold ${cfg.text}`;
            document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
            document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
        }

        priorityEl?.addEventListener('change', updatePreview);
        urgencyEl?.addEventListener('change', updatePreview);
        updatePreview();
    </script>
</x-app-layout>
