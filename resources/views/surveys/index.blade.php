<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight">
                {{ __('Encuestas') }}
            </h2>
            <a href="{{ route('teams.surveys.create', $team) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                {{ __('Nueva Encuesta') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                @if(session('success'))
                    <div class="p-4 text-sm text-green-700 bg-green-100 rounded-t-lg m-4">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="p-6">
                    @if($surveys->isEmpty())
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-4 text-gray-500">{{ __('No hay encuestas aún en este equipo') }}</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-50 border-b">
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Título') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Tipo') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Creador') }}</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ __('Votos') }}</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ __('Estado') }}</th>
                                        <th class="px-4 py-3 text-left font-semibold text-gray-700">{{ __('Expira') }}</th>
                                        <th class="px-4 py-3 text-center font-semibold text-gray-700">{{ __('Acciones') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($surveys as $survey)
                                        <tr class="border-b hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                                    {{ Str::limit($survey->title, 40) }}
                                                </a>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="px-2 py-1 text-xs rounded-full {{ match($survey->type) {
                                                    'single_choice' => 'bg-purple-100 text-purple-700',
                                                    'multiple_choice' => 'bg-indigo-100 text-indigo-700',
                                                    'rating' => 'bg-yellow-100 text-yellow-700',
                                                    'text' => 'bg-green-100 text-green-700',
                                                } }}">
                                                    {{ match($survey->type) {
                                                        'single_choice' => __('Opción única'),
                                                        'multiple_choice' => __('Opción múltiple'),
                                                        'rating' => __('Valoración'),
                                                        'text' => __('Texto'),
                                                    } }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3">{{ $survey->creator?->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-center">{{ $survey->vote_count }}</td>
                                            <td class="px-4 py-3 text-center">
                                                @if($survey->is_closed)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-700">{{ __('Cerrada') }}</span>
                                                @elseif($survey->is_expired)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-700">{{ __('Expirada') }}</span>
                                                @elseif(!$survey->is_active)
                                                    <span class="px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-700">{{ __('Inactiva') }}</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">{{ __('Activa') }}</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3">
                                                {{ $survey->expires_at?->format('d/m/Y H:i') ?? __('Sin fecha') }}
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <div class="flex items-center justify-center gap-2">
                                                    <a href="{{ route('teams.surveys.show', [$team, $survey]) }}" class="text-blue-600 hover:text-blue-800" title="{{ __('Ver') }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                        </svg>
                                                    </a>
                                                    @can('update', $survey)
                                                        <a href="{{ route('teams.surveys.edit', [$team, $survey]) }}" class="text-yellow-600 hover:text-yellow-800" title="{{ __('Editar') }}">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                            </svg>
                                                        </a>
                                                        @if(!$survey->is_closed)
                                                            <form action="{{ route('teams.surveys.close', [$team, $survey]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-orange-600 hover:text-orange-800" title="{{ __('Cerrar') }}">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form action="{{ route('teams.surveys.reactivate', [$team, $survey]) }}" method="POST" class="inline">
                                                                @csrf
                                                                <button type="submit" class="text-green-600 hover:text-green-800" title="{{ __('Reactivar') }}">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                                                    </svg>
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form action="{{ route('teams.surveys.destroy', [$team, $survey]) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Estás seguro?') }}')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800" title="{{ __('Eliminar') }}">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">
                            {{ $surveys->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
