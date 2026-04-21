@props(['disabled' => false, 'emoji' => true])

<div class="relative group w-full" x-data="{ 
    showEmoji: false, 
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
        {{ $attributes->merge(['class' => 'pr-10 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all w-full']) }}>
    
    @if($emoji && !$disabled)
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
        </div>
    @endif
</div>
