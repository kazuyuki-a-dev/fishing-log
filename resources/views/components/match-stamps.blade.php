{{-- 一致のハンコ（#158）。時期・時間帯・潮のうち、行く日と同じ条件はオレンジ、違う条件は青で、釣れたときにいちばん多かった値 --}}
{{-- 中身は ConditionMatcher の stamps。読み上げ用に、まとめた説明を aria-label に入れる --}}
@props(['stamps'])

@php
$spoken = collect($stamps)->map(fn($stamp) => $stamp['name'] . ' ' . $stamp['value'] . ($stamp['same'] ? '（行く日と同じ）' : '（違う。釣れたときに多かった値）'))->implode('、');
@endphp

<div {{ $attributes->merge(['class' => 'flex shrink-0 gap-1']) }} role="img" aria-label="{{ $spoken }}">
    @foreach ($stamps as $stamp)
    <span class="crayon-stamp crayon-match-stamp {{ $stamp['same'] ? '' : 'crayon-match-stamp-blue' }}" aria-hidden="true">
        <span><span class="block text-[0.6rem] opacity-80">{{ $stamp['name'] }}</span>{{ $stamp['value'] }}</span>
    </span>
    @endforeach
</div>
