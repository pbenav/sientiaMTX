<x-app-layout>
    @section('title', __('tasks.edit') . ': ' . $task->title)

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-white heading truncate">{{ __('tasks.edit') }}: {{ $task->title }}</h1>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-5">
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <form method="POST" action="{{ route('teams.tasks.update', [$team, $task]) }}" class="space-y-5">
                @csrf @method('PATCH')

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.name') }}</label>
                    <input type="text" name="title" value="{{ old('title', $task->title) }}" required
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all">
                    @error('title')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all resize-none">{{ old('description', $task->description) }}</textarea>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.priority') }}</label>
                        <select name="priority" required
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none transition-all">
                            @foreach (__('tasks.priorities') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('priority', $task->priority) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.urgency') }}</label>
                        <select name="urgency" required
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none transition-all">
                            @foreach (__('tasks.urgencies') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('urgency', $task->urgency) === $val ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.status') }}</label>
                        <select name="status" required
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-3 py-2.5 text-sm text-white outline-none transition-all">
                            @foreach (__('tasks.statuses') as $val => $label)
                                <option value="{{ $val }}"
                                    {{ old('status', $task->status) === $val ? 'selected' : '' }}>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.scheduled_date') }}</label>
                        <input type="datetime-local" name="scheduled_date"
                            value="{{ old('scheduled_date', $task->scheduled_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-300 outline-none transition-all">
                    </div>
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.due_date') }}</label>
                        <input type="datetime-local" name="due_date"
                            value="{{ old('due_date', $task->due_date?->format('Y-m-d\TH:i')) }}"
                            class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 rounded-xl px-4 py-2.5 text-sm text-gray-300 outline-none transition-all">
                    </div>
                </div>

                <!-- Assigned To -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @if ($users->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('tasks.assigned_to') }}</label>
                            @php $assignedIds = $task->assignedTo->pluck('id')->toArray(); @endphp
                            <div
                                class="bg-gray-800 border border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($users as $user)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="assigned_to[]" value="{{ $user->id }}"
                                            {{ in_array($user->id, old('assigned_to', $assignedIds)) ? 'checked' : '' }}
                                            class="accent-violet-500 w-4 h-4 rounded border-gray-600">
                                        <span class="text-sm text-gray-300">{{ $user->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($groups->count() > 0)
                        <div>
                            <label
                                class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('Assign to Groups') }}</label>
                            @php $assignedGroupIds = $task->assignedGroups->pluck('id')->toArray(); @endphp
                            <div
                                class="bg-gray-800 border border-gray-700 rounded-xl p-3 space-y-2 max-h-40 overflow-y-auto">
                                @foreach ($groups as $group)
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" name="assigned_groups[]" value="{{ $group->id }}"
                                            {{ in_array($group->id, old('assigned_groups', $assignedGroupIds)) ? 'checked' : '' }}
                                            class="accent-indigo-500 w-4 h-4 rounded border-gray-600">
                                        <span class="text-sm text-gray-300">{{ $group->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="flex justify-between items-center pt-2 border-t border-gray-800">
                    <form method="POST" action="{{ route('teams.tasks.destroy', [$team, $task]) }}"
                        onsubmit="return confirm('{{ __('tasks.delete_confirm') }}')">
                        @csrf @method('DELETE')
                        <button type="submit"
                            class="text-xs text-red-500 hover:text-red-400 transition-colors">{{ __('tasks.delete') }}</button>
                    </form>
                    <div class="flex gap-3">
                        <a href="{{ route('teams.tasks.show', [$team, $task]) }}"
                            class="text-sm text-gray-400 hover:text-white px-4 py-2.5 rounded-xl border border-gray-700 hover:border-gray-600 transition-all">{{ __('tasks.back') }}</a>
                        <button type="submit"
                            class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-xl font-medium transition-all">{{ __('tasks.save') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
