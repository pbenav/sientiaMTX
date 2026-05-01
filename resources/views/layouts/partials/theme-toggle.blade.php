<div class="relative" x-data="{
    open: false,
    theme: '{{ auth()->check() ? auth()->user()->theme : request()->cookie('theme', 'system') }}',
    init() {
        window.addEventListener('close-other-system-menus', (e) => {
            if (e.detail.id !== 'theme-menu') this.open = false;
        });
    },
    updateTheme(newTheme) {
        this.theme = newTheme;
        this.open = false;

        document.cookie = 'theme=' + newTheme + '; path=/; max-age=' + (30 * 24 * 60 * 60) + '; SameSite=Lax';

        if (newTheme === 'dark' || (newTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        @auth
        fetch('{{ route('theme.update') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ theme: newTheme })
        }).then(response => response.json())
          .catch(error => console.error('Error updating theme:', error));
        @endauth
    }
}">
    <button @click="if(!open) window.dispatchEvent(new CustomEvent('close-other-system-menus', { detail: { id: 'theme-menu' } })); open = !open" @click.outside="open = false"
        class="flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 w-9 h-9 rounded-lg transition-all">
        <!-- Sun -->
        <svg x-show="theme === 'light'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 9H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707M14 12a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        <!-- Moon -->
        <svg x-show="theme === 'dark'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
        </svg>
        <!-- System -->
        <svg x-show="theme === 'system'" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    </button>
    <div x-show="open" x-transition x-cloak
        class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl overflow-hidden z-[90]">
        <button @click="updateTheme('light')" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'light' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300'">☀️ Light</button>
        <button @click="updateTheme('dark')" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'dark' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300'">🌙 Dark</button>
        <button @click="updateTheme('system')" class="w-full flex items-center gap-2 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors" :class="theme === 'system' ? 'text-violet-600 dark:text-violet-400 font-semibold' : 'text-gray-600 dark:text-gray-300'">💻 System</button>
    </div>
</div>
