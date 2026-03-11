<x-app-layout>
    @section('title', __('tasks.edit') . ': ' . $task->title)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <div>
                @include('teams.partials.breadcrumb')
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading truncate">{{ __('tasks.edit') }}:
                    {{ $task->title }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-5">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-6 shadow-sm dark:shadow-none transition-colors">
            <form method="POST" action="{{ route('teams.tasks.update', [$team, $task]) }}" class="space-y-6">
                @csrf @method('PATCH')

                @if ($team->isCoordinator(auth()->user()))
                    <div class="mb-6">
                        <label
                            class="block text-sm font-bold text-violet-600 dark:text-violet-400 mb-2 uppercase tracking-wide">{{ __('tasks.owner') }}</label>
                        <select name="created_by_id" required
                            class="w-full bg-violet-50/50 dark:bg-violet-900/10 border border-violet-100 dark:border-violet-800 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer font-medium">
                            @foreach ($allMembers as $u)
                                <option value="{{ $u->id }}"
                                    {{ old('created_by_id', $task->created_by_id) == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('created_by_id')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title', $task->title) }}" required
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all placeholder-gray-400">
                    @error('title')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all resize-none placeholder-gray-400">{{ old('description', $task->description) }}</textarea>
                </div>

                <!-- Observations (Markdown) -->
                <div>
                    <label
                        class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.observations') }}</label>
                    <textarea name="observations" id="observations"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">{{ old('observations', $task->observations) }}</textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.priority') }}</label>
                        <select name="priority" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', $task->priority) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.urgency') }}</label>
                        <select name="urgency" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', $task->urgency) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.status') }}</label>
                        <select name="status" required
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                            @foreach (__('tasks.statuses') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('status', $task->status) === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <!-- Dependency -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">
                        {{ __('tasks.dependency') ?? 'Dependencia (Tarea Padre)' }}
                    </label>
                    <select name="parent_id"
                        class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none transition-all cursor-pointer">
                        <option value="">{{ __('tasks.no_dependency') ?? 'Sin dependencia' }}</option>
                        @foreach ($tasks as $t)
                            <option value="{{ $t->id }}"
                                {{ old('parent_id', $task->parent_id) == $t->id ? 'selected' : '' }}>
                                {{ $t->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4 font-mono">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date"
                            value="{{ old('scheduled_date', $task->scheduled_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2 font-sans">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-700 dark:text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($users->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assigned_to') }}</label>
                            @php
                                $assignedIds = $task->assignedTo->pluck('id')->toArray();
                                if ($task->assigned_user_id && !in_array($task->assigned_user_id, $assignedIds)) {
                                    $assignedIds[] = $task->assigned_user_id;
                                }
                            @endphp
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', $assignedIds)) ? 'checked' : '' }}
                                            class="accent-violet-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <span
                                            class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-600 dark:text-gray-300 mb-2">{{ __('tasks.assign_groups') }}</label>
                            @php $assignedGroupIds = $task->assignedGroups->pluck('id')->toArray(); @endphp
                            <div
                                class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-2 cursor-pointer group">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            {{ in_array($group->id, old('assigned_groups', $assignedGroupIds)) ? 'checked' : '' }}
                                            class="accent-indigo-500 w-4 h-4 rounded border-gray-300 dark:border-gray-600">
                                        <span
                                            class="text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-white transition-colors">{{ $group->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-gray-800">
                    <div>
                        <button type="button"
                            onclick="confirmDelete('delete-task-form', '{{ __('tasks.delete_confirm') }}')"
                            class="text-xs font-bold text-red-500 hover:text-red-600 transition-colors uppercase tracking-widest">{{ __('tasks.delete') }}</button>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white px-5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-all font-medium">{{ __('tasks.back') }}</a>
                        <button type="submit"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-8 py-2.5 rounded-xl font-bold transition-all shadow-lg hover:shadow-violet-500/25">{{ __('tasks.save') }}</button>
                    </div>
                </div>
            </form>

            <form id="delete-task-form" method="POST" action="{{ route('teams.tasks.destroy', [$team, $task]) }}"
                class="hidden">
                @csrf @method('DELETE')
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
        });
    </script>
</x-app-layout>
