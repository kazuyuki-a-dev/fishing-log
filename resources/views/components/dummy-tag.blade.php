@props(['trip'])
{{-- ダミーの釣行の印（#211）。シーダーで作ったダミーの人の釣行にだけ出す --}}
{{-- 自分の釣行は、ログインしている人の印で見る（一覧で自分の分の会員を読み込まないため） --}}
@php($isSample = $trip->user_id === auth()->id() ? (bool) auth()->user()?->is_sample : (bool) $trip->user?->is_sample)
@if ($isSample)
<span {{ $attributes->merge(['class' => 'crayon-dummy-tag']) }} title="お試し用に作った記録で、本物の釣行ではありません">ダミー</span>
@endif
