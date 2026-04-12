<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Notificaciones') }}
            </h2>
            <div class="flex gap-2">
                @if($notifications->where('read_at', null)->count() > 0)
                    <form action="{{ route('notifications.mark-all-as-read') }}" method="POST">
                        @csrf
                        <x-secondary-button type="submit" class="text-xs">
                            {{ __('Marcar todas como leídas') }}
                        </x-secondary-button>
                    </form>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
        selected: [], 
        allSelected: false,
        toggleAll() {
            if (this.allSelected) {
                this.selected = [];
                this.allSelected = false;
            } else {
                this.selected = Array.from(document.querySelectorAll('.notification-checkbox')).map(el => el.value);
                this.allSelected = true;
            }
        }
    }">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 relative">
            
            <!-- Mensajes de estado -->
            @if (session('warning'))
                <div class="mb-4 p-4 bg-amber-50 border-l-4 border-amber-400 text-amber-700 text-sm rounded shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('warning') }}
                    </div>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-4 p-4 bg-emerald-50 border-l-4 border-emerald-400 text-emerald-700 text-sm rounded shadow-sm">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        {{ session('success') }}
                    </div>
                </div>
            @endif

            <!-- Bulk Actions Toolbar (Floating) -->
            <div x-show="selected.length > 0" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-4"
                 class="fixed bottom-8 left-1/2 -translate-x-1/2 z-50 bg-white dark:bg-gray-800 shadow-2xl rounded-full px-6 py-3 border border-indigo-100 dark:border-indigo-900 flex items-center gap-6"
                 style="display: none">
                <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400">
                    <span x-text="selected.length"></span> seleccionadas
                </span>
                
                <div class="h-6 w-px bg-gray-200 dark:bg-gray-700"></div>
                
                <div class="flex items-center gap-2">
                    <form action="{{ route('notifications.bulk-action') }}" method="POST" class="inline">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="notification_ids[]" :value="id">
                        </template>
                        <input type="hidden" name="action" value="mark_as_read">
                        <button type="submit" class="text-xs font-bold text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition-colors uppercase tracking-wider">
                            Marcar leídas
                        </button>
                    </form>

                    <form action="{{ route('notifications.bulk-action') }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de que quieres eliminar estas notificaciones?')">
                        @csrf
                        <template x-for="id in selected" :key="id">
                            <input type="hidden" name="notification_ids[]" :value="id">
                        </template>
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 transition-colors uppercase tracking-wider ml-4">
                            Eliminar
                        </button>
                    </form>
                </div>

                <button @click="selected = []; allSelected = false" class="text-gray-400 hover:text-gray-600 ml-2">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                <!-- Header con Select All -->
                <div class="bg-gray-50/50 dark:bg-gray-900/50 px-6 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center gap-4">
                    <input type="checkbox" @click="toggleAll()" x-model="allSelected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <span class="text-xs font-bold text-gray-500 uppercase tracking-widest leading-none">Seleccionar todo</span>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($notifications as $notification)
                        <div class="p-6 flex items-start gap-4 transition-colors {{ $notification->unread() ? 'bg-indigo-50/30 dark:bg-indigo-900/10' : '' }}">
                            
                            <!-- Checkbox -->
                            <div class="mt-1 shrink-0">
                                <input type="checkbox" 
                                       value="{{ $notification->id }}" 
                                       x-model="selected"
                                       class="notification-checkbox rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            </div>

                            <!-- Icon logic -->
                            <div class="mt-1 shrink-0">
                                @php
                                    $type = $notification->data['type'] ?? 'default';
                                    $iconClass = "p-2 rounded-lg ";
                                    switch($type) {
                                        case 'task_nudge':
                                        case 'task_reminder':
                                            $iconClass .= "bg-amber-100 text-amber-600 dark:bg-amber-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                                            break;
                                        case 'kudo_received':
                                            $iconClass .= "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" /></svg>';
                                            break;
                                        case 'forum_message':
                                            $iconClass .= "bg-blue-100 text-blue-600 dark:bg-blue-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>';
                                            break;
                                        default:
                                            $iconClass .= "bg-gray-100 text-gray-600 dark:bg-gray-800";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>';
                                    }
                                @endphp
                                <div class="{{ $iconClass }}">
                                    {!! $icon !!}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100 {{ $notification->unread() ? 'font-bold' : '' }}">
                                        {{ $notification->data['message'] ?? 'Nueva notificación' }}
                                    </p>
                                    <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap ml-2">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center gap-3">
                                    @if($notification->unread())
                                        <form action="{{ route('notifications.mark-as-read', $notification->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors uppercase tracking-wider">
                                                {{ __('Ver / Marcar como leída') }}
                                            </button>
                                        </form>
                                    @elseif(isset($notification->data['url']) || (isset($notification->data['task_id']) && isset($notification->data['team_id'])))
                                        @php
                                            $url = $notification->data['url'] ?? route('teams.tasks.show', [$notification->data['team_id'], $notification->data['task_id']]);
                                        @endphp
                                        <a href="{{ $url }}" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors uppercase tracking-wider">
                                            {{ __('Ver de nuevo') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">{{ __('No hay notificaciones') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('¡Estás al día!') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
            
            <div class="mt-6">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
