@props(['disabled' => false, 'emoji' => false, 'type' => 'text'])

<div class="relative group w-full" x-data="{ 
    showEmoji: false, 
    showPassword: false,
    inputType: '{{ $type }}',
    insertEmoji(emoji) {
        const input = $refs.input;
        const start = input.selectionStart;
        const end = input.selectionEnd;
        const text = input.value;
        input.value = text.substring(0, start) + emoji + text.substring(end);
        input.focus();
        input.selectionStart = input.selectionEnd = start + emoji.length;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        this.showEmoji = false;
    }
}">
    <input x-ref="input" @disabled($disabled)
        :type="inputType"
        {{ $attributes->merge(['class' => 'pr-10 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all w-full']) }}>
    
    @if($type === 'password')
        <button type="button" 
            @click="showPassword = !showPassword; inputType = showPassword ? 'text' : 'password'"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-violet-500 transition-all focus:outline-none pointer-events-auto"
            title="{{ __('Ver/Ocultar contraseña') }}">
            <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            <svg x-show="showPassword" style="display:none" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057-5.064-7-9.542-7 1.274 4.057 5.064 7 9.542 7-4.477 0-8.268-2.943-9.542-7zM17.94 17.94l-1.976-1.976m-1.976-1.976l-1.976-1.976m-1.976-1.976L3 3m15.94 18.94L3 3m18.94 18.94l-4.06-4.06m-1.976-1.976l-1.976-1.976m-1.976-1.976L21 21"/></svg>
        </button>
    @elseif($emoji && !$disabled)
        <button type="button" 
            @click="showEmoji = !showEmoji"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-violet-500 opacity-0 group-hover:opacity-100 transition-all focus:outline-none pointer-events-auto"
            title="Añadir icono">
            😊
        </button>

        <div x-show="showEmoji" 
            @click.away="showEmoji = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="absolute right-0 mt-2 z-[100] w-64 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-4"
            style="display: none" x-cloak>
            <div class="grid grid-cols-6 gap-2 max-h-48 overflow-y-auto no-scrollbar">
                @foreach(['🎯','📈','📋','👥','🌍','📜','💡','📞','📧','🚀','✅','❌','⚠️','🔥','⭐','🏢','🏠','🛠️','📅','⏰','💰','🏆','📣','🤝','🔍','🔗','💻','📱','🔒','🔑','💾','📑','📎','📊','📌','📍','🚩','🎨','🎬','🎧','🌈','⚡','✨','🔴','🔵','🟢','🟡','🟠','🟣'] as $icon)
                    <button type="button" @click="insertEmoji('{{ $icon }}')" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg text-lg transition-colors">
                        {{ $icon }}
                    </button>
                @endforeach
            </div>
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-center">
                <a href="https://emojicopy.com/" target="_blank" class="text-[10px] font-black uppercase tracking-widest text-indigo-600 dark:text-indigo-400 hover:text-indigo-700 dark:hover:text-indigo-300 transition-colors flex items-center justify-center gap-1.5 group/link">
                    <span>Buscar más emojis</span>
                    <svg class="w-2.5 h-2.5 group-hover/link:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                </a>
            </div>
        </div>
    @endif
</div>
