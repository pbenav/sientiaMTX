<x-app-layout>
    @section('title', 'Gestión del Tablón Edictal — ' . $team->name)

    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('teams.circuits.index', $team) }}"
                    class="p-2.5 bg-gray-50 dark:bg-gray-800/50 text-gray-400 hover:text-violet-600 dark:hover:text-violet-400 rounded-2xl transition-all shadow-sm border border-gray-100 dark:border-gray-700/50 shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white heading tracking-tight">
                        Tablón Edictal y Documentos Públicos
                    </h1>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        Documentos, bandos y acuerdos tramitados y puestos a disposición de la ciudadanía
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('public.circuits.board', $team) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-black bg-teal-600 hover:bg-teal-700 text-white shadow-md shadow-teal-600/20 transition-all">
                    <span>Ver Tablón Público de Ciudadanía</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <!-- Public URL Banner -->
        <div class="p-5 bg-gradient-to-r from-teal-500/10 via-emerald-500/10 to-transparent border border-teal-200 dark:border-teal-800/50 rounded-2xl flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </div>
                <div>
                    <h4 class="text-sm font-black text-gray-900 dark:text-white">
                        Enlace público oficial del Tablón Edictal del equipo
                    </h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 font-mono select-all">
                        {{ route('public.circuits.board', $team) }}
                    </p>
                </div>
            </div>

            <button type="button" onclick="navigator.clipboard.writeText('{{ route('public.circuits.board', $team) }}'); alert('Enlace copiado al portapapeles');"
                class="px-4 py-2 text-xs font-bold rounded-xl bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors shadow-2xs">
                Copiar Enlace
            </button>
        </div>

        <!-- Filter tabs -->
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-1 text-xs font-bold">
                <a href="{{ route('teams.circuits.board.index', $team) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ empty($status) ? 'bg-teal-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Todos
                </a>
                <a href="{{ route('teams.circuits.board.index', [$team, 'status' => 'published']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'published' ? 'bg-teal-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Vigentes
                </a>
                <a href="{{ route('teams.circuits.board.index', [$team, 'status' => 'expired']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'expired' ? 'bg-teal-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Expirados
                </a>
                <a href="{{ route('teams.circuits.board.index', [$team, 'status' => 'withdrawn']) }}"
                    class="px-3 py-1.5 rounded-xl transition-colors {{ $status === 'withdrawn' ? 'bg-teal-600 text-white' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800' }}">
                    Retirados
                </a>
            </div>
        </div>

        <!-- Publications Table -->
        @if($publications->isEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-3xl p-12 text-center border border-gray-100 dark:border-gray-700 shadow-sm max-w-lg mx-auto">
                <div class="w-16 h-16 rounded-3xl bg-teal-50 dark:bg-teal-900/20 text-teal-600 dark:text-teal-400 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
                <h3 class="text-lg font-black text-gray-900 dark:text-white mb-1">
                    No hay publicaciones en el tablón
                </h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Las tramitaciones aprobadas que completen el circuito pueden ser publicadas aquí con un solo clic.
                </p>
            </div>
        @else
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-gray-50 dark:bg-gray-750 text-gray-500 dark:text-gray-400 uppercase font-black tracking-wider text-[10px] border-b border-gray-100 dark:border-gray-700">
                            <tr>
                                <th class="py-3.5 px-4">Código Oficial</th>
                                <th class="py-3.5 px-4">Título del Edicto</th>
                                <th class="py-3.5 px-4">Fecha Publicación</th>
                                <th class="py-3.5 px-4">Vencimiento</th>
                                <th class="py-3.5 px-4">Estado</th>
                                <th class="py-3.5 px-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700 text-gray-700 dark:text-gray-300">
                            @foreach($publications as $pub)
                                <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/30 transition-colors">
                                    <td class="py-3.5 px-4 font-mono font-bold text-teal-700 dark:text-teal-400">
                                        {{ $pub->document_code }}
                                    </td>
                                    <td class="py-3.5 px-4 font-bold text-gray-900 dark:text-white max-w-sm truncate">
                                        {{ $pub->title }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400">
                                        {{ $pub->publication_date->format('d/m/Y') }}
                                    </td>
                                    <td class="py-3.5 px-4 text-gray-500 dark:text-gray-400">
                                        {{ $pub->expiration_date ? $pub->expiration_date->format('d/m/Y') : 'Indefinido' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        @if($pub->status === 'published' && $pub->is_currently_active)
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                                Vigente
                                            </span>
                                        @elseif($pub->status === 'withdrawn')
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                                                Retirado
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider rounded-md bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                                Expirado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <a href="{{ route('public.circuits.show', $pub->public_slug) }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-bold text-teal-700 dark:text-teal-300 bg-teal-50 dark:bg-teal-900/30 hover:bg-teal-100 dark:hover:bg-teal-900/50 transition-colors">
                                            <span>Ver</span>
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        </a>

                                        @if($pub->status === 'published')
                                            <form action="{{ route('teams.circuits.board.withdraw', [$team, $pub]) }}" method="POST" class="inline-block"
                                                onsubmit="return confirm('¿Retirar este documento del tablón público?')">
                                                @csrf
                                                <input type="hidden" name="reason" value="Retirado manualmente por el equipo emisor">
                                                <button type="submit"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-900/30 transition-colors">
                                                    Retirar
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-gray-100 dark:border-gray-700">
                    {{ $publications->links() }}
                </div>
            </div>
        @endif

    </div>
</x-app-layout>
