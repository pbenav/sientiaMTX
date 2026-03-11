<x-app-layout>
    @section('title', __('tasks.create') . ' — ' . $team->name)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.dashboard', $team) }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                @include('teams.partials.breadcrumb')
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">{{ __('tasks.create') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
            <form method="POST" action="{{ route('teams.tasks.store', $team) }}" class="space-y-6">
                @csrf

                <!-- Title -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none transition-all"
                        placeholder="{{ __('tasks.name') }}...">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Description -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 outline-none transition-all resize-none"
                        placeholder="{{ __('tasks.description') }}...">{{ old('description') }}</textarea>
                </div>

                <!-- Observations (Markdown) -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.observations') }}</label>
                    <textarea name="observations" id="observations"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">{{ old('observations') }}</textarea>
                </div>

                <!-- Priority + Urgency (the Eisenhower axes) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                            {{ __('tasks.priority') }}
                        </label>
                        <select name="priority" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                            {{ __('tasks.urgency') }}
                        </label>
                        <select name="urgency" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', 'medium') === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('urgency')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Quadrant preview (calculated in JS) -->
                <div id="quadrant-preview" class="rounded-xl border p-3 text-xs hidden transition-all">
                    <span class="font-semibold" id="qp-label"></span>
                    <span class="text-gray-400 ml-1" id="qp-desc"></span>
                </div>

                <!-- Dependency -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.dependency') ?? 'Dependencia (Tarea Padre)' }}
                    </label>
                    <select name="parent_id"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="">{{ __('tasks.no_dependency') ?? 'Sin dependencia' }}</option>
                        @foreach ($tasks as $t)
                            <option value="{{ $t->id }}" {{ old('parent_id') == $t->id ? 'selected' : '' }}>
                                {{ $t->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Dates -->
                <div class="grid grid-cols-2 gap-4 font-mono">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date" value="{{ old('scheduled_date') }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date" value="{{ old('due_date') }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if ($users->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assigned_to') }}</label>
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', [])) ? 'checked' : '' }}
                                            class="accent-violet-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-tight group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $user->name }}</span>
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
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assign_groups') }}</label>
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-3 cursor-pointer group">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            {{ in_array($group->id, old('assigned_groups', [])) ? 'checked' : '' }}
                                            class="accent-indigo-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <div class="flex flex-col">
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300 leading-tight group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $group->name }}</span>
                                            <span class="text-[10px] text-gray-500">{{ $group->users->count() }}
                                                {{ __('teams.members') }}</span>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                    <a href="{{ route('teams.dashboard', $team) }}"
                        class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-all font-medium">{{ __('tasks.back') }}</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">
                        {{ __('tasks.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <link rel="stylesheet" href="https://unpkg.com/easymde/dist/easymde.min.css">
    <script src="https://unpkg.com/easymde/dist/easymde.min.js"></script>
    <style>
        .EasyMDEContainer .CodeMirror {
            background: #f9fafb;
            border-bottom-left-radius: 0.75rem;
            border-bottom-right-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            color: #111827;
        }

        .dark .EasyMDEContainer .CodeMirror {
            background: #1f2937;
            border-color: #374151;
            color: #f3f4f6;
        }

        .EasyMDEContainer .editor-toolbar {
            background: #f3f4f6;
            border-top-left-radius: 0.75rem;
            border-top-right-radius: 0.75rem;
            border-color: #e5e7eb;
        }

        .dark .EasyMDEContainer .editor-toolbar {
            background: #111827;
            border-color: #374151;
        }

        .dark .EasyMDEContainer .editor-toolbar button {
            color: #9ca3af;
        }

        .dark .EasyMDEContainer .editor-toolbar button:hover,
        .dark .EasyMDEContainer .editor-toolbar button.active {
            background: #374151;
            color: white;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const easyMDE = new EasyMDE({
                element: document.getElementById('observations'),
                spellChecker: false,
                autosave: {
                    enabled: false,
                },
                status: false,
                minHeight: '150px',
                placeholder: 'Añade observaciones aquí...',
            });

            const quadrantData = @json(__('tasks.quadrants'));
            const priorityEl = document.querySelector('[name="priority"]');
            const urgencyEl = document.querySelector('[name="urgency"]');
            const preview = document.getElementById('quadrant-preview');
            const highLevels = ['high', 'critical'];

            const qColors = {
                1: {
                    border: 'border-red-200 dark:border-red-700',
                    bg: 'bg-red-50 dark:bg-red-950/30',
                    text: 'text-red-600 dark:text-red-300'
                },
                2: {
                    border: 'border-blue-200 dark:border-blue-700',
                    bg: 'bg-blue-50 dark:bg-blue-950/30',
                    text: 'text-blue-600 dark:text-blue-300'
                },
                3: {
                    border: 'border-amber-200 dark:border-amber-700',
                    bg: 'bg-amber-50 dark:bg-amber-950/30',
                    text: 'text-amber-600 dark:text-amber-300'
                },
                4: {
                    border: 'border-gray-200 dark:border-gray-700',
                    bg: 'bg-gray-50 dark:bg-gray-800',
                    text: 'text-gray-600 dark:text-gray-300'
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
                preview.className =
                    `rounded-xl border p-3 text-xs transition-all shadow-sm dark:shadow-none ${cfg.border} ${cfg.bg}`;
                preview.classList.remove('hidden');
                document.getElementById('qp-label').className = `font-bold uppercase tracking-wider ${cfg.text}`;
                document.getElementById('qp-label').textContent = `Q${q}: ${quadrantData[q].label}`;
                document.getElementById('qp-desc').className =
                    `text-gray-500 dark:text-gray-400 ml-1 italic font-medium`;
                document.getElementById('qp-desc').textContent = `— ${quadrantData[q].description}`;
            }

            priorityEl?.addEventListener('change', updatePreview);
            urgencyEl?.addEventListener('change', updatePreview);
            updatePreview();
        });
    </script>
</x-app-layout>
