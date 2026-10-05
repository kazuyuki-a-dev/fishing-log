{{-- 一致のレベルのバッジ（FN-16・FN-11）。ぴったりはクレヨンの黄色に黒い字（#102）、ゆるめたものはうすい青 --}}
@props(['level', 'label'])

<span {{ $attributes->merge(['class' => 'inline-block rounded px-2 py-0.5 text-xs font-bold ' . ($level === 'exact' ? 'bg-crayon text-ink' : 'bg-sea-100 text-sea')]) }}>{{ $label }}</span>
