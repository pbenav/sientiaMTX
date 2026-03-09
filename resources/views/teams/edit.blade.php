<x-app-layout>
    @section('title', __('teams.edit'))

    <x-slot name="header">
        <div class="flex items-center gap-3">
            <a href="{{ route('teams.show', $team) }}" class="text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                </svg>
            </a>
            <h1 class="text-xl font-bold text-white heading">{{ __('teams.edit') }}: {{ $team->name }}</h1>
        </div>
    </x-slot>

    <div class="max-w-lg mx-auto space-y-5">
        <!-- Edit form -->
        <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6">
            <form method="POST" action="{{ route('teams.update', $team) }}" class="space-y-5">
                @csrf @method('PATCH')
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('teams.name') }}</label>
                    <input type="text" name="name" value="{{ old('name', $team->name) }}" required
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all">
                    @error('name')
                        <p class="mt-1 text-xs text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-1.5">{{ __('teams.description') }}</label>
                    <textarea name="description" rows="3"
                        class="w-full bg-gray-800 border border-gray-700 focus:border-violet-500 focus:ring focus:ring-violet-500/20 rounded-xl px-4 py-2.5 text-sm text-white outline-none transition-all resize-none">{{ old('description', $team->description) }}</textarea>
                </div>
                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('teams.show', $team) }}"
                        class="text-sm text-gray-400 hover:text-white px-4 py-2.5 rounded-xl border border-gray-700 hover:border-gray-600 transition-all">{{ __('teams.back') }}</a>
                    <button type="submit"
                        class="text-sm bg-violet-600 hover:bg-violet-500 text-white px-6 py-2.5 rounded-xl font-medium transition-all">{{ __('teams.save_changes') }}</button>
                </div>
            </form>
        </div>

        <!-- Danger zone -->
        @can('delete', $team)
            <div class="bg-gray-900 border border-red-900/50 rounded-2xl p-5">
                <h3 class="text-sm font-semibold text-red-400 heading mb-2">Danger Zone</h3>
                <p class="text-xs text-gray-500 mb-4">{{ __('teams.delete_confirm') }}</p>
                <form method="POST" action="{{ route('teams.destroy', $team) }}"
                    onsubmit="return confirm('{{ __('teams.delete_confirm') }}')">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="text-sm bg-red-900/30 hover:bg-red-900/60 border border-red-800 text-red-400 hover:text-red-300 px-4 py-2 rounded-xl transition-all">
                        Delete Team
                    </button>
                </form>
            </div>
        @endcan
    </div>
</x-app-layout>
