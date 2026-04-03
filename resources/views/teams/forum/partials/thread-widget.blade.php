<!-- Forum Thread Widget -->
@php
    $rootTask = $task;
    while ($rootTask->parent_id && $rootTask->parent) {
        $rootTask = $rootTask->parent;
    }
@endphp
<div
    class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-4 shadow-sm dark:shadow-none transition-colors">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
            </svg>
            {{ __('forum.discussion') }}
        </h3>

        @if ($rootTask->forumThread)
            <a href="{{ route('teams.forum.show', [$team, $rootTask->forumThread]) }}"
                class="text-[10px] bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300 px-2 py-1 rounded-md font-bold transition-colors">
                {{ __('forum.view_full') }}
            </a>
        @endif
    </div>

    @if (!$rootTask->forumThread)
        <div class="text-center py-6">
            <div
                class="w-12 h-12 bg-violet-50 dark:bg-violet-900/30 text-violet-500 rounded-full flex items-center justify-center mx-auto mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ __('forum.no_thread_yet') }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">{{ __('forum.no_thread_desc') }}</p>

            <form action="{{ route('teams.forum.store', $team) }}" method="POST">
                @csrf
                <input type="hidden" name="task_id" value="{{ $rootTask->id }}">
                <input type="hidden" name="title" value="Discusión: {{ $rootTask->title }}">
                <input type="hidden" name="content"
                    value="Hilo de discusión abierto para la tarea: {{ $rootTask->title }}">

                <button type="submit"
                    class="w-full bg-violet-600 hover:bg-violet-700 text-white font-bold text-xs py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('forum.create_thread') }}
                </button>
            </form>
        </div>
    @else
        <div class="space-y-4">
            <div class="max-h-[300px] overflow-y-auto pr-2 space-y-3 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600"
                id="widget-messages-container">
                @forelse($rootTask->forumThread->messages()->with('user')->get() as $message)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-xl p-3 {{ $loop->last ? 'mb-1' : '' }}">
                        <div class="flex items-center justify-between mb-1.5">
                            <div class="flex items-center gap-1.5">
                                <div
                                    class="w-5 h-5 rounded-full bg-violet-100 dark:bg-violet-900/50 flex flex-shrink-0 items-center justify-center text-[8px] font-bold text-violet-600 dark:text-violet-400">
                                    {{ strtoupper(substr($message->user->name, 0, 2)) }}
                                </div>
                                <span
                                    class="text-[10px] font-bold text-gray-700 dark:text-gray-200 line-clamp-1">{{ $message->user->name }}</span>
                            </div>
                            <span class="text-[9px] text-gray-400" title="{{ $message->created_at }}">
                                {{ $message->created_at->diffForHumans() }}
                            </span>
                        </div>
                        <div class="text-[11px] text-gray-600 dark:text-gray-300 leading-relaxed break-words whitespace-pre-wrap">{{ $message->content }}</div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400 italic text-center py-2">{{ __('forum.no_comments_yet') }}</p>
                @endforelse
            </div>

            @if (!$rootTask->forumThread->is_locked)
                <form action="{{ route('teams.forum.messages.store', [$team, $rootTask->forumThread]) }}" method="POST"
                    class="mt-3 relative" x-data="{ showEmojiPicker: false }">
                    @csrf
                    <textarea name="content" rows="2" id="forum-thread-textarea-{{ $rootTask->id }}"
                        class="w-full bg-gray-50 dark:bg-gray-800 border {{ $errors->has('content') ? 'border-red-300 dark:border-red-700 focus:border-red-500 focus:ring-red-500' : 'border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500' }} rounded-xl text-xs py-2 pl-3 pr-[4.5rem] text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-colors resize-none"
                        placeholder="{{ __('forum.write_message') }}..." required></textarea>
                    
                    <div class="absolute right-2 bottom-2 flex items-center gap-1">
                        <!-- Emoji Button -->
                        <div class="relative">
                            <button type="button" @click="showEmojiPicker = !showEmojiPicker" @click.outside="showEmojiPicker = false"
                                class="p-1.5 text-gray-400 hover:text-violet-500 hover:bg-violet-50 dark:hover:bg-violet-900/40 rounded-lg transition-colors"
                                title="Añadir emoticono">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                  <path stroke-linecap="round" stroke-linejoin="round" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </button>
                            <!-- Simple Emoji Panel -->
                            <div x-show="showEmojiPicker" x-transition style="display: none;"
                                class="absolute bottom-full right-0 mb-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-2 z-50 w-48 max-h-48 overflow-y-auto grid grid-cols-5 gap-1 text-base">
                                @php
                                    $emojis = ['😊','😂','😉','😍','😘','😜','😎','😭','😡','🥺','👍','👎','👏','🙌','🤝','🔥','✨','❤️','🎉','💯'];
                                @endphp
                                @foreach($emojis as $emoji)
                                    <button type="button" onclick="insertEmoji('{{ $emoji }}', 'forum-thread-textarea-{{ $rootTask->id }}')" 
                                        class="hover:bg-gray-100 dark:hover:bg-gray-700 rounded p-1 transition-colors text-center cursor-pointer">
                                        {{ $emoji }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <button type="submit"
                            class="p-1.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg transition-colors shadow-sm"
                            title="Enviar mensaje">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </div>
                </form>
                @error('content')
                    <p class="text-red-500 text-[10px] mt-1">{{ $message }}</p>
                @enderror

                <script>
                    function insertEmoji(emoji, targetId) {
                        const input = document.getElementById(targetId);
                        if (input) {
                            const start = input.selectionStart;
                            const end = input.selectionEnd;
                            const text = input.value;
                            input.value = text.substring(0, start) + emoji + text.substring(end);
                            const newPos = start + emoji.length;
                            input.setSelectionRange(newPos, newPos);
                            input.focus();
                        }
                    }

                    // Auto-scroll to bottom of widget messages
                    document.addEventListener('DOMContentLoaded', function() {
                        const container = document.getElementById('widget-messages-container');
                        if (container) {
                            container.scrollTop = container.scrollHeight;
                        }
                    });
                </script>
            @else
                <div
                    class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-3 text-center">
                    <p
                        class="text-[10px] font-bold text-amber-700 dark:text-amber-500 uppercase flex items-center justify-center gap-1.5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        {{ __('forum.thread_locked') }}
                    </p>
                </div>
            @endif
        </div>
    @endif
</div>
