<section>
    <header class="mb-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-violet-600 dark:text-violet-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12h9.75c1.05 0 1.5.45 1.5 1.5V15c0 1.05-.45 1.5-1.5 1.5H7.5c-1.05 0-1.5-.45-1.5-1.5V7.5c0-1.05.45-1.5 1.5-1.5ZM9 10.5h.008v.008H9V10.5Zm0 3h.008v.008H9v-.008Z"></path>
            </svg>
            {{ __('Pases VIP de Invitación') }}
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('Reparte pases VIP exclusivos a tus compañeros o amigos para que puedan registrarse al instante saltándose la lista de espera.') }}
        </p>
    </header>

    <!-- Contador de Invitaciones Restantes -->
    <div class="p-5 bg-gradient-to-br from-violet-50 to-fuchsia-50 dark:from-violet-950/20 dark:to-fuchsia-950/20 rounded-2xl border border-violet-100 dark:border-violet-900/40 mb-8 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300">
        <div>
            <div class="text-xs font-bold text-violet-600 dark:text-violet-400 uppercase tracking-widest mb-1">{{ __('Invitaciones Disponibles') }}</div>
            <div class="text-4xl font-extrabold text-gray-900 dark:text-white flex items-baseline gap-1.5">
                {{ auth()->user()->invitations_left }}
                <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ __('restantes') }}</span>
            </div>
        </div>
        
        <form method="POST" action="{{ route('profile.invitations.generate') }}">
            @csrf
            <button type="submit" 
                @if(auth()->user()->invitations_left <= 0) disabled @endif
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-violet-600 to-fuchsia-600 hover:from-violet-700 hover:to-fuchsia-700 text-white font-bold text-sm rounded-xl transition-all shadow-md hover:shadow-lg focus:outline-none disabled:opacity-40 disabled:cursor-not-allowed active:scale-[0.98]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                {{ __('Generar Pase VIP') }}
            </button>
        </form>
    </div>

    <!-- Listado de Pases VIP Generados -->
    <div class="space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ __('Tus Enlaces de Invitación') }}</h4>
        
        @if($invitationsList->isEmpty())
            <div class="text-center py-8 bg-gray-50 dark:bg-gray-800/30 rounded-2xl border border-dashed border-gray-200 dark:border-gray-800">
                <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-12h9.75c1.05 0 1.5.45 1.5 1.5V15c0 1.05-.45 1.5-1.5 1.5H7.5c-1.05 0-1.5-.45-1.5-1.5V7.5c0-1.05.45-1.5 1.5-1.5ZM9 10.5h.008v.008H9V10.5Zm0 3h.008v.008H9v-.008Z"></path>
                </svg>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Aún no has generado ningún enlace de invitación.') }}
                </p>
            </div>
        @else
            <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                @foreach($invitationsList as $invitation)
                    @php
                        $regUrl = route('register') . '?code=' . $invitation->code;
                    @endphp
                    <div class="p-4 bg-gray-50 dark:bg-gray-800/40 rounded-xl border border-gray-100 dark:border-gray-800/60 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 hover:border-gray-200 dark:hover:border-gray-700 transition-colors duration-300">
                        <div class="min-w-0 flex-1 w-full">
                            <div class="flex items-center gap-2 mb-1.5">
                                <span class="font-mono text-xs font-bold text-violet-600 dark:text-violet-400 bg-violet-100/50 dark:bg-violet-950/30 px-2 py-0.5 rounded-md">
                                    {{ $invitation->code }}
                                </span>
                                @if($invitation->used_at)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700">
                                        {{ __('Consumido') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 dark:bg-emerald-950/30 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-900/40 animate-pulse">
                                        {{ __('Disponible') }}
                                    </span>
                                @endif
                            </div>
                            <input type="text" readonly value="{{ $regUrl }}" class="w-full text-xs font-mono text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-950/50 border border-gray-200 dark:border-gray-800 rounded-lg px-2.5 py-1.5 outline-none select-all">
                        </div>

                        @if(!$invitation->used_at)
                            <div x-data="{ copied: false }" class="w-full sm:w-auto">
                                <button type="button" 
                                    @click="
                                        navigator.clipboard.writeText('{{ $regUrl }}');
                                        copied = true;
                                        setTimeout(() => copied = false, 2000);
                                    "
                                    :class="copied ? 'bg-emerald-600 text-white' : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 border border-gray-200 dark:border-gray-700'"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all active:scale-95">
                                    <svg x-show="!copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 0 1-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 0 1 1.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.16-7.5-8.875a9.06 9.06 0 0 0-1.5-.124m-7.5 10.375c0 .621.504 1.125 1.125 1.125H6.75m11.25-1.125v-1.5m0 1.5h1.5m-1.5 0h-1.5m-2.25-4.5h.008v.008H12v-.008Z"></path>
                                    </svg>
                                    <svg x-show="copied" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="display: none;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"></path>
                                    </svg>
                                    <span x-text="copied ? '{{ __('Copiado') }}' : '{{ __('Copiar') }}'"></span>
                                </button>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
