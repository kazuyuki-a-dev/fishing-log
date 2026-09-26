@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm']) }}>