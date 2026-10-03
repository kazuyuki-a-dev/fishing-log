{{-- 一致のレベルのバッジ（FN-16・FN-11）。ぴったりはオレンジ、ゆるめたものはうすい色 --}}
@props(['level', 'label'])

<span {{ $attributes->merge(['class' => 'inline-block rounded px-2 py-0.5 text-xs font-bold ' . ($level === 'exact' ? 'bg-float text-white' : 'bg-sea-100 text-sea')]) }}>{{ $label }}</span>
