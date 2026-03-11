@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 transition-all']) }}>
