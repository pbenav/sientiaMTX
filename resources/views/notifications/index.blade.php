<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center w-full">
            <div class="flex items-center gap-3 flex-1">
                <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="p-2 -ml-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors text-gray-500 dark:text-gray-400" title="{{ __('Volver') }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
                <div>
                    <nav class="flex items-center gap-1 text-[10px] sm:text-xs text-gray-400 dark:text-gray-500 mb-1 font-medium select-none" aria-label="breadcrumb">
                        <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">{{ __('Inicio') }}</a>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                        <span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ __('Notificaciones') }}</span>
                    </nav>
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight whitespace-nowrap">
                        {{ __('Notificaciones') }}
                    </h2>
                </div>
            </div>
            <div class="flex items-center gap-4 ml-12">
                <button onclick="window.location.reload()" class="inline-flex items-center px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-500 rounded-md font-bold text-[10px] text-gray-700 dark:text-gray-300 uppercase tracking-widest shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    {{ __('Actualizar') }}
                </button>
                @if($notifications->where('read_at', null)->count() > 0)
                    <form action="{{ route('notifications.mark-all-as-read') }}" method="POST" class="hidden sm:block">
                        @csrf
                        <x-secondary-button type="submit" class="text-[10px] font-bold uppercase tracking-widest whitespace-nowrap">
                            {{ __('Marcar todas leídas') }}
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

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                <!-- Header con Select All y Bulk Actions Integradas -->
                <div class="bg-gray-50/80 dark:bg-gray-900/80 px-6 py-4 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between sticky top-0 z-10 backdrop-blur-md">
                    <div class="flex items-center gap-4">
                        <input type="checkbox" @click="toggleAll()" x-model="allSelected" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                        
                        <div x-show="selected.length === 0" class="flex items-center gap-2">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest leading-none">Seleccionar todo</span>
                        </div>

                        <div x-show="selected.length > 0" class="flex items-center gap-4 animate-in fade-in slide-in-from-left-2" style="display: none">
                            <span class="text-sm font-black text-indigo-600 dark:text-indigo-400">
                                <span x-text="selected.length"></span> seleccionadas
                            </span>
                            
                            <div class="h-4 w-px bg-gray-200 dark:bg-gray-700"></div>
                            
                            <div class="flex items-center gap-4">
                                <form action="{{ route('notifications.bulk-action') }}" method="POST" class="inline">
                                    @csrf
                                    <template x-for="id in selected" :key="id">
                                        <input type="hidden" name="notification_ids[]" :value="id">
                                    </template>
                                    <input type="hidden" name="action" value="mark_as_read">
                                    <button type="submit" class="text-[10px] font-black text-gray-600 dark:text-gray-300 hover:text-indigo-600 transition-colors uppercase tracking-widest flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>
                                        {{ __('Leídas') }}
                                    </button>
                                </form>

                                <form id="bulk-delete-notifications" action="{{ route('notifications.bulk-action') }}" method="POST" class="inline" onsubmit="window.confirmDelete('bulk-delete-notifications', '¿Estás seguro de que quieres eliminar estas notificaciones?'); return false;">
                                    @csrf
                                    <template x-for="id in selected" :key="id">
                                        <input type="hidden" name="notification_ids[]" :value="id">
                                    </template>
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="text-[10px] font-black text-red-500 hover:text-red-700 transition-colors uppercase tracking-widest flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        {{ __('Eliminar') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div x-show="selected.length > 0" style="display: none">
                         <button @click="selected = []; allSelected = false" class="p-1 px-2 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-400 hover:text-gray-600 transition-colors text-[10px] font-bold uppercase tracking-tighter">
                            {{ __('Cancelar selección') }}
                        </button>
                    </div>
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
                                        case 'assigned':
                                            $iconClass .= "bg-amber-100 text-amber-600 dark:bg-amber-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                                            break;
                                        case 'kudo_received':
                                            $iconClass .= "bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" /></svg>';
                                            break;
                                        case 'morning_summary':
                                            $iconClass .= "bg-orange-100 text-orange-600 dark:bg-orange-900/30";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>';
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
                                        {{ $notification->data['title'] ?? ($notification->data['message'] ?? 'Nueva notificación') }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        @if(isset($notification->data['team_name']))
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-black bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300 uppercase tracking-tight">
                                                {{ $notification->data['team_name'] }}
                                            </span>
                                        @endif
                                        <span class="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap ml-2">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </span>
                                    </div>
                                </div>

                                @if($type === 'morning_summary')
                                    <div class="mt-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl p-4 border border-gray-100 dark:border-gray-800">
                                        <p class="text-sm italic text-gray-600 dark:text-gray-400 mb-3 font-medium">
                                            "{{ $notification->data['phrase'] ?? '' }}"
                                        </p>
                                        <div class="space-y-2">
                                            @foreach($notification->data['tasks'] ?? [] as $task)
                                                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                                    <span class="font-bold text-gray-700 dark:text-gray-300">{{ $task['title'] }}</span>
                                                    <span class="opacity-50">•</span>
                                                    <span>{{ $task['team'] }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                        {{ $notification->data['message'] ?? '' }}
                                    </p>
                                @endif

                                <div class="mt-2 flex items-center gap-3">
                                    @if($notification->unread())
                                        <a href="{{ route('notifications.mark-as-read', $notification->id) }}" class="text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 transition-colors uppercase tracking-wider">
                                            {{ __('Ver / Marcar como leída') }}
                                        </a>
                                    @elseif(isset($notification->data['url']) || (isset($notification->data['task_id']) && isset($notification->data['team_id'])))
                                        <a href="{{ route('notifications.mark-as-read', $notification->id) }}" class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors uppercase tracking-wider">
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
