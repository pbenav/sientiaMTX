<div class="relative" x-data="{ 
    open: false,
    init() {
        window.addEventListener('close-other-system-menus', (e) => {
            if (e.detail.id !== 'lang-menu') this.open = false;
        });
    }
}">
    <button @click="if(!open) window.dispatchEvent(new CustomEvent('close-other-system-menus', { detail: { id: 'lang-menu' } })); open = !open" @click.outside="open = false"
        class="flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 px-2.5 py-1.5 rounded-lg transition-all">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
        </svg>
        <span class="font-semibold uppercase text-xs">{{ app()->getLocale() }}</span>
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
        </svg>
    </button>
    <div x-show="open" x-transition x-cloak
        class="absolute right-0 mt-2 w-36 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-[90]">
        <a href="{{ route('locale.switch', 'en') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ app()->getLocale() === 'en' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">🇬🇧 English</a>
        <a href="{{ route('locale.switch', 'es') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ app()->getLocale() === 'es' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300' }}">🇪🇸 Español</a>
    </div>
</div>
