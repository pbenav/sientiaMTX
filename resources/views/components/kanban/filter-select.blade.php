@props(['name', 'options' => [], 'selected' => null, 'placeholder' => '', 'width' => 'w-40'])

<select name="{{ $name }}" onchange="this.form.submit()" 
        class="{{ $width }} {{ ($selected ?? null) ? 'bg-violet-50/50 dark:bg-violet-900/10 border-violet-300 dark:border-violet-800 ring-2 ring-violet-500/20 text-violet-700 dark:text-violet-300' : 'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400' }} border rounded-xl text-xs font-bold uppercase py-2.5 pr-10 focus:ring-2 focus:ring-violet-500/20 focus:border-violet-500 cursor-pointer transition-all shadow-sm">
    <option value="">{{ $placeholder }}</option>
    @foreach($options as $key => $option)
        @if(is_array($option))
            <option value="{{ $key }}" {{ ($selected ?? null) == $key ? 'selected' : '' }}>{{ $option['_label'] ?? $key }}</option>
        @else
            <option value="{{ $key }}" {{ ($selected ?? null) == $key ? 'selected' : '' }}>{{ $option }}</option>
        @endif
    @endforeach
</select>
