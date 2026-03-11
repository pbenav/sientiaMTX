<x-app-layout>
    @section('title', __('Sync Google Tasks'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('teams.dashboard', $team) }}"
                    class="text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <h1 class="text-xl font-bold text-gray-900 dark:text-white heading">
                    {{ __('Select Tasks to Import') }}
                </h1>
            </div>
            <div class="text-xs text-gray-400">
                {{ $team->name }}
            </div>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-2xl rounded-3xl overflow-hidden transition-all">
            <form action="{{ route('google.import') }}" method="POST">
                @csrf
                <input type="hidden" name="team_id" value="{{ $team->id }}">
                <input type="hidden" name="visibility" value="{{ $visibility }}">

                <div
                    class="px-8 py-6 border-b border-gray-100 dark:border-white/5 bg-gray-50/50 dark:bg-gray-900/50 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400">
                            {{ __('Found Events') }}
                        </h2>
                        <p class="text-[10px] text-gray-400 mt-1">
                            {{ __('Choose the calendar events you want to convert into tasks.') }}
                        </p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" id="select-all"
                            class="text-[10px] font-bold text-violet-600 dark:text-violet-400 uppercase tracking-tighter hover:underline">
                            {{ __('Select All') }}
                        </button>
                    </div>
                </div>

                <div class="divide-y divide-gray-50 dark:divide-white/5 max-h-[500px] overflow-y-auto custom-scrollbar">
                    @forelse($events as $event)
                        <div class="group relative px-8 py-5 hover:bg-violet-50/30 dark:hover:bg-violet-500/5 transition-all cursor-pointer"
                            onclick="toggleCheckbox('checkbox-{{ $event['id'] }}')">
                            <div class="flex items-start gap-6">
                                <div class="mt-1">
                                    <input type="checkbox" name="events[]" value="{{ $event['id'] }}"
                                        id="checkbox-{{ $event['id'] }}"
                                        class="w-5 h-5 rounded-lg border-gray-300 dark:border-gray-700 text-violet-600 focus:ring-violet-500 bg-white dark:bg-gray-800 transition-all pointer-events-none"
                                        {{ $event['exists'] ? 'disabled' : 'checked' }}>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2">
                                        <h3
                                            class="text-sm font-bold text-gray-900 dark:text-white truncate group-hover:text-violet-600 dark:group-hover:text-violet-400 transition-colors">
                                            {{ $event['title'] }}
                                        </h3>
                                        <span class="shrink-0 text-[10px] font-mono font-bold text-gray-400 uppercase">
                                            {{ date('d M, H:i', strtotime($event['start'])) }}
                                        </span>
                                    </div>
                                    @if ($event['description'])
                                        <p class="text-xs text-gray-500 dark:text-gray-500 line-clamp-1 mt-0.5">
                                            {{ $event['description'] }}
                                        </p>
                                    @endif
                                    @if ($event['exists'])
                                        <span
                                            class="inline-flex items-center gap-1 mt-2 text-[9px] font-bold text-emerald-500 uppercase tracking-tighter">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20"
                                                fill="currentColor">
                                                <path fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ __('Already Sync') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="py-20 text-center">
                            <div
                                class="inline-flex items-center justify-center w-16 h-16 rounded-3xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 text-gray-300 mb-4 transition-transform hover:scale-110">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm text-gray-500 italic">
                                {{ __('No upcoming events found in your calendar.') }}</p>
                        </div>
                    @endforelse
                </div>

                <div
                    class="px-8 py-6 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-white/5 flex items-center justify-end gap-3">
                    <a href="{{ route('teams.dashboard', $team) }}"
                        class="px-5 py-2.5 text-xs font-bold text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white transition-all uppercase tracking-wider">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit"
                        class="px-8 py-2.5 bg-violet-600 hover:bg-violet-500 text-white rounded-2xl shadow-lg shadow-violet-500/20 transition-all font-bold text-xs uppercase tracking-wider active:scale-95 disabled:opacity-50 disabled:pointer-events-none"
                        id="import-btn">
                        {{ __('Import Selected') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function toggleCheckbox(id) {
                const cb = document.getElementById(id);
                if (!cb.disabled) {
                    cb.checked = !cb.checked;
                    updateButtonState();
                }
            }

            function updateButtonState() {
                const checkboxes = document.querySelectorAll('input[name="events[]"]:checked');
                const btn = document.getElementById('import-btn');
                btn.disabled = checkboxes.length === 0;
            }

            document.getElementById('select-all').addEventListener('click', function() {
                const checkboxes = document.querySelectorAll('input[name="events[]"]:not(:disabled)');
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                checkboxes.forEach(cb => cb.checked = !allChecked);
                updateButtonState();
            });

            // Initial state
            updateButtonState();
        </script>
        <style>
            .custom-scrollbar::-webkit-scrollbar {
                width: 4px;
            }

            .custom-scrollbar::-webkit-scrollbar-track {
                background: transparent;
            }

            .custom-scrollbar::-webkit-scrollbar-thumb {
                background: rgba(139, 92, 246, 0.2);
                border-radius: 10px;
            }
        </style>
    @endpush
</x-app-layout>
