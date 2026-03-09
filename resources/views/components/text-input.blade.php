@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'bg-gray-800 border-gray-700 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm text-white placeholder-gray-500 transition-all']) }}>
