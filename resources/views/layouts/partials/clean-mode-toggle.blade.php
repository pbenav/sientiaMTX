<button @click="toggleCleanMode()"
    type="button"
    class="flex items-center justify-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white border border-gray-200 dark:border-gray-700 hover:border-violet-300 dark:hover:border-violet-700 w-9 h-9 rounded-lg transition-all shadow-sm bg-white dark:bg-gray-800"
    title="Modo Limpio (Ocultar elementos flotantes)">
    
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" 
         :class="cleanMode ? 'text-violet-600 dark:text-violet-400 scale-110 rotate-12' : ''" 
         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
    </svg>
</button>
