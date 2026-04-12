<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Notificaciones') }}
            </h2>
            @if($notifications->where('read_at', null)->count() > 0)
                <form action="{{ route('notifications.mark-all-as-read') }}" method="POST">
                    @csrf
                    <x-secondary-button type="submit" class="text-xs">
                        {{ __('Marcar todas como leídas') }}
                    </x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl border border-gray-100">
                <div class="divide-y divide-gray-100">
                    @forelse ($notifications as $notification)
                        <div class="p-6 flex items-start gap-4 transition-colors {{ $notification->unread() ? 'bg-indigo-50/30' : '' }}">
                            <!-- Icon logic based on notification type -->
                            <div class="mt-1 shrink-0">
                                @php
                                    $type = $notification->data['type'] ?? 'default';
                                    $iconClass = "p-2 rounded-lg ";
                                    switch($type) {
                                        case 'task_nudge':
                                        case 'task_reminder':
                                            $iconClass .= "bg-amber-100 text-amber-600";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>';
                                            break;
                                        case 'kudo_received':
                                            $iconClass .= "bg-emerald-100 text-emerald-600";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-7.714 2.143L11 21l-2.286-6.857L1 12l7.714-2.143L11 3z" /></svg>';
                                            break;
                                        case 'forum_message':
                                            $iconClass .= "bg-blue-100 text-blue-600";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" /></svg>';
                                            break;
                                        default:
                                            $iconClass .= "bg-gray-100 text-gray-600";
                                            $icon = '<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" /></svg>';
                                    }
                                @endphp
                                <div class="{{ $iconClass }}">
                                    {!! $icon !!}
                                </div>
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <p class="text-sm font-medium text-gray-900 {{ $notification->unread() ? 'font-bold' : '' }}">
                                        {{ $notification->data['message'] ?? 'Nueva notificación' }}
                                    </p>
                                    <span class="text-xs text-gray-400 whitespace-nowrap ml-2">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center gap-3">
                                    @if($notification->unread())
                                        <form action="{{ route('notifications.mark-as-read', $notification->id) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                                {{ __('Ver / Marcar como leída') }}
                                            </button>
                                        </form>
                                    @elseif(isset($notification->data['url']) || (isset($notification->data['task_id']) && isset($notification->data['team_id'])))
                                        @php
                                            $url = $notification->data['url'] ?? route('teams.tasks.show', [$notification->data['team_id'], $notification->data['task_id']]);
                                        @endphp
                                        <a href="{{ $url }}" class="text-xs font-medium text-gray-500 hover:text-gray-700 transition-colors">
                                            {{ __('Ver de nuevo') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-12 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('No hay notificaciones') }}</h3>
                            <p class="mt-1 text-sm text-gray-500">{{ __('¡Estás al día!') }}</p>
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
